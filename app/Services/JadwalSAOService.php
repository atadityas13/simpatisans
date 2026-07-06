<?php

namespace App\Services;

use App\Models\BebanMengajar;
use App\Models\Jadwal;
use App\Models\Kelas;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Penjadwalan Constraint Satisfaction Problem (CSP).
 *
 * Metode: Backtracking + MRV (Minimum Remaining Values) + forward checking,
 * diikuti fase perbaikan konflik (conflict-directed repair).
 * Ini pendekatan standar industri untuk school timetabling (setara inti FET / OR-Tools CP).
 */
class JadwalSAOService
{
    private const BTQ_HARI = 'Jumat';
    private const BTQ_JAM_AKHIR = 5;
    private const MAX_JAM_GURU_HARI = 7;
    private const MAX_KANDIDAT = 100;

    private array $strukturHari = ['Senin' => 9, 'Selasa' => 10, 'Rabu' => 10, 'Kamis' => 10, 'Jumat' => 5];

    private array $guruOcc = [];
    private array $grid = [];
    private array $units = [];
    private array $unitByBm = [];
    private array $kelasIds = [];

    private int $deadline = 0;
    private int $terisiTerbaik = 0;
    private int $unitLengkapTerbaik = 0;
    private array $gridTerbaik = [];
    /** true = semua jam terisi lebih penting daripada pola blok JTM sempurna */
    private bool $prioritasIsiPenuh = false;

    public function generate(int $semesterId): array
    {
        @ini_set('memory_limit', '384M');

        $beban = BebanMengajar::where('semester_id', $semesterId)
            ->where('is_satminkal', 1)
            ->with(['guru', 'mapel', 'kelas'])
            ->get();

        if ($beban->isEmpty()) {
            throw new \Exception('Data Beban Mengajar (KBM) kosong. Distribusikan jam terlebih dahulu.');
        }

        $this->kelasIds = Kelas::orderByRaw("FIELD(tingkat, 'VII', 'VIII', 'IX')")->pluck('id')->toArray();
        if (empty($this->kelasIds)) {
            throw new \Exception('Data Kelas kosong.');
        }

        $this->units = $this->buatUnits($beban);
        $this->unitByBm = [];
        foreach ($this->units as $u) {
            $this->unitByBm[$u['bmId']] = $u;
        }

        $totalJtm = array_sum(array_column($this->units, 'jtm'));
        if ($totalJtm > count($this->kelasIds) * 44) {
            throw new \Exception("Kelebihan beban: {$totalJtm} JTM melebihi kapasitas grid.");
        }

        $this->deadline = time() + 100;
        $this->terisiTerbaik = 0;
        $this->unitLengkapTerbaik = 0;
        $this->gridTerbaik = $this->gridKosong();
        $this->prioritasIsiPenuh = false;

        for ($seed = 0; $seed < 8; $seed++) {
            if ($this->waktuHabis()) {
                break;
            }
            $this->mulaiUlang();
            if ($this->cspBacktrack($seed)) {
                break;
            }
        }

        $gridCsp = $this->salinGrid($this->gridTerbaik);
        $terisiCsp = $this->terisiTerbaik;
        $unitCsp = $this->unitLengkapTerbaik;

        if (!$this->semuaJamLengkap()) {
            $this->grid = $this->salinGrid($this->gridTerbaik);
            $this->rebuildOcc();
            $this->perbaikiKonflik(false);
            $this->simpanTerbaik();
        }

        if (!$this->semuaJamLengkap()) {
            $this->prioritasIsiPenuh = true;
            $this->grid = $this->salinGrid($this->gridTerbaik);
            $this->rebuildOcc();
            $this->isiPaksaSisa();
            $this->perbaikiKonflik(true);
            $this->isiPaksaSisa();
            $this->simpanTerbaik();
        }

        if ($this->terisiTerbaik < $terisiCsp) {
            $this->gridTerbaik = $gridCsp;
            $this->terisiTerbaik = $terisiCsp;
            $this->unitLengkapTerbaik = $unitCsp;
        }

        if ($this->terisiTerbaik === 0) {
            throw new \Exception('Gagal membuat jadwal. Periksa beban mengajar.');
        }

        $kosong = $totalJtm - $this->terisiTerbaik;

        return $this->simpan($semesterId, $this->gridTerbaik, $this->terisiTerbaik, $totalJtm, $kosong);
    }

