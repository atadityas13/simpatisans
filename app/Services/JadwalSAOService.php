<?php

namespace App\Services;

use App\Models\BebanMengajar;
use App\Models\GuruConstraint;
use App\Models\Jadwal;
use App\Models\Kelas;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Generator jadwal sederhana & cepat.
 * Greedy + beberapa percobaan ulang. Tanpa backtracking dalam.
 */
class JadwalSAOService
{
    private const BTQ_HARI = 'Jumat';
    private const BTQ_JAM_AKHIR = 5;
    private const MAX_JAM_GURU_HARI = 7;
    private const MAX_COMBO = 60;

    private array $strukturHari = ['Senin' => 9, 'Selasa' => 10, 'Rabu' => 10, 'Kamis' => 10, 'Jumat' => 5];

    /** @var array<int, array<string, array<int, int>>> guru_id -> hari -> jam_ke -> type */
    private array $preset = [];

    /** @var array<string, array<int, array<int, true>>> hari -> jam -> guru_id */
    private array $guruOcc = [];

    private bool $hormatiBlokir = true;

    public function generate(int $semesterId): array
    {
        @ini_set('memory_limit', '256M');

        $beban = BebanMengajar::where('semester_id', $semesterId)
            ->where('is_satminkal', 1)
            ->with(['guru', 'mapel', 'kelas'])
            ->get();

        if ($beban->isEmpty()) {
            throw new \Exception('Data Beban Mengajar (KBM) kosong. Distribusikan jam terlebih dahulu.');
        }

        $kelasIds = Kelas::orderByRaw("FIELD(tingkat, 'VII', 'VIII', 'IX')")->pluck('id')->toArray();
        if (empty($kelasIds)) {
            throw new \Exception('Data Kelas kosong.');
        }

        $this->loadPreset();
        $units = $this->buatUnits($beban);
        $totalJtm = array_sum(array_column($units, 'jtm'));

        if ($totalJtm > count($kelasIds) * 44) {
            throw new \Exception("Kelebihan beban: {$totalJtm} JTM melebihi kapasitas grid.");
        }

        $deadline = time() + 50;
        $terbaik = null;
        $terisiTerbaik = 0;

        for ($attempt = 0; $attempt < 16; $attempt++) {
            if (time() >= $deadline) {
                break;
            }

            $this->hormatiBlokir = ($attempt < 8);
            $grid = $this->gridKosong($kelasIds);
            $this->guruOcc = [];

            $urutan = $this->urutkanUnits($units, $attempt);

            foreach ($urutan as $unit) {
                $this->tempatkan($grid, $unit, $kelasIds);
            }

            foreach ($urutan as $unit) {
                if (!$this->lengkap($grid, $unit)) {
                    $this->tempatkan($grid, $unit, $kelasIds, true);
                }
            }

            $terisi = $this->hitungTerisi($grid);
            if ($terisi > $terisiTerbaik) {
                $terisiTerbaik = $terisi;
                $terbaik = $grid;
            }
            if ($terisiTerbaik >= $totalJtm) {
                break;
            }
        }

        if ($terbaik === null || $terisiTerbaik === 0) {
            throw new \Exception('Gagal membuat jadwal. Periksa beban mengajar.');
        }

        $kosong = $totalJtm - $terisiTerbaik;

        return $this->simpan($semesterId, $terbaik, $terisiTerbaik, $totalJtm, $kosong);
    }

    // ─── Penempatan ───────────────────────────────────────────────────────

    private function tempatkan(array &$grid, array $unit, array $kelasIds, bool $paksa = false): bool
    {
        if ($this->lengkap($grid, $unit)) {
            return true;
        }

        $sisa = $this->sisaJam($grid, $unit);
        if ($sisa > 0 && $sisa < $unit['jtm'] && !$this->prefixValid($grid, $unit)) {
            $this->hapusUnit($grid, $unit);
            $sisa = $unit['jtm'];
        }

        if ($sisa <= 0) {
            return $this->lengkap($grid, $unit);
        }

        $wasHormat = $this->hormatiBlokir;
        if ($paksa) {
            $this->hormatiBlokir = false;
        }

        foreach ($this->kandidatPenempatan($grid, $unit, $sisa) as $blok) {
            if (!$this->bebanGuruOk($grid, $unit['guruId'], $blok)) {
                continue;
            }
            if (!$this->bisaTaruh($grid, $unit, $blok)) {
                continue;
            }
            $this->taruh($grid, $unit, $blok);
            if ($this->lengkap($grid, $unit)) {
                $this->hormatiBlokir = $wasHormat;
                return true;
            }
            $this->batalTaruh($grid, $unit, $blok);
        }

        $this->hormatiBlokir = $wasHormat;
        return $this->lengkap($grid, $unit);
    }

    /** @return list<list<array{hari:string,start:int,size:int}>> */
    private function kandidatPenempatan(array $grid, array $unit, int $sisa): array
    {
        if (!empty($unit['btq'])) {
            return $this->kandidatBtq($grid, $unit, $sisa);
        }

        $hariTerpakai = $this->hariUnit($grid, $unit);
        $hasil = [];

        foreach ($this->polaJtm($sisa) as $potongan) {
            $this->kumpulKandidat($grid, $unit, $potongan, 0, [], $hariTerpakai, $hasil);
            if (count($hasil) >= self::MAX_COMBO) {
                break;
            }
        }

        return $hasil;
    }

