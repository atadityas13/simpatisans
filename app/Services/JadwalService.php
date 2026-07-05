<?php

namespace App\Services;

use App\Models\Jadwal;
use App\Models\Guru;
use App\Models\BebanMengajar;
use App\Models\GuruConstraint;

class JadwalService
{
    private $strukturHari = ['Senin' => 9, 'Selasa' => 10, 'Rabu' => 10, 'Kamis' => 10, 'Jumat' => 5];
    /**
     * Jalankan analisa penuh terhadap seluruh data jadwal.
     */
    public function analisaPenuh(int $semesterId): array
    {
        $jadwals = Jadwal::where('semester_id', $semesterId)
            ->with(['bebanMengajar.guru', 'bebanMengajar.mapel', 'bebanMengajar.kelas'])
            ->get();
        $gurus   = Guru::all();

        $analisa = [
            'bentrok' => [],
            'kelebihan_jtm' => [],
            'fatigue' => [],
            'over_blocked' => [],
            'pelanggaran_ketentuan' => [],
            'struktur_jtm' => [],
            'aturan_btq' => [],
            'belum_terisi' => [],
            'invalid_slots' => [],
            'summary' => [
                'total_warnings' => 0,
                'health_score' => 100,
                'unassigned_kelas_mapel' => 0
            ]
        ];

        // 0. Pre-load constraints
        $blockedLookup = [];
        $allBlocked = GuruConstraint::where('type', 0)->get();
        foreach ($allBlocked as $c) {
            $h = ucfirst(strtolower(trim($c->hari)));
            $blockedLookup["{$c->guru_id}-{$h}-{$c->jam_ke}"] = true;
        }

        // 1. Deteksi Pelanggaran Ketentuan
        foreach ($jadwals as $j) {
            $gid = $j->bebanMengajar?->guru_id;
            $h = ucfirst(strtolower(trim($j->hari)));
            
            $maxJam = $this->strukturHari[$h] ?? 10;
            if ($j->jam_ke > $maxJam) {
                $analisa['invalid_slots'][] = [
                    'guru' => $j->bebanMengajar->guru->kode_guru ?? '?',
                    'hari' => $h,
                    'jam' => $j->jam_ke,
                    'kelas' => $j->bebanMengajar->kelas->nama_kelas ?? '?'
                ];
            }

            if ($gid && isset($blockedLookup["{$gid}-{$h}-{$j->jam_ke}"])) {
                $analisa['pelanggaran_ketentuan'][] = [
                    'guru' => $j->bebanMengajar->guru->kode_guru,
                    'hari' => $h,
                    'jam' => $j->jam_ke,
                    'kelas' => $j->bebanMengajar->kelas->nama_kelas
                ];
            }
        }

        // 2. Deteksi Bentrok (Normalize Hari)
        $bentrokGroups = $jadwals->filter(fn($j) => $j->bebanMengajar)->groupBy(fn($j) => ucfirst(strtolower(trim($j->hari))) . "-{$j->jam_ke}-{$j->bebanMengajar->guru_id}");
        foreach ($bentrokGroups as $group) {
            if (count($group) > 1) {
                $first = $group->first();
                $analisa['bentrok'][] = [
                    'guru' => $first->bebanMengajar->guru->kode_guru,
                    'hari' => ucfirst(strtolower(trim($first->hari))),
                    'jam' => $first->jam_ke,
                    'kelas' => $group->map(fn($g) => $g->bebanMengajar->kelas->nama_kelas)->toArray(),
                ];
            }
        }

        // 3. Deteksi Kelebihan JTM (Satu BebanMengajar dipecah ke terlalu banyak slot)
        $bebanUsage = $jadwals->filter(fn($j) => $j->bebanMengajar)->groupBy('beban_mengajar_id');
        foreach ($bebanUsage as $items) {
            $beban = $items->first()->bebanMengajar;
            if ($items->count() > $beban->jtm) {
                $analisa['kelebihan_jtm'][] = [
                    'guru' => $beban->guru->kode_guru,
                    'mapel' => $beban->mapel->nama_mapel,
                    'kelas' => $beban->kelas->nama_kelas,
                    'standar' => $beban->jtm,
                    'aktual' => $items->count()
                ];
            }
        }

        // 4. Deteksi Kelelahan Guru (>= 8 jam/hari) - Menggunakan jam unik
        $dailyLoad = $jadwals->filter(fn($j) => $j->bebanMengajar)->groupBy(fn($j) => ucfirst(strtolower(trim($j->hari))) . "-{$j->bebanMengajar->guru_id}");
        foreach ($dailyLoad as $items) {
            $h = ucfirst(strtolower(trim($items->first()->hari)));
            $maxJam = $this->strukturHari[$h] ?? 10;
            $uniqueValidHours = $items->filter(fn($v) => $v->jam_ke <= $maxJam)->pluck('jam_ke')->unique()->count();

            if ($uniqueValidHours >= 8) {
                $first = $items->first();
                $analisa['fatigue'][] = [
                    'guru' => $first->bebanMengajar->guru->kode_guru,
                    'hari' => $h,
                    'jumlah' => $uniqueValidHours
                ];
            }
        }

        // 5. Deteksi Over-Blocked
        $totalGuruCount = count($gurus);
        if ($totalGuruCount > 0) {
            $blockedGroups = GuruConstraint::where('type', 0)->get()->groupBy(fn($c) => "{$c->hari}-{$c->jam_ke}");
            foreach ($blockedGroups as $key => $group) {
                $count = count($group);
                $ratio = ($count / $totalGuruCount) * 100;
                if ($ratio > 70) {
                    list($h, $j) = explode('-', $key);
                    $analisa['over_blocked'][] = [
                        'hari' => $h,
                        'jam' => $j,
                        'jumlah' => $count,
                        'persen' => round($ratio, 1)
                    ];
                }
            }
        }

        // 6. Deteksi Pelanggaran Struktur JTM - ELITE SYNC
        foreach ($bebanUsage as $bmId => $items) {
            $beban = $items->first()->bebanMengajar;
            $jtm = $beban->jtm;
            $hGroups = $items->groupBy('hari');
            $dist = [];
            foreach ($hGroups as $hari => $hItems) {
                $dist[] = count($hItems);
            }
            rsort($dist);

            $mapelName = $beban->mapel->nama_mapel;
            $kelasName = $beban->kelas->nama_kelas;
            $guruName = $beban->guru->kode_guru;
            $errorPrefix = "Mapel <b>{$mapelName}</b> ({$guruName}) di <b>{$kelasName}</b>";

            if ($jtm == 1 && $dist != [1]) {
                $analisa['struktur_jtm'][] = "{$errorPrefix} JTM 1 harus 1 jam.";
            } elseif ($jtm == 2 && $dist != [2]) {
                $analisa['struktur_jtm'][] = "{$errorPrefix} JTM 2 <b>HARUS</b> digabung 2 jam (Tidak boleh split hari).";
            } elseif ($jtm == 3 && $dist != [3] && $dist != [2, 1]) {
                $analisa['struktur_jtm'][] = "{$errorPrefix} JTM 3 harus dipecah 3 jam atau 2+1.";
            } elseif ($jtm == 4 && $dist != [2, 2]) {
                $analisa['struktur_jtm'][] = "{$errorPrefix} JTM 4 <b>HARUS</b> dipecah 2+2 jam.";
            } elseif ($jtm == 5 && $dist != [3, 2] && $dist != [2, 2, 1]) {
                $analisa['struktur_jtm'][] = "{$errorPrefix} JTM 5 harus dipecah 3+2 atau 2+2+1.";
            } elseif ($jtm == 6 && $dist != [3, 3] && $dist != [2, 2, 2]) {
                $analisa['struktur_jtm'][] = "{$errorPrefix} JTM 6 harus dipecah 3+3 atau 2+2+2.";
            }

            // Check Kontiguitas (Bebas Split di hari yang sama)
            foreach ($hGroups as $hari => $hItems) {
                if (count($hItems) > 1) {
                    $jams = $hItems->pluck('jam_ke')->sort()->values()->toArray();
                    for ($i = 0; $i < count($jams) - 1; $i++) {
                        if ($jams[$i+1] - $jams[$i] > 1) {
                            $analisa['struktur_jtm'][] = "{$errorPrefix} di hari <b>{$hari}</b> terpisah jam (Tidak Blok).";
                        }
                    }
                }
            }
        }

        // 6b. Aturan BTQ — wajib Jumat jam ke-5 (terakhir)
        foreach ($bebanUsage as $bmId => $items) {
            $beban = $items->first()->bebanMengajar;
            if (!$this->isMapelBtq($beban->mapel->nama_mapel ?? '')) {
                continue;
            }
            $mapelName = $beban->mapel->nama_mapel;
            $kelasName = $beban->kelas->nama_kelas;
            $guruName = $beban->guru->kode_guru;
            $prefix = "Mapel <b>{$mapelName}</b> ({$guruName}) di <b>{$kelasName}</b>";

            $jams = $items->map(fn($j) => [
                'hari' => ucfirst(strtolower(trim($j->hari))),
                'jam' => $j->jam_ke,
            ])->values();

            $ok = $jams->count() === $beban->jtm
                && $jams->every(fn($j) => $j['hari'] === 'Jumat')
                && $jams->max('jam') === 5
                && $jams->pluck('jam')->sort()->values()->toArray() === range(6 - $beban->jtm, 5);

            if (!$ok) {
                $analisa['aturan_btq'][] = "{$prefix} <b>wajib</b> di hari <b>Jumat jam ke-5</b> (jam pelajaran terakhir).";
            }
        }

        // 6c. Mapel belum terisi penuh (JTM vs slot jadwal)
        $allBeban = BebanMengajar::where('semester_id', $semesterId)->where('is_satminkal', 1)
            ->with(['guru', 'mapel', 'kelas'])->get();
        foreach ($allBeban as $beban) {
            $placed = $bebanUsage->get($beban->id)?->count() ?? 0;
            if ($placed < $beban->jtm) {
                $analisa['belum_terisi'][] = [
                    'mapel' => $beban->mapel->nama_mapel,
                    'guru' => $beban->guru->kode_guru,
                    'kelas' => $beban->kelas->nama_kelas,
                    'standar' => $beban->jtm,
                    'aktual' => $placed,
                ];
            }
        }

        // 7. Calculate Summary
        $totalProblems = count($analisa['bentrok']) + count($analisa['kelebihan_jtm']) + count($analisa['fatigue'])
            + count($analisa['pelanggaran_ketentuan']) + count($analisa['struktur_jtm']) + count($analisa['aturan_btq'])
            + count($analisa['belum_terisi']) + count($analisa['invalid_slots']);
        $analisa['summary']['total_warnings'] = $totalProblems;
        $analisa['summary']['health_score'] = max(0, 100 - ($totalProblems * 5));

        return $analisa;
    }

    private function isMapelBtq(?string $namaMapel): bool
    {
        if ($namaMapel === null || $namaMapel === '') {
            return false;
        }
        $n = strtolower($namaMapel);
        return str_contains($n, 'btq') || str_contains($n, 'baca tulis');
    }
}
