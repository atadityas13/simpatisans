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
                'critical_warnings' => 0,
                'info_warnings' => 0,
                'health_score' => 100,
                'unassigned_kelas_mapel' => 0,
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

        // 6. Deteksi Pelanggaran Struktur JTM
        $strukturDistSalah = [];
        foreach ($bebanUsage as $bmId => $items) {
            $beban = $items->first()->bebanMengajar;
            $jtm = $beban->jtm;
            $hGroups = $items->groupBy('hari');
            $dist = [];
            foreach ($hGroups as $hItems) {
                $dist[] = count($hItems);
            }
            rsort($dist);

            $mapelName = $beban->mapel->nama_mapel;
            $kelasName = $beban->kelas->nama_kelas;
            $guruName = $beban->guru->kode_guru;
            $errorPrefix = "Mapel <b>{$mapelName}</b> ({$guruName}) di <b>{$kelasName}</b>";

            $distOk = match ($jtm) {
                1 => $dist === [1],
                2 => $dist === [2],
                3 => $dist === [3] || $dist === [2, 1],
                4 => $dist === [2, 2],
                5 => $dist === [3, 2] || $dist === [2, 2, 1],
                6 => $dist === [3, 3] || $dist === [2, 2, 2],
                default => true,
            };

            if (!$distOk) {
                $msg = match ($jtm) {
                    1 => "{$errorPrefix} JTM 1 harus 1 jam.",
                    2 => "{$errorPrefix} JTM 2 <b>HARUS</b> digabung 2 jam (Tidak boleh split hari).",
                    3 => "{$errorPrefix} JTM 3 harus dipecah 3 jam atau 2+1.",
                    4 => "{$errorPrefix} JTM 4 <b>HARUS</b> dipecah 2+2 jam.",
                    5 => "{$errorPrefix} JTM 5 harus dipecah 3+2 atau 2+2+1.",
                    6 => "{$errorPrefix} JTM 6 harus dipecah 3+3 atau 2+2+2.",
                    default => "{$errorPrefix} struktur JTM tidak sesuai.",
                };
                $analisa['struktur_jtm'][] = $msg;
                $strukturDistSalah[$bmId] = true;
            }

            if (isset($strukturDistSalah[$bmId])) {
                continue;
            }

            foreach ($hGroups as $hari => $hItems) {
                if (count($hItems) <= 1) {
                    continue;
                }
                $jams = $hItems->pluck('jam_ke')->sort()->values()->toArray();
                for ($i = 0; $i < count($jams) - 1; $i++) {
                    if ($jams[$i + 1] - $jams[$i] > 1) {
                        $analisa['struktur_jtm'][] = "{$errorPrefix} di hari <b>{$hari}</b> terpisah jam (Tidak Blok).";
                        break;
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

        // 7. Ringkasan: pisah masalah kritis vs penanda kualitas
        $critical = count($analisa['bentrok']) + count($analisa['kelebihan_jtm'])
            + count($analisa['belum_terisi']) + count($analisa['invalid_slots'])
            + count($analisa['aturan_btq']);
        $info = count($analisa['pelanggaran_ketentuan']) + count($analisa['struktur_jtm'])
            + count($analisa['fatigue']);

        $analisa['summary']['critical_warnings'] = $critical;
        $analisa['summary']['info_warnings'] = $info;
        $analisa['summary']['total_warnings'] = $critical + $info;
        $analisa['summary']['unassigned_kelas_mapel'] = count($analisa['belum_terisi']);
        $analisa['summary']['health_score'] = max(0, 100 - ($critical * 8) - min($info, 20));

        return $analisa;
    }

    /**
     * Validasi penempatan slot (batch) — mirror aturan analisa.
     *
     * @param  array<int, array{hari: string, jam_ke: int, kelas_id: int, beban_mengajar_id: int}>  $placements
     * @return array{warnings: array<int, array{level: string, code: string, message: string}>, has_critical: bool}
     */
    public function validatePlacements(int $semesterId, array $placements): array
    {
        $warnings = [];
        $seen = [];

        if (empty($placements)) {
            return ['warnings' => [], 'has_critical' => false];
        }

        $beban = BebanMengajar::with(['guru', 'mapel', 'kelas'])->find($placements[0]['beban_mengajar_id'] ?? null);
        if (!$beban) {
            return [
                'warnings' => [['level' => 'critical', 'code' => 'invalid', 'message' => 'Beban mengajar tidak ditemukan.']],
                'has_critical' => true,
            ];
        }

        $coordKeys = [];
        foreach ($placements as $p) {
            $h = $this->normalizeHari($p['hari']);
            $coordKeys["{$h}-{$p['jam_ke']}-{$p['kelas_id']}"] = true;
        }

        $existingBebanJadwals = Jadwal::where('semester_id', $semesterId)
            ->where('beban_mengajar_id', $beban->id)
            ->with('bebanMengajar')
            ->get();

        $removedFromBeban = $existingBebanJadwals->filter(function ($j) use ($coordKeys) {
            $key = $this->normalizeHari($j->hari) . "-{$j->jam_ke}-{$j->bebanMengajar->kelas_id}";
            return isset($coordKeys[$key]);
        });

        $newCount = $existingBebanJadwals->count() - $removedFromBeban->count() + count($placements);
        $prefix = "Mapel {$beban->mapel->nama_mapel} ({$beban->guru->kode_guru}) di {$beban->kelas->nama_kelas}";

        if ($newCount > $beban->jtm) {
            $this->pushWarning($warnings, $seen, 'critical', 'kelebihan_jtm',
                "{$prefix}: melebihi JTM ({$newCount}/{$beban->jtm} jam di kelas ini).");
        }

        $blockedLookup = [];
        foreach (GuruConstraint::where('guru_id', $beban->guru_id)->where('type', 0)->get() as $c) {
            $blockedLookup[$this->normalizeHari($c->hari) . "-{$c->jam_ke}"] = true;
        }

        foreach ($placements as $p) {
            $hari = $this->normalizeHari($p['hari']);
            $jam = (int) $p['jam_ke'];
            $kelasId = (int) $p['kelas_id'];
            $maxJam = $this->strukturHari[$hari] ?? 10;

            if ($jam > $maxJam) {
                $this->pushWarning($warnings, $seen, 'critical', 'invalid_slot',
                    "Jam ke-{$jam} di {$hari} di luar jam operasional (maks {$maxJam}).");
            }

            if (isset($blockedLookup["{$hari}-{$jam}"])) {
                $this->pushWarning($warnings, $seen, 'info', 'preset',
                    "Guru [{$beban->guru->kode_guru}] preset DIBLOKIR di {$hari} jam ke-{$jam}.");
            }

            $bentrok = Jadwal::where('semester_id', $semesterId)
                ->where('hari', $hari)
                ->where('jam_ke', $jam)
                ->whereHas('bebanMengajar', fn ($q) => $q->where('guru_id', $beban->guru_id))
                ->whereHas('bebanMengajar', fn ($q) => $q->where('kelas_id', '!=', $kelasId))
                ->with('bebanMengajar.kelas')
                ->first();

            if ($bentrok) {
                $this->pushWarning($warnings, $seen, 'critical', 'bentrok',
                    "Guru [{$beban->guru->kode_guru}] bentrok: juga mengajar di {$bentrok->bebanMengajar->kelas->nama_kelas} pada {$hari} jam ke-{$jam}.");
            }
        }

        // Simulasi slot beban setelah penempatan
        $simulated = $existingBebanJadwals->reject(function ($j) use ($coordKeys) {
            $key = $this->normalizeHari($j->hari) . "-{$j->jam_ke}-{$j->bebanMengajar->kelas_id}";
            return isset($coordKeys[$key]);
        })->map(fn ($j) => ['hari' => $this->normalizeHari($j->hari), 'jam_ke' => (int) $j->jam_ke]);

        foreach ($placements as $p) {
            $simulated->push([
                'hari' => $this->normalizeHari($p['hari']),
                'jam_ke' => (int) $p['jam_ke'],
            ]);
        }

        $hGroups = $simulated->groupBy('hari');
        $dist = $hGroups->map->count()->values()->sort()->reverse()->values()->toArray();
        $jtm = (int) $beban->jtm;

        $distOk = match ($jtm) {
            1 => $dist === [1],
            2 => $dist === [2],
            3 => $dist === [3] || $dist === [2, 1],
            4 => $dist === [2, 2],
            5 => $dist === [3, 2] || $dist === [2, 2, 1],
            6 => $dist === [3, 3] || $dist === [2, 2, 2],
            default => true,
        };

        if (!$distOk && $newCount > 0) {
            $msg = match ($jtm) {
                2 => "{$prefix}: JTM 2 HARUS digabung 2 jam (tidak boleh split hari).",
                3 => "{$prefix}: JTM 3 harus dipecah 3 jam atau 2+1.",
                4 => "{$prefix}: JTM 4 HARUS dipecah 2+2 jam.",
                5 => "{$prefix}: JTM 5 harus dipecah 3+2 atau 2+2+1.",
                6 => "{$prefix}: JTM 6 harus dipecah 3+3 atau 2+2+2.",
                default => "{$prefix}: struktur pembagian JTM belum sesuai.",
            };
            $this->pushWarning($warnings, $seen, 'info', 'struktur_jtm', $msg);
        } else {
            foreach ($hGroups as $hari => $items) {
                if ($items->count() <= 1) {
                    continue;
                }
                $jams = $items->pluck('jam_ke')->sort()->values()->toArray();
                for ($i = 0; $i < count($jams) - 1; $i++) {
                    if ($jams[$i + 1] - $jams[$i] > 1) {
                        $this->pushWarning($warnings, $seen, 'info', 'struktur_jtm',
                            "{$prefix} di {$hari} terpisah jam (tidak blok).");
                        break;
                    }
                }
            }
        }

        // BTQ
        if ($this->isMapelBtq($beban->mapel->nama_mapel ?? '')) {
            $ok = $simulated->count() === $jtm
                && $simulated->every(fn ($s) => $s['hari'] === 'Jumat')
                && $simulated->max('jam_ke') === 5
                && $simulated->pluck('jam_ke')->sort()->values()->toArray() === range(6 - $jtm, 5);

            if (!$ok && $newCount > 0) {
                $this->pushWarning($warnings, $seen, 'critical', 'btq',
                    "{$prefix}: BTQ wajib di hari Jumat jam ke-5 (jam pelajaran terakhir).");
            }
        }

        // Kelelahan guru per hari yang terdampak
        $affectedDays = collect($placements)->map(fn ($p) => $this->normalizeHari($p['hari']))->unique();
        foreach ($affectedDays as $hari) {
            $jams = Jadwal::where('semester_id', $semesterId)
                ->where('hari', $hari)
                ->whereHas('bebanMengajar', fn ($q) => $q->where('guru_id', $beban->guru_id))
                ->with('bebanMengajar')
                ->get()
                ->map(fn ($j) => [
                    'jam' => (int) $j->jam_ke,
                    'kelas_id' => $j->bebanMengajar->kelas_id,
                ]);

            foreach ($placements as $p) {
                if ($this->normalizeHari($p['hari']) !== $hari) {
                    continue;
                }
                $jams = $jams->reject(fn ($item) => $item['jam'] === (int) $p['jam_ke'] && $item['kelas_id'] === (int) $p['kelas_id']);
                $jams->push(['jam' => (int) $p['jam_ke'], 'kelas_id' => (int) $p['kelas_id']]);
            }

            $maxJam = $this->strukturHari[$hari] ?? 10;
            $validHours = $jams->pluck('jam')->unique()->filter(fn ($j) => $j <= $maxJam)->count();

            if ($validHours >= 8) {
                $this->pushWarning($warnings, $seen, 'info', 'fatigue',
                    "Guru [{$beban->guru->kode_guru}] mengajar {$validHours} jam di {$hari} (≥8 jam/hari).");
            }
        }

        $hasCritical = collect($warnings)->contains(fn ($w) => $w['level'] === 'critical');

        return ['warnings' => array_values($warnings), 'has_critical' => $hasCritical];
    }

    /**
     * Terapkan penempatan slot batch (dalam transaksi).
     *
     * @param  array<int, array{hari: string, jam_ke: int, kelas_id: int, beban_mengajar_id: int}>  $placements
     */
    public function applyPlacements(int $semesterId, array $placements): void
    {
        \DB::transaction(function () use ($semesterId, $placements) {
            foreach ($placements as $p) {
                $hari = $this->normalizeHari($p['hari']);
                $jam = (int) $p['jam_ke'];
                $kelasId = (int) $p['kelas_id'];
                $bebanId = (int) $p['beban_mengajar_id'];

                $jadwal = Jadwal::where('semester_id', $semesterId)
                    ->where('hari', $hari)
                    ->where('jam_ke', $jam)
                    ->whereHas('bebanMengajar', fn ($q) => $q->where('kelas_id', $kelasId))
                    ->first();

                if ($jadwal) {
                    $jadwal->update(['beban_mengajar_id' => $bebanId]);
                } else {
                    Jadwal::create([
                        'semester_id' => $semesterId,
                        'hari' => $hari,
                        'jam_ke' => $jam,
                        'beban_mengajar_id' => $bebanId,
                    ]);
                }
            }
        });
    }

    private function normalizeHari(string $hari): string
    {
        return ucfirst(strtolower(trim($hari)));
    }

    /** @param  array<int, array{level: string, code: string, message: string}>  $warnings */
    private function pushWarning(array &$warnings, array &$seen, string $level, string $code, string $message): void
    {
        $key = $level . ':' . $code . ':' . $message;
        if (isset($seen[$key])) {
            return;
        }
        $seen[$key] = true;
        $warnings[] = ['level' => $level, 'code' => $code, 'message' => $message];
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
