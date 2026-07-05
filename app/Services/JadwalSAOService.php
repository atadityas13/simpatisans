<?php

namespace App\Services;

use App\Models\BebanMengajar;
use App\Models\Kelas;
use App\Models\Jadwal;
use App\Models\GuruConstraint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Penjadwalan CSP: backtracking per kelas + occupancy guru global.
 * Target: 100% JTM terisi, struktur blok valid, tanpa bentrok/blokir.
 */
class JadwalSAOService
{
    private const BTQ_HARI = 'Jumat';
    private const BTQ_JAM_AKHIR = 5;
    private const MAX_JAM_GURU_HARI = 7;

    private array $strukturHari = ['Senin' => 9, 'Selasa' => 10, 'Rabu' => 10, 'Kamis' => 10, 'Jumat' => 5];
    private array $fastConstraints = [];
    private array $lockedSlots = [];
    private array $bebanMeta = [];
    private array $kelasIds = [];
    /** Izinkan penempatan di slot preset blokir jika tidak ada solusi lain. */
    private bool $honorBlockConstraints = true;
    /** @var array<string, array<int, array<int, true>>> */
    private array $guruOcc = [];

    public function generate(int $semesterId)
    {
        @ini_set('memory_limit', '512M');

        $bebanMengajar = BebanMengajar::where('semester_id', $semesterId)
            ->where('is_satminkal', 1)
            ->with(['guru', 'mapel', 'kelas'])
            ->get();

        if ($bebanMengajar->isEmpty()) {
            throw new \Exception('Data Beban Mengajar (KBM) kosong. Distribusikan jam terlebih dahulu.');
        }

        $kelasIds = Kelas::orderByRaw("FIELD(tingkat, 'VII', 'VIII', 'IX')")->pluck('id')->toArray();
        if (empty($kelasIds)) {
            throw new \Exception('Data Kelas kosong.');
        }
        $this->kelasIds = $kelasIds;

        $this->loadConstraints();
        $this->bebanMeta = [];
        foreach ($bebanMengajar as $beban) {
            $this->bebanMeta[$beban->id] = [
                'jtm' => (int) $beban->jtm,
                'guru_id' => $beban->guru_id,
                'kelas_id' => $beban->kelas_id,
                'mapel_id' => $beban->mapel_id,
                'is_btq' => $this->isMapelBtq($beban->mapel->nama_mapel ?? ''),
            ];
        }

        $totalJtm = (int) $bebanMengajar->sum('jtm');
        if ($totalJtm > count($kelasIds) * 44) {
            throw new \Exception("Kelebihan beban: {$totalJtm} JTM melebihi kapasitas grid.");
        }

        $unitsByKelas = $this->kelompokkanPerKelas($bebanMengajar);
        $bebanMap = $this->getBebanMap();

        $waktuMulai = time();
        $deadlineTotal = $waktuMulai + 165;
        $deadlineCari = $waktuMulai + 28;
        $solusiTerbaik = null;
        $skorTerbaik = PHP_INT_MAX;

        for ($attempt = 0; $attempt < 25; $attempt++) {
            if (time() >= $deadlineCari) {
                break;
            }

            $this->lockedSlots = [];
            $this->guruOcc = [];
            $jadwal = $this->buatJadwalKosong($kelasIds);
            $urutanKelas = $this->urutkanKelas($unitsByKelas, $attempt);

            $this->selesaikanSemuaKelas($jadwal, $unitsByKelas, $urutanKelas, $kelasIds, $waktuMulai, $deadlineCari);
            $this->tempatkanGlobalGreedy($jadwal, $unitsByKelas, $kelasIds);

            $kosong = $totalJtm - $this->hitungTerisi($jadwal);
            $skor = ($kosong * 1000000) + $this->hitungSkorKelelahan($jadwal, $kelasIds);

            if ($skor < $skorTerbaik) {
                $skorTerbaik = $skor;
                $solusiTerbaik = $this->salinJadwal($jadwal);
            }

            if ($kosong === 0 && $this->hitungSkorKelelahan($jadwal, $kelasIds) === 0) {
                $solusiTerbaik = $jadwal;
                $skorTerbaik = 0;
                break;
            }
        }

        if ($solusiTerbaik === null) {
            throw new \Exception('Gagal membuat jadwal. Kurangi preset blokir guru.');
        }

        $kosongTerbaik = $totalJtm - $this->hitungTerisi($solusiTerbaik);

        if (time() < $deadlineCari + 6) {
            $this->rebuildGuruOcc($solusiTerbaik, $kelasIds);
            $solusiTerbaik = $this->paksaLengkapi($solusiTerbaik, $unitsByKelas, $kelasIds, $bebanMap, $waktuMulai, $deadlineCari + 6);
            $kosongTerbaik = $totalJtm - $this->hitungTerisi($solusiTerbaik);
        }

        // Fase paksa: budget waktu penuh, abaikan preset blokir, maks 7 jam/hari guru
        $this->honorBlockConstraints = false;
        $this->unlockNonBtqSlots($solusiTerbaik, $unitsByKelas);
        $this->perbaikiSemuaUnitRusak($solusiTerbaik, $unitsByKelas, $kelasIds);
        $this->rebuildGuruOcc($solusiTerbaik, $kelasIds);
        $solusiTerbaik = $this->isiSemuaPaksa($solusiTerbaik, $unitsByKelas, $kelasIds, time(), $deadlineTotal - 22);
        $kosongTerbaik = $totalJtm - $this->hitungTerisi($solusiTerbaik);

        $this->rebuildGuruOcc($solusiTerbaik, $kelasIds);
        $solusiTerbaik = $this->seimbangkanKelelahan($solusiTerbaik, $unitsByKelas, $kelasIds, $bebanMap, time(), $deadlineTotal - 8);
        $kosongTerbaik = $totalJtm - $this->hitungTerisi($solusiTerbaik);

        if ($kosongTerbaik > 0) {
            $this->unlockNonBtqSlots($solusiTerbaik, $unitsByKelas);
            $this->rebuildGuruOcc($solusiTerbaik, $kelasIds);
            $solusiTerbaik = $this->isiSemuaPaksa($solusiTerbaik, $unitsByKelas, $kelasIds, time(), $deadlineTotal);
            $kosongTerbaik = $totalJtm - $this->hitungTerisi($solusiTerbaik);
        }

        $this->honorBlockConstraints = true;
        $this->rebuildGuruOcc($solusiTerbaik, $kelasIds);
        $solusiTerbaik = $this->seimbangkanKelelahan($solusiTerbaik, $unitsByKelas, $kelasIds, $bebanMap, time(), $deadlineTotal);

        $terisi = $this->hitungTerisi($solusiTerbaik);
        if ($terisi === 0) {
            throw new \Exception('Gagal membuat jadwal. Kurangi preset blokir guru atau periksa beban mengajar.');
        }

        $kosongTerbaik = $totalJtm - $terisi;
        return $this->simpanJadwal($semesterId, $solusiTerbaik, $terisi, $totalJtm, $kosongTerbaik);
    }

    private function waktuHabis(int $deadline): bool
    {
        return time() >= $deadline;
    }

    // ─── Inti CSP per kelas ───────────────────────────────────────────────

    private function selesaikanSemuaKelas(array &$jadwal, array $unitsByKelas, array $urutanKelas, array $kelasIds, int $t0, int $limit): bool
    {
        $this->tempatkanPreserveGlobal($jadwal, $unitsByKelas, $kelasIds);

        foreach ($urutanKelas as $kelasId) {
            if ($this->waktuHabis($limit)) {
                break;
            }
            $units = $unitsByKelas[$kelasId] ?? [];
            if (empty($units)) {
                continue;
            }

            $this->tempatkanBtqKelas($jadwal, $units, $kelasIds);

            if (!$this->backtrackKelas($jadwal, $units, $kelasId, $kelasIds, 0, $t0, $limit)) {
                $this->tempatkanKelasGreedy($jadwal, $units, $kelasIds);
            }
        }

        return $this->semuaLengkap($unitsByKelas, $jadwal);
    }