    // ─── CSP Backtracking + MRV ───────────────────────────────────────────

    private function cspBacktrack(int $seed): bool
    {
        if ($this->semuaLengkap()) {
            $this->simpanTerbaik();
            return true;
        }

        if ($this->waktuHabis()) {
            $this->simpanTerbaik();
            return $this->semuaJamLengkap();
        }

        $idx = $this->pilihMrv();
        if ($idx === null) {
            $this->simpanTerbaik();
            return $this->semuaLengkap();
        }

        $unit = $this->units[$idx];
        $this->siapkanUnit($unit);

        $sisa = $this->sisaJam($unit);
        if ($sisa <= 0) {
            return $this->cspBacktrack($seed);
        }

        foreach ($this->kandidatTerpilih($unit, $sisa) as $blok) {
            if (!$this->bebanGuruOk($unit['guruId'], $blok)) {
                continue;
            }
            if (!$this->bisaTaruh($unit, $blok)) {
                continue;
            }

            $this->taruh($unit, $blok);
            if ($this->cspBacktrack($seed)) {
                return true;
            }
            $this->batalTaruh($unit, $blok);
        }

        $this->simpanTerbaik();
        return false;
    }

    /** MRV: pilih mapel dengan kandidat penempatan paling sedikit. */
    private function pilihMrv(): ?int
    {
        $pilih = null;
        $min = PHP_INT_MAX;

        foreach ($this->units as $i => $unit) {
            if ($this->lengkap($unit)) {
                continue;
            }
            $this->siapkanUnit($unit);
            $sisa = $this->sisaJam($unit);
            if ($sisa <= 0) {
                continue;
            }
            $n = count($this->kandidatPenempatan($unit, $sisa));
            if ($n < $min) {
                $min = $n;
                $pilih = $i;
                if ($n === 0) {
                    break;
                }
            }
        }

        return $pilih;
    }

    /** LCV: kandidat diurutkan beban guru paling ringan dulu. */
    private function kandidatTerpilih(array $unit, int $sisa): array
    {
        $list = $this->kandidatPenempatan($unit, $sisa);
        usort($list, function ($a, $b) use ($unit) {
            return $this->skorBlok($unit['guruId'], $a) <=> $this->skorBlok($unit['guruId'], $b);
        });
        return $list;
    }

    private function skorBlok(int $guruId, array $blok): int
    {
        $skor = 0;
        foreach ($blok as $b) {
            $skor += $this->bebanGuruHari($guruId, $b['hari']) + $b['size'] * 10;
        }
        return $skor;
    }

    // ─── Perbaikan konflik (geser mapel penghalang) ───────────────────────

    private function perbaikiKonflik(bool $paksa): void
    {
        for ($round = 0; $round < ($paksa ? 300 : 200); $round++) {
            if ($this->waktuHabis()) {
                break;
            }

            $pending = array_values(array_filter(
                $this->units,
                fn($u) => $paksa ? $this->sisaJam($u) > 0 : !$this->lengkap($u)
            ));
            if (empty($pending)) {
                break;
            }

            usort($pending, fn($a, $b) => $this->sisaJam($b) <=> $this->sisaJam($a));
            $progress = false;
            $terisiAwal = $this->hitungTerisi();

            foreach ($pending as $unit) {
                $snap = $this->salinGrid($this->grid);
                if ($this->cobaPasangDenganEviksi($unit)) {
                    if ($this->hitungTerisi() >= $terisiAwal) {
                        $progress = true;
                        $this->simpanTerbaik();
                        break;
                    }
                    $this->grid = $snap;
                    $this->rebuildOcc();
                }
            }

            if (!$progress) {
                break;
            }
        }
    }

