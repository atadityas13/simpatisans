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
    private $fastConstraints = []; // Fast lookup [guru_id][hari][jam_ke] = type
    private $lockedSlots = []; // [Hari][Jam][KelasId] = true;

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

        // Memuat Batasan Guru (Constraints bersifat global, namun bisa difilter jika nanti ada semester_id di constraints)
        $rawConstraints = GuruConstraint::all();
        $this->constraints = $rawConstraints->groupBy('guru_id');
        $this->fastConstraints = [];
        foreach ($rawConstraints as $c) {
            $this->fastConstraints[$c->guru_id][$c->hari][$c->jam_ke] = $c->type;
        }
        $this->lockedSlots = [];

        $jadwalAwal = $this->buatJadwalKosong($kelasIds);

        // Pencacahan slot tugas
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
        $solusiSekarang = $this->sebarkanTugasAcak($jadwalAwal, $slotTugas, $kelasIds);
        $biayaSekarang = $this->hitungHardPenalti($solusiSekarang, $kelasIds, $bebanMap);

        $solusiTerbaik = $solusiSekarang;
        $biayaTerbaik = $biayaSekarang;

        $suhu = $this->suhuAwal;
        for ($i = 0; $i < $this->maxIterasi; $i++) {
            if ($suhu < 0.01 || $biayaTerbaik === 0) break;

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

        // Hapus secara agresif via DB Table (Deep Clean) untuk semester ini
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

    private function buatJadwalKosong($kelasIds) {
        $jadwal = [];
        foreach ($this->strukturHari as $hari => $jml) {
            for ($jam = 1; $jam <= $jml; $jam++) {
                foreach ($kelasIds as $kId) $jadwal[$hari][$jam][$kId] = null;
            }
        }
        return $jadwal;
    }

    private function sebarkanTugasAcak($jadwal, $slotTugas, $kelasIds) {
        // TAHAP 0: GRUP TUGAS MENJADI BLOK (V2.5)
        $bebanGroups = [];
        foreach ($slotTugas as $tugas) {
            $bebanGroups[$tugas['beban_mengajar_id']][] = $tugas;
        }

        // TAHAP 1: IMPLEMENTASI PRESERVE (KUNCI GURU) - Prioritas Tinggi
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

        // TAHAP 1.5: PERBAIKAN BLOK PARSIAL (V2.6 FIX)
        // Jika ada grup yang sudah punya 1 slot preserve tapi masih ada sisa,
        // tempatkan sisa slot bersebelahan agar tetap nempel.
        foreach ($bebanGroups as $bmId => &$tugasList) {
            if (empty($tugasList)) continue;
            $kelasId = $tugasList[0]['kelas_id'];

            // Cari slot yang sudah ditempatkan untuk grup ini
            $placedSlots = [];
            foreach ($this->strukturHari as $hari => $jml) {
                for ($j = 1; $j <= $jml; $j++) {
                    $slot = $jadwal[$hari][$j][$kelasId];
                    if ($slot !== null && $slot['beban_mengajar_id'] == $bmId) {
                        $placedSlots[] = ['hari' => $hari, 'jam' => $j];
                    }
                }
            }

            // Jika ada yang sudah ditempatkan dan masih ada sisa, tempatkan bersebelahan
            if (!empty($placedSlots) && !empty($tugasList)) {
                foreach ($placedSlots as $placed) {
                    $hari = $placed['hari'];
                    $jam = $placed['jam'];
                    $maxJam = $this->strukturHari[$hari];
                    // Coba di jam berikutnya
                    if ($jam + 1 <= $maxJam && $jadwal[$hari][$jam + 1][$kelasId] === null) {
                        $jadwal[$hari][$jam + 1][$kelasId] = array_shift($tugasList);
                        if (empty($tugasList)) break;
                    }
                    // Coba di jam sebelumnya
                    if (!empty($tugasList) && $jam - 1 >= 1 && $jadwal[$hari][$jam - 1][$kelasId] === null) {
                        $jadwal[$hari][$jam - 1][$kelasId] = array_shift($tugasList);
                        if (empty($tugasList)) break;
                    }
                }
            }
        }
        unset($tugasList);

        // TAHAP 2: SEBAR BLOK (NON-PRESERVED)
        foreach ($bebanGroups as $bmId => $tugasList) {
            if (empty($tugasList)) continue;
            
            $jtm = count($tugasList);
            $kelasId = $tugasList[0]['kelas_id'];
            
            // Tentukan Ukuran Blok Berdasarkan JTM sisa
            $blocks = [];
            if ($jtm == 1) $blocks = [1];
            else if ($jtm == 2) $blocks = [2];
            else if ($jtm == 3) $blocks = [3];
            else if ($jtm == 4) $blocks = [2, 2];
            else if ($jtm == 5) $blocks = [3, 2];
            else $blocks = array_fill(0, ceil($jtm / 2), 2);

            foreach ($blocks as $size) {
                $placed = false;
                $attempts = 0;
                while (!$placed && $attempts < 100) {
                    $hari = array_rand($this->strukturHari);
                    $maxJam = $this->strukturHari[$hari];
                    $startJam = mt_rand(1, $maxJam - $size + 1);
                    
                    $available = true;
                    for ($s = 0; $s < $size; $s++) {
                        if ($jadwal[$hari][$startJam + $s][$kelasId] !== null) {
                            $available = false; break;
                        }
                    }

                    if ($available) {
                        for ($s = 0; $s < $size; $s++) {
                            if (!empty($tugasList)) {
                                $jadwal[$hari][$startJam + $s][$kelasId] = array_shift($tugasList);
                            }
                        }
                        $placed = true;
                    }
                    $attempts++;
                }
                
                // Fallback: taruh eceran jika blok tidak dapat ditempatkan
                if (!$placed) {
                    while (!empty($tugasList)) {
                        $hari = array_rand($this->strukturHari);
                        $jam = mt_rand(1, $this->strukturHari[$hari]);
                        if ($jadwal[$hari][$jam][$kelasId] === null) {
                            $jadwal[$hari][$jam][$kelasId] = array_shift($tugasList);
                        }
                    }
                }
            }
        }
        return $jadwal;
    }

    private function buatTetangga($solusi, $kelasIds) {
        $baru = $solusi;
        $kelasAcak = $kelasIds[array_rand($kelasIds)];
        $hariArr = array_keys($this->strukturHari);
        
        // 40% Chance: Swap Entire Days for this class (Keeps blocks intact)
        if (mt_rand(1, 100) <= 40) {
            $h1 = $hariArr[array_rand($hariArr)];
            $h2 = $hariArr[array_rand($hariArr)];
            if ($h1 != $h2) {
                $max1 = $this->strukturHari[$h1];
                $max2 = $this->strukturHari[$h2];
                $max = max($max1, $max2);

                // Ensure no locked slots in these days for this class
                $locked = false;
                for ($j = 1; $j <= $max; $j++) {
                    if (isset($this->lockedSlots[$h1][$j][$kelasAcak]) || isset($this->lockedSlots[$h2][$j][$kelasAcak])) {
                        $locked = true;
                        break;
                    }
                }

                if (!$locked) {
                    // We only swap the specific class columns across days (IF BOTH DAYS SUPPORT THIS JAM)
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

        // Standard Swap
        $hari1 = $hariArr[array_rand($hariArr)];
        $jam1 = mt_rand(1, $this->strukturHari[$hari1]);
        $hari2 = $hariArr[array_rand($hariArr)];
        $jam2 = mt_rand(1, $this->strukturHari[$hari2]);

        if (isset($this->lockedSlots[$hari1][$jam1][$kelasAcak]) || isset($this->lockedSlots[$hari2][$jam2][$kelasAcak])) {
            return $this->buatTetangga($solusi, $kelasIds);
        }

        $temp = $baru[$hari1][$jam1][$kelasAcak];
        $baru[$hari1][$jam1][$kelasAcak] = $baru[$hari2][$jam2][$kelasAcak];
        $baru[$hari2][$jam2][$kelasAcak] = $temp;
        return $baru;
    }

    private function hitungHardPenalti($jadwal, $kelasIds, $bebanMap) {
        $penalti = 0;
        $guruDailyLoad = []; // [guruId][hari] = count

        foreach ($this->strukturHari as $hari => $jmlJam) {
            for ($jam = 1; $jam <= $jmlJam; $jam++) {
                $guruHadirJamIni = [];
                foreach ($kelasIds as $kId) {
                    $slot = $jadwal[$hari][$jam][$kId];
                    if ($slot !== null) {
                        $guruId = $slot['guru_id'];
                        
                        // 0. Audit Jam Ilegal (Pengaman Extra)
                        if ($hari === 'Jumat' && $jam > 5) $penalti += 2000000;

                        // Fatigue Counter
                        if (!isset($guruDailyLoad[$guruId][$hari])) $guruDailyLoad[$guruId][$hari] = 0;
                        $guruDailyLoad[$guruId][$hari]++;

                        // 1. Hard Constraint: Bentrok Guru (1.000.000)
                        if (isset($guruHadirJamIni[$guruId])) $penalti += 1000000;
                        else $guruHadirJamIni[$guruId] = true;

                        // 2. Hard Constraint: Guru DIBLOKIR (1.000.000)
                        if (isset($this->fastConstraints[$guruId][$hari][$jam])) {
                            if ($this->fastConstraints[$guruId][$hari][$jam] == 0) {
                                $penalti += 1000000;
                            }
                        }
                    }
                }
            }
        }

        // 3. Stuktur JTM (Struktur Jam Tatap Muka) - HARD ENFORCEMENT (1.000.000)
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
                // ELITE OPTIMIZATION: HAPUS DB QUERY (DB CLEAN)
                $jtm = $bebanMap[$bmId] ?? 0;
                if ($jtm === 0) continue;
                $distribution = array_values($days);
                rsort($distribution);

                // ATURAN 1: LARANGAN SAME-DAY SPLIT (HARD 1.000.000)
                foreach ($mapelBlocks[$bmId] as $hari => $blocks) {
                    if ($blocks > 1) $penalti += 1000000;
                }

                // ATURAN 2: STRUKTUR BERDASARKAN JTM (HARD 1.000.000) - ELITE ENFORCEMENT
                if ($jtm == 1) {
                    if ($distribution !== [1]) $penalti += 1000000;
                } elseif ($jtm == 2) {
                    // JTM 2 MUTLAK HARUS [2] (Tidak boleh pecah di hari berbeda atau terpisah jam)
                    if ($distribution !== [2]) $penalti += 1000000;
                } elseif ($jtm == 3) {
                    if ($distribution !== [3] && $distribution !== [2, 1]) $penalti += 1000000;
                } elseif ($jtm == 4) {
                    // JTM 4 MUTLAK HARUS [2, 2]
                    if ($distribution !== [2, 2]) $penalti += 1000000;
                } elseif ($jtm == 5) {
                    if ($distribution !== [3, 2] && $distribution !== [2, 2, 1]) $penalti += 1000000;
                } elseif ($jtm == 6) {
                    if ($distribution !== [3, 3] && $distribution !== [2, 2, 2]) $penalti += 1000000;
                }
            }
        }

        // 4. Fatigue Constraint (>= 8 jam/hari) - 500.000
        foreach ($guruDailyLoad as $guruId => $days) {
            foreach ($days as $hari => $count) {
                if ($count >= 8) $penalti += 500000;
            }
        }

        return $penalti;
    }
}