    /** Penempatan global semua mapel — tidak berhenti jika satu kelas gagal. */
    private function tempatkanGlobalGreedy(array &$jadwal, array $unitsByKelas, array $kelasIds): void
    {
        $all = [];
        foreach ($unitsByKelas as $units) {
            foreach ($units as $u) {
                $all[] = $u;
            }
        }
        usort($all, function ($a, $b) {
            if (($a['isBtq'] ?? false) !== ($b['isBtq'] ?? false)) {
                return ($b['isBtq'] ?? false) <=> ($a['isBtq'] ?? false);
            }
            if ($a['jtm'] !== $b['jtm']) {
                return $b['jtm'] <=> $a['jtm'];
            }
            return $b['constraintCount'] <=> $a['constraintCount'];
        });

        for ($pass = 0; $pass < 12; $pass++) {
            $progress = false;
            foreach ($all as $unit) {
                if ($this->unitLengkap($jadwal, $unit)) {
                    continue;
                }
                $remaining = $this->sisaUnit($jadwal, $unit);
                if ($remaining <= 0) {
                    continue;
                }
                $days = $this->hariTerpakai($jadwal, $unit);
                $combos = $this->urutkanComboByBeban(
                    $jadwal,
                    $unit,
                    $this->enumerasiPenempatan($jadwal, $unit, $remaining, $kelasIds, $days, 400),
                    $kelasIds
                );
                if ($this->cobaTempatkanCombo($jadwal, $unit, $combos, $kelasIds)) {
                    $progress = true;
                }
            }
            if (!$progress) {
                break;
            }
        }
    }

    private function bebanGuruPadaHari(array $jadwal, int $guruId, string $hari, array $kelasIds): int
    {
        $n = 0;
        foreach ($kelasIds as $kId) {
            for ($j = 1; $j <= ($this->strukturHari[$hari] ?? 0); $j++) {
                $s = $jadwal[$hari][$j][$kId] ?? null;
                if ($s !== null && $s['guru_id'] == $guruId) {
                    $n++;
                }
            }
        }
        return $n;
    }

    private function hitungSkorKelelahan(array $jadwal, array $kelasIds): int
    {
        $load = $this->bebanGuruHarian($jadwal, $kelasIds);
        $skor = 0;
        foreach ($load as $days) {
            foreach ($days as $cnt) {
                if ($cnt > self::MAX_JAM_GURU_HARI) {
                    $skor += ($cnt - self::MAX_JAM_GURU_HARI) * ($cnt - self::MAX_JAM_GURU_HARI);
                }
            }
        }
        return $skor;
    }

    /** Urutkan combo: hari dengan beban guru paling ringan dulu. */
    private function urutkanComboByBeban(array $jadwal, array $unit, array $combos, array $kelasIds): array
    {
        usort($combos, function ($a, $b) use ($jadwal, $unit, $kelasIds) {
            return $this->skorComboBeban($jadwal, $unit, $a, $kelasIds) <=> $this->skorComboBeban($jadwal, $unit, $b, $kelasIds);
        });
        return $combos;
    }

    private function skorComboBeban(array $jadwal, array $unit, array $combo, array $kelasIds): int
    {
        $guruId = $unit['guruId'];
        $skor = 0;
        foreach ($combo as $blok) {
            $beban = $this->bebanGuruPadaHari($jadwal, $guruId, $blok['hari'], $kelasIds);
            $skor += ($beban + $blok['size']) * 10;
        }
        return $skor;
    }

    private function comboLayakBeban(array $jadwal, array $unit, array $combo, array $kelasIds): bool
    {
        $guruId = $unit['guruId'];
        $sim = [];
        foreach ($combo as $blok) {
            $hari = $blok['hari'];
            $beban = $this->bebanGuruPadaHari($jadwal, $guruId, $hari, $kelasIds) + ($sim[$hari] ?? 0);
            if ($beban + $blok['size'] > self::MAX_JAM_GURU_HARI) {
                return false;
            }
            $sim[$hari] = ($sim[$hari] ?? 0) + $blok['size'];
        }
        return true;
    }

    /** Coba tempatkan combo (maks 7 jam/hari guru). */
    private function cobaTempatkanCombo(array &$jadwal, array $unit, array $combos, array $kelasIds): bool
    {
        foreach ($combos as $combo) {
            if (!$this->comboLayakBeban($jadwal, $unit, $combo, $kelasIds)) {
                continue;
            }
            $this->terapkanCombo($jadwal, $unit, $combo);
            if ($this->unitLengkap($jadwal, $unit)) {
                if (!empty($unit['isBtq'])) {
                    $this->kunciSlotBtq($jadwal, $unit);
                }
                return true;
            }
            $this->batalkanCombo($jadwal, $unit, $combo);
        }
        return false;
    }

    /** Greedy fallback jika backtrack gagal — tetap isi sebanyak mungkin. */
    private function tempatkanKelasGreedy(array &$jadwal, array $units, array $kelasIds): void
    {
        for ($pass = 0; $pass < 8; $pass++) {
            $progress = false;
            foreach ($units as $unit) {
                if ($this->unitLengkap($jadwal, $unit)) {
                    continue;
                }
                $remaining = $this->sisaUnit($jadwal, $unit);
                if ($remaining <= 0) {
                    continue;
                }
                $days = $this->hariTerpakai($jadwal, $unit);
                $combos = $this->urutkanComboByBeban(
                    $jadwal,
                    $unit,
                    $this->enumerasiPenempatan($jadwal, $unit, $remaining, $kelasIds, $days, 300),
                    $kelasIds
                );
                if ($this->cobaTempatkanCombo($jadwal, $unit, $combos, $kelasIds)) {
                    $progress = true;
                }
            }
            if (!$progress) {
                break;
            }
        }
    }

    /** Fase akhir: coba isi semua unit yang masih kurang. */
    private function lengkapiSerangkaian(array $jadwal, array $unitsByKelas, array $kelasIds, int $t0, int $limit): array
    {
        $best = $jadwal;
        $this->rebuildGuruOcc($best, $kelasIds);

        for ($round = 0; $round < 2000; $round++) {
            if ($this->waktuHabis($limit)) {
                break;
            }

            $pending = [];
            foreach ($unitsByKelas as $units) {
                foreach ($units as $unit) {
                    if ($this->sisaUnit($best, $unit) > 0) {
                        $pending[] = $unit;
                    }
                }
            }
            if (empty($pending)) {
                break;
            }

            usort($pending, fn($a, $b) => $this->sisaUnit($best, $b) <=> $this->sisaUnit($best, $a));
            $roundProgress = false;

            foreach ($pending as $unit) {
                $remaining = $this->sisaUnit($best, $unit);
                $days = $this->hariTerpakai($best, $unit);
                $combos = $this->urutkanComboByBeban(
                    $best,
                    $unit,
                    $this->enumerasiPenempatan($best, $unit, $remaining, $kelasIds, $days, 500),
                    $kelasIds
                );
                if ($this->cobaTempatkanCombo($best, $unit, $combos, $kelasIds)) {
                    $roundProgress = true;
                }
            }

            if (!$roundProgress) {
                break;
            }
        }
        return $best;
    }

    private function backtrackKelas(array &$jadwal, array $units, int $kelasId, array $kelasIds, int $idx, int $t0, int $limit): bool
    {
        if ($this->waktuHabis($limit)) {
            return false;
        }

        while ($idx < count($units) && $this->unitLengkap($jadwal, $units[$idx])) {
            $idx++;
        }
        if ($idx >= count($units)) {
            return true;
        }

        $unit = $units[$idx];
        $remaining = $this->sisaUnit($jadwal, $unit);

        if ($remaining <= 0) {
            return $this->backtrackKelas($jadwal, $units, $kelasId, $kelasIds, $idx + 1, $t0, $limit);
        }

        $existingDays = $this->hariTerpakai($jadwal, $unit);
        $placements = $this->urutkanComboByBeban(
            $jadwal,
            $unit,
            $this->enumerasiPenempatan($jadwal, $unit, $remaining, $kelasIds, $existingDays),
            $kelasIds
        );

        foreach ($placements as $combo) {
            if (!$this->comboLayakBeban($jadwal, $unit, $combo, $kelasIds)) {
                continue;
            }
            $this->terapkanCombo($jadwal, $unit, $combo);
            if ($this->backtrackKelas($jadwal, $units, $kelasId, $kelasIds, $idx + 1, $t0, $limit)) {
                return true;
            }
            $this->batalkanCombo($jadwal, $unit, $combo);
        }

        return false;
    }