    /** Isi sisa jam per slot tunggal — prioritas utama: tidak ada mapel belum dialokasikan. */
    private function isiPaksaSisa(): void
    {
        for ($round = 0; $round < 2000; $round++) {
            if ($this->waktuHabis()) {
                break;
            }

            $pending = array_values(array_filter($this->units, fn($u) => $this->sisaJam($u) > 0));
            if (empty($pending)) {
                break;
            }

            usort($pending, function ($a, $b) {
                return $this->sisaJam($b) <=> $this->sisaJam($a)
                    ?: ($b['guruLoad'] <=> $a['guruLoad'])
                    ?: count($this->kandidatPenempatan($a, $this->sisaJam($a))) <=> count($this->kandidatPenempatan($b, $this->sisaJam($b)));
            });

            $progress = false;
            foreach ($pending as $unit) {
                $snap = $this->salinGrid($this->grid);
                $terisiAwal = $this->hitungTerisi();

                if ($this->cobaPasangSatuJam($unit) || $this->cobaPasangDenganEviksi($unit)) {
                    if ($this->hitungTerisi() > $terisiAwal) {
                        $progress = true;
                        $this->simpanTerbaik();
                        break;
                    }
                    $this->grid = $snap;
                    $this->rebuildOcc();
                }
            }

            if (!$progress) {
                break;
            }
        }
    }

    private function cobaPasangSatuJam(array $unit): bool
    {
        if ($this->sisaJam($unit) <= 0) {
            return true;
        }

        foreach ($this->kandidatJamTunggal($unit) as $blok) {
            if (!$this->bebanGuruOk($unit['guruId'], $blok)) {
                continue;
            }
            if ($this->bisaTaruh($unit, $blok)) {
                $this->taruh($unit, $blok);
                return true;
            }
        }

        return false;
    }

    private function cobaPasangDenganEviksi(array $unit): bool
    {
        if ($this->lengkap($unit)) {
            return true;
        }

        $this->siapkanUnit($unit);
        $sisa = $this->sisaJam($unit);
        if ($sisa <= 0) {
            return $this->lengkap($unit);
        }

        $kandidat = $this->prioritasIsiPenuh
            ? array_merge($this->kandidatPenempatan($unit, $sisa), $this->kandidatJamTunggal($unit))
            : $this->kandidatPenempatan($unit, $sisa);

        foreach ($kandidat as $blok) {
            if (!$this->bebanGuruOk($unit['guruId'], $blok)) {
                continue;
            }

            $evicted = $this->kumpulkanPenghalang($unit, $blok);
            foreach ($evicted as $e) {
                $this->hapusUnit($e);
            }

            if ($this->bisaTaruh($unit, $blok)) {
                $this->taruh($unit, $blok);
                if ($this->lengkap($unit)) {
                    foreach ($evicted as $e) {
                        $this->pasangGreedy($e);
                    }
                    return true;
                }
                $this->batalTaruh($unit, $blok);
            }

            foreach (array_reverse($evicted) as $e) {
                $this->pasangGreedy($e);
            }
        }

        return false;
    }

    private function pasangGreedy(array $unit): bool
    {
        if ($this->lengkap($unit)) {
            return true;
        }
        $this->siapkanUnit($unit);
        $sisa = $this->sisaJam($unit);
        $kandidat = array_merge(
            $this->kandidatTerpilih($unit, $sisa),
            $this->prioritasIsiPenuh ? $this->kandidatJamTunggal($unit) : []
        );
        foreach ($kandidat as $blok) {
            if ($this->bebanGuruOk($unit['guruId'], $blok) && $this->bisaTaruh($unit, $blok)) {
                $this->taruh($unit, $blok);
                if ($this->lengkap($unit)) {
                    return true;
                }
                $this->batalTaruh($unit, $blok);
            }
        }
        return $this->lengkap($unit);
    }

