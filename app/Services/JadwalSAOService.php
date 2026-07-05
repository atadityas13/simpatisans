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
    private $maxIterasi = 400000;
    private $suhuAwal = 2000000.0;
    private $coolingRate = 0.99995;
    private $constraints = [];
    private $fastConstraints = [];
    private $lockedSlots = [];

    public function generate(int $semesterId)
    {
        $bebanMengajar = BebanMengajar::where('semester_id', $semesterId)
                            ->where('is_satminkal', 1)
                            ->with(['guru', 'mapel', 'kelas'])
                            ->get();
        if ($bebanMengajar->isEmpty()) throw new \Exception("Data Beban Mengajar (KBM) kosong untuk semester ini. Silakan distribusikan jam terlebih dahulu.");

        $kelasList = Kelas::orderByRaw("FIELD(tingkat, 'VII', 'VIII', 'IX')")->get();
        $kelasIds = $kelasList->pluck('id')->toArray();
        if (empty($kelasIds)) throw new \Exception("Data Kelas kosong.");

        $rawConstraints = GuruConstraint::all();
        $this->constraints = $rawConstraints->groupBy('guru_id');
        $this->fastConstraints = [];
        foreach ($rawConstraints as $c) {
            $this->fastConstraints[$c->guru_id][$c->hari][$c->jam_ke] = $c->type;
        }
        $this->lockedSlots = [];

        $jadwalAwal = $this->buatJadwalKosong($kelasIds);

        $slotTugas = [];
        foreach ($bebanMengajar as $beban) {
            for ($i = 0; $i < $beban->jtm; $i++) {
                $slotTugas[] = [
                    'beban_mengajar_id' => $beban->id,
                    'guru_id' => $beban->guru_id,
                    'mapel_id' => $beban->mapel_id,
                    'kelas_id' => $beban->kelas_id
                ];
            }
        }

        $totalSlotTersedia = count($kelasIds) * 44;
        if (count($slotTugas) > $totalSlotTersedia) throw new \Exception("Kelebihan Beban: ".count($slotTugas)." JTM vs ". $totalSlotTersedia ." Kapasitas Slot Tersedia.");

        $bebanMap = $bebanMengajar->pluck('jtm', 'id')->toArray();

        // Multi-restart: coba beberapa solusi awal, pilih yang terbaik
        $solusiTerbaik = null;
        $biayaTerbaik = PHP_INT_MAX;
        $jumlahRestart = 5;

        for ($r = 0; $r < $jumlahRestart; $r++) {
            $this->lockedSlots = [];
            $solusi = $this->sebarkanTugasAcak($this->buatJadwalKosong($kelasIds), $slotTugas, $kelasIds, $bebanMap);
            $biaya = $this->hitungHardPenalti($solusi, $kelasIds, $bebanMap);
            if ($biaya < $biayaTerbaik) {
                $solusiTerbaik = $solusi;
                $biayaTerbaik = $biaya;
            }
            if ($biayaTerbaik === 0) break;
        }

        $solusiSekarang = $solusiTerbaik;
        $biayaSekarang = $biayaTerbaik;

        $waktuMulai = time();
        $batasWaktu = 110;

        $suhu = $this->suhuAwal;
        for ($i = 0; $i < $this->maxIterasi; $i++) {
            if ($suhu < 0.01 || $biayaTerbaik === 0) break;

            if ($i % 500 === 0 && (time() - $waktuMulai) >= $batasWaktu) {
                Log::warning("SAO Time Limit Reached ($batasWaktu detik). Berhenti di iterasi $i.");
                break;
            }

            $solusiBaru = $this->buatTetangga($solusiSekarang, $kelasIds);
            $biayaBaru = $this->hitungHardPenalti($solusiBaru, $kelasIds, $bebanMap);

            $delta = $biayaBaru - $biayaSekarang;

            if ($delta < 0 || exp(-$delta / $suhu) > (mt_rand() / mt_getrandmax())) {
                $solusiSekarang = $solusiBaru;
                $biayaSekarang = $biayaBaru;
                if ($biayaSekarang < $biayaTerbaik) {
                    $solusiTerbaik = $solusiSekarang;
                    $biayaTerbaik = $biayaSekarang;
                }
            }
            $suhu *= $this->coolingRate;

            if ($i % 5000 === 0) {
                Log::info("SAO Iterasi {$i}: Biaya Terbaik = {$biayaTerbaik}");
            }
        }

        // Fase perbaikan terakhir untuk JTM & kelelahan
        $solusiTerbaik = $this->perbaikiSolusi($solusiTerbaik, $kelasIds, $bebanMap, $waktuMulai, $batasWaktu);
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
                                'hari' => $hari,
                                'jam_ke' => $jam,
                                'created_at' => $now,
                                'updated_at' => $now
                            ];
                        }
                    }
                }
            }
            foreach (array_chunk($insertData, 500) as $chunk) Jadwal::insert($chunk);
            DB::commit();
            return ['status' => 'success', 'biaya_penalti' => $biayaTerbaik, 'total_slot_terisi' => count($insertData)];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            throw new \Exception("Gagal menyimpan jadwal SAO: " . $e->getMessage());
        }
    }

    /**
     * Pola blok valid untuk sisa JTM yang belum ditempatkan.
     */
    private function getBlockPatterns(int $remaining): array
    {
        return match ($remaining) {
            1 => [[1]],
            2 => [[2]],
            3 => [[3], [2, 1]],
            4 => [[2, 2]],
            5 => [[3, 2], [2, 2, 1]],
            6 => [[3, 3], [2, 2, 2]],
            default => [array_fill(0, (int) ceil($remaining / 2), 2)],
        };
    }

    private function buatJadwalKosong($kelasIds) {
        $jadwal = [];
        foreach ($this->strukturHari as $hari => $jml) {
            for ($jam = 1; $jam <= $jml; $jam++) {
                foreach ($kelasIds as $kId) $jadwal[$hari][$jam][$kId] = null;
            }
        }
        return $jadwal;
    }

    private function guruBebasDiBlok($jadwal, $guruId, $hari, $startJam, $size, $kelasId, $kelasIds): bool
    {
        $maxJam = $this->strukturHari[$hari];
        if ($startJam + $size - 1 > $maxJam) return false;

        for ($s = 0; $s < $size; $s++) {
            $jam = $startJam + $s;
            if (isset($this->fastConstraints[$guruId][$hari][$jam]) &&
                $this->fastConstraints[$guruId][$hari][$jam] == 0) {
                return false;
            }
            foreach ($kelasIds as $kId) {
                if ($kId == $kelasId) continue;
                $slot = $jadwal[$hari][$jam][$kId];
                if ($slot !== null && $slot['guru_id'] == $guruId) return false;
            }
        }
        return true;
    }

    private function kelasSlotKosong($jadwal, $hari, $startJam, $size, $kelasId): bool
    {
        $maxJam = $this->strukturHari[$hari];
        if ($startJam + $size - 1 > $maxJam) return false;
        for ($s = 0; $s < $size; $s++) {
            if ($jadwal[$hari][$startJam + $s][$kelasId] !== null) return false;
            if (isset($this->lockedSlots[$hari][$startJam + $s][$kelasId])) return false;
        }
        return true;
    }

    /**
     * Cari semua posisi blok kosong untuk kelas, diurutkan acak.
     * @param array $excludeDays Hari yang sudah dipakai (wajib berbeda untuk multi-blok JTM)
     */
    private function cariPosisiBlokKosong($jadwal, $size, $kelasId, $kelasIds, $guruId = null, array $excludeDays = []): array
    {
        $posisi = [];
        $hariArr = array_keys($this->strukturHari);
        shuffle($hariArr);

        foreach ($hariArr as $hari) {
            if (in_array($hari, $excludeDays, true)) continue;
            $maxJam = $this->strukturHari[$hari];
            $startJams = range(1, $maxJam - $size + 1);
            shuffle($startJams);
            foreach ($startJams as $startJam) {
                if (!$this->kelasSlotKosong($jadwal, $hari, $startJam, $size, $kelasId)) continue;
                if ($guruId !== null && !$this->guruBebasDiBlok($jadwal, $guruId, $hari, $startJam, $size, $kelasId, $kelasIds)) continue;
                $posisi[] = ['hari' => $hari, 'startJam' => $startJam];
            }
        }
        return $posisi;
    }

    private function tempatkanBlok(&$jadwal, &$tugasList, $hari, $startJam, $size): void
    {
        for ($s = 0; $s < $size; $s++) {
            if (!empty($tugasList)) {
                $kelasId = $tugasList[0]['kelas_id'];
                $jadwal[$hari][$startJam + $s][$kelasId] = array_shift($tugasList);
            }
        }
    }

    private function sebarkanTugasAcak($jadwal, $slotTugas, $kelasIds, $bebanMap) {
        $bebanGroups = [];
        foreach ($slotTugas as $tugas) {
            $bebanGroups[$tugas['beban_mengajar_id']][] = $tugas;
        }

        // Preserve constraints
        foreach ($this->constraints as $guruId => $guruCons) {
            $preserved = $guruCons->where('type', 1);
            foreach ($preserved as $con) {
                foreach ($bebanGroups as $bmId => &$tugasList) {
                    if (empty($tugasList)) continue;
                    $first = $tugasList[0];
                    if ($first['guru_id'] == $guruId && $jadwal[$con->hari][$con->jam_ke][$first['kelas_id']] === null) {
                        $jadwal[$con->hari][$con->jam_ke][$first['kelas_id']] = array_shift($tugasList);
                        $this->lockedSlots[$con->hari][$con->jam_ke][$first['kelas_id']] = true;
                        break;
                    }
                }
            }
        }

        // Perbaiki blok parsial dari preserve
        foreach ($bebanGroups as $bmId => &$tugasList) {
            if (empty($tugasList)) continue;
            $kelasId = $tugasList[0]['kelas_id'];
            $guruId = $tugasList[0]['guru_id'];
            $placedSlots = [];
            foreach ($this->strukturHari as $hari => $jml) {
                for ($j = 1; $j <= $jml; $j++) {
                    $slot = $jadwal[$hari][$j][$kelasId];
                    if ($slot !== null && $slot['beban_mengajar_id'] == $bmId) {
                        $placedSlots[] = ['hari' => $hari, 'jam' => $j];
                    }
                }
            }
            if (!empty($placedSlots) && !empty($tugasList)) {
                foreach ($placedSlots as $placed) {
                    $hari = $placed['hari'];
                    $jam = $placed['jam'];
                    $maxJam = $this->strukturHari[$hari];
                    if ($jam + 1 <= $maxJam && $jadwal[$hari][$jam + 1][$kelasId] === null &&
                        $this->guruBebasDiBlok($jadwal, $guruId, $hari, $jam + 1, 1, $kelasId, $kelasIds)) {
                        $jadwal[$hari][$jam + 1][$kelasId] = array_shift($tugasList);
                        if (empty($tugasList)) break;
                    }
                    if (!empty($tugasList) && $jam - 1 >= 1 && $jadwal[$hari][$jam - 1][$kelasId] === null &&
                        $this->guruBebasDiBlok($jadwal, $guruId, $hari, $jam - 1, 1, $kelasId, $kelasIds)) {
                        $jadwal[$hari][$jam - 1][$kelasId] = array_shift($tugasList);
                        if (empty($tugasList)) break;
                    }
                }
            }
        }
        unset($tugasList);

        // Urutkan: JTM besar dulu, guru dengan banyak constraint dulu
        $sortedBmIds = array_keys($bebanGroups);
        usort($sortedBmIds, function ($a, $b) use ($bebanGroups, $bebanMap) {
            $jtmA = $bebanMap[$a] ?? count($bebanGroups[$a]);
            $jtmB = $bebanMap[$b] ?? count($bebanGroups[$b]);
            if ($jtmA !== $jtmB) return $jtmB <=> $jtmA;
            $consA = count($this->constraints[$bebanGroups[$a][0]['guru_id']] ?? []);
            $consB = count($this->constraints[$bebanGroups[$b][0]['guru_id']] ?? []);
            return $consB <=> $consA;
        });

        foreach ($sortedBmIds as $bmId) {
            $tugasList = $bebanGroups[$bmId];
            if (empty($tugasList)) continue;

            $remaining = count($tugasList);
            $kelasId = $tugasList[0]['kelas_id'];
            $guruId = $tugasList[0]['guru_id'];
            $patterns = $this->getBlockPatterns($remaining);
            shuffle($patterns);

            $placed = false;
            foreach ($patterns as $blocks) {
                $tempJadwal = $jadwal;
                $tempTugas = $tugasList;
                $usedDays = [];
                $allBlocksPlaced = true;
                $multiDay = count($blocks) > 1;

                foreach ($blocks as $size) {
                    $posisi = $this->cariPosisiBlokKosong($tempJadwal, $size, $kelasId, $kelasIds, $guruId, $multiDay ? $usedDays : []);
                    if (empty($posisi)) {
                        $allBlocksPlaced = false;
                        break;
                    }
                    $pick = $posisi[array_rand($posisi)];
                    $this->tempatkanBlok($tempJadwal, $tempTugas, $pick['hari'], $pick['startJam'], $size);
                    $usedDays[] = $pick['hari'];
                }

                if ($allBlocksPlaced && empty($tempTugas)) {
                    $jadwal = $tempJadwal;
                    $tugasList = $tempTugas;
                    $placed = true;
                    break;
                }
            }

            // Fallback cerdas: coba setiap blok secara individual dengan pencarian sistematis
            if (!$placed && !empty($tugasList)) {
                $jtm = $remaining;
                $fallbackPatterns = $this->getBlockPatterns($jtm);
                foreach ($fallbackPatterns as $blocks) {
                    $tempJadwal = $jadwal;
                    $tempTugas = $tugasList;
                    $usedDays = [];
                    $ok = true;
                    $multiDay = count($blocks) > 1;
                    foreach ($blocks as $size) {
                        $posisi = $this->cariPosisiBlokKosong($tempJadwal, $size, $kelasId, $kelasIds, $guruId, $multiDay ? $usedDays : []);
                        if (empty($posisi)) { $ok = false; break; }
                        $pick = $posisi[0];
                        $this->tempatkanBlok($tempJadwal, $tempTugas, $pick['hari'], $pick['startJam'], $size);
                        $usedDays[] = $pick['hari'];
                    }
                    if ($ok && empty($tempTugas)) {
                        $jadwal = $tempJadwal;
                        $placed = true;
                        break;
                    }
                }
            }

            // Fallback terakhir: pecah blok menjadi ukuran 1 tapi tetap cari slot bersebelahan jika memungkinkan
            if (!$placed && !empty($tugasList)) {
                while (!empty($tugasList)) {
                    $posisi = $this->cariPosisiBlokKosong($jadwal, 1, $kelasId, $kelasIds, $guruId);
                    if (empty($posisi)) {
                        $posisi = $this->cariPosisiBlokKosong($jadwal, 1, $kelasId, $kelasIds, null);
                    }
                    if (empty($posisi)) break;
                    $pick = $posisi[array_rand($posisi)];
                    $this->tempatkanBlok($jadwal, $tugasList, $pick['hari'], $pick['startJam'], 1);
                }
            }
        }
        return $jadwal;
    }

    /**
     * Temukan semua blok kontigu per beban_mengajar dalam satu kelas.
     */
    private function temukanBlokMapel($jadwal, $kelasId, $bmId): array
    {
        $blok = [];
        foreach ($this->strukturHari as $hari => $jml) {
            $run = [];
            for ($j = 1; $j <= $jml; $j++) {
                $slot = $jadwal[$hari][$j][$kelasId];
                if ($slot !== null && $slot['beban_mengajar_id'] == $bmId) {
                    $run[] = $j;
                } elseif (!empty($run)) {
                    $blok[] = ['hari' => $hari, 'startJam' => $run[0], 'size' => count($run), 'bmId' => $bmId];
                    $run = [];
                }
            }
            if (!empty($run)) {
                $blok[] = ['hari' => $hari, 'startJam' => $run[0], 'size' => count($run), 'bmId' => $bmId];
            }
        }
        return $blok;
    }

    private function hapusBlok($jadwal, $kelasId, $hari, $startJam, $size)
    {
        for ($s = 0; $s < $size; $s++) {
            if (!isset($this->lockedSlots[$hari][$startJam + $s][$kelasId])) {
                $jadwal[$hari][$startJam + $s][$kelasId] = null;
            }
        }
        return $jadwal;
    }

    private function pindahkanBlok($jadwal, $kelasId, $kelasIds, $fromHari, $fromStart, $size, $toHari, $toStart): ?array
    {
        $slot = $jadwal[$fromHari][$fromStart][$kelasId];
        if ($slot === null) return null;
        $guruId = $slot['guru_id'];

        for ($s = 0; $s < $size; $s++) {
            if (isset($this->lockedSlots[$fromHari][$fromStart + $s][$kelasId])) return null;
        }

        $isiBlok = [];
        for ($s = 0; $s < $size; $s++) {
            $isiBlok[] = $jadwal[$fromHari][$fromStart + $s][$kelasId];
        }

        $temp = $this->hapusBlok($jadwal, $kelasId, $fromHari, $fromStart, $size);

        if (!$this->kelasSlotKosong($temp, $toHari, $toStart, $size, $kelasId)) return null;
        if (!$this->guruBebasDiBlok($temp, $guruId, $toHari, $toStart, $size, $kelasId, $kelasIds)) return null;

        for ($s = 0; $s < $size; $s++) {
            $temp[$toHari][$toStart + $s][$kelasId] = $isiBlok[$s];
        }
        return $temp;
    }

    private function rebalanceGuru($solusi, $kelasIds)
    {
        $guruLoad = [];
        foreach ($this->strukturHari as $hari => $jml) {
            for ($jam = 1; $jam <= $jml; $jam++) {
                foreach ($kelasIds as $kId) {
                    $slot = $solusi[$hari][$jam][$kId];
                    if ($slot !== null) {
                        $gid = $slot['guru_id'];
                        $guruLoad[$gid][$hari] = ($guruLoad[$gid][$hari] ?? 0) + 1;
                    }
                }
            }
        }

        $overloaded = [];
        foreach ($guruLoad as $gid => $days) {
            foreach ($days as $hari => $count) {
                if ($count >= 8) $overloaded[] = ['guru_id' => $gid, 'hari' => $hari, 'count' => $count];
            }
        }
        if (empty($overloaded)) return $solusi;

        usort($overloaded, fn($a, $b) => $b['count'] <=> $a['count']);
        $pick = $overloaded[array_rand(array_slice($overloaded, 0, min(5, count($overloaded))))];
        $guruId = $pick['guru_id'];
        $fromHari = $pick['hari'];

        $hariArr = array_keys($this->strukturHari);
        shuffle($hariArr);

        foreach ($hariArr as $toHari) {
            if ($toHari === $fromHari) continue;
            if (($guruLoad[$guruId][$toHari] ?? 0) >= 7) continue;

            foreach ($kelasIds as $kId) {
                $bloks = [];
                foreach ($this->strukturHari as $h => $jml) {
                    if ($h !== $fromHari) continue;
                    $run = [];
                    $runSlots = [];
                    for ($j = 1; $j <= $jml; $j++) {
                        $slot = $solusi[$h][$j][$kId];
                        if ($slot !== null && $slot['guru_id'] == $guruId) {
                            $run[] = $j;
                            $runSlots[] = $slot;
                        } elseif (!empty($run)) {
                            $bloks[] = ['startJam' => $run[0], 'size' => count($run)];
                            $run = [];
                        }
                    }
                    if (!empty($run)) $bloks[] = ['startJam' => $run[0], 'size' => count($run)];
                }

                foreach ($bloks as $blok) {
                    $posisi = $this->cariPosisiBlokKosong($solusi, $blok['size'], $kId, $kelasIds, $guruId);
                    foreach ($posisi as $pos) {
                        if ($pos['hari'] === $toHari) {
                            $hasil = $this->pindahkanBlok($solusi, $kId, $kelasIds, $fromHari, $blok['startJam'], $blok['size'], $pos['hari'], $pos['startJam']);
                            if ($hasil !== null) return $hasil;
                        }
                    }
                }
            }
        }
        return $solusi;
    }

    private function swapBlokMapel($solusi, $kelasIds)
    {
        $kelasAcak = $kelasIds[array_rand($kelasIds)];
        $bmIds = [];
        foreach ($this->strukturHari as $hari => $jml) {
            for ($j = 1; $j <= $jml; $j++) {
                $slot = $solusi[$hari][$j][$kelasAcak];
                if ($slot !== null) $bmIds[$slot['beban_mengajar_id']] = true;
            }
        }
        $bmIdList = array_keys($bmIds);
        if (count($bmIdList) < 1) return $solusi;

        $bmPick = $bmIdList[array_rand($bmIdList)];
        $bloks = $this->temukanBlokMapel($solusi, $kelasAcak, $bmPick);
        if (count($bloks) < 1) return $solusi;

        $blok = $bloks[array_rand($bloks)];
        $guruId = $solusi[$blok['hari']][$blok['startJam']][$kelasAcak]['guru_id'] ?? null;
        $posisi = $this->cariPosisiBlokKosong($solusi, $blok['size'], $kelasAcak, $kelasIds, $guruId);
        if (empty($posisi)) return $solusi;

        $pick = $posisi[array_rand($posisi)];
        if ($pick['hari'] === $blok['hari'] && $pick['startJam'] === $blok['startJam']) return $solusi;

        $hasil = $this->pindahkanBlok($solusi, $kelasAcak, $kelasIds, $blok['hari'], $blok['startJam'], $blok['size'], $pick['hari'], $pick['startJam']);
        return $hasil ?? $solusi;
    }

    private function buatTetangga($solusi, $kelasIds) {
        $roll = mt_rand(1, 100);

        // 15% Rebalance guru overload
        if ($roll <= 15) {
            return $this->rebalanceGuru($solusi, $kelasIds);
        }

        // 25% Pindah blok mapel utuh (JTM-aware)
        if ($roll <= 40) {
            return $this->swapBlokMapel($solusi, $kelasIds);
        }

        $baru = $solusi;
        $kelasAcak = $kelasIds[array_rand($kelasIds)];
        $hariArr = array_keys($this->strukturHari);

        // 30% Swap entire days for this class
        if ($roll <= 70) {
            $h1 = $hariArr[array_rand($hariArr)];
            $h2 = $hariArr[array_rand($hariArr)];
            if ($h1 != $h2) {
                $max1 = $this->strukturHari[$h1];
                $max2 = $this->strukturHari[$h2];
                $max = max($max1, $max2);

                $locked = false;
                for ($j = 1; $j <= $max; $j++) {
                    if (isset($this->lockedSlots[$h1][$j][$kelasAcak]) || isset($this->lockedSlots[$h2][$j][$kelasAcak])) {
                        $locked = true;
                        break;
                    }
                }

                if (!$locked) {
                    for ($j = 1; $j <= $max; $j++) {
                        if ($j <= $max1 && $j <= $max2) {
                            if (isset($baru[$h1][$j][$kelasAcak]) || isset($baru[$h2][$j][$kelasAcak])) {
                                $t = $baru[$h1][$j][$kelasAcak] ?? null;
                                $baru[$h1][$j][$kelasAcak] = $baru[$h2][$j][$kelasAcak] ?? null;
                                $baru[$h2][$j][$kelasAcak] = $t;
                            }
                        }
                    }
                    return $baru;
                }
            }
        }

        // 30% Standard swap (reduced from 60%)
        $hari1 = $hariArr[array_rand($hariArr)];
        $jam1 = mt_rand(1, $this->strukturHari[$hari1]);
        $hari2 = $hariArr[array_rand($hariArr)];
        $jam2 = mt_rand(1, $this->strukturHari[$hari2]);

        if (isset($this->lockedSlots[$hari1][$jam1][$kelasAcak]) || isset($this->lockedSlots[$hari2][$jam2][$kelasAcak])) {
            return $this->swapBlokMapel($solusi, $kelasIds);
        }

        $temp = $baru[$hari1][$jam1][$kelasAcak];
        $baru[$hari1][$jam1][$kelasAcak] = $baru[$hari2][$jam2][$kelasAcak];
        $baru[$hari2][$jam2][$kelasAcak] = $temp;
        return $baru;
    }

    /**
     * Perbaiki satu beban mengajar yang melanggar struktur JTM dengan menempatkan ulang semua slotnya.
     */
    private function perbaikiBebanMapel($jadwal, $kelasId, $bmId, $jtm, $kelasIds)
    {
        $tugasList = [];
        foreach ($this->strukturHari as $hari => $jml) {
            for ($j = 1; $j <= $jml; $j++) {
                $slot = $jadwal[$hari][$j][$kelasId];
                if ($slot !== null && $slot['beban_mengajar_id'] == $bmId) {
                    if (!isset($this->lockedSlots[$hari][$j][$kelasId])) {
                        $tugasList[] = $slot;
                        $jadwal[$hari][$j][$kelasId] = null;
                    }
                }
            }
        }
        if (empty($tugasList)) return $jadwal;

        $guruId = $tugasList[0]['guru_id'];
        $remaining = count($tugasList);
        $patterns = $this->getBlockPatterns($remaining);

        foreach ($patterns as $blocks) {
            $temp = $jadwal;
            $tempTugas = $tugasList;
            $usedDays = [];
            $multiDay = count($blocks) > 1;
            $ok = true;

            foreach ($blocks as $size) {
                $posisi = $this->cariPosisiBlokKosong($temp, $size, $kelasId, $kelasIds, $guruId, $multiDay ? $usedDays : []);
                if (empty($posisi)) { $ok = false; break; }
                $pick = $posisi[array_rand($posisi)];
                $this->tempatkanBlok($temp, $tempTugas, $pick['hari'], $pick['startJam'], $size);
                $usedDays[] = $pick['hari'];
            }

            if ($ok && empty($tempTugas)) return $temp;
        }
        return $jadwal;
    }

    private function deteksiPelanggarJTM($jadwal, $kelasIds, $bebanMap): array
    {
        $pelanggar = [];
        foreach ($kelasIds as $kId) {
            $mapelDaily = [];
            $mapelBlocks = [];

            foreach ($this->strukturHari as $hari => $jml) {
                $lastBMId = null;
                for ($j = 1; $j <= $jml; $j++) {
                    $slot = $jadwal[$hari][$j][$kId];
                    if ($slot !== null) {
                        $bmId = $slot['beban_mengajar_id'];
                        if (!isset($mapelDaily[$bmId][$hari])) {
                            $mapelDaily[$bmId][$hari] = 0;
                            $mapelBlocks[$bmId][$hari] = 0;
                        }
                        $mapelDaily[$bmId][$hari]++;
                        if ($bmId !== $lastBMId) $mapelBlocks[$bmId][$hari]++;
                        $lastBMId = $bmId;
                    } else {
                        $lastBMId = null;
                    }
                }
            }

            foreach ($mapelDaily as $bmId => $days) {
                $jtm = $bebanMap[$bmId] ?? 0;
                if ($jtm === 0) continue;
                $distribution = array_values($days);
                rsort($distribution);
                $violation = false;

                foreach ($mapelBlocks[$bmId] as $blocks) {
                    if ($blocks > 1) { $violation = true; break; }
                }
                if (!$violation) {
                    $valid = match ($jtm) {
                        1 => $distribution === [1],
                        2 => $distribution === [2],
                        3 => $distribution === [3] || $distribution === [2, 1],
                        4 => $distribution === [2, 2],
                        5 => $distribution === [3, 2] || $distribution === [2, 2, 1],
                        6 => $distribution === [3, 3] || $distribution === [2, 2, 2],
                        default => true,
                    };
                    if (!$valid) $violation = true;
                }
                if ($violation) $pelanggar[] = ['kelasId' => $kId, 'bmId' => $bmId, 'jtm' => $jtm];
            }
        }
        return $pelanggar;
    }

    /**
     * Fase perbaikan: coba perbaiki pelanggaran JTM dengan memindahkan blok utuh.
     */
    private function perbaikiSolusi($jadwal, $kelasIds, $bebanMap, $waktuMulai, $batasWaktu)
    {
        $terbaik = $jadwal;
        $biayaTerbaik = $this->hitungHardPenalti($terbaik, $kelasIds, $bebanMap);
        if ($biayaTerbaik === 0) return $terbaik;

        // Perbaikan JTM terarah: tempatkan ulang mapel yang melanggar
        $pelanggar = $this->deteksiPelanggarJTM($terbaik, $kelasIds, $bebanMap);
        shuffle($pelanggar);
        foreach ($pelanggar as $p) {
            if ((time() - $waktuMulai) >= $batasWaktu - 10) break;
            $kandidat = $this->perbaikiBebanMapel($terbaik, $p['kelasId'], $p['bmId'], $p['jtm'], $kelasIds);
            $biaya = $this->hitungHardPenalti($kandidat, $kelasIds, $bebanMap);
            if ($biaya < $biayaTerbaik) {
                $terbaik = $kandidat;
                $biayaTerbaik = $biaya;
            }
            if ($biayaTerbaik === 0) return $terbaik;
        }

        $maxPerbaikan = 8000;
        for ($i = 0; $i < $maxPerbaikan; $i++) {
            if ($i % 200 === 0 && (time() - $waktuMulai) >= $batasWaktu - 5) break;
            if ($biayaTerbaik === 0) break;

            $roll = mt_rand(1, 100);
            if ($roll <= 30) {
                $pelanggar = $this->deteksiPelanggarJTM($terbaik, $kelasIds, $bebanMap);
                if (!empty($pelanggar)) {
                    $p = $pelanggar[array_rand($pelanggar)];
                    $kandidat = $this->perbaikiBebanMapel($terbaik, $p['kelasId'], $p['bmId'], $p['jtm'], $kelasIds);
                } else {
                    $kandidat = $this->swapBlokMapel($terbaik, $kelasIds);
                }
            } elseif ($roll <= 55) {
                $kandidat = $this->swapBlokMapel($terbaik, $kelasIds);
            } elseif ($roll <= 80) {
                $kandidat = $this->rebalanceGuru($terbaik, $kelasIds);
            } else {
                $kandidat = $this->buatTetangga($terbaik, $kelasIds);
            }

            $biaya = $this->hitungHardPenalti($kandidat, $kelasIds, $bebanMap);
            if ($biaya < $biayaTerbaik) {
                $terbaik = $kandidat;
                $biayaTerbaik = $biaya;
            }
        }
        return $terbaik;
    }

    private function hitungHardPenalti($jadwal, $kelasIds, $bebanMap) {
        $penalti = 0;
        $guruDailyLoad = [];

        foreach ($this->strukturHari as $hari => $jmlJam) {
            for ($jam = 1; $jam <= $jmlJam; $jam++) {
                $guruHadirJamIni = [];
                foreach ($kelasIds as $kId) {
                    $slot = $jadwal[$hari][$jam][$kId];
                    if ($slot !== null) {
                        $guruId = $slot['guru_id'];

                        if ($hari === 'Jumat' && $jam > 5) $penalti += 2000000;

                        if (!isset($guruDailyLoad[$guruId][$hari])) $guruDailyLoad[$guruId][$hari] = 0;
                        $guruDailyLoad[$guruId][$hari]++;

                        if (isset($guruHadirJamIni[$guruId])) $penalti += 1000000;
                        else $guruHadirJamIni[$guruId] = true;

                        if (isset($this->fastConstraints[$guruId][$hari][$jam])) {
                            if ($this->fastConstraints[$guruId][$hari][$jam] == 0) {
                                $penalti += 1000000;
                            }
                        }
                    }
                }
            }
        }

        foreach ($kelasIds as $kId) {
            $mapelDaily = [];
            $mapelBlocks = [];

            foreach ($this->strukturHari as $hari => $jml) {
                $lastBMId = null;
                for ($j = 1; $j <= $jml; $j++) {
                    $slot = $jadwal[$hari][$j][$kId];
                    if ($slot !== null) {
                        $bmId = $slot['beban_mengajar_id'];
                        if (!isset($mapelDaily[$bmId][$hari])) {
                            $mapelDaily[$bmId][$hari] = 0;
                            $mapelBlocks[$bmId][$hari] = 0;
                        }
                        $mapelDaily[$bmId][$hari]++;

                        if ($bmId !== $lastBMId) $mapelBlocks[$bmId][$hari]++;
                        $lastBMId = $bmId;
                    } else {
                        $lastBMId = null;
                    }
                }
            }

            foreach ($mapelDaily as $bmId => $days) {
                $jtm = $bebanMap[$bmId] ?? 0;
                if ($jtm === 0) continue;
                $distribution = array_values($days);
                rsort($distribution);

                foreach ($mapelBlocks[$bmId] as $hari => $blocks) {
                    if ($blocks > 1) $penalti += 1000000;
                }

                if ($jtm == 1) {
                    if ($distribution !== [1]) $penalti += 1000000;
                } elseif ($jtm == 2) {
                    if ($distribution !== [2]) $penalti += 1000000;
                } elseif ($jtm == 3) {
                    if ($distribution !== [3] && $distribution !== [2, 1]) $penalti += 1000000;
                } elseif ($jtm == 4) {
                    if ($distribution !== [2, 2]) $penalti += 1000000;
                } elseif ($jtm == 5) {
                    if ($distribution !== [3, 2] && $distribution !== [2, 2, 1]) $penalti += 1000000;
                } elseif ($jtm == 6) {
                    if ($distribution !== [3, 3] && $distribution !== [2, 2, 2]) $penalti += 1000000;
                }
            }
        }

        // Fatigue: penalty setara hard constraint, skala progresif
        foreach ($guruDailyLoad as $guruId => $days) {
            foreach ($days as $hari => $count) {
                if ($count >= 8) {
                    $penalti += 1000000 + (($count - 8) * 200000);
                }
            }
        }

        return $penalti;
    }
}