    private function enumerasiPenempatan(array $jadwal, array $unit, int $remaining, array $kelasIds, array $fixedDays, int $maxCombo = 120): array
    {
        if (!empty($unit['isBtq'])) {
            return $this->enumerasiBtq($jadwal, $unit, $remaining, $kelasIds);
        }

        if ($remaining === 1 && count($fixedDays) === 1) {
            $out = [];
            foreach ($this->posisiLengkapi($jadwal, $unit, $kelasIds) as $p) {
                $out[] = [$p];
            }
            return $out;
        }

        $hasil = [];
        foreach ($this->getBlockPatterns($remaining) as $blocks) {
            $this->kumpulkanCombo($jadwal, $unit, $blocks, 0, [], $kelasIds, $fixedDays, $hasil, $maxCombo);
        }
        return $hasil;
    }

    private function kumpulkanCombo(array $jadwal, array $unit, array $blocks, int $bi, array $chosen, array $kelasIds, array $fixedDays, array &$hasil, int $max): void
    {
        if (count($hasil) >= $max) {
            return;
        }
        if ($bi >= count($blocks)) {
            $hasil[] = $chosen;
            return;
        }

        $size = $blocks[$bi];
        // Hari yang sudah dipakai mapel ini tidak boleh dipakai lagi (2+2, 3+2, dll.)
        $exclude = array_unique(array_merge($fixedDays, array_column($chosen, 'hari')));

        foreach ($this->semuaPosisiBlok($jadwal, $unit, $size, $kelasIds, $exclude) as $pos) {
            $temp = $jadwal;
            $this->letakkan($temp, $unit, $pos['hari'], $pos['startJam'], $size);
            $next = $chosen;
            $next[] = $pos;
            $this->kumpulkanCombo($temp, $unit, $blocks, $bi + 1, $next, $kelasIds, $fixedDays, $hasil, $max);
        }
    }

    private function semuaPosisiBlok(array $jadwal, array $unit, int $size, array $kelasIds, array $excludeDays): array
    {
        if (!empty($unit['isBtq'])) {
            return $this->posisiBtqBlok($jadwal, $unit, $size, $kelasIds);
        }

        $out = [];
        $kelasId = $unit['kelasId'];
        $guruId = $unit['guruId'];

        foreach ($this->strukturHari as $hari => $maxJam) {
            if (in_array($hari, $excludeDays, true)) {
                continue;
            }
            for ($start = 1; $start <= $maxJam - $size + 1; $start++) {
                if ($this->bisaLetakkan($jadwal, $unit, $hari, $start, $size, $kelasIds)) {
                    $beban = $this->bebanGuruPadaHari($jadwal, $guruId, $hari, $kelasIds);
                    $out[] = ['hari' => $hari, 'startJam' => $start, 'size' => $size, 'beban' => $beban];
                }
            }
        }
        usort($out, fn($a, $b) => $a['beban'] <=> $b['beban']);
        return $out;
    }

    private function posisiLengkapi(array $jadwal, array $unit, array $kelasIds): array
    {
        $out = [];
        $kelasId = $unit['kelasId'];
        $bmId = $unit['bmId'];
        $byDay = [];
        foreach ($this->strukturHari as $hari => $jml) {
            for ($j = 1; $j <= $jml; $j++) {
                $s = $jadwal[$hari][$j][$kelasId] ?? null;
                if ($s !== null && $s['beban_mengajar_id'] == $bmId) {
                    $byDay[$hari][] = $j;
                }
            }
        }
        foreach ($byDay as $hari => $jams) {
            foreach ([min($jams) - 1, max($jams) + 1] as $aj) {
                if ($aj < 1 || $aj > $this->strukturHari[$hari]) {
                    continue;
                }
                if ($this->bisaLetakkan($jadwal, $unit, $hari, $aj, 1, $kelasIds)) {
                    $out[] = ['hari' => $hari, 'startJam' => $aj, 'size' => 1];
                }
            }
        }
        return $out;
    }

    // ─── Occupancy & placement ────────────────────────────────────────────

    private function bisaLetakkan(array $jadwal, array $unit, string $hari, int $start, int $size, array $kelasIds): bool
    {
        $kelasId = $unit['kelasId'];
        $guruId = $unit['guruId'];
        $maxJam = $this->strukturHari[$hari];
        if ($start + $size - 1 > $maxJam) {
            return false;
        }
        for ($s = 0; $s < $size; $s++) {
            $jam = $start + $s;
            if (($jadwal[$hari][$jam][$kelasId] ?? null) !== null) {
                return false;
            }
            if (isset($this->lockedSlots[$hari][$jam][$kelasId])) {
                $cur = $jadwal[$hari][$jam][$kelasId] ?? null;
                if ($cur === null || ($cur['beban_mengajar_id'] ?? null) != $unit['bmId']) {
                    return false;
                }
            }
            if ($this->honorBlockConstraints && $this->isBlocked($guruId, $hari, $jam)) {
                return false;
            }
            if (isset($this->guruOcc[$hari][$jam][$guruId])) {
                return false;
            }
        }
        return true;
    }

    private function letakkan(array &$jadwal, array $unit, string $hari, int $start, int $size): void
    {
        $kelasId = $unit['kelasId'];
        $guruId = $unit['guruId'];
        $tpl = $unit['slotTemplate'];
        for ($s = 0; $s < $size; $s++) {
            $jam = $start + $s;
            $jadwal[$hari][$jam][$kelasId] = $tpl;
            $this->guruOcc[$hari][$jam][$guruId] = true;
        }
    }

    private function copot(array &$jadwal, array $unit, string $hari, int $start, int $size): void
    {
        $kelasId = $unit['kelasId'];
        $guruId = $unit['guruId'];
        for ($s = 0; $s < $size; $s++) {
            $jam = $start + $s;
            if (isset($this->lockedSlots[$hari][$jam][$kelasId])) {
                continue;
            }
            $jadwal[$hari][$jam][$kelasId] = null;
            $masihPakai = false;
            foreach ($this->kelasIds as $kId) {
                if ($kId == $kelasId) {
                    continue;
                }
                $other = $jadwal[$hari][$jam][$kId] ?? null;
                if ($other !== null && $other['guru_id'] == $guruId) {
                    $masihPakai = true;
                    break;
                }
            }
            if (!$masihPakai) {
                unset($this->guruOcc[$hari][$jam][$guruId]);
            }
        }
    }

    private function terapkanCombo(array &$jadwal, array $unit, array $combo): void
    {
        foreach ($combo as $blok) {
            $this->letakkan($jadwal, $unit, $blok['hari'], $blok['startJam'], $blok['size']);
        }
    }

    private function batalkanCombo(array &$jadwal, array $unit, array $combo): void
    {
        foreach ($combo as $blok) {
            $this->copot($jadwal, $unit, $blok['hari'], $blok['startJam'], $blok['size']);
        }
    }

    private function rebuildGuruOcc(array $jadwal, array $kelasIds): void
    {
        $this->guruOcc = [];
        foreach ($this->strukturHari as $hari => $jml) {
            for ($jam = 1; $jam <= $jml; $jam++) {
                foreach ($kelasIds as $kId) {
                    $slot = $jadwal[$hari][$jam][$kId] ?? null;
                    if ($slot !== null) {
                        $this->guruOcc[$hari][$jam][$slot['guru_id']] = true;
                    }
                }
            }
        }
    }

    // ─── Preserve & data ──────────────────────────────────────────────────