    /** @return list<array> */
    private function kumpulkanPenghalang(array $unit, array $blok): array
    {
        $out = [];
        $gid = $unit['guruId'];
        $kid = $unit['kelasId'];

        foreach ($blok as $b) {
            for ($j = $b['start']; $j < $b['start'] + $b['size']; $j++) {
                foreach ($this->kelasIds as $kId) {
                    if ($kId === $kid) {
                        continue;
                    }
                    $s = $this->grid[$b['hari']][$j][$kId] ?? null;
                    if ($s !== null && ($s['guru_id'] ?? null) == $gid) {
                        $bm = $s['beban_mengajar_id'];
                        if (isset($this->unitByBm[$bm]) && empty($this->unitByBm[$bm]['btq'])) {
                            $out[$bm] = $this->unitByBm[$bm];
                        }
                    }
                }
            }
        }

        return array_values($out);
    }

    // ─── Penempatan grid ──────────────────────────────────────────────────

    private function siapkanUnit(array $unit): void
    {
        $placed = $this->hitungJamUnit($unit);
        if ($placed === 0) {
            return;
        }
        if ($placed > $unit['jtm']) {
            $this->hapusUnit($unit);
            return;
        }
        if ($this->prioritasIsiPenuh) {
            return;
        }
        if (!$this->strukturOk($unit)) {
            $this->hapusUnit($unit);
            return;
        }
        $sisa = $this->sisaJam($unit);
        if ($sisa > 0 && !$this->prefixValid($unit)) {
            $this->hapusUnit($unit);
        }
    }

    /** @return list<list<array{hari:string,start:int,size:int}>> */
    private function kandidatPenempatan(array $unit, int $sisa): array
    {
        if (!empty($unit['btq'])) {
            $start = self::BTQ_JAM_AKHIR - $sisa + 1;
            if ($start < 1) {
                return [];
            }
            $blok = [['hari' => self::BTQ_HARI, 'start' => $start, 'size' => $sisa]];
            return $this->bisaTaruh($unit, $blok) ? [$blok] : [];
        }

        $hariTerpakai = $this->hariUnit($unit);
        $hasil = [];
        foreach ($this->polaJtm($sisa) as $potongan) {
            $this->kumpulKandidat($unit, $potongan, 0, [], $hariTerpakai, $hasil);
            if (count($hasil) >= self::MAX_KANDIDAT) {
                break;
            }
        }

        if ($this->prioritasIsiPenuh && empty($hasil)) {
            $hasil = $this->kandidatJamTunggal($unit);
        }

        return $hasil;
    }

    /** @return list<list<array{hari:string,start:int,size:int}>> */
    private function kandidatJamTunggal(array $unit): array
    {
        if (!empty($unit['btq'])) {
            $sisa = $this->sisaJam($unit);
            $start = self::BTQ_JAM_AKHIR - $sisa + 1;
            if ($start < 1) {
                return [];
            }
            $blok = [['hari' => self::BTQ_HARI, 'start' => $start, 'size' => $sisa]];
            return $this->bisaTaruh($unit, $blok) ? [$blok] : [];
        }

        $hasil = [];
        foreach (array_keys($this->strukturHari) as $hari) {
            $max = $this->strukturHari[$hari];
            for ($j = 1; $j <= $max; $j++) {
                $blok = [['hari' => $hari, 'start' => $j, 'size' => 1]];
                if ($this->bisaTaruh($unit, $blok)) {
                    $hasil[] = $blok;
                }
            }
        }
        return $hasil;
    }

    private function kumpulKandidat(array $unit, array $potongan, int $idx, array $pilih, array $hariTerpakai, array &$hasil): void
    {
        if (count($hasil) >= self::MAX_KANDIDAT) {
            return;
        }
        if ($idx >= count($potongan)) {
            $hasil[] = $pilih;
            return;
        }

        $ukuran = $potongan[$idx];
        $exclude = array_unique(array_merge($hariTerpakai, array_column($pilih, 'hari')));

        foreach (array_keys($this->strukturHari) as $hari) {
            if (in_array($hari, $exclude, true)) {
                continue;
            }
            $max = $this->strukturHari[$hari];
            for ($start = 1; $start <= $max - $ukuran + 1; $start++) {
                $segmen = array_merge($pilih, [['hari' => $hari, 'start' => $start, 'size' => $ukuran]]);
                if (!$this->bisaTaruh($unit, $segmen)) {
                    continue;
                }
                $this->kumpulKandidat($unit, $potongan, $idx + 1, $segmen, $hariTerpakai, $hasil);
            }
        }
    }

