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
        @ini_set('memory_limit', '512M');

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

        $totalJtm = $bebanMengajar->sum('jtm');
        $totalSlotTersedia = count($kelasIds) * 44;
        if ($totalJtm > $totalSlotTersedia) {
            throw new \Exception("Kelebihan Beban: {$totalJtm} JTM vs {$totalSlotTersedia} kapasitas slot.");
        }

        $bebanMap = $this->getBebanMap();
        $units = $this->buildUnits($bebanMengajar);
        $totalJtmTarget = (int) $totalJtm;

        $waktuMulai = time();
        $batasWaktu = 85;
        $solusiTerbaik = $this->buatJadwalKosong($kelasIds);
        $skorTerbaik = PHP_INT_MAX;

        for ($r = 0; $r < 15; $r++) {
            if ((time() - $waktuMulai) >= $batasWaktu - 20) break;

            $this->lockedSlots = [];
            $jadwal = $this->buatJadwalKosong($kelasIds);
            $orderedUnits = $this->urutkanUnits($units, $r);
            $this->tempatkanPreserve($jadwal, $orderedUnits, $kelasIds);
            $this->tempatkanSemuaGreedy($jadwal, $orderedUnits, $kelasIds);
            $this->isiSlotKosong($jadwal, $orderedUnits, $kelasIds, $waktuMulai, $batasWaktu);

            $kosong = $this->hitungSlotBelumTerisi($jadwal, $totalJtmTarget);
            $biaya = $this->hitungHardPenalti($jadwal, $kelasIds, $bebanMap);
            $skor = ($kosong * 50000000) + $biaya;

            if ($skor < $skorTerbaik) {
                $solusiTerbaik = $this->salinJadwal($jadwal);
                $skorTerbaik = $skor;
            }
            if ($kosong === 0 && $biaya === 0) break;
        }

        $solusiTerbaik = $this->perbaikiIteratif($solusiTerbaik, $kelasIds, $bebanMap, $units, $waktuMulai, $batasWaktu);
        $solusiTerbaik = $this->seimbangkanBebanGuru($solusiTerbaik, $kelasIds, $bebanMap, $waktuMulai, $batasWaktu);
        $biayaTerbaik = $this->hitungHardPenalti($solusiTerbaik, $kelasIds, $bebanMap);

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
            if (empty($insertData)) {
                throw new \Exception("Tidak ada slot yang berhasil ditempatkan. Kurangi preset blokir atau periksa beban mengajar.");
            }
            foreach (array_chunk($insertData, 500) as $chunk) {
                Jadwal::insert($chunk);
            }
            DB::commit();
            return [
                'status' => 'success',
                'biaya_penalti' => $biayaTerbaik,
                'total_slot_terisi' => count($insertData),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('JadwalSAO save error: ' . $e->getMessage());
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

    private function getBebanMap(): array
    {
        $map = [];
        foreach ($this->bebanMeta as $id => $meta) {
            $map[$id] = $meta['jtm'];
        }
        return $map;
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
            $units[] = [
                'bmId' => $beban->id,
                'guruId' => $beban->guru_id,
                'kelasId' => $beban->kelas_id,
                'jtm' => $beban->jtm,
                'remaining' => $beban->jtm,
                'slotTemplate' => [
                    'beban_mengajar_id' => $beban->id,
                    'guru_id' => $beban->guru_id,
                    'mapel_id' => $beban->mapel_id,
                    'kelas_id' => $beban->kelas_id,
                ],
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
            $offset = ($seed % 3) * (int) floor(count($copy) / 4);
            if ($offset > 0 && $offset < count($copy)) {
                $head = array_splice($copy, 0, $offset);
                $copy = array_merge($copy, $head);
            }
            for ($i = count($copy) - 1; $i > 0; $i -= 7) {
                $j = mt_rand(0, min($i, count($copy) - 1));
                [$copy[$i], $copy[$j]] = [$copy[$j], $copy[$i]];
            }
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

    private function hitungBebanGuruHarian(array $jadwal, array $kelasIds): array
    {
        $load = [];
        foreach ($this->strukturHari as $hari => $jml) {
            for ($jam = 1; $jam <= $jml; $jam++) {
                foreach ($kelasIds as $kId) {
                    $slot = $jadwal[$hari][$jam][$kId];
                    if ($slot !== null) {
                        $gid = $slot['guru_id'];
                        $load[$gid][$hari] = ($load[$gid][$hari] ?? 0) + 1;
                    }
                }
            }
        }
        return $load;
    }

    private function hitungSlotBelumTerisi(array $jadwal, int $totalTarget): int
    {
        $terisi = 0;
        foreach ($jadwal as $jamKeData) {
            foreach ($jamKeData as $kelasData) {
                foreach ($kelasData as $slot) {
                    if ($slot !== null) $terisi++;
                }
            }
        }
        return max(0, $totalTarget - $terisi);
    }

    private function cariPosisiBlok(array $jadwal, int $size, int $kelasId, array $kelasIds, int $guruId, array $excludeDays = [], int $limit = 25, bool $fullScan = false): array
    {
        $posisi = [];
        $guruLoad = $this->hitungBebanGuruHarian($jadwal, $kelasIds);
        $hariArr = array_keys($this->strukturHari);

        if (!$fullScan) {
            shuffle($hariArr);
        } else {
            usort($hariArr, fn($a, $b) => ($guruLoad[$guruId][$a] ?? 0) <=> ($guruLoad[$guruId][$b] ?? 0));
        }

        foreach ($hariArr as $hari) {
            if (in_array($hari, $excludeDays, true)) continue;
            $bebanHari = $guruLoad[$guruId][$hari] ?? 0;
            if (!$fullScan && $bebanHari + $size > 7) continue;

            $maxJam = $this->strukturHari[$hari];
            for ($startJam = 1; $startJam <= $maxJam - $size + 1; $startJam++) {
                if (!$this->kelasSlotKosong($jadwal, $hari, $startJam, $size, $kelasId)) continue;
                if (!$this->guruBebasDiBlok($jadwal, $guruId, $hari, $startJam, $size, $kelasId, $kelasIds)) continue;
                $posisi[] = [
                    'hari' => $hari,
                    'startJam' => $startJam,
                    'size' => $size,
                    'beban' => $bebanHari,
                ];
            }
        }

        usort($posisi, fn($a, $b) => $a['beban'] <=> $b['beban']);
        if ($fullScan) {
            return $posisi;
        }
        return array_slice($posisi, 0, $limit);
    }

    private function tempatkanBlok(array &$jadwal, array $template, string $hari, int $startJam, int $size): void
    {
        $kelasId = $template['kelas_id'];
        for ($s = 0; $s < $size; $s++) {
            $jadwal[$hari][$startJam + $s][$kelasId] = $template;
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

    private function syncRemainingUnit(array &$jadwal, array &$unit): void
    {
        $unit['remaining'] = max(0, $unit['jtm'] - $this->hitungSlotBeban($jadwal, $unit['kelasId'], $unit['bmId']));
    }

    private function tempatkanPreserve(array &$jadwal, array &$units, array $kelasIds): void
    {
        foreach ($units as &$unit) {
            $guruId = $unit['guruId'];
            $kelasId = $unit['kelasId'];
            foreach ($this->fastConstraints[$guruId] ?? [] as $hari => $jams) {
                foreach ($jams as $jam => $type) {
                    if ($type !== 1 || $unit['remaining'] <= 0) continue;
                    if ($jadwal[$hari][$jam][$kelasId] !== null) continue;
                    if (!$this->guruBebasDiBlok($jadwal, $guruId, $hari, $jam, 1, $kelasId, $kelasIds)) continue;
                    $jadwal[$hari][$jam][$kelasId] = $unit['slotTemplate'];
                    $this->lockedSlots[$hari][$jam][$kelasId] = true;
                    $unit['remaining']--;
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
                foreach ([1, -1] as $d) {
                    $nj = $p['jam'] + $d;
                    if ($nj < 1 || $nj > $this->strukturHari[$p['hari']]) continue;
                    if ($jadwal[$p['hari']][$nj][$kelasId] !== null) continue;
                    if (!$this->guruBebasDiBlok($jadwal, $guruId, $p['hari'], $nj, 1, $kelasId, $kelasIds)) continue;
                    $jadwal[$p['hari']][$nj][$kelasId] = $unit['slotTemplate'];
                    $unit['remaining']--;
                }
            }
        }
        unset($unit);
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
            foreach ([min($jams) - 1, max($jams) + 1] as $aj) {
                if ($aj < 1 || $aj > $this->strukturHari[$hari]) continue;
                if ($jadwal[$hari][$aj][$kelasId] !== null) continue;
                if (!$this->guruBebasDiBlok($jadwal, $unit['guruId'], $hari, $aj, 1, $kelasId, $kelasIds)) continue;
                $posisi[] = ['hari' => $hari, 'startJam' => $aj, 'size' => 1];
            }
        }
        return $posisi;
    }

    private function cariSatuKombinasi(array $jadwal, array $unit, array $blocks, array $kelasIds, array $fixedExcludeDays = [], bool $fullScan = false): ?array
    {
        return $this->cariKombinasiIteratif($jadwal, $unit, $blocks, 0, [], $kelasIds, $fixedExcludeDays, 0, $fullScan);
    }

    private function cariKombinasiIteratif(array $jadwal, array $unit, array $blocks, int $idx, array $chosen, array $kelasIds, array $fixedExcludeDays, int $depth, bool $fullScan = false): ?array
    {
        if ($depth > 6) return null;

        if ($idx >= count($blocks)) {
            return $chosen;
        }

        $size = $blocks[$idx];
        $multiDay = count($blocks) > 1;
        $exclude = $multiDay ? array_unique(array_merge($fixedExcludeDays, array_column($chosen, 'hari'))) : [];
        $posisi = $this->cariPosisiBlok($jadwal, $size, $unit['kelasId'], $kelasIds, $unit['guruId'], $exclude, $fullScan ? 999 : 30, $fullScan);

        $try = $fullScan ? $posisi : array_slice($posisi, 0, 20);
        if (!$fullScan) shuffle($try);

        foreach ($try as $pos) {
            $temp = $jadwal;
            $this->tempatkanBlok($temp, $unit['slotTemplate'], $pos['hari'], $pos['startJam'], $size);
            $next = $chosen;
            $next[] = ['hari' => $pos['hari'], 'startJam' => $pos['startJam'], 'size' => $size];
            $result = $this->cariKombinasiIteratif($temp, $unit, $blocks, $idx + 1, $next, $kelasIds, $fixedExcludeDays, $depth + 1, $fullScan);
            if ($result !== null) return $result;
        }
        return null;
    }

    private function tempatkanUnit(array &$jadwal, array &$unit, array $kelasIds, bool $fullScan = false): bool
    {
        $this->syncRemainingUnit($jadwal, $unit);
        if ($unit['remaining'] <= 0) return true;

        $existingDays = $this->getHariTerpakaiBeban($jadwal, $unit);

        if ($unit['remaining'] === 1 && count($existingDays) === 1) {
            foreach ($this->cariPosisiMelengkapi($jadwal, $unit, $kelasIds) as $pos) {
                $this->tempatkanBlok($jadwal, $unit['slotTemplate'], $pos['hari'], $pos['startJam'], 1);
                $this->syncRemainingUnit($jadwal, $unit);
                if ($this->validasiBeban($jadwal, $unit['kelasId'], $unit['bmId'], $unit['jtm'])) {
                    return true;
                }
                $this->hapusBlok($jadwal, $unit['kelasId'], $pos['hari'], $pos['startJam'], 1);
                $this->syncRemainingUnit($jadwal, $unit);
            }
        }

        $patterns = $this->getBlockPatterns($unit['remaining']);
        shuffle($patterns);

        foreach ($patterns as $blocks) {
            $kombinasi = $this->cariSatuKombinasi($jadwal, $unit, $blocks, $kelasIds, $existingDays, $fullScan);
            if ($kombinasi === null) continue;
            foreach ($kombinasi as $blok) {
                $this->tempatkanBlok($jadwal, $unit['slotTemplate'], $blok['hari'], $blok['startJam'], $blok['size']);
            }
            $this->syncRemainingUnit($jadwal, $unit);
            if ($unit['remaining'] <= 0 && $this->validasiBeban($jadwal, $unit['kelasId'], $unit['bmId'], $unit['jtm'])) {
                return true;
            }
            foreach ($kombinasi as $blok) {
                $this->hapusBlok($jadwal, $unit['kelasId'], $blok['hari'], $blok['startJam'], $blok['size']);
            }
            $this->syncRemainingUnit($jadwal, $unit);
        }
        return false;
    }

    private function tempatkanSemuaGreedy(array &$jadwal, array $units, array $kelasIds): void
    {
        for ($pass = 0; $pass < 6; $pass++) {
            $progress = false;
            foreach ($units as $unit) {
                $u = $unit;
                $this->syncRemainingUnit($jadwal, $u);
                if ($u['remaining'] <= 0) continue;
                if ($this->tempatkanUnit($jadwal, $u, $kelasIds, $pass >= 3)) {
                    $progress = true;
                }
            }
            if (!$progress) break;
        }
    }

    /** Paksa isi mapel yang belum lengkap — prioritas utama. */
    private function isiSlotKosong(array &$jadwal, array $units, array $kelasIds, int $waktuMulai, int $batasWaktu): void
    {
        for ($round = 0; $round < 8; $round++) {
            if ((time() - $waktuMulai) >= $batasWaktu - 18) break;

            $pending = [];
            foreach ($units as $unit) {
                $u = $unit;
                $this->syncRemainingUnit($jadwal, $u);
                if ($u['remaining'] > 0) {
                    $pending[] = $u;
                }
            }
            if (empty($pending)) break;

            usort($pending, fn($a, $b) => $b['remaining'] <=> $a['remaining']);
            $filled = false;

            foreach ($pending as $unit) {
                $u = $unit;
                $this->syncRemainingUnit($jadwal, $u);
                if ($u['remaining'] <= 0) continue;
                if ($this->tempatkanUnit($jadwal, $u, $kelasIds, true)) {
                    $filled = true;
                }
            }
            if (!$filled) break;
        }
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

    private function salinJadwal(array $jadwal): array
    {
        return unserialize(serialize($jadwal));
    }

    private function perbaikiBebanMapel(array $jadwal, int $kelasId, int $bmId, array $kelasIds, bool $fullScan = false): ?array
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

        $temp = $this->salinJadwal($jadwal);
        foreach ($this->strukturHari as $hari => $jml) {
            for ($j = 1; $j <= $jml; $j++) {
                $slot = $temp[$hari][$j][$kelasId];
                if ($slot !== null && $slot['beban_mengajar_id'] == $bmId) {
                    if (!isset($this->lockedSlots[$hari][$j][$kelasId])) {
                        $temp[$hari][$j][$kelasId] = null;
                    }
                }
            }
        }

        $u = $unit;
        if ($this->tempatkanUnit($temp, $u, $kelasIds, $fullScan)) {
            return $temp;
        }
        return null;
    }

    private function deteksiPelanggar(array $jadwal, array $kelasIds, array $bebanMap): array
    {
        $pelanggar = [];
        foreach ($kelasIds as $kId) {
            foreach ($bebanMap as $bmId => $jtm) {
                if (($this->bebanMeta[$bmId]['kelas_id'] ?? null) != $kId) continue;
                if (!$this->validasiBeban($jadwal, $kId, $bmId, $jtm)) {
                    $pelanggar[] = ['kelasId' => $kId, 'bmId' => $bmId];
                }
            }
        }
        return $pelanggar;
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

    private function perbaikiIteratif(array $jadwal, array $kelasIds, array $bebanMap, array $units, int $waktuMulai, int $batasWaktu): array
    {
        $terbaik = $jadwal;
        $totalTarget = 0;
        foreach ($units as $u) $totalTarget += $u['jtm'];
        $skor = ($this->hitungSlotBelumTerisi($terbaik, $totalTarget) * 50000000)
            + $this->hitungHardPenalti($terbaik, $kelasIds, $bebanMap);

        for ($i = 0; $i < 3000; $i++) {
            if ((time() - $waktuMulai) >= $batasWaktu - 5) break;

            $kandidat = null;

            if ($i % 4 === 0) {
                foreach ($units as $unit) {
                    $u = $unit;
                    $this->syncRemainingUnit($terbaik, $u);
                    if ($u['remaining'] <= 0) continue;
                    $trial = $this->salinJadwal($terbaik);
                    $tu = $u;
                    if ($this->tempatkanUnit($trial, $tu, $kelasIds, true)) {
                        $kandidat = $trial;
                        break;
                    }
                }
            } elseif ($i % 4 === 1) {
                $pelanggar = $this->deteksiPelanggar($terbaik, $kelasIds, $bebanMap);
                if (!empty($pelanggar)) {
                    $p = $pelanggar[array_rand($pelanggar)];
                    $kandidat = $this->perbaikiBebanMapel($terbaik, $p['kelasId'], $p['bmId'], $kelasIds, true);
                }
            } else {
                $bentrok = $this->deteksiBentrok($terbaik, $kelasIds);
                if (!empty($bentrok)) {
                    $b = $bentrok[array_rand($bentrok)];
                    $kandidat = $this->perbaikiBebanMapel($terbaik, $b['kelasId'], $b['bmId'], $kelasIds, true);
                }
            }

            if ($kandidat === null) continue;
            $kosong = $this->hitungSlotBelumTerisi($kandidat, $totalTarget);
            $baru = ($kosong * 50000000) + $this->hitungHardPenalti($kandidat, $kelasIds, $bebanMap);
            if ($baru <= $skor) {
                $terbaik = $kandidat;
                $skor = $baru;
            }
            if ($kosong === 0 && $this->hitungHardPenalti($kandidat, $kelasIds, $bebanMap) === 0) break;
        }
        return $terbaik;
    }

    /** Pindahkan mapel dari hari overload ke hari yang lebih ringan untuk guru yang sama. */
    private function seimbangkanBebanGuru(array $jadwal, array $kelasIds, array $bebanMap, int $waktuMulai, int $batasWaktu): array
    {
        $terbaik = $jadwal;
        $load = $this->hitungBebanGuruHarian($terbaik, $kelasIds);

        for ($i = 0; $i < 1500; $i++) {
            if ((time() - $waktuMulai) >= $batasWaktu - 2) break;

            $overload = [];
            foreach ($load as $gid => $days) {
                foreach ($days as $hari => $cnt) {
                    if ($cnt >= 8) $overload[] = ['guruId' => $gid, 'hari' => $hari, 'cnt' => $cnt];
                }
            }
            if (empty($overload)) break;

            usort($overload, fn($a, $b) => $b['cnt'] <=> $a['cnt']);
            $pick = $overload[0];

            $bmIds = [];
            foreach ($kelasIds as $kId) {
                foreach ($this->strukturHari as $hari => $jml) {
                    if ($hari !== $pick['hari']) continue;
                    for ($j = 1; $j <= $jml; $j++) {
                        $slot = $terbaik[$hari][$j][$kId];
                        if ($slot !== null && $slot['guru_id'] == $pick['guruId']) {
                            $bmIds[$slot['beban_mengajar_id']] = $kId;
                        }
                    }
                }
            }

            $improved = false;
            foreach ($bmIds as $bmId => $kId) {
                $kandidat = $this->perbaikiBebanMapel($terbaik, $kId, $bmId, $kelasIds, true);
                if ($kandidat === null) continue;

                $newLoad = $this->hitungBebanGuruHarian($kandidat, $kelasIds);
                $oldMax = max($load[$pick['guruId']] ?? [0]);
                $newMax = max($newLoad[$pick['guruId']] ?? [0]);

                if ($newMax < $oldMax && $this->hitungHardPenalti($kandidat, $kelasIds, $bebanMap) <= $this->hitungHardPenalti($terbaik, $kelasIds, $bebanMap)) {
                    $terbaik = $kandidat;
                    $load = $newLoad;
                    $improved = true;
                    break;
                }
            }
            if (!$improved) break;
        }
        return $terbaik;
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
                if ($count >= 8) $penalti += 500000 + (($count - 8) * 300000);
            }
        }

        return $penalti;
    }
}