    private function tempatkanPreserveGlobal(array &$jadwal, array $unitsByKelas, array $kelasIds): void
    {
        foreach ($unitsByKelas as $kelasId => $units) {
            foreach ($units as $unit) {
                if (!empty($unit['isBtq'])) {
                    continue;
                }
                $guruId = $unit['guruId'];
                foreach ($this->fastConstraints[$guruId] ?? [] as $hari => $jams) {
                    foreach ($jams as $jam => $type) {
                        if ($type !== 1) {
                            continue;
                        }
                        if (($jadwal[$hari][$jam][$kelasId] ?? null) !== null) {
                            continue;
                        }
                        if (!$this->bisaLetakkan($jadwal, $unit, $hari, $jam, 1, $kelasIds)) {
                            continue;
                        }
                        $this->letakkan($jadwal, $unit, $hari, $jam, 1);
                        $this->lockedSlots[$hari][$jam][$kelasId] = true;
                    }
                }
            }
            foreach ($units as $unit) {
                if (!empty($unit['isBtq'])) {
                    continue;
                }
                if ($this->sisaUnit($jadwal, $unit) <= 0) {
                    continue;
                }
                $placed = [];
                foreach ($this->strukturHari as $hari => $jml) {
                    for ($j = 1; $j <= $jml; $j++) {
                        $s = $jadwal[$hari][$j][$kelasId] ?? null;
                        if ($s !== null && $s['beban_mengajar_id'] == $unit['bmId']) {
                            $placed[] = ['hari' => $hari, 'jam' => $j];
                        }
                    }
                }
                foreach ($placed as $p) {
                    if ($this->sisaUnit($jadwal, $unit) <= 0) {
                        break;
                    }
                    foreach ([1, -1] as $d) {
                        $nj = $p['jam'] + $d;
                        if ($nj < 1 || $nj > $this->strukturHari[$p['hari']]) {
                            continue;
                        }
                        if ($this->bisaLetakkan($jadwal, $unit, $p['hari'], $nj, 1, $kelasIds)) {
                            $this->letakkan($jadwal, $unit, $p['hari'], $nj, 1);
                        }
                    }
                }
            }
        }
    }

    /** BTQ wajib Jumat jam terakhir — per kelas, setelah kelas sebelumnya terisi. */
    private function tempatkanBtqKelas(array &$jadwal, array $units, array $kelasIds): void
    {
        foreach ($units as $unit) {
            if (empty($unit['isBtq'])) {
                continue;
            }

            if ($this->unitLengkap($jadwal, $unit)) {
                $this->kunciSlotBtq($jadwal, $unit);
                continue;
            }

            foreach ($this->strukturHari as $hari => $jml) {
                for ($j = 1; $j <= $jml; $j++) {
                    $s = $jadwal[$hari][$j][$unit['kelasId']] ?? null;
                    if ($s !== null && $s['beban_mengajar_id'] == $unit['bmId'] && !isset($this->lockedSlots[$hari][$j][$unit['kelasId']])) {
                        $this->copot($jadwal, $unit, $hari, $j, 1);
                    }
                }
            }

            $remaining = $this->sisaUnit($jadwal, $unit);
            if ($remaining <= 0) {
                continue;
            }

            foreach ($this->enumerasiBtq($jadwal, $unit, $remaining, $kelasIds) as $combo) {
                $this->terapkanCombo($jadwal, $unit, $combo);
                if ($this->validasiBeban($jadwal, $unit['kelasId'], $unit['bmId'], $unit['jtm'])) {
                    $this->kunciSlotBtq($jadwal, $unit);
                    break;
                }
                $this->batalkanCombo($jadwal, $unit, $combo);
            }
        }
    }

    private function kunciSlotBtq(array $jadwal, array $unit): void
    {
        $kelasId = $unit['kelasId'];
        $bmId = $unit['bmId'];
        foreach ($this->strukturHari as $hari => $jml) {
            for ($j = 1; $j <= $jml; $j++) {
                $s = $jadwal[$hari][$j][$kelasId] ?? null;
                if ($s !== null && $s['beban_mengajar_id'] == $bmId) {
                    $this->lockedSlots[$hari][$j][$kelasId] = true;
                }
            }
        }
    }

    private function enumerasiBtq(array $jadwal, array $unit, int $remaining, array $kelasIds): array
    {
        $pos = $this->posisiBtqBlok($jadwal, $unit, $remaining, $kelasIds);
        if (empty($pos)) {
            return [];
        }
        return [[$pos[0]]];
    }

    private function posisiBtqBlok(array $jadwal, array $unit, int $size, array $kelasIds): array
    {
        $hari = self::BTQ_HARI;
        $maxJam = self::BTQ_JAM_AKHIR;
        if ($size > $maxJam) {
            return [];
        }
        $start = $maxJam - $size + 1;
        if ($start < 1) {
            return [];
        }
        if ($this->bisaLetakkan($jadwal, $unit, $hari, $start, $size, $kelasIds)) {
            return [['hari' => $hari, 'startJam' => $start, 'size' => $size]];
        }
        return [];
    }

    private function isMapelBtq(?string $namaMapel): bool
    {
        if ($namaMapel === null || $namaMapel === '') {
            return false;
        }
        $n = strtolower($namaMapel);
        return str_contains($n, 'btq') || str_contains($n, 'baca tulis');
    }

    private function kelompokkanPerKelas($bebanMengajar): array
    {
        $groups = [];
        foreach ($bebanMengajar as $beban) {
            $kid = $beban->kelas_id;
            $groups[$kid][] = [
                'bmId' => $beban->id,
                'guruId' => $beban->guru_id,
                'kelasId' => $kid,
                'jtm' => (int) $beban->jtm,
                'isBtq' => $this->isMapelBtq($beban->mapel->nama_mapel ?? ''),
                'slotTemplate' => [
                    'beban_mengajar_id' => $beban->id,
                    'guru_id' => $beban->guru_id,
                    'mapel_id' => $beban->mapel_id,
                    'kelas_id' => $kid,
                ],
                'constraintCount' => count($this->fastConstraints[$beban->guru_id] ?? []),
            ];
        }
        foreach ($groups as &$units) {
            usort($units, function ($a, $b) {
                if (($a['isBtq'] ?? false) !== ($b['isBtq'] ?? false)) {
                    return ($b['isBtq'] ?? false) <=> ($a['isBtq'] ?? false);
                }
                if ($a['jtm'] !== $b['jtm']) {
                    return $b['jtm'] <=> $a['jtm'];
                }
                return $b['constraintCount'] <=> $a['constraintCount'];
            });
        }
        unset($units);
        return $groups;
    }

    private function urutkanKelas(array $unitsByKelas, int $seed): array
    {
        $ids = array_keys($unitsByKelas);
        usort($ids, function ($a, $b) use ($unitsByKelas) {
            $diffA = array_sum(array_column($unitsByKelas[$a], 'jtm'));
            $diffB = array_sum(array_column($unitsByKelas[$b], 'jtm'));
            $consA = array_sum(array_column($unitsByKelas[$a], 'constraintCount'));
            $consB = array_sum(array_column($unitsByKelas[$b], 'constraintCount'));
            if ($consA !== $consB) {
                return $consB <=> $consA;
            }
            return $diffB <=> $diffA;
        });
        if ($seed > 0) {
            mt_srand($seed * 9973);
            $n = count($ids);
            $rot = $seed % $n;
            if ($rot > 0) {
                $ids = array_merge(array_slice($ids, $rot), array_slice($ids, 0, $rot));
            }
        }
        return $ids;
    }

    // ─── Paksa lengkapi & optimasi ────────────────────────────────────────