    private function bisaTaruh(array $unit, array $blok): bool
    {
        $kid = $unit['kelasId'];
        $gid = $unit['guruId'];

        foreach ($blok as $b) {
            for ($j = $b['start']; $j < $b['start'] + $b['size']; $j++) {
                if (($this->grid[$b['hari']][$j][$kid] ?? null) !== null) {
                    return false;
                }
                if (isset($this->guruOcc[$b['hari']][$j][$gid])) {
                    return false;
                }
            }
        }
        return true;
    }

    private function bebanGuruOk(int $guruId, array $blok): bool
    {
        $sim = [];
        foreach ($blok as $b) {
            $load = $this->bebanGuruHari($guruId, $b['hari']) + ($sim[$b['hari']] ?? 0);
            if ($load + $b['size'] > self::MAX_JAM_GURU_HARI) {
                return false;
            }
            $sim[$b['hari']] = ($sim[$b['hari']] ?? 0) + $b['size'];
        }
        return true;
    }

    private function taruh(array $unit, array $blok): void
    {
        foreach ($blok as $b) {
            for ($j = $b['start']; $j < $b['start'] + $b['size']; $j++) {
                $this->grid[$b['hari']][$j][$unit['kelasId']] = $unit['tpl'];
                $this->guruOcc[$b['hari']][$j][$unit['guruId']] = true;
            }
        }
    }

    private function batalTaruh(array $unit, array $blok): void
    {
        foreach ($blok as $b) {
            for ($j = $b['start']; $j < $b['start'] + $b['size']; $j++) {
                $this->grid[$b['hari']][$j][$unit['kelasId']] = null;
                unset($this->guruOcc[$b['hari']][$j][$unit['guruId']]);
            }
        }
    }

    private function hapusUnit(array $unit): void
    {
        $kid = $unit['kelasId'];
        $bm = $unit['bmId'];
        foreach ($this->strukturHari as $hari => $max) {
            for ($j = 1; $j <= $max; $j++) {
                $s = $this->grid[$hari][$j][$kid] ?? null;
                if ($s !== null && ($s['beban_mengajar_id'] ?? null) == $bm) {
                    $this->grid[$hari][$j][$kid] = null;
                    unset($this->guruOcc[$hari][$j][$unit['guruId']]);
                }
            }
        }
    }

    // ─── Validasi ─────────────────────────────────────────────────────────

    private function lengkap(array $unit): bool
    {
        if ($this->sisaJam($unit) !== 0) {
            return false;
        }
        return $this->prioritasIsiPenuh || $this->strukturOk($unit);
    }

    private function jamLengkap(array $unit): bool
    {
        return $this->sisaJam($unit) === 0;
    }

    private function semuaJamLengkap(): bool
    {
        foreach ($this->units as $u) {
            if (!$this->jamLengkap($u)) {
                return false;
            }
        }
        return true;
    }

    private function semuaLengkap(): bool
    {
        foreach ($this->units as $u) {
            if (!$this->lengkap($u)) {
                return false;
            }
        }
        return true;
    }

    private function sisaJam(array $unit): int
    {
        return $unit['jtm'] - $this->hitungJamUnit($unit);
    }

    private function hitungJamUnit(array $unit): int
    {
        $n = 0;
        $kid = $unit['kelasId'];
        $bm = $unit['bmId'];
        foreach ($this->strukturHari as $hari => $max) {
            for ($j = 1; $j <= $max; $j++) {
                $s = $this->grid[$hari][$j][$kid] ?? null;
                if ($s !== null && ($s['beban_mengajar_id'] ?? null) == $bm) {
                    $n++;
                }
            }
        }
        return $n;
    }

