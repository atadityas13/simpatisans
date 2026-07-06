<?php

namespace App\Services;

use App\Models\BebanMengajar;
use App\Models\GuruConstraint;
use App\Models\Jadwal;
use App\Models\Kelas;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Fill-first scheduling: isi semua slot dengan constraint fisik + BTQ Jumat jam 5.
 * Preset, struktur JTM, max 7 jam/hari → hanya Laporan Analisa.
 */
class JadwalSAOService
{
    private const BTQ_HARI = 'Jumat';
    private const BTQ_JAM_AKHIR = 5;

    private const MAX_KANDIDAT = 80;

    private array $strukturHari = ['Senin' => 9, 'Selasa' => 10, 'Rabu' => 10, 'Kamis' => 10, 'Jumat' => 5];

    private array $guruOcc = [];
    private array $grid = [];
    private array $units = [];
    private array $unitByBm = [];
    private array $kelasIds = [];
    private array $guruLoad = [];
    /** @var array<int, array<string, array<int, true>>> */
    private array $presetBlokir = [];

    private int $deadline = 0;
    private int $terisiTerbaik = 0;
    private array $gridTerbaik = [];

    public function generate(int $semesterId): array
    {
        @ini_set('memory_limit', '512M');

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

        $this->guruLoad = [];
        foreach ($beban as $b) {
            $this->guruLoad[$b->guru_id] = ($this->guruLoad[$b->guru_id] ?? 0) + (int) $b->jtm;
        }

        $this->loadPresetBlokir();
        $this->units = $this->buatUnits($beban);
        $this->unitByBm = [];
        foreach ($this->units as $u) {
            $this->unitByBm[$u['bmId']] = $u;
        }

        $totalJtm = array_sum(array_column($this->units, 'jtm'));
        $kapasitas = count($this->kelasIds) * 44;
        if ($totalJtm > $kapasitas) {
            throw new \Exception("Kelebihan beban: {$totalJtm} JTM melebihi kapasitas {$kapasitas}.");
        }

        $this->deadline = time() + 100;
        $this->terisiTerbaik = 0;
        $this->gridTerbaik = $this->gridKosong();

        for ($seed = 0; $seed < 30; $seed++) {
            if ($this->waktuHabis()) {
                break;
            }

            $this->mulaiUlang();
            $this->tempatkanBtq();
            $this->tempatkanGreedy($this->urutkanUnits($seed));
            $this->perbaikiSisa();

            $this->simpanTerbaik();

            if ($this->terisiTerbaik >= $totalJtm) {
                break;
            }
        }

        // Satu putaran perbaikan ekstra pada grid terbaik
        $this->grid = $this->salinGrid($this->gridTerbaik);
        $this->rebuildOcc();
        $this->perbaikiSisa();
        $this->simpanTerbaik();

        if ($this->terisiTerbaik === 0) {
            throw new \Exception('Gagal membuat jadwal. Periksa beban mengajar.');
        }

        $kosong = $totalJtm - $this->terisiTerbaik;

        return $this->simpan($semesterId, $this->gridTerbaik, $this->terisiTerbaik, $totalJtm, $kosong);
    }

    // ─── Fase penempatan ──────────────────────────────────────────────────

    private function tempatkanBtq(): void
    {
        foreach ($this->units as $unit) {
            if (empty($unit['btq'])) {
                continue;
            }
            $sisa = $this->sisaJam($unit);
            if ($sisa <= 0) {
                continue;
            }
            foreach ($this->kandidatBtq($unit, $sisa) as $blok) {
                if ($this->bisaTaruh($unit, $blok)) {
                    $this->taruh($unit, $blok);
                    break;
                }
            }
        }
    }

    private function tempatkanGreedy(array $urutan): void
    {
        foreach ($urutan as $unit) {
            if (!empty($unit['btq'])) {
                continue;
            }
            while ($this->sisaJam($unit) > 0 && !$this->waktuHabis()) {
                if (!$this->tempatkanLangkah($unit)) {
                    break;
                }
            }
        }
    }

    private function perbaikiSisa(): void
    {
        for ($round = 0; $round < 3000; $round++) {
            if ($this->waktuHabis()) {
                break;
            }

            $pending = array_values(array_filter($this->units, fn($u) => $this->sisaJam($u) > 0));
            if (empty($pending)) {
                break;
            }

            usort($pending, fn($a, $b) => $this->sisaJam($b) <=> $this->sisaJam($a));

            $progress = false;
            foreach ($pending as $unit) {
                $snapGrid = $this->salinGrid($this->grid);
                $terisiAwal = $this->hitungTerisi();

                $ok = $this->tempatkanLangkah($unit) || $this->tempatkanDenganEviksi($unit);

                if ($ok && $this->hitungTerisi() > $terisiAwal) {
                    $progress = true;
                    break;
                }

                $this->grid = $snapGrid;
                $this->rebuildOcc();
            }

            if (!$progress) {
                break;
            }
        }
    }