    private function paksaLengkapi(array $jadwal, array $unitsByKelas, array $kelasIds, array $bebanMap, int $t0, int $limit): array
    {
        $best = $jadwal;
        $this->rebuildGuruOcc($best, $kelasIds);
        $target = 0;
        foreach ($unitsByKelas as $units) {
            foreach ($units as $u) {
                $target += $u['jtm'];
            }
        }
        $bestKosong = $target - $this->hitungTerisi($best);

        for ($round = 0; $round < 40; $round++) {
            if ($this->waktuHabis($limit)) {
                break;
            }

            $trial = $this->salinJadwal($best);
            $this->rebuildGuruOcc($trial, $kelasIds);
            $progress = false;

            foreach ($unitsByKelas as $kelasId => $units) {
                foreach ($units as $unit) {
                    if ($this->sisaUnit($trial, $unit) <= 0) {
                        continue;
                    }
                    $remaining = $this->sisaUnit($trial, $unit);
                    $days = $this->hariTerpakai($trial, $unit);
                    $combos = $this->urutkanComboByBeban(
                        $trial,
                        $unit,
                        $this->enumerasiPenempatan($trial, $unit, $remaining, $kelasIds, $days, 400),
                        $kelasIds
                    );
                    if ($this->cobaTempatkanCombo($trial, $unit, $combos, $kelasIds)) {
                        $progress = true;
                    }
                }
            }

            if (!$progress) {
                break;
            }

            $kosong = $target - $this->hitungTerisi($trial);
            if ($kosong < $bestKosong) {
                $best = $trial;
                $bestKosong = $kosong;
            }
            if ($bestKosong === 0) {
                break;
            }
        }
        return $best;
    }

    /** Fase paksa: isi semua JTM tersisa, boleh langgar preset blokir, maks 7 jam/hari guru. */
    private function isiSemuaPaksa(array $jadwal, array $unitsByKelas, array $kelasIds, int $t0, int $deadline): array
    {
        $best = $jadwal;
        $unitMap = $this->flatUnits($unitsByKelas);

        for ($pass = 0; $pass < 200; $pass++) {
            if ($this->waktuHabis($deadline)) {
                break;
            }

            $this->perbaikiSemuaUnitRusak($best, $unitsByKelas, $kelasIds);

            $pending = [];
            foreach ($unitsByKelas as $units) {
                foreach ($units as $unit) {
                    if ($this->sisaUnit($best, $unit) > 0) {
                        $pending[] = $unit;
                    }
                }
            }
            if (empty($pending)) {
                break;
            }

            if ($pass % 3 === 1) {
                shuffle($pending);
            } else {
                usort($pending, fn($a, $b) => $this->sisaUnit($best, $b) <=> $this->sisaUnit($best, $a));
            }

            $progress = false;
            foreach ($pending as $unit) {
                if ($this->unitLengkap($best, $unit)) {
                    continue;
                }
                if ($this->isiSatuUnit($best, $unit, $kelasIds, $unitMap)) {
                    $progress = true;
                }
            }

            if (!$progress) {
                foreach ($pending as $unit) {
                    if ($this->unitLengkap($best, $unit)) {
                        continue;
                    }
                    if ($this->cobaTempatkanSebagian($best, $unit, $kelasIds)) {
                        $progress = true;
                    }
                }
            }

            if (!$progress) {
                foreach ($pending as $unit) {
                    if ($this->unitLengkap($best, $unit)) {
                        continue;
                    }
                    if ($this->resetDanCobaUlangUnit($best, $unit, $kelasIds, $unitMap)) {
                        $progress = true;
                    }
                }
            }

            if (!$progress) {
                break;
            }
        }

        $this->rebuildGuruOcc($best, $kelasIds);
        $this->tempatkanGlobalGreedy($best, $unitsByKelas, $kelasIds);

        return $best;
    }

    private function isiSatuUnit(array &$jadwal, array $unit, array $kelasIds, array $unitMap): bool
    {
        if ($this->unitSebagianRusak($jadwal, $unit)) {
            $this->kosongkanUnit($jadwal, $unit, $kelasIds);
        }

        $remaining = $this->sisaUnit($jadwal, $unit);
        if ($remaining <= 0) {
            return false;
        }

        // Coba tempatkan seluruh sisa sekaligus dari awal (fresh)
        if ($remaining === $unit['jtm']) {
            $combos = $this->urutkanComboByBeban(
                $jadwal,
                $unit,
                $this->enumerasiPenempatan($jadwal, $unit, $remaining, $kelasIds, [], 2500),
                $kelasIds
            );
            if ($this->cobaTempatkanCombo($jadwal, $unit, $combos, $kelasIds)) {
                return true;
            }
            foreach ($combos as $combo) {
                if (!$this->comboLayakBeban($jadwal, $unit, $combo, $kelasIds)) {
                    continue;
                }
                $trial = $this->salinJadwal($jadwal);
                $this->rebuildGuruOcc($trial, $kelasIds);
                $this->bebaskanPenghalangGuru($trial, $unit, $combo, $kelasIds, $unitMap, 4);
                if (!$this->comboLayakBeban($trial, $unit, $combo, $kelasIds)) {
                    continue;
                }
                $this->terapkanCombo($trial, $unit, $combo);
                if ($this->unitLengkap($trial, $unit)) {
                    if (!empty($unit['isBtq'])) {
                        $this->kunciSlotBtq($trial, $unit);
                    }
                    $jadwal = $trial;
                    return true;
                }
                $this->batalkanCombo($trial, $unit, $combo);
            }
        }

        $days = $this->hariTerpakai($jadwal, $unit);
        $combos = $this->urutkanComboByBeban(
            $jadwal,
            $unit,
            $this->enumerasiPenempatan($jadwal, $unit, $remaining, $kelasIds, $days, 2000),
            $kelasIds
        );

        if ($this->cobaTempatkanCombo($jadwal, $unit, $combos, $kelasIds)) {
            return true;
        }

        foreach ($combos as $combo) {
            if (!$this->comboLayakBeban($jadwal, $unit, $combo, $kelasIds)) {
                continue;
            }

            $trial = $this->salinJadwal($jadwal);
            $this->rebuildGuruOcc($trial, $kelasIds);
            $this->bebaskanPenghalangGuru($trial, $unit, $combo, $kelasIds, $unitMap, 4);

            if (!$this->comboLayakBeban($trial, $unit, $combo, $kelasIds)) {
                continue;
            }
            $this->terapkanCombo($trial, $unit, $combo);
            if ($this->unitLengkap($trial, $unit)) {
                if (!empty($unit['isBtq'])) {
                    $this->kunciSlotBtq($trial, $unit);
                }
                $jadwal = $trial;
                return true;
            }
            $this->batalkanCombo($trial, $unit, $combo);
        }

        if ($remaining === 1 || $unit['jtm'] === 1) {
            return $this->tempatkanJamTunggal($jadwal, $unit, $kelasIds, $unitMap);
        }

        return false;
    }

    private function cobaTempatkanSebagian(array &$jadwal, array $unit, array $kelasIds): bool
    {
        $before = $this->sisaUnit($jadwal, $unit);
        if ($before <= 0) {
            return false;
        }

        foreach ($this->posisiLengkapi($jadwal, $unit, $kelasIds) as $pos) {
            $combo = [$pos];
            if (!$this->comboLayakBeban($jadwal, $unit, $combo, $kelasIds)) {
                continue;
            }
            $this->terapkanCombo($jadwal, $unit, $combo);
            if ($this->sisaUnit($jadwal, $unit) < $before && $this->bisaDilengkapi($jadwal, $unit)) {
                return true;
            }
            $this->batalkanCombo($jadwal, $unit, $combo);
        }

        foreach ($this->getLeadingBlockSizes($before) as $size) {
            $exclude = ($size >= 2) ? $this->hariTerpakai($jadwal, $unit) : [];
            foreach ($this->semuaPosisiBlok($jadwal, $unit, $size, $kelasIds, $exclude) as $pos) {
                $combo = [$pos];
                if (!$this->comboLayakBeban($jadwal, $unit, $combo, $kelasIds)) {
                    continue;
                }
                $this->terapkanCombo($jadwal, $unit, $combo);
                if ($this->sisaUnit($jadwal, $unit) < $before && $this->bisaDilengkapi($jadwal, $unit)) {
                    return true;
                }
                $this->batalkanCombo($jadwal, $unit, $combo);
            }
        }

        return false;
    }