    private function strukturOk(array $unit): bool
    {
        $kid = $unit['kelasId'];
        $bm = $unit['bmId'];
        $jtm = $unit['jtm'];
        $perHari = [];

        foreach ($this->strukturHari as $hari => $max) {
            $jams = [];
            for ($j = 1; $j <= $max; $j++) {
                $s = $this->grid[$hari][$j][$kid] ?? null;
                if ($s !== null && ($s['beban_mengajar_id'] ?? null) == $bm) {
                    $jams[] = $j;
                }
            }
            if (empty($jams)) {
                continue;
            }
            sort($jams);
            for ($i = 0; $i < count($jams) - 1; $i++) {
                if ($jams[$i + 1] - $jams[$i] > 1) {
                    return false;
                }
            }
            $perHari[] = count($jams);
        }

        if (array_sum($perHari) !== $jtm) {
            return false;
        }

        rsort($perHari);

        if (!empty($unit['btq'])) {
            $jams = [];
            for ($j = 1; $j <= self::BTQ_JAM_AKHIR; $j++) {
                $s = $this->grid[self::BTQ_HARI][$j][$kid] ?? null;
                if ($s !== null && ($s['beban_mengajar_id'] ?? null) == $bm) {
                    $jams[] = $j;
                }
            }
            return count($jams) === $jtm && (!empty($jams) && max($jams) === self::BTQ_JAM_AKHIR);
        }

        return match ($jtm) {
            1 => $perHari === [1],
            2 => $perHari === [2],
            3 => $perHari === [3] || $perHari === [2, 1],
            4 => $perHari === [2, 2],
            5 => $perHari === [3, 2] || $perHari === [2, 2, 1],
            6 => $perHari === [3, 3] || $perHari === [2, 2, 2],
            default => true,
        };
    }

    private function prefixValid(array $unit): bool
    {
        $sisa = $this->sisaJam($unit);
        if ($sisa <= 0) {
            return $this->strukturOk($unit);
        }
        $dist = [];
        foreach ($this->hariUnit($unit) as $hari) {
            $n = 0;
            for ($j = 1; $j <= $this->strukturHari[$hari]; $j++) {
                $s = $this->grid[$hari][$j][$unit['kelasId']] ?? null;
                if ($s !== null && ($s['beban_mengajar_id'] ?? null) == $unit['bmId']) {
                    $n++;
                }
            }
            if ($n > 0) {
                $dist[] = $n;
            }
        }
        rsort($dist);
        foreach ($this->polaJtm($unit['jtm']) as $pola) {
            $target = $pola;
            rsort($target);
            foreach ($this->polaJtm($sisa) as $sp) {
                $c = array_merge($dist, $sp);
                rsort($c);
                if ($c === $target) {
                    return true;
                }
            }
        }
        return false;
    }

    private function hariUnit(array $unit): array
    {
        $days = [];
        foreach (array_keys($this->strukturHari) as $hari) {
            for ($j = 1; $j <= $this->strukturHari[$hari]; $j++) {
                $s = $this->grid[$hari][$j][$unit['kelasId']] ?? null;
                if ($s !== null && ($s['beban_mengajar_id'] ?? null) == $unit['bmId']) {
                    $days[$hari] = true;
                    break;
                }
            }
        }
        return array_keys($days);
    }

    private function bebanGuruHari(int $guruId, string $hari): int
    {
        $n = 0;
        foreach ($this->grid[$hari] ?? [] as $kelas) {
            foreach ($kelas as $slot) {
                if ($slot !== null && ($slot['guru_id'] ?? null) == $guruId) {
                    $n++;
                }
            }
        }
        return $n;
    }

    // ─── State ────────────────────────────────────────────────────────────

    private function mulaiUlang(): void
    {
        $this->grid = $this->gridKosong();
        $this->guruOcc = [];
    }

