<?php

namespace App\Services;

use App\Models\BebanMengajar;
use App\Models\Kelas;
use App\Models\Jadwal;
use App\Models\GuruConstraint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class JadwalSAOService
{
    private $strukturHari = ['Senin' => 9, 'Selasa' => 10, 'Rabu' => 10, 'Kamis' => 10, 'Jumat' => 5];
    private $fastConstraints = [];
    private $lockedSlots = [];
    private $bebanMeta = [];

    public function generate(int $semesterId)
    {
        $bebanMengajar = BebanMengajar::where('semester_id', $semesterId)
            ->where('is_satminkal', 1)
            ->with(['guru', 'mapel', 'kelas'])
            ->get();

        if ($bebanMengajar->isEmpty()) {
            throw new \Exception("Data Beban Mengajar (KBM) kosong untuk semester ini. Silakan distribusikan jam terlebih dahulu.");
        }

        $kelasList = Kelas::orderByRaw("FIELD(tingkat, 'VII', 'VIII', 'IX')")->get();
        $kelasIds = $kelasList->pluck('id')->toArray();
        if (empty($kelasIds)) {
            throw new \Exception("Data Kelas kosong.");
        }

        $this->loadConstraints();
        $this->bebanMeta = [];
        foreach ($bebanMengajar as $beban) {
            $this->bebanMeta[$beban->id] = [
                'jtm' => $beban->jtm,
                'guru_id' => $beban->guru_id,
                'kelas_id' => $beban->kelas_id,
                'mapel_id' => $beban->mapel_id,
            ];
        }

        $totalSlotTersedia = count($kelasIds) * 44;
        $totalJtm = $bebanMengajar->sum('jtm');
        if ($totalJtm > $totalSlotTersedia) {
            throw new \Exception("Kelebihan Beban: {$totalJtm} JTM vs {$totalSlotTersedia} Kapasitas Slot Tersedia.");
        }

        $bebanMap = $bebanMengajar->pluck('jtm', 'id')->toArray();
        $units = $this->buildUnits($bebanMengajar);

        $waktuMulai = time();
        $batasWaktu = 250;
        $solusiTerbaik = null;
        $biayaTerbaik = PHP_INT_MAX;
        $maxRestart = 15;

        for ($r = 0; $r < $maxRestart; $r++) {
            if ((time() - $waktuMulai) >= $batasWaktu) break;

            $this->lockedSlots = [];
            $jadwal = $this->buatJadwalKosong($kelasIds);
            $orderedUnits = $this->urutkanUnits($units, $r);

            if ($this->tempatkanPreserve($jadwal, $orderedUnits, $kelasIds)) {
                $orderedUnits = array_values(array_filter($orderedUnits, fn($u) => $u['remaining'] > 0));
            }

            if ($this->backtrackTempatkan($jadwal, $orderedUnits, 0, $kelasIds, $waktuMulai, $batasWaktu)) {
                $solusiTerbaik = $jadwal;
                $biayaTerbaik = 0;
                break;
            }

            $biaya = $this->hitungHardPenalti($jadwal, $kelasIds, $bebanMap);
            if ($biaya < $biayaTerbaik) {
                $solusiTerbaik = $jadwal;
                $biayaTerbaik = $biaya;
            }
        }

        if ($solusiTerbaik === null) {
            throw new \Exception("Gagal membuat jadwal. Coba kurangi preset blokir atau periksa beban mengajar.");
        }

        if ($biayaTerbaik > 0) {
            $solusiTerbaik = $this->perbaikiAgresif($solusiTerbaik, $kelasIds, $bebanMap, $waktuMulai, $batasWaktu);
            $biayaTerbaik = $this->hitungHardPenalti($solusiTerbaik, $kelasIds, $bebanMap);
        }

        if ($biayaTerbaik > 0) {
            $solusiTerbaik = $this->optimasiKelelahan($solusiTerbaik, $kelasIds, $bebanMap, $waktuMulai, $batasWaktu);
            $biayaTerbaik = $this->hitungHardPenalti($solusiTerbaik, $kelasIds, $bebanMap);
        }

        if ($biayaTerbaik > 0) {
            throw new \Exception(
                "Generate selesai tapi masih ada {$this->ringkasPelanggaran($solusiTerbaik, $kelasIds, $bebanMap)}. " .
                "Kurangi preset blokir atau sesuaikan beban mengajar lalu coba lagi."
            );
        }

        DB::table('jadwals')->where('semester_id', $semesterId)->delete();

        DB::beginTransaction();
        try {
            $insertData = [];
            $now = now();
            foreach ($solusiTerbaik as $hari => $jamKeData) {
                foreach ($jamKeData as $jam => $kelasData) {
                    foreach ($kelasData as $kelasId => $isiSlot) {
                        if ($isiSlot !== null) {
                            $insertData[] = [
                                'semester_id' => $semesterId,
                                'beban_mengajar_id' => $isiSlot['beban_mengajar_id'],
                                'hari' => $this->normalizeHari($hari),
                                'jam_ke' => $jam,
                                'created_at' => $now,
                                'updated_at' => $now,
                            ];
                        }
                    }
                }
            }
            foreach (array_chunk($insertData, 500) as $chunk) {
                Jadwal::insert($chunk);
            }
            DB::commit();
            return ['status' => 'success', 'biaya_penalti' => 0, 'total_slot_terisi' => count($insertData)];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            throw new \Exception("Gagal menyimpan jadwal: " . $e->getMessage());
        }
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

    private function buildUnits($bebanMengajar): array
    {
        $units = [];
        foreach ($bebanMengajar as $beban) {
            $slotTemplate = [
                'beban_mengajar_id' => $beban->id,
                'guru_id' => $beban->guru_id,
                'mapel_id' => $beban->mapel_id,
                'kelas_id' => $beban->kelas_id,
            ];
            $units[] = [
                'bmId' => $beban->id,
                'guruId' => $beban->guru_id,
                'kelasId' => $beban->kelas_id,
                'jtm' => $beban->jtm,
                'remaining' => $beban->jtm,
                'slotTemplate' => $slotTemplate,
                'constraintCount' => count($this->fastConstraints[$beban->guru_id] ?? []),
            ];
        }
        return $units;
    }

    private function urutkanUnits(array $units, int $seed): array
    {
        $copy = $units;
        usort($copy, function ($a, $b) {
            if ($a['jtm'] !== $b['jtm']) return $b['jtm'] <=> $a['jtm'];
            if ($a['constraintCount'] !== $b['constraintCount']) return $b['constraintCount'] <=> $a['constraintCount'];
            return $a['bmId'] <=> $b['bmId'];
        });

        if ($seed > 0) {
            mt_srand($seed * 7919);
            $chunks = array_chunk($copy, max(1, (int) ceil(count($copy) / 4)));
            foreach ($chunks as &$chunk) {
                shuffle($chunk);
            }
            $copy = array_merge(...$chunks);
        }
        return $copy;
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

    private function slotPayload(array $template): array
    {
        return $template;
    }

    private function guruBebasDiBlok(array $jadwal, int $guruId, string $hari, int $startJam, int $size, int $kelasId, array $kelasIds): bool
    {
        $maxJam = $this->strukturHari[$hari];
        if ($startJam + $size - 1 > $maxJam) return false;

        for ($s = 0; $s < $size; $s++) {
            $jam = $startJam + $s;
            if ($this->isBlocked($guruId, $hari, $jam)) return false;
            foreach ($kelasIds as $kId) {
                if ($kId == $kelasId) continue;
                $slot = $jadwal[$hari][$jam][$kId];
                if ($slot !== null && $slot['guru_id'] == $guruId) return false;
            }
        }
        return true;
    }

    private function kelasSlotKosong(array $jadwal, string $hari, int $startJam, int $size, int $kelasId): bool
    {
        $maxJam = $this->strukturHari[$hari];
        if ($startJam + $size - 1 > $maxJam) return false;
        for ($s = 0; $s < $size; $s++) {
            $jam = $startJam + $s;
            if ($jadwal[$hari][$jam][$kelasId] !== null) return false;
            if (isset($this->lockedSlots[$hari][$jam][$kelasId])) return false;
        }
        return true;
    }

    private function cariPosisiBlok(array $jadwal, int $size, int $kelasId, array $kelasIds, int $guruId, array $excludeDays = []): array
    {
        $posisi = [];
        $hariArr = array_keys($this->strukturHari);

        foreach ($hariArr as $hari) {
            if (in_array($hari, $excludeDays, true)) continue;
            $maxJam = $this->strukturHari[$hari];
            for ($startJam = 1; $startJam <= $maxJam - $size + 1; $startJam++) {
                if (!$this->kelasSlotKosong($jadwal, $hari, $startJam, $size, $kelasId)) continue;
                if (!$this->guruBebasDiBlok($jadwal, $guruId, $hari, $startJam, $size, $kelasId, $kelasIds)) continue;
                $posisi[] = ['hari' => $hari, 'startJam' => $startJam];
            }
        }
        return $posisi;
    }

    private function tempatkanBlok(array &$jadwal, array $template, string $hari, int $startJam, int $size): void
    {
        $kelasId = $template['kelas_id'];
        for ($s = 0; $s < $size; $s++) {
            $jadwal[$hari][$startJam + $s][$kelasId] = $this->slotPayload($template);
        }
    }

    private function hapusBlok(array &$jadwal, int $kelasId, string $hari, int $startJam, int $size): void
    {
        for ($s = 0; $s < $size; $s++) {
            $jam = $startJam + $s;
            if (!isset($this->lockedSlots[$hari][$jam][$kelasId])) {
                $jadwal[$hari][$jam][$kelasId] = null;
            }
        }
    }

    private function tempatkanPreserve(array &$jadwal, array &$units, array $kelasIds): bool
    {
        foreach ($units as &$unit) {
            $guruId = $unit['guruId'];
            $kelasId = $unit['kelasId'];
            $bmId = $unit['bmId'];

            foreach ($this->fastConstraints[$guruId] ?? [] as $hari => $jams) {
                foreach ($jams as $jam => $type) {
                    if ($type !== 1) continue;
                    if ($jadwal[$hari][$jam][$kelasId] !== null) continue;
                    if ($unit['remaining'] <= 0) continue;

                    if ($this->guruBebasDiBlok($jadwal, $guruId, $hari, $jam, 1, $kelasId, $kelasIds)) {
                        $jadwal[$hari][$jam][$kelasId] = $this->slotPayload($unit['slotTemplate']);
                        $this->lockedSlots[$hari][$jam][$kelasId] = true;
                        $unit['remaining']--;
                    }
                }
            }
        }
        unset($unit);

        foreach ($units as &$unit) {
            if ($unit['remaining'] <= 0) continue;
            $kelasId = $unit['kelasId'];
            $guruId = $unit['guruId'];
            $bmId = $unit['bmId'];

            $placed = [];
            foreach ($this->strukturHari as $hari => $jml) {
                for ($j = 1; $j <= $jml; $j++) {
                    $slot = $jadwal[$hari][$j][$kelasId];
                    if ($slot !== null && $slot['beban_mengajar_id'] == $bmId) {
                        $placed[] = ['hari' => $hari, 'jam' => $j];
                    }
                }
            }

            foreach ($placed as $p) {
                if ($unit['remaining'] <= 0) break;
                foreach ([1, -1] as $delta) {
                    $nj = $p['jam'] + $delta;
                    if ($nj < 1 || $nj > $this->strukturHari[$p['hari']]) continue;
                    if ($jadwal[$p['hari']][$nj][$kelasId] !== null) continue;
                    if (!$this->guruBebasDiBlok($jadwal, $guruId, $p['hari'], $nj, 1, $kelasId, $kelasIds)) continue;
                    $jadwal[$p['hari']][$nj][$kelasId] = $this->slotPayload($unit['slotTemplate']);
                    $unit['remaining']--;
                }
            }
        }
        unset($unit);

        return true;
    }

    private function getHariTerpakaiBeban(array $jadwal, array $unit): array
    {
        $days = [];
        foreach ($this->strukturHari as $hari => $jml) {
            for ($j = 1; $j <= $jml; $j++) {
                $slot = $jadwal[$hari][$j][$unit['kelasId']];
                if ($slot !== null && $slot['beban_mengajar_id'] == $unit['bmId']) {
                    $days[$hari] = true;
                }
            }
        }
        return array_keys($days);
    }

    private function cariPosisiMelengkapi(array $jadwal, array $unit, array $kelasIds): array
    {
        $posisi = [];
        $kelasId = $unit['kelasId'];
        $bmId = $unit['bmId'];
        $byDay = [];

        foreach ($this->strukturHari as $hari => $jml) {
            for ($j = 1; $j <= $jml; $j++) {
                $slot = $jadwal[$hari][$j][$kelasId];
                if ($slot !== null && $slot['beban_mengajar_id'] == $bmId) {
                    $byDay[$hari][] = $j;
                }
            }
        }

        foreach ($byDay as $hari => $jams) {
            $minJ = min($jams);
            $maxJ = max($jams);
            foreach ([$minJ - 1, $maxJ + 1] as $aj) {
                if ($aj < 1 || $aj > $this->strukturHari[$hari]) continue;
                if ($jadwal[$hari][$aj][$kelasId] !== null) continue;
                if (!$this->guruBebasDiBlok($jadwal, $unit['guruId'], $hari, $aj, 1, $kelasId, $kelasIds)) continue;
                $posisi[] = ['hari' => $hari, 'startJam' => $aj, 'size' => 1];
            }
        }
        return $posisi;
    }

    /**
     * Cari semua kombinasi penempatan blok valid untuk satu unit.
     */
    private function cariKombinasiPenempatan(array $jadwal, array $unit, array $blocks, array $kelasIds, int $limit = 80, array $fixedExcludeDays = []): array
    {
        $hasil = [];
        $this->cariKombinasiRekursif($jadwal, $unit, $blocks, 0, [], $kelasIds, $hasil, $limit, $fixedExcludeDays);
        shuffle($hasil);
        return array_slice($hasil, 0, $limit);
    }

    private function cariKombinasiRekursif(array $jadwal, array $unit, array $blocks, int $idx, array $usedDays, array $kelasIds, array &$hasil, int $limit, array $fixedExcludeDays = []): void
    {
        if (count($hasil) >= $limit) return;

        if ($idx >= count($blocks)) {
            $hasil[] = $usedDays;
            return;
        }

        $size = $blocks[$idx];
        $multiDay = count($blocks) > 1;
        $exclude = $multiDay ? array_unique(array_merge($fixedExcludeDays, array_column($usedDays, 'hari'))) : [];

        $posisi = $this->cariPosisiBlok(
            $jadwal,
            $size,
            $unit['kelasId'],
            $kelasIds,
            $unit['guruId'],
            $exclude
        );

        foreach ($posisi as $pos) {
            $temp = $jadwal;
            $this->tempatkanBlok($temp, $unit['slotTemplate'], $pos['hari'], $pos['startJam'], $size);
            $newUsed = $usedDays;
            $newUsed[] = ['hari' => $pos['hari'], 'startJam' => $pos['startJam'], 'size' => $size];
            $this->cariKombinasiRekursif($temp, $unit, $blocks, $idx + 1, $newUsed, $kelasIds, $hasil, $limit, $fixedExcludeDays);
        }
    }

    private function syncRemainingUnit(array &$jadwal, array &$unit): void
    {
        $placed = $this->hitungSlotBeban($jadwal, $unit['kelasId'], $unit['bmId']);
        $unit['remaining'] = max(0, $unit['jtm'] - $placed);
    }

    private function terapkanKombinasi(array &$jadwal, array &$unit, array $kombinasi): void
    {
        foreach ($kombinasi as $blok) {
            $this->tempatkanBlok($jadwal, $unit['slotTemplate'], $blok['hari'], $blok['startJam'], $blok['size']);
        }
        $this->syncRemainingUnit($jadwal, $unit);
    }

    private function batalkanKombinasi(array &$jadwal, array &$unit, array $kombinasi): void
    {
        foreach ($kombinasi as $blok) {
            $this->hapusBlok($jadwal, $unit['kelasId'], $blok['hari'], $blok['startJam'], $blok['size']);
        }
        $this->syncRemainingUnit($jadwal, $unit);
    }

    private function backtrackTempatkan(array &$jadwal, array &$units, int $index, array $kelasIds, int $waktuMulai, int $batasWaktu): bool
    {
        if ((time() - $waktuMulai) >= $batasWaktu) return false;

        while ($index < count($units) && $units[$index]['remaining'] <= 0) {
            $index++;
        }
        if ($index >= count($units)) {
            return $this->hitungHardPenalti($jadwal, $kelasIds, $this->getBebanMap()) === 0;
        }

        $unit = &$units[$index];
        $this->syncRemainingUnit($jadwal, $unit);
        if ($unit['remaining'] <= 0) {
            return $this->backtrackTempatkan($jadwal, $units, $index + 1, $kelasIds, $waktuMulai, $batasWaktu);
        }

        $existingDays = $this->getHariTerpakaiBeban($jadwal, $unit);

        // JTM 2/3: lengkapi slot bersebelahan jika sudah ada sebagian
        if ($unit['remaining'] === 1 && count($existingDays) === 1) {
            foreach ($this->cariPosisiMelengkapi($jadwal, $unit, $kelasIds) as $pos) {
                $kombinasi = [$pos];
                $this->terapkanKombinasi($jadwal, $unit, $kombinasi);
                if ($this->backtrackTempatkan($jadwal, $units, $index + 1, $kelasIds, $waktuMulai, $batasWaktu)) {
                    return true;
                }
                $this->batalkanKombinasi($jadwal, $unit, $kombinasi);
            }
        }

        $patterns = $this->getBlockPatterns($unit['remaining']);

        foreach ($patterns as $blocks) {
            $kombinasiList = $this->cariKombinasiPenempatan($jadwal, $unit, $blocks, $kelasIds, 80, $existingDays);
            foreach ($kombinasiList as $kombinasi) {
                $this->terapkanKombinasi($jadwal, $unit, $kombinasi);
                if ($this->backtrackTempatkan($jadwal, $units, $index + 1, $kelasIds, $waktuMulai, $batasWaktu)) {
                    return true;
                }
                $this->batalkanKombinasi($jadwal, $unit, $kombinasi);
            }
        }

        return false;
    }

    private function temukanBlokMapel(array $jadwal, int $kelasId, int $bmId): array
    {
        $blok = [];
        foreach ($this->strukturHari as $hari => $jml) {
            $run = [];
            for ($j = 1; $j <= $jml; $j++) {
                $slot = $jadwal[$hari][$j][$kelasId];
                if ($slot !== null && $slot['beban_mengajar_id'] == $bmId) {
                    $run[] = $j;
                } elseif (!empty($run)) {
                    $blok[] = ['hari' => $hari, 'startJam' => $run[0], 'size' => count($run)];
                    $run = [];
                }
            }
            if (!empty($run)) {
                $blok[] = ['hari' => $hari, 'startJam' => $run[0], 'size' => count($run)];
            }
        }
        return $blok;
    }

    private function hapusSemuaSlotBeban(array &$jadwal, int $kelasId, int $bmId): array
    {
        $slots = [];
        foreach ($this->strukturHari as $hari => $jml) {
            for ($j = 1; $j <= $jml; $j++) {
                $slot = $jadwal[$hari][$j][$kelasId];
                if ($slot !== null && $slot['beban_mengajar_id'] == $bmId) {
                    if (!isset($this->lockedSlots[$hari][$j][$kelasId])) {
                        $slots[] = ['hari' => $hari, 'jam' => $j, 'data' => $slot];
                        $jadwal[$hari][$j][$kelasId] = null;
                    }
                }
            }
        }
        return $slots;
    }

    private function perbaikiBebanMapel(array $jadwal, int $kelasId, int $bmId, array $kelasIds): ?array
    {
        $meta = $this->bebanMeta[$bmId] ?? null;
        if (!$meta) return null;

        $unit = [
            'bmId' => $bmId,
            'guruId' => $meta['guru_id'],
            'kelasId' => $meta['kelas_id'],
            'jtm' => $meta['jtm'],
            'remaining' => $meta['jtm'],
            'slotTemplate' => [
                'beban_mengajar_id' => $bmId,
                'guru_id' => $meta['guru_id'],
                'mapel_id' => $meta['mapel_id'],
                'kelas_id' => $meta['kelas_id'],
            ],
        ];

        $temp = $jadwal;
        $this->hapusSemuaSlotBeban($temp, $kelasId, $bmId);

        $unit['remaining'] = $meta['jtm'] - $this->hitungSlotBeban($temp, $kelasId, $bmId);

        if ($unit['remaining'] <= 0) return $temp;

        foreach ($this->getBlockPatterns($unit['remaining']) as $blocks) {
            foreach ($this->cariKombinasiPenempatan($temp, $unit, $blocks, $kelasIds, 120) as $kombinasi) {
                $trial = $temp;
                $u = $unit;
                $this->terapkanKombinasi($trial, $u, $kombinasi);
                if ($this->validasiBeban($trial, $kelasId, $bmId, $meta['jtm'])) {
                    return $trial;
                }
            }
        }
        return null;
    }

    private function hitungSlotBeban(array $jadwal, int $kelasId, int $bmId): int
    {
        $n = 0;
        foreach ($this->strukturHari as $hari => $jml) {
            for ($j = 1; $j <= $jml; $j++) {
                $slot = $jadwal[$hari][$j][$kelasId];
                if ($slot !== null && $slot['beban_mengajar_id'] == $bmId) $n++;
            }
        }
        return $n;
    }

    private function validasiBeban(array $jadwal, int $kelasId, int $bmId, int $jtm): bool
    {
        $days = [];
        foreach ($this->strukturHari as $hari => $jml) {
            $jams = [];
            for ($j = 1; $j <= $jml; $j++) {
                $slot = $jadwal[$hari][$j][$kelasId];
                if ($slot !== null && $slot['beban_mengajar_id'] == $bmId) {
                    $jams[] = $j;
                }
            }
            if (empty($jams)) continue;

            for ($i = 0; $i < count($jams) - 1; $i++) {
                if ($jams[$i + 1] - $jams[$i] > 1) return false;
            }
            $days[] = count($jams);
        }
        rsort($days);

        return match ($jtm) {
            1 => $days === [1],
            2 => $days === [2],
            3 => $days === [3] || $days === [2, 1],
            4 => $days === [2, 2],
            5 => $days === [3, 2] || $days === [2, 2, 1],
            6 => $days === [3, 3] || $days === [2, 2, 2],
            default => true,
        };
    }

    private function deteksiPelanggar(array $jadwal, array $kelasIds, array $bebanMap): array
    {
        $pelanggar = [];
        foreach ($kelasIds as $kId) {
            foreach ($bebanMap as $bmId => $jtm) {
                if (($this->bebanMeta[$bmId]['kelas_id'] ?? null) != $kId) continue;
                if (!$this->validasiBeban($jadwal, $kId, $bmId, $jtm)) {
                    $pelanggar[] = ['kelasId' => $kId, 'bmId' => $bmId, 'jtm' => $jtm];
                }
            }
        }
        return $pelanggar;
    }

    private function perbaikiAgresif(array $jadwal, array $kelasIds, array $bebanMap, int $waktuMulai, int $batasWaktu): array
    {
        $terbaik = $jadwal;
        $biaya = $this->hitungHardPenalti($terbaik, $kelasIds, $bebanMap);

        for ($pass = 0; $pass < 5; $pass++) {
            if ((time() - $waktuMulai) >= $batasWaktu - 3) break;

            $pelanggar = $this->deteksiPelanggar($terbaik, $kelasIds, $bebanMap);
            shuffle($pelanggar);
            foreach ($pelanggar as $p) {
                $kandidat = $this->perbaikiBebanMapel($terbaik, $p['kelasId'], $p['bmId'], $kelasIds);
                if ($kandidat !== null) {
                    $b = $this->hitungHardPenalti($kandidat, $kelasIds, $bebanMap);
                    if ($b <= $biaya) {
                        $terbaik = $kandidat;
                        $biaya = $b;
                    }
                }
                if ($biaya === 0) return $terbaik;
            }

            $bentrok = $this->deteksiBentrok($terbaik, $kelasIds);
            foreach ($bentrok as $b) {
                $kandidat = $this->perbaikiBebanMapel($terbaik, $b['kelasId'], $b['bmId'], $kelasIds);
                if ($kandidat !== null) {
                    $cost = $this->hitungHardPenalti($kandidat, $kelasIds, $bebanMap);
                    if ($cost < $biaya) {
                        $terbaik = $kandidat;
                        $biaya = $cost;
                    }
                }
            }
        }
        return $terbaik;
    }

    private function deteksiBentrok(array $jadwal, array $kelasIds): array
    {
        $out = [];
        foreach ($this->strukturHari as $hari => $jml) {
            for ($jam = 1; $jam <= $jml; $jam++) {
                $seen = [];
                foreach ($kelasIds as $kId) {
                    $slot = $jadwal[$hari][$jam][$kId];
                    if ($slot === null) continue;
                    $gid = $slot['guru_id'];
                    if (isset($seen[$gid])) {
                        $out[] = ['kelasId' => $kId, 'bmId' => $slot['beban_mengajar_id']];
                    }
                    $seen[$gid] = true;
                }
            }
        }
        return $out;
    }

    private function optimasiKelelahan(array $jadwal, array $kelasIds, array $bebanMap, int $waktuMulai, int $batasWaktu): array
    {
        $terbaik = $jadwal;
        $biaya = $this->hitungHardPenalti($terbaik, $kelasIds, $bebanMap);
        if ($biaya === 0) return $terbaik;

        for ($i = 0; $i < 3000; $i++) {
            if ((time() - $waktuMulai) >= $batasWaktu - 2) break;
            $kandidat = $this->cobaRebalanceSatu($terbaik, $kelasIds);
            if ($kandidat === null) continue;
            $b = $this->hitungHardPenalti($kandidat, $kelasIds, $bebanMap);
            if ($b < $biaya) {
                $terbaik = $kandidat;
                $biaya = $b;
                if ($biaya === 0) return $terbaik;
            }
        }
        return $terbaik;
    }

    private function cobaRebalanceSatu(array $jadwal, array $kelasIds): ?array
    {
        $load = [];
        foreach ($this->strukturHari as $hari => $jml) {
            for ($jam = 1; $jam <= $jml; $jam++) {
                foreach ($kelasIds as $kId) {
                    $slot = $jadwal[$hari][$jam][$kId];
                    if ($slot !== null) {
                        $load[$slot['guru_id']][$hari] = ($load[$slot['guru_id']][$hari] ?? 0) + 1;
                    }
                }
            }
        }

        $over = [];
        foreach ($load as $gid => $days) {
            foreach ($days as $hari => $cnt) {
                if ($cnt >= 8) $over[] = ['guruId' => $gid, 'hari' => $hari];
            }
        }
        if (empty($over)) return null;

        $pick = $over[array_rand($over)];
        foreach ($kelasIds as $kId) {
            $bmIds = [];
            foreach ($this->strukturHari as $hari => $jml) {
                if ($hari !== $pick['hari']) continue;
                for ($j = 1; $j <= $jml; $j++) {
                    $slot = $jadwal[$hari][$j][$kId];
                    if ($slot !== null && $slot['guru_id'] == $pick['guruId']) {
                        $bmIds[$slot['beban_mengajar_id']] = true;
                    }
                }
            }
            foreach (array_keys($bmIds) as $bmId) {
                $kandidat = $this->perbaikiBebanMapel($jadwal, $kId, $bmId, $kelasIds);
                if ($kandidat !== null) return $kandidat;
            }
        }
        return null;
    }

    private function ringkasPelanggaran(array $jadwal, array $kelasIds, array $bebanMap): string
    {
        $p = $this->hitungHardPenalti($jadwal, $kelasIds, $bebanMap);
        $jtm = count($this->deteksiPelanggar($jadwal, $kelasIds, $bebanMap));
        $bentrok = count($this->deteksiBentrok($jadwal, $kelasIds));
        return "penalti {$p} (JTM: {$jtm}, bentrok: {$bentrok})";
    }

    private function getBebanMap(): array
    {
        $map = [];
        foreach ($this->bebanMeta as $id => $meta) {
            $map[$id] = $meta['jtm'];
        }
        return $map;
    }

    private function hitungHardPenalti(array $jadwal, array $kelasIds, array $bebanMap): int
    {
        $penalti = 0;
        $guruDailyLoad = [];

        foreach ($this->strukturHari as $hari => $jmlJam) {
            for ($jam = 1; $jam <= $jmlJam; $jam++) {
                $guruHadirJamIni = [];
                foreach ($kelasIds as $kId) {
                    $slot = $jadwal[$hari][$jam][$kId];
                    if ($slot === null) continue;

                    $guruId = $slot['guru_id'];
                    if ($hari === 'Jumat' && $jam > 5) $penalti += 2000000;

                    $guruDailyLoad[$guruId][$hari] = ($guruDailyLoad[$guruId][$hari] ?? 0) + 1;

                    if (isset($guruHadirJamIni[$guruId])) $penalti += 1000000;
                    else $guruHadirJamIni[$guruId] = true;

                    if ($this->isBlocked($guruId, $hari, $jam)) $penalti += 1000000;
                }
            }
        }

        foreach ($kelasIds as $kId) {
            foreach ($bebanMap as $bmId => $jtm) {
                if (($this->bebanMeta[$bmId]['kelas_id'] ?? null) != $kId) continue;
                if (!$this->validasiBeban($jadwal, $kId, $bmId, $jtm)) $penalti += 1000000;
            }
        }

        foreach ($guruDailyLoad as $days) {
            foreach ($days as $count) {
                if ($count >= 8) $penalti += 1000000 + (($count - 8) * 200000);
            }
        }

        return $penalti;
    }
}