    private function tempatkanLangkah(array $unit): bool
    {
        if ($this->sisaJam($unit) <= 0) {
            return true;
        }

        foreach ($this->kandidatPenempatan($unit) as $blok) {
            if ($this->bisaTaruh($unit, $blok)) {
                $this->taruh($unit, $blok);
                return true;
            }
        }

        return false;
    }

    private function tempatkanDenganEviksi(array $unit): bool
    {
        if ($this->sisaJam($unit) <= 0) {
            return true;
        }

        foreach ($this->kandidatPenempatan($unit) as $blok) {
            $evicted = $this->kumpulkanPenghalang($unit, $blok);
            if (empty($evicted)) {
                continue;
            }

            $snap = $this->snapshotEvicted($evicted);
            foreach ($evicted as $e) {
                $this->hapusJam($e, $blok);
            }

            if ($this->bisaTaruh($unit, $blok)) {
                $this->taruh($unit, $blok);
                $this->pulihkanEvicted($snap);
                return true;
            }

            $this->restoreSnapshot($snap);
        }

        return false;
    }

    // ─── Kandidat penempatan ──────────────────────────────────────────────

    /** @return list<list<array{hari:string,start:int,size:int}>> */
    private function kandidatPenempatan(array $unit): array
    {
        $sisa = $this->sisaJam($unit);
        if ($sisa <= 0) {
            return [];
        }

        if (!empty($unit['btq'])) {
            return $this->kandidatBtq($unit, $sisa);
        }

        $hasil = [];
        $hariTerpakai = $this->hariUnit($unit);

        foreach ($this->polaJtm($sisa) as $potongan) {
            $this->kumpulBlok($unit, $potongan, 0, [], $hariTerpakai, $hasil);
            if (count($hasil) >= self::MAX_KANDIDAT) {
                break;
            }
        }

        foreach ($this->kandidatJamTunggal($unit) as $blok) {
            $hasil[] = $blok;
        }

        usort($hasil, fn($a, $b) => $this->skorBlok($unit, $a) <=> $this->skorBlok($unit, $b));

        return $hasil;
    }

    private function kumpulBlok(array $unit, array $potongan, int $idx, array $pilih, array $hariTerpakai, array &$hasil): void
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