    private function tempatkanJamTunggal(array &$jadwal, array $unit, array $kelasIds, array $unitMap): bool
    {
        $kelasId = $unit['kelasId'];
        foreach ($this->strukturHari as $hari => $maxJam) {
            for ($jam = 1; $jam <= $maxJam; $jam++) {
                if (($jadwal[$hari][$jam][$kelasId] ?? null) !== null) {
                    continue;
                }
                $combo = [['hari' => $hari, 'startJam' => $jam, 'size' => 1]];
                if (!$this->comboLayakBeban($jadwal, $unit, $combo, $kelasIds)) {
                    continue;
                }
                $trial = $this->salinJadwal($jadwal);
                $this->rebuildGuruOcc($trial, $kelasIds);
                $this->bebaskanPenghalangGuru($trial, $unit, $combo, $kelasIds, $unitMap, 3);
                if (!$this->comboLayakBeban($trial, $unit, $combo, $kelasIds)) {
                    continue;
                }
                if (!$this->bisaLetakkan($trial, $unit, $hari, $jam, 1, $kelasIds)) {
                    continue;
                }
                $this->terapkanCombo($trial, $unit, $combo);
                if ($this->unitLengkap($trial, $unit)) {
                    $jadwal = $trial;
                    return true;
                }
                $this->batalkanCombo($trial, $unit, $combo);
            }
        }
        return false;
    }

    private function getLeadingBlockSizes(int $remaining): array
    {
        $sizes = [];
        foreach ($this->getBlockPatterns($remaining) as $pattern) {
            $sizes[$pattern[0]] = true;
        }
        return array_keys($sizes);
    }

    private function distribusiHariUnit(array $jadwal, array $unit): array
    {
        $dist = [];
        $kelasId = $unit['kelasId'];
        $bmId = $unit['bmId'];
        foreach ($this->strukturHari as $hari => $jml) {
            $cnt = 0;
            for ($j = 1; $j <= $jml; $j++) {
                $s = $jadwal[$hari][$j][$kelasId] ?? null;
                if ($s !== null && $s['beban_mengajar_id'] == $bmId) {
                    $cnt++;
                }
            }
            if ($cnt > 0) {
                $dist[] = $cnt;
            }
        }
        rsort($dist);
        return $dist;
    }

    private function bisaDilengkapi(array $jadwal, array $unit): bool
    {
        $rem = $this->sisaUnit($jadwal, $unit);
        if ($rem <= 0) {
            return $this->validasiBeban($jadwal, $unit['kelasId'], $unit['bmId'], $unit['jtm']);
        }
        if (!$this->validasiKontiguitasUnit($jadwal, $unit)) {
            return false;
        }

        $dist = $this->distribusiHariUnit($jadwal, $unit);
        foreach ($this->getBlockPatterns($unit['jtm']) as $fullPattern) {
            $target = $fullPattern;
            rsort($target);
            foreach ($this->getBlockPatterns($rem) as $remPattern) {
                $candidate = array_merge($dist, $remPattern);
                rsort($candidate);
                if ($candidate === $target) {
                    return true;
                }
            }
        }
        return false;
    }

    private function validasiKontiguitasUnit(array $jadwal, array $unit): bool
    {
        $kelasId = $unit['kelasId'];
        $bmId = $unit['bmId'];
        foreach ($this->strukturHari as $hari => $jml) {
            $jams = [];
            for ($j = 1; $j <= $jml; $j++) {
                $s = $jadwal[$hari][$j][$kelasId] ?? null;
                if ($s !== null && $s['beban_mengajar_id'] == $bmId) {
                    $jams[] = $j;
                }
            }
            if (count($jams) <= 1) {
                continue;
            }
            sort($jams);
            for ($i = 0; $i < count($jams) - 1; $i++) {
                if ($jams[$i + 1] - $jams[$i] > 1) {
                    return false;
                }
            }
        }
        return true;
    }

    private function unitSebagianRusak(array $jadwal, array $unit): bool
    {
        if (!empty($unit['isBtq'])) {
            return false;
        }
        $placed = $unit['jtm'] - $this->sisaUnit($jadwal, $unit);
        return $placed > 0
            && $this->sisaUnit($jadwal, $unit) > 0
            && !$this->bisaDilengkapi($jadwal, $unit);
    }

    private function perbaikiSemuaUnitRusak(array &$jadwal, array $unitsByKelas, array $kelasIds): void
    {
        foreach ($unitsByKelas as $units) {
            foreach ($units as $unit) {
                if ($this->unitSebagianRusak($jadwal, $unit)) {
                    $this->kosongkanUnit($jadwal, $unit, $kelasIds);
                }
            }
        }
    }

    private function resetDanCobaUlangUnit(array &$jadwal, array $unit, array $kelasIds, array $unitMap): bool
    {
        if (!empty($unit['isBtq'])) {
            return false;
        }
        if ($this->sisaUnit($jadwal, $unit) <= 0) {
            return false;
        }
        $this->kosongkanUnit($jadwal, $unit, $kelasIds);
        return $this->isiSatuUnit($jadwal, $unit, $kelasIds, $unitMap);
    }

    private function unlockNonBtqSlots(array $jadwal, array $unitsByKelas): void
    {
        $btqBmIds = [];
        foreach ($unitsByKelas as $units) {
            foreach ($units as $u) {
                if (!empty($u['isBtq'])) {
                    $btqBmIds[$u['bmId']] = true;
                }
            }
        }
        foreach ($this->lockedSlots as $hari => $jams) {
            foreach ($jams as $jam => $classes) {
                foreach (array_keys($classes) as $kId) {
                    $slot = $jadwal[$hari][$jam][$kId] ?? null;
                    if ($slot === null || !isset($btqBmIds[$slot['beban_mengajar_id']])) {
                        unset($this->lockedSlots[$hari][$jam][$kId]);
                    }
                }
            }
        }
    }

    /** Relokasi mapel lain yang bentrok dengan guru di slot target. */
    private function bebaskanPenghalangGuru(array &$jadwal, array $unit, array $combo, array $kelasIds, array $unitMap, int $depth = 2): void
    {
        $guruId = $unit['guruId'];
        $kelasId = $unit['kelasId'];

        foreach ($combo as $blok) {
            $hari = $blok['hari'];
            for ($jam = $blok['startJam']; $jam < $blok['startJam'] + $blok['size']; $jam++) {
                foreach ($kelasIds as $kId) {
                    if ($kId === $kelasId) {
                        continue;
                    }
                    $slot = $jadwal[$hari][$jam][$kId] ?? null;
                    if ($slot === null || $slot['guru_id'] != $guruId) {
                        continue;
                    }
                    $blocker = $unitMap[$slot['beban_mengajar_id']] ?? null;
                    if (!$blocker || !empty($blocker['isBtq'])) {
                        continue;
                    }
                    if ($this->honorBlockConstraints && isset($this->lockedSlots[$hari][$jam][$kId])) {
                        continue;
                    }

                    $this->kosongkanUnit($jadwal, $blocker, $kelasIds);
                    $rem = $this->sisaUnit($jadwal, $blocker);
                    if ($rem <= 0) {
                        continue;
                    }

                    $alt = $this->urutkanComboByBeban(
                        $jadwal,
                        $blocker,
                        $this->enumerasiPenempatan($jadwal, $blocker, $rem, $kelasIds, [], 600),
                        $kelasIds
                    );
                    if ($this->cobaTempatkanCombo($jadwal, $blocker, $alt, $kelasIds)) {
                        continue;
                    }
                    if ($depth > 0) {
                        foreach ($alt as $bc) {
                            $this->bebaskanPenghalangGuru($jadwal, $blocker, [$bc], $kelasIds, $unitMap, $depth - 1);
                            if ($this->cobaTempatkanCombo($jadwal, $blocker, $alt, $kelasIds)) {
                                break;
                            }
                        }
                    }
                }
            }
        }
    }

