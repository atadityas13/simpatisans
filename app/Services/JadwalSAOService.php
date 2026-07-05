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

    private array $strukturHari = ['Senin' => 9, 'Selasa' => 10, 'Rabu' => 10, 'Kamis' => 10, 'Jumat' => 5];
    private array $fastConstraints = [];
    private array $lockedSlots = [];
    private array $bebanMeta = [];
    private array $kelasIds = [];
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
        $batasWaktu = 105;
        $solusiTerbaik = null;
        $kosongTerbaik = $totalJtm;

        for ($attempt = 0; $attempt < 40; $attempt++) {
            if ((time() - $waktuMulai) >= $batasWaktu - 8) {
                break;
            }

            $this->lockedSlots = [];
            $this->guruOcc = [];
            $jadwal = $this->buatJadwalKosong($kelasIds);
            $urutanKelas = $this->urutkanKelas($unitsByKelas, $attempt);

            $ok = $this->selesaikanSemuaKelas($jadwal, $unitsByKelas, $urutanKelas, $kelasIds, $waktuMulai, $batasWaktu);

            $kosong = $totalJtm - $this->hitungTerisi($jadwal);
            if ($kosong < $kosongTerbaik) {
                $kosongTerbaik = $kosong;
                $solusiTerbaik = $this->salinJadwal($jadwal);
            }

            if ($ok && $kosong === 0) {
                $solusiTerbaik = $jadwal;
                $kosongTerbaik = 0;
                break;
            }
        }

        if ($solusiTerbaik === null) {
            throw new \Exception('Gagal membuat jadwal. Kurangi preset blokir guru.');
        }

        if ($kosongTerbaik > 0) {
            $this->rebuildGuruOcc($solusiTerbaik, $kelasIds);
            $solusiTerbaik = $this->paksaLengkapi($solusiTerbaik, $unitsByKelas, $kelasIds, $bebanMap, $waktuMulai, $batasWaktu);
            $kosongTerbaik = $totalJtm - $this->hitungTerisi($solusiTerbaik);
        }

        if ($kosongTerbaik > 0) {
            $this->rebuildGuruOcc($solusiTerbaik, $kelasIds);
            $solusiTerbaik = $this->lengkapiSerangkaian($solusiTerbaik, $unitsByKelas, $kelasIds, $waktuMulai, $batasWaktu);
            $kosongTerbaik = $totalJtm - $this->hitungTerisi($solusiTerbaik);
        }

        if ($kosongTerbaik === 0) {
            $solusiTerbaik = $this->optimasiRingan($solusiTerbaik, $kelasIds, $bebanMap, $waktuMulai, $batasWaktu);
        }

        $terisi = $this->hitungTerisi($solusiTerbaik);
        if ($terisi === 0) {
            throw new \Exception('Gagal membuat jadwal. Kurangi preset blokir guru atau periksa beban mengajar.');
        }

        $kosongTerbaik = $totalJtm - $terisi;
        return $this->simpanJadwal($semesterId, $solusiTerbaik, $terisi, $totalJtm, $kosongTerbaik);
    }

    // ─── Inti CSP per kelas ───────────────────────────────────────────────

    private function selesaikanSemuaKelas(array &$jadwal, array $unitsByKelas, array $urutanKelas, array $kelasIds, int $t0, int $limit): bool
    {
        $this->tempatkanPreserveGlobal($jadwal, $unitsByKelas, $kelasIds);

        foreach ($urutanKelas as $kelasId) {
            if ((time() - $t0) >= $limit - 5) {
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
                foreach ($this->enumerasiPenempatan($jadwal, $unit, $remaining, $kelasIds, $days, 300) as $combo) {
                    $this->terapkanCombo($jadwal, $unit, $combo);
                    if ($this->sisaUnit($jadwal, $unit) <= 0 && $this->validasiBeban($jadwal, $unit['kelasId'], $unit['bmId'], $unit['jtm'])) {
                        if (!empty($unit['isBtq'])) {
                            $this->kunciSlotBtq($jadwal, $unit);
                        }
                        $progress = true;
                        break;
                    }
                    $this->batalkanCombo($jadwal, $unit, $combo);
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
            if ((time() - $t0) >= $limit - 1) {
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

                foreach ($this->enumerasiPenempatan($best, $unit, $remaining, $kelasIds, $days, 500) as $combo) {
                    $this->terapkanCombo($best, $unit, $combo);
                    if ($this->sisaUnit($best, $unit) <= 0 && $this->validasiBeban($best, $unit['kelasId'], $unit['bmId'], $unit['jtm'])) {
                        if (!empty($unit['isBtq'])) {
                            $this->kunciSlotBtq($best, $unit);
                        }
                        $roundProgress = true;
                        break;
                    }
                    $this->batalkanCombo($best, $unit, $combo);
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
        if ((time() - $t0) >= $limit - 3) {
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
        $placements = $this->enumerasiPenempatan($jadwal, $unit, $remaining, $kelasIds, $existingDays);

        foreach ($placements as $combo) {
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
        $multi = count($blocks) > 1;
        $exclude = $multi ? array_unique(array_merge($fixedDays, array_column($chosen, 'hari'))) : [];

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
                    $out[] = ['hari' => $hari, 'startJam' => $start, 'size' => $size];
                }
            }
        }
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
            if ($this->isBlocked($guruId, $hari, $jam)) {
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

        for ($round = 0; $round < 500; $round++) {
            if ((time() - $t0) >= $limit - 2) {
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
                    foreach ($this->enumerasiPenempatan($trial, $unit, $remaining, $kelasIds, $days) as $combo) {
                        $this->terapkanCombo($trial, $unit, $combo);
                        if ($this->sisaUnit($trial, $unit) <= 0 && $this->validasiBeban($trial, $unit['kelasId'], $unit['bmId'], $unit['jtm'])) {
                            $progress = true;
                            break;
                        }
                        $this->batalkanCombo($trial, $unit, $combo);
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

    private function optimasiRingan(array $jadwal, array $kelasIds, array $bebanMap, int $t0, int $limit): array
    {
        $best = $jadwal;
        $this->rebuildGuruOcc($best, $kelasIds);
        $load = $this->bebanGuruHarian($best, $kelasIds);

        for ($i = 0; $i < 800; $i++) {
            if ((time() - $t0) >= $limit - 1) {
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
                if ($cnt >= 9) {
                    $p += ($cnt - 8) * 100000;
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