    private function kandidatBtq(array $grid, array $unit, int $sisa): array
    {
        $start = self::BTQ_JAM_AKHIR - $sisa + 1;
        if ($start < 1) {
            return [];
        }
        $blok = [['hari' => self::BTQ_HARI, 'start' => $start, 'size' => $sisa]];
        if ($this->bisaTaruh($grid, $unit, $blok)) {
            return [$blok];
        }
        return [];
    }

    private function kumpulKandidat(
        array $grid,
        array $unit,
        array $potongan,
        int $idx,
        array $pilih,
        array $hariTerpakai,
        array &$hasil
    ): void {
        if (count($hasil) >= self::MAX_COMBO) {
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
                if (!$this->bisaTaruh($grid, $unit, $segmen)) {
                    continue;
                }
                $this->kumpulKandidat($grid, $unit, $potongan, $idx + 1, $segmen, $hariTerpakai, $hasil);
            }
        }
    }

    private function bisaTaruh(array $grid, array $unit, array $blok): bool
    {
        $kid = $unit['kelasId'];
        $gid = $unit['guruId'];

        foreach ($blok as $b) {
            for ($j = $b['start']; $j < $b['start'] + $b['size']; $j++) {
                if (($grid[$b['hari']][$j][$kid] ?? null) !== null) {
                    return false;
                }
                if ($this->hormatiBlokir && $this->presetBlokir($gid, $b['hari'], $j)) {
                    return false;
                }
                if (isset($this->guruOcc[$b['hari']][$j][$gid])) {
                    return false;
                }
            }
        }
        return true;
    }

    private function bebanGuruOk(array $grid, int $guruId, array $blok): bool
    {
        $sim = [];
        foreach ($blok as $b) {
            $load = $this->bebanGuruHari($grid, $guruId, $b['hari']) + ($sim[$b['hari']] ?? 0);
            if ($load + $b['size'] > self::MAX_JAM_GURU_HARI) {
                return false;
            }
            $sim[$b['hari']] = ($sim[$b['hari']] ?? 0) + $b['size'];
        }
        return true;
    }

    private function taruh(array &$grid, array $unit, array $blok): void
    {
        $tpl = $unit['tpl'];
        foreach ($blok as $b) {
            for ($j = $b['start']; $j < $b['start'] + $b['size']; $j++) {
                $grid[$b['hari']][$j][$unit['kelasId']] = $tpl;
                $this->guruOcc[$b['hari']][$j][$unit['guruId']] = true;
            }
        }
    }

    private function batalTaruh(array &$grid, array $unit, array $blok): void
    {
        foreach ($blok as $b) {
            for ($j = $b['start']; $j < $b['start'] + $b['size']; $j++) {
                $grid[$b['hari']][$j][$unit['kelasId']] = null;
                unset($this->guruOcc[$b['hari']][$j][$unit['guruId']]);
            }
        }
    }

    private function hapusUnit(array &$grid, array $unit): void
    {
        $kid = $unit['kelasId'];
        $bm = $unit['bmId'];
        foreach ($this->strukturHari as $hari => $max) {
            for ($j = 1; $j <= $max; $j++) {
                $s = $grid[$hari][$j][$kid] ?? null;
                if ($s !== null && ($s['beban_mengajar_id'] ?? null) == $bm) {
                    $grid[$hari][$j][$kid] = null;
                    unset($this->guruOcc[$hari][$j][$unit['guruId']]);
                }
            }
        }
    }

    // ─── Validasi & hitung ────────────────────────────────────────────────

    private function lengkap(array $grid, array $unit): bool
    {
        return $this->sisaJam($grid, $unit) === 0 && $this->strukturOk($grid, $unit);
    }

    private function sisaJam(array $grid, array $unit): int
    {
        return $unit['jtm'] - $this->hitungJamUnit($grid, $unit);
    }

    private function hitungJamUnit(array $grid, array $unit): int
    {
        $n = 0;
        $kid = $unit['kelasId'];
        $bm = $unit['bmId'];
        foreach ($this->strukturHari as $hari => $max) {
            for ($j = 1; $j <= $max; $j++) {
                $s = $grid[$hari][$j][$kid] ?? null;
                if ($s !== null && ($s['beban_mengajar_id'] ?? null) == $bm) {
                    $n++;
                }
            }
        }
        return $n;
    }

    private function strukturOk(array $grid, array $unit): bool
    {
        $kid = $unit['kelasId'];
        $bm = $unit['bmId'];
        $jtm = $unit['jtm'];
        $perHari = [];

        foreach ($this->strukturHari as $hari => $max) {
            $jams = [];
            for ($j = 1; $j <= $max; $j++) {
                $s = $grid[$hari][$j][$kid] ?? null;
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
            return $this->btqOk($grid, $unit);
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

    private function btqOk(array $grid, array $unit): bool
    {
        $kid = $unit['kelasId'];
        $bm = $unit['bmId'];
        $jams = [];
        for ($j = 1; $j <= self::BTQ_JAM_AKHIR; $j++) {
            $s = $grid[self::BTQ_HARI][$j][$kid] ?? null;
            if ($s !== null && ($s['beban_mengajar_id'] ?? null) == $bm) {
                $jams[] = $j;
            }
        }
        if (count($jams) !== $unit['jtm']) {
            return false;
        }
        sort($jams);
        return max($jams) === self::BTQ_JAM_AKHIR;
    }

    private function prefixValid(array $grid, array $unit): bool
    {
        $sisa = $this->sisaJam($grid, $unit);
        if ($sisa <= 0) {
            return $this->strukturOk($grid, $unit);
        }
        $dist = $this->distribusiUnit($grid, $unit);
        foreach ($this->polaJtm($unit['jtm']) as $pola) {
            $target = $pola;
            rsort($target);
            foreach ($this->polaJtm($sisa) as $sisaPola) {
                $c = array_merge($dist, $sisaPola);
                rsort($c);
                if ($c === $target) {
                    return true;
                }
            }
        }
        return false;
    }

    private function distribusiUnit(array $grid, array $unit): array
    {
        $d = [];
        foreach ($this->hariUnit($grid, $unit) as $hari) {
            $n = 0;
            for ($j = 1; $j <= $this->strukturHari[$hari]; $j++) {
                $s = $grid[$hari][$j][$unit['kelasId']] ?? null;
                if ($s !== null && ($s['beban_mengajar_id'] ?? null) == $unit['bmId']) {
                    $n++;
                }
            }
            if ($n > 0) {
                $d[] = $n;
            }
        }
        rsort($d);
        return $d;
    }

    private function hariUnit(array $grid, array $unit): array
    {
        $days = [];
        foreach (array_keys($this->strukturHari) as $hari) {
            for ($j = 1; $j <= $this->strukturHari[$hari]; $j++) {
                $s = $grid[$hari][$j][$unit['kelasId']] ?? null;
                if ($s !== null && ($s['beban_mengajar_id'] ?? null) == $unit['bmId']) {
                    $days[$hari] = true;
                    break;
                }
            }
        }
        return array_keys($days);
    }

    private function bebanGuruHari(array $grid, int $guruId, string $hari): int
    {
        $n = 0;
        foreach ($grid[$hari] ?? [] as $jam => $kelas) {
            foreach ($kelas as $slot) {
                if ($slot !== null && ($slot['guru_id'] ?? null) == $guruId) {
                    $n++;
                }
            }
        }
        return $n;
    }

    private function hitungTerisi(array $grid): int
    {
        $n = 0;
        foreach ($grid as $jamData) {
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

    // ─── Data & util ──────────────────────────────────────────────────────

    private function buatUnits($bebanMengajar): array
    {
        $units = [];
        foreach ($bebanMengajar as $b) {
            $nama = $b->mapel->nama_mapel ?? '';
            $units[] = [
                'bmId' => $b->id,
                'guruId' => $b->guru_id,
                'kelasId' => $b->kelas_id,
                'jtm' => (int) $b->jtm,
                'btq' => $this->isBtq($nama),
                'blokir' => count($this->preset[$b->guru_id] ?? []),
                'tpl' => [
                    'beban_mengajar_id' => $b->id,
                    'guru_id' => $b->guru_id,
                    'mapel_id' => $b->mapel_id,
                    'kelas_id' => $b->kelas_id,
                ],
            ];
        }
        return $units;
    }

    private function urutkanUnits(array $units, int $seed): array
    {
        $u = $units;
        usort($u, function ($a, $b) {
            if ($a['btq'] !== $b['btq']) {
                return $b['btq'] <=> $a['btq'];
            }
            if ($a['blokir'] !== $b['blokir']) {
                return $b['blokir'] <=> $a['blokir'];
            }
            return $b['jtm'] <=> $a['jtm'];
        });
        if ($seed > 0) {
            mt_srand($seed * 9973);
            $n = count($u);
            $rot = $seed % $n;
            if ($rot > 0) {
                $u = array_merge(array_slice($u, $rot), array_slice($u, 0, $rot));
            }
        }
        return $u;
    }

    /** @return list<list<int>> */
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

    private function gridKosong(array $kelasIds): array
    {
        $g = [];
        foreach ($this->strukturHari as $hari => $max) {
            for ($j = 1; $j <= $max; $j++) {
                foreach ($kelasIds as $k) {
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

    private function loadPreset(): void
    {
        $this->preset = [];
        foreach (GuruConstraint::all() as $c) {
            $h = ucfirst(strtolower(trim($c->hari)));
            $this->preset[$c->guru_id][$h][$c->jam_ke] = (int) $c->type;
        }
    }

    private function presetBlokir(int $guruId, string $hari, int $jam): bool
    {
        return isset($this->preset[$guruId][$hari][$jam])
            && $this->preset[$guruId][$hari][$jam] === 0;
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