    /** Pindahkan mapel dari hari overload (>7 jam) ke hari lebih ringan. */
    private function seimbangkanKelelahan(array $jadwal, array $unitsByKelas, array $kelasIds, array $bebanMap, int $t0, int $limit): array
    {
        $best = $jadwal;
        $this->rebuildGuruOcc($best, $kelasIds);

        for ($i = 0; $i < 800; $i++) {
            if ($this->waktuHabis($limit)) {
                break;
            }

            $load = $this->bebanGuruHarian($best, $kelasIds);
            $over = null;
            foreach ($load as $gid => $days) {
                foreach ($days as $hari => $cnt) {
                    if ($cnt > self::MAX_JAM_GURU_HARI && ($over === null || $cnt > $over['cnt'])) {
                        $over = ['guruId' => $gid, 'hari' => $hari, 'cnt' => $cnt];
                    }
                }
            }
            if ($over === null) {
                break;
            }

            $improved = false;
            $unitMap = $this->flatUnits($unitsByKelas);
            $savedHonor = $this->honorBlockConstraints;

            foreach ([true, false] as $honorBlock) {
                if ($improved) {
                    break;
                }
                $this->honorBlockConstraints = $honorBlock;

                foreach ($kelasIds as $kId) {
                    for ($j = 1; $j <= ($this->strukturHari[$over['hari']] ?? 0); $j++) {
                        $slot = $best[$over['hari']][$j][$kId] ?? null;
                        if ($slot === null || $slot['guru_id'] != $over['guruId']) {
                            continue;
                        }
                        $bmId = $slot['beban_mengajar_id'];
                        $unit = $unitMap[$bmId] ?? null;
                        if (!$unit || !empty($unit['isBtq'])) {
                            continue;
                        }
                        if (isset($this->lockedSlots[$over['hari']][$j][$kId])) {
                            continue;
                        }

                        $trial = $this->salinJadwal($best);
                        $this->rebuildGuruOcc($trial, $kelasIds);
                        $this->kosongkanUnit($trial, $unit, $kelasIds);

                        $rem = $this->sisaUnit($trial, $unit);
                        if ($rem <= 0) {
                            continue;
                        }

                        $combos = $this->urutkanComboByBeban(
                            $trial,
                            $unit,
                            $this->enumerasiPenempatan($trial, $unit, $rem, $kelasIds, [], 500),
                            $kelasIds
                        );

                        foreach ($combos as $combo) {
                            if (!$this->comboLayakBeban($trial, $unit, $combo, $kelasIds)) {
                                continue;
                            }
                            $t2 = $this->salinJadwal($trial);
                            $this->rebuildGuruOcc($t2, $kelasIds);
                            $this->terapkanCombo($t2, $unit, $combo);
                            if (!$this->unitLengkap($t2, $unit)) {
                                continue;
                            }
                            $newLoad = $this->bebanGuruHarian($t2, $kelasIds);
                            $oldCnt = $over['cnt'];
                            $newCnt = $newLoad[$over['guruId']][$over['hari']] ?? 0;
                            $oldSkor = $this->hitungSkorKelelahan($best, $kelasIds);
                            $newSkor = $this->hitungSkorKelelahan($t2, $kelasIds);
                            $oldTerisi = $this->hitungTerisi($best);
                            $newTerisi = $this->hitungTerisi($t2);
                            if ($newTerisi >= $oldTerisi && ($newCnt < $oldCnt || $newSkor < $oldSkor)) {
                                $best = $t2;
                                $improved = true;
                                break 3;
                            }
                        }
                    }
                }
            }

            $this->honorBlockConstraints = $savedHonor;
            if (!$improved) {
                break;
            }
        }
        return $best;
    }

    private function flatUnits(array $unitsByKelas): array
    {
        $map = [];
        foreach ($unitsByKelas as $units) {
            foreach ($units as $u) {
                $map[$u['bmId']] = $u;
            }
        }
        return $map;
    }

    private function kosongkanUnit(array &$jadwal, array $unit, array $kelasIds): void
    {
        $kId = $unit['kelasId'];
        $bmId = $unit['bmId'];
        foreach ($this->strukturHari as $hari => $jml) {
            for ($j = 1; $j <= $jml; $j++) {
                $s = $jadwal[$hari][$j][$kId] ?? null;
                if ($s !== null && $s['beban_mengajar_id'] == $bmId && !isset($this->lockedSlots[$hari][$j][$kId])) {
                    $this->copot($jadwal, $unit, $hari, $j, 1);
                }
            }
        }
    }

    private function optimasiRingan(array $jadwal, array $kelasIds, array $bebanMap, int $t0, int $limit): array
    {
        $best = $jadwal;
        $this->rebuildGuruOcc($best, $kelasIds);
        $load = $this->bebanGuruHarian($best, $kelasIds);

        for ($i = 0; $i < 800; $i++) {
            if ($this->waktuHabis($limit)) {
                break;
            }
            $over = null;
            foreach ($load as $gid => $days) {
                foreach ($days as $hari => $cnt) {
                    if ($cnt >= 9 && ($over === null || $cnt > $over['cnt'])) {
                        $over = ['guruId' => $gid, 'hari' => $hari, 'cnt' => $cnt];
                    }
                }
            }
            if ($over === null) {
                break;
            }

            $improved = false;
            foreach ($kelasIds as $kId) {
                for ($j = 1; $j <= ($this->strukturHari[$over['hari']] ?? 0); $j++) {
                    $slot = $best[$over['hari']][$j][$kId] ?? null;
                    if ($slot === null || $slot['guru_id'] != $over['guruId']) {
                        continue;
                    }
                    $bmId = $slot['beban_mengajar_id'];
                    $meta = $this->bebanMeta[$bmId] ?? null;
                    if (!$meta || ($meta['is_btq'] ?? false)) {
                        continue;
                    }
                    $unit = [
                        'bmId' => $bmId,
                        'guruId' => $meta['guru_id'],
                        'kelasId' => $kId,
                        'jtm' => $meta['jtm'],
                        'slotTemplate' => $slot,
                    ];
                    $trial = $this->salinJadwal($best);
                    $this->rebuildGuruOcc($trial, $kelasIds);
                    foreach ($this->strukturHari as $h => $jm) {
                        for ($jj = 1; $jj <= $jm; $jj++) {
                            $s = $trial[$h][$jj][$kId] ?? null;
                            if ($s !== null && $s['beban_mengajar_id'] == $bmId && !isset($this->lockedSlots[$h][$jj][$kId])) {
                                $this->copot($trial, $unit, $h, $jj, 1);
                            }
                        }
                    }
                    $rem = $this->sisaUnit($trial, $unit);
                    if ($rem <= 0) {
                        continue;
                    }
                    foreach ($this->enumerasiPenempatan($trial, $unit, $rem, $kelasIds, []) as $combo) {
                        $t2 = $this->salinJadwal($trial);
                        $this->rebuildGuruOcc($t2, $kelasIds);
                        $this->terapkanCombo($t2, $unit, $combo);
                        if ($this->sisaUnit($t2, $unit) > 0) {
                            continue;
                        }
                        $newLoad = $this->bebanGuruHarian($t2, $kelasIds);
                        $oldMax = max($load[$over['guruId']] ?? [0]);
                        $newMax = max($newLoad[$over['guruId']] ?? [0]);
                        if ($newMax < $oldMax && $this->hitungHardPenalti($t2, $kelasIds, $bebanMap) === 0) {
                            $best = $t2;
                            $load = $newLoad;
                            $improved = true;
                            break 3;
                        }
                    }
                }
            }
            if (!$improved) {
                break;
            }
        }
        return $best;
    }

    // ─── Validasi & utilitas ──────────────────────────────────────────────

    private function unitLengkap(array $jadwal, array $unit): bool
    {
        return $this->sisaUnit($jadwal, $unit) <= 0
            && $this->validasiBeban($jadwal, $unit['kelasId'], $unit['bmId'], $unit['jtm']);
    }

    private function sisaUnit(array $jadwal, array $unit): int
    {
        $n = 0;
        $kid = $unit['kelasId'];
        $bmId = $unit['bmId'];
        foreach ($this->strukturHari as $hari => $jml) {
            for ($j = 1; $j <= $jml; $j++) {
                $s = $jadwal[$hari][$j][$kid] ?? null;
                if ($s !== null && $s['beban_mengajar_id'] == $bmId) {
                    $n++;
                }
            }
        }
        return max(0, $unit['jtm'] - $n);
    }

    private function semuaLengkap(array $unitsByKelas, array $jadwal): bool
    {
        foreach ($unitsByKelas as $units) {
            foreach ($units as $unit) {
                if (!$this->unitLengkap($jadwal, $unit)) {
                    return false;
                }
            }
        }
        return true;
    }