    private function rebuildOcc(): void
    {
        $this->guruOcc = [];
        foreach ($this->grid as $hari => $jamData) {
            foreach ($jamData as $jam => $kelasData) {
                foreach ($kelasData as $slot) {
                    if ($slot !== null) {
                        $this->guruOcc[$hari][$jam][$slot['guru_id']] = true;
                    }
                }
            }
        }
    }

    private function simpanTerbaik(): void
    {
        $unitLengkap = $this->hitungUnitJamLengkap();
        $t = $this->hitungTerisi();

        $lebihBaik = $t > $this->terisiTerbaik
            || ($t === $this->terisiTerbaik && $unitLengkap > $this->unitLengkapTerbaik);

        if ($lebihBaik) {
            $this->unitLengkapTerbaik = $unitLengkap;
            $this->terisiTerbaik = $t;
            $this->gridTerbaik = $this->salinGrid($this->grid);
        }
    }

    private function hitungUnitJamLengkap(): int
    {
        $n = 0;
        foreach ($this->units as $u) {
            if ($this->jamLengkap($u)) {
                $n++;
            }
        }
        return $n;
    }

    private function salinGrid(array $grid): array
    {
        return unserialize(serialize($grid));
    }

    private function hitungTerisi(): int
    {
        $n = 0;
        foreach ($this->grid as $jamData) {
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

    private function waktuHabis(): bool
    {
        return time() >= $this->deadline;
    }

    // ─── Data ─────────────────────────────────────────────────────────────

    private function buatUnits($bebanMengajar): array
    {
        $guruLoad = [];
        foreach ($bebanMengajar as $b) {
            $guruLoad[$b->guru_id] = ($guruLoad[$b->guru_id] ?? 0) + (int) $b->jtm;
        }

        $units = [];
        foreach ($bebanMengajar as $b) {
            $nama = $b->mapel->nama_mapel ?? '';
            $units[] = [
                'bmId' => $b->id,
                'guruId' => $b->guru_id,
                'kelasId' => $b->kelas_id,
                'jtm' => (int) $b->jtm,
                'btq' => $this->isBtq($nama),
                'guruLoad' => $guruLoad[$b->guru_id] ?? 0,
                'tpl' => [
                    'beban_mengajar_id' => $b->id,
                    'guru_id' => $b->guru_id,
                    'mapel_id' => $b->mapel_id,
                    'kelas_id' => $b->kelas_id,
                ],
            ];
        }
        usort($units, fn($a, $b) => ($b['btq'] <=> $a['btq']) ?: ($b['guruLoad'] <=> $a['guruLoad']) ?: ($b['jtm'] <=> $a['jtm']));
        return $units;
    }

    private function polaJtm(int $jtm): array
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

    private function gridKosong(): array
    {
        $g = [];
        foreach ($this->strukturHari as $hari => $max) {
            for ($j = 1; $j <= $max; $j++) {
                foreach ($this->kelasIds as $k) {
                    $g[$hari][$j][$k] = null;
                }
            }
        }
        return $g;
    }

    private function isBtq(string $nama): bool
    {
        $n = strtolower($nama);
        return str_contains($n, 'btq') || str_contains($n, 'baca tulis');
    }

    private function simpan(int $semesterId, array $grid, int $terisi, int $target, int $kosong): array
    {
        DB::table('jadwals')->where('semester_id', $semesterId)->delete();
        DB::beginTransaction();
        try {
            $rows = [];
            $now = now();
            foreach ($grid as $hari => $jamData) {
                foreach ($jamData as $jam => $kelasData) {
                    foreach ($kelasData as $slot) {
                        if ($slot === null) {
                            continue;
                        }
                        $rows[] = [
                            'semester_id' => $semesterId,
                            'beban_mengajar_id' => $slot['beban_mengajar_id'],
                            'hari' => ucfirst(strtolower(trim($hari))),
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
                'total_target' => $target,
                'slot_kosong' => $kosong,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Jadwal save: ' . $e->getMessage());
            throw new \Exception('Gagal menyimpan jadwal: ' . $e->getMessage());
        }
    }
}