        foreach ($this->strukturHari as $hari => $max) {
            if (in_array($hari, $exclude, true)) {
                continue;
            }
            for ($start = 1; $start <= $max - $ukuran + 1; $start++) {
                $segmen = array_merge($pilih, [['hari' => $hari, 'start' => $start, 'size' => $ukuran]]);
                if (!$this->bisaTaruh($unit, $segmen)) {
                    continue;
                }
                $this->kumpulBlok($unit, $potongan, $idx + 1, $segmen, $hariTerpakai, $hasil);
            }
        }
    }

    /** @return list<list<array{hari:string,start:int,size:int}>> */
    private function kandidatBtq(array $unit, int $sisa): array
    {
        $start = self::BTQ_JAM_AKHIR - $sisa + 1;
        if ($start < 1) {
            return [];
        }
        $blok = [['hari' => self::BTQ_HARI, 'start' => $start, 'size' => $sisa]];
        return [$blok];
    }

    /** @return list<list<array{hari:string,start:int,size:int}>> */
    private function kandidatJamTunggal(array $unit): array
    {
        $scored = [];
        $gid = $unit['guruId'];

        foreach ($this->strukturHari as $hari => $max) {
            $bebanHari = $this->bebanGuruHari($gid, $hari);
            for ($j = 1; $j <= $max; $j++) {
                $blok = [['hari' => $hari, 'start' => $j, 'size' => 1]];
                if ($this->bisaTaruh($unit, $blok)) {
                    $scored[] = ['blok' => $blok, 'skor' => $this->skorBlok($unit, $blok)];
                }
            }
        }

        usort($scored, fn($a, $b) => $a['skor'] <=> $b['skor']);

        return array_map(fn($s) => $s['blok'], $scored);
    }

    private function skorBlok(array $unit, array $blok): int
    {
        $skor = 0;
        $gid = $unit['guruId'];
        foreach ($blok as $b) {
            $skor += $this->bebanGuruHari($gid, $b['hari']) * 10;
            $skor -= $b['size'] * 8;
            for ($j = $b['start']; $j < $b['start'] + $b['size']; $j++) {
                if ($this->isPresetBlokir($gid, $b['hari'], $j)) {
                    $skor += 50;
                }
            }
        }
        return $skor;
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

    private function loadPresetBlokir(): void
    {
        $this->presetBlokir = [];
        foreach (GuruConstraint::where('type', 0)->get() as $c) {
            $h = ucfirst(strtolower(trim($c->hari)));
            $this->presetBlokir[$c->guru_id][$h][$c->jam_ke] = true;
        }
    }

    private function isPresetBlokir(int $guruId, string $hari, int $jam): bool
    {
        return isset($this->presetBlokir[$guruId][$hari][$jam]);
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

    private function taruh(array $unit, array $blok): void
    {
        foreach ($blok as $b) {
            for ($j = $b['start']; $j < $b['start'] + $b['size']; $j++) {
                $this->grid[$b['hari']][$j][$unit['kelasId']] = $unit['tpl'];
                $this->guruOcc[$b['hari']][$j][$unit['guruId']] = true;
            }
        }
    }

    private function hapusJam(array $unit, array $blok): void
    {
        foreach ($blok as $b) {
            for ($j = $b['start']; $j < $b['start'] + $b['size']; $j++) {
                $s = $this->grid[$b['hari']][$j][$unit['kelasId']] ?? null;
                if ($s !== null && ($s['beban_mengajar_id'] ?? null) == $unit['bmId']) {
                    $this->grid[$b['hari']][$j][$unit['kelasId']] = null;
                    unset($this->guruOcc[$b['hari']][$j][$unit['guruId']]);
                }
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

    /** @return array<int, list<array{hari:string,jam:int}>> */
    private function snapshotEvicted(array $evicted): array
    {
        $snap = [];
        foreach ($evicted as $e) {
            $slots = [];
            $kid = $e['kelasId'];
            $bm = $e['bmId'];
            foreach ($this->strukturHari as $hari => $max) {
                for ($j = 1; $j <= $max; $j++) {
                    $s = $this->grid[$hari][$j][$kid] ?? null;
                    if ($s !== null && ($s['beban_mengajar_id'] ?? null) == $bm) {
                        $slots[] = ['hari' => $hari, 'jam' => $j];
                    }
                }
            }
            $snap[$bm] = $slots;
        }
        return $snap;
    }

    private function restoreSnapshot(array $snap): void
    {
        foreach ($snap as $bmId => $slots) {
            if (!isset($this->unitByBm[$bmId])) {
                continue;
            }
            $this->hapusUnit($this->unitByBm[$bmId]);
            $unit = $this->unitByBm[$bmId];
            foreach ($slots as $s) {
                $this->grid[$s['hari']][$s['jam']][$unit['kelasId']] = $unit['tpl'];
                $this->guruOcc[$s['hari']][$s['jam']][$unit['guruId']] = true;
            }
        }
    }

    private function pulihkanEvicted(array $snap): void
    {
        foreach ($snap as $bmId => $slots) {
            if (!isset($this->unitByBm[$bmId])) {
                continue;
            }
            $unit = $this->unitByBm[$bmId];
            $placed = $this->hitungJamUnit($unit);
            $target = count($slots);
            if ($placed >= $target) {
                continue;
            }
            foreach ($this->kandidatPenempatan($unit) as $blok) {
                $this->taruh($unit, $blok);
                if ($this->hitungJamUnit($unit) >= $target) {
                    break;
                }
            }
        }
    }

    // ─── Urutan & state ───────────────────────────────────────────────────

    private function urutkanUnits(int $seed): array
    {
        $list = $this->units;
        usort($list, function ($a, $b) {
            if (($a['btq'] ?? false) !== ($b['btq'] ?? false)) {
                return ($b['btq'] ?? false) <=> ($a['btq'] ?? false);
            }
            $la = $this->guruLoad[$a['guruId']] ?? 0;
            $lb = $this->guruLoad[$b['guruId']] ?? 0;
            if ($la !== $lb) {
                return $lb <=> $la;
            }
            return $b['jtm'] <=> $a['jtm'];
        });

        if ($seed > 0) {
            $n = count($list);
            $rot = $seed % $n;
            if ($rot > 0) {
                $list = array_merge(array_slice($list, $rot), array_slice($list, 0, $rot));
            }
            if ($seed % 3 === 1) {
                shuffle($list);
            }
        }

        return $list;
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

    private function mulaiUlang(): void
    {
        $this->grid = $this->gridKosong();
        $this->guruOcc = [];
    }

    private function simpanTerbaik(): void
    {
        $t = $this->hitungTerisi();
        if ($t > $this->terisiTerbaik) {
            $this->terisiTerbaik = $t;
            $this->gridTerbaik = $this->salinGrid($this->grid);
        }
    }

    private function salinGrid(array $grid): array
    {
        return json_decode(json_encode($grid), true);
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
        $units = [];
        foreach ($bebanMengajar as $b) {
            $nama = $b->mapel->nama_mapel ?? '';
            $units[] = [
                'bmId' => $b->id,
                'guruId' => $b->guru_id,
                'kelasId' => $b->kelas_id,
                'jtm' => (int) $b->jtm,
                'btq' => $this->isBtq($nama),
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