    private function hariTerpakai(array $jadwal, array $unit): array
    {
        $days = [];
        foreach ($this->strukturHari as $hari => $jml) {
            for ($j = 1; $j <= $jml; $j++) {
                $s = $jadwal[$hari][$j][$unit['kelasId']] ?? null;
                if ($s !== null && $s['beban_mengajar_id'] == $unit['bmId']) {
                    $days[$hari] = true;
                }
            }
        }
        return array_keys($days);
    }

    private function validasiBeban(array $jadwal, int $kelasId, int $bmId, int $jtm): bool
    {
        $days = [];
        foreach ($this->strukturHari as $hari => $jml) {
            $jams = [];
            for ($j = 1; $j <= $jml; $j++) {
                $s = $jadwal[$hari][$j][$kelasId] ?? null;
                if ($s !== null && $s['beban_mengajar_id'] == $bmId) {
                    $jams[] = $j;
                }
            }
            if (empty($jams)) {
                continue;
            }
            for ($i = 0; $i < count($jams) - 1; $i++) {
                if ($jams[$i + 1] - $jams[$i] > 1) {
                    return false;
                }
            }
            $days[] = count($jams);
        }
        rsort($days);
        $base = match ($jtm) {
            1 => $days === [1],
            2 => $days === [2],
            3 => $days === [3] || $days === [2, 1],
            4 => $days === [2, 2],
            5 => $days === [3, 2] || $days === [2, 2, 1],
            6 => $days === [3, 3] || $days === [2, 2, 2],
            default => array_sum($days) === $jtm,
        };
        if (!$base) {
            return false;
        }
        if ($this->bebanMeta[$bmId]['is_btq'] ?? false) {
            return $this->validasiBtq($jadwal, $kelasId, $bmId, $jtm);
        }
        return true;
    }

    private function validasiBtq(array $jadwal, int $kelasId, int $bmId, int $jtm): bool
    {
        $jams = [];
        foreach ($this->strukturHari as $hari => $jml) {
            for ($j = 1; $j <= $jml; $j++) {
                $s = $jadwal[$hari][$j][$kelasId] ?? null;
                if ($s !== null && $s['beban_mengajar_id'] == $bmId) {
                    if ($hari !== self::BTQ_HARI) {
                        return false;
                    }
                    $jams[] = $j;
                }
            }
        }
        if (count($jams) !== $jtm) {
            return false;
        }
        if (empty($jams)) {
            return false;
        }
        sort($jams);
        if (max($jams) !== self::BTQ_JAM_AKHIR) {
            return false;
        }
        for ($i = 0; $i < count($jams) - 1; $i++) {
            if ($jams[$i + 1] - $jams[$i] > 1) {
                return false;
            }
        }
        return true;
    }

    private function hitungTerisi(array $jadwal): int
    {
        $n = 0;
        foreach ($jadwal as $jamData) {
            foreach ($jamData as $kelasData) {
                foreach ($kelasData as $slot) {
                    if ($slot !== null) {
                        $n++;
                    }
                }
            }
        }
        return $n;
    }

    private function hitungHardPenalti(array $jadwal, array $kelasIds, array $bebanMap): int
    {
        $p = 0;
        $guruLoad = [];
        foreach ($this->strukturHari as $hari => $jml) {
            for ($jam = 1; $jam <= $jml; $jam++) {
                $seen = [];
                foreach ($kelasIds as $kId) {
                    $slot = $jadwal[$hari][$jam][$kId] ?? null;
                    if ($slot === null) {
                        continue;
                    }
                    $gid = $slot['guru_id'];
                    if (isset($seen[$gid])) {
                        $p += 1000000;
                    }
                    $seen[$gid] = true;
                    if ($this->isBlocked($gid, $hari, $jam)) {
                        $p += 1000000;
                    }
                    $guruLoad[$gid][$hari] = ($guruLoad[$gid][$hari] ?? 0) + 1;
                }
            }
        }
        foreach ($kelasIds as $kId) {
            foreach ($bebanMap as $bmId => $jtm) {
                if (($this->bebanMeta[$bmId]['kelas_id'] ?? null) != $kId) {
                    continue;
                }
                if (!$this->validasiBeban($jadwal, $kId, $bmId, $jtm)) {
                    $p += 1000000;
                }
            }
        }
        foreach ($guruLoad as $days) {
            foreach ($days as $cnt) {
                if ($cnt > self::MAX_JAM_GURU_HARI) {
                    $p += ($cnt - self::MAX_JAM_GURU_HARI) * 100000;
                }
            }
        }
        return $p;
    }

    private function bebanGuruHarian(array $jadwal, array $kelasIds): array
    {
        $load = [];
        foreach ($this->strukturHari as $hari => $jml) {
            for ($jam = 1; $jam <= $jml; $jam++) {
                foreach ($kelasIds as $kId) {
                    $slot = $jadwal[$hari][$jam][$kId] ?? null;
                    if ($slot !== null) {
                        $load[$slot['guru_id']][$hari] = ($load[$slot['guru_id']][$hari] ?? 0) + 1;
                    }
                }
            }
        }
        return $load;
    }

    private function getBlockPatterns(int $jtm): array
    {
        return match ($jtm) {
            1 => [[1]],
            2 => [[2]],
            3 => [[3], [2, 1]],
            4 => [[2, 2]],
            5 => [[3, 2], [2, 2, 1]],
            6 => [[3, 3], [2, 2, 2]],
            default => [array_fill(0, (int) ceil($jtm / 2), 2)],
        };
    }

    private function getBebanMap(): array
    {
        $m = [];
        foreach ($this->bebanMeta as $id => $meta) {
            $m[$id] = $meta['jtm'];
        }
        return $m;
    }

    private function buatJadwalKosong(array $kelasIds): array
    {
        $jadwal = [];
        foreach ($this->strukturHari as $hari => $jml) {
            for ($jam = 1; $jam <= $jml; $jam++) {
                foreach ($kelasIds as $kId) {
                    $jadwal[$hari][$jam][$kId] = null;
                }
            }
        }
        return $jadwal;
    }

    private function salinJadwal(array $jadwal): array
    {
        return unserialize(serialize($jadwal));
    }

    private function normalizeHari(string $hari): string
    {
        return ucfirst(strtolower(trim($hari)));
    }

    private function loadConstraints(): void
    {
        $this->fastConstraints = [];
        foreach (GuruConstraint::all() as $c) {
            $h = $this->normalizeHari($c->hari);
            $this->fastConstraints[$c->guru_id][$h][$c->jam_ke] = (int) $c->type;
        }
    }

    private function isBlocked(int $guruId, string $hari, int $jam): bool
    {
        $h = $this->normalizeHari($hari);
        return isset($this->fastConstraints[$guruId][$h][$jam])
            && $this->fastConstraints[$guruId][$h][$jam] === 0;
    }

    private function simpanJadwal(int $semesterId, array $solusi, int $terisi, int $totalTarget, int $kosong): array
    {
        DB::table('jadwals')->where('semester_id', $semesterId)->delete();
        DB::beginTransaction();
        try {
            $rows = [];
            $now = now();
            foreach ($solusi as $hari => $jamData) {
                foreach ($jamData as $jam => $kelasData) {
                    foreach ($kelasData as $slot) {
                        if ($slot === null) {
                            continue;
                        }
                        $rows[] = [
                            'semester_id' => $semesterId,
                            'beban_mengajar_id' => $slot['beban_mengajar_id'],
                            'hari' => $this->normalizeHari($hari),
                            'jam_ke' => $jam,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }
            }
            foreach (array_chunk($rows, 500) as $chunk) {
                Jadwal::insert($chunk);
            }
            DB::commit();
            return [
                'status' => $kosong === 0 ? 'success' : 'partial',
                'biaya_penalti' => 0,
                'total_slot_terisi' => $terisi,
                'total_target' => $totalTarget,
                'slot_kosong' => $kosong,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('JadwalSAO save: ' . $e->getMessage());
            throw new \Exception('Gagal menyimpan jadwal: ' . $e->getMessage());
        }
    }
}
