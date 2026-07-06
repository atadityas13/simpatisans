<?php
/**
 * Analisis feasibility jadwal dari dump SQL.
 * Usage: php scripts/analyze_feasibility.php "path/to/dump.sql" [semester_id]
 */

$sqlFile = $argv[1] ?? '';
$semesterId = (int) ($argv[2] ?? 1);

if ($sqlFile === '' || !is_file($sqlFile)) {
    fwrite(STDERR, "Usage: php analyze_feasibility.php <dump.sql> [semester_id]\n");
    exit(1);
}

$content = file_get_contents($sqlFile);

function parseInsertRows(string $content, string $table): array
{
    $pattern = '/INSERT INTO `' . preg_quote($table, '/') . '`\s*\([^)]+\)\s*VALUES\s*(.+?);/s';
    if (!preg_match($pattern, $content, $m)) {
        return [];
    }
    $blob = $m[1];
    preg_match_all('/\(([^)]*(?:\'[^\']*\'[^)]*)*)\)/', $blob, $matches);
    $rows = [];
    foreach ($matches[1] as $rowStr) {
        $fields = str_getcsv($rowStr, ',', "'", '\\');
        $rows[] = array_map(static function ($v) {
            $v = trim($v);
            if ($v === 'NULL') {
                return null;
            }
            return $v;
        }, $fields);
    }
    return $rows;
}

$bebanRows = parseInsertRows($content, 'beban_mengajars');
$guruRows = parseInsertRows($content, 'gurus');
$mapelRows = parseInsertRows($content, 'mapels');
$kelasRows = parseInsertRows($content, 'kelas');
$constraintRows = parseInsertRows($content, 'guru_constraints');

$gurus = [];
foreach ($guruRows as $r) {
    $gurus[(int) $r[0]] = ['kode' => $r[2], 'nama' => $r[5]];
}

$mapels = [];
foreach ($mapelRows as $r) {
    $mapels[(int) $r[0]] = $r[1];
}

$kelas = [];
foreach ($kelasRows as $r) {
    $kelas[(int) $r[0]] = ['nama' => $r[1], 'tingkat' => $r[2]];
}

$blocked = [];
foreach ($constraintRows as $r) {
    if ((int) $r[4] !== 0) {
        continue;
    }
    $gid = (int) $r[1];
    $h = ucfirst(strtolower(trim($r[2])));
    $blocked[$gid][$h][(int) $r[3]] = true;
}

$strukturHari = ['Senin' => 9, 'Selasa' => 10, 'Rabu' => 10, 'Kamis' => 10, 'Jumat' => 5];
$totalSlots = array_sum($strukturHari) * count($kelas);
$maxJamHari = 7;
$maxJamMinggu = $maxJamHari * count($strukturHari);

$units = [];
foreach ($bebanRows as $r) {
    if ((int) $r[1] !== $semesterId || (int) $r[6] !== 1) {
        continue;
    }
    $mapelId = (int) $r[3];
    $nama = $mapels[$mapelId] ?? '?';
    $n = strtolower($nama);
    $units[] = [
        'id' => (int) $r[0],
        'guru_id' => (int) $r[2],
        'mapel_id' => $mapelId,
        'mapel' => $nama,
        'kelas_id' => (int) $r[4],
        'jtm' => (int) $r[5],
        'btq' => str_contains($n, 'btq') || str_contains($n, 'baca tulis'),
    ];
}

$totalJtm = array_sum(array_column($units, 'jtm'));
$guruLoad = [];
$kelasLoad = [];
foreach ($units as $u) {
    $guruLoad[$u['guru_id']] = ($guruLoad[$u['guru_id']] ?? 0) + $u['jtm'];
    $kelasLoad[$u['kelas_id']] = ($kelasLoad[$u['kelas_id']] ?? 0) + $u['jtm'];
}

echo "=== ANALISIS FEASIBILITY JADWAL MTs MAJA ===\n";
echo "Semester ID: {$semesterId}\n";
echo "Kelas: " . count($kelas) . " | Kapasitas slot: {$totalSlots} | Total JTM KBM: {$totalJtm}\n\n";

$issues = [];

if ($totalJtm !== $totalSlots) {
    $issues[] = ['KRITIS', 'KAPASITAS', "Total JTM ({$totalJtm}) != kapasitas grid ({$totalSlots}), selisih " . ($totalSlots - $totalJtm)];
}

foreach ($kelas as $kid => $k) {
    $load = $kelasLoad[$kid] ?? 0;
    if ($load !== 44) {
        $issues[] = ['KRITIS', 'KELAS', "{$k['nama']}: beban {$load}/44 jam"];
    }
}

echo "--- 1. PER GURU (total jam & slot tersedia vs preset blokir) ---\n";
echo str_pad('Guru', 6) . str_pad('JTM', 5) . str_pad('Blok', 5) . str_pad('Tersedia', 10) . str_pad('Max7x5', 8) . " Status\n";
echo str_repeat('-', 55) . "\n";

$guruProblems = [];
foreach ($guruLoad as $gid => $load) {
    $kode = $gurus[$gid]['kode'] ?? "?{$gid}";
    $blockedCount = 0;
    $available = 0;
    foreach ($strukturHari as $hari => $max) {
        for ($j = 1; $j <= $max; $j++) {
            if (isset($blocked[$gid][$hari][$j])) {
                $blockedCount++;
            } else {
                $available++;
            }
        }
    }

    $status = 'OK';
    if ($load > $maxJamMinggu) {
        $status = 'MUSTAHIL (>35 jam/minggu)';
        $guruProblems[] = [$kode, "total {$load} jam > max {$maxJamMinggu} dengan aturan 7 jam/hari"];
        $issues[] = ['KRITIS', 'GURU_BEBAN', "{$kode}: {$load} jam/minggu > {$maxJamMinggu}"];
    } elseif ($available < $load) {
        $status = 'MUSTAHIL (slot blokir > beban)';
        $guruProblems[] = [$kode, "butuh {$load} slot, tersedia {$available} ({$blockedCount} diblokir)"];
        $issues[] = ['KRITIS', 'GURU_BLOKIR', "{$kode}: butuh {$load} slot, hanya {$available} tidak diblokir"];
    } elseif ($load > 28 && $blockedCount > 10) {
        $status = 'SULIT (padat+blokir)';
    }

    echo str_pad($kode, 6) . str_pad((string) $load, 5) . str_pad((string) $blockedCount, 5)
        . str_pad((string) $available, 10) . str_pad((string) $maxJamMinggu, 8) . " {$status}\n";
}

echo "\n--- 2. BTQ (Jumat jam 5) ---\n";
$btqUnits = array_filter($units, fn($u) => $u['btq']);
$btqByGuru = [];
foreach ($btqUnits as $u) {
    $btqByGuru[$u['guru_id']][] = $u;
}
foreach ($btqByGuru as $gid => $list) {
    $kode = $gurus[$gid]['kode'] ?? "?";
    $classes = count($list);
    echo "{$kode}: BTQ di {$classes} kelas (Jumat jam 5) — ";
    if ($classes > 1) {
        echo "OK (1 slot/kelas, guru sama di jam berbeda... bentrok!)\n";
        if ($classes > 1) {
            $issues[] = ['KRITIS', 'BTQ', "{$kode} mengajar BTQ di {$classes} kelas — hanya 1 slot Jumat jam 5 per kelas OK, tapi guru bentrok jika paralel"];
        }
    } else {
        echo "OK\n";
    }
}
echo "Total kelas BTQ: " . count($btqUnits) . " (harus 18 jika semua kelas punya BTQ)\n";
if (count($btqUnits) < count($kelas)) {
    $issues[] = ['INFO', 'BTQ', 'Tidak semua kelas punya BTQ di KBM: ' . count($btqUnits) . '/' . count($kelas)];
}

echo "\n--- 3. GURU DENGAN PRESET BLOKIR EKSTREM ---\n";
foreach ([29 => 'KK', 34 => 'EV', 35 => 'IQ', 36 => 'IR'] as $gid => $kode) {
    if (!isset($guruLoad[$gid])) {
        continue;
    }
    $load = $guruLoad[$gid];
    $bc = 0;
    $avail = 0;
    $byDay = [];
    foreach ($strukturHari as $hari => $max) {
        $byDay[$hari] = ['avail' => 0, 'block' => 0];
        for ($j = 1; $j <= $max; $j++) {
            if (isset($blocked[$gid][$hari][$j])) {
                $bc++;
                $byDay[$hari]['block']++;
            } else {
                $avail++;
                $byDay[$hari]['avail']++;
            }
        }
    }
    echo "{$kode}: JTM={$load}, blokir={$bc}, tersedia={$avail} | ";
    $dayInfo = [];
    foreach ($byDay as $h => $d) {
        if ($d['block'] > 0) {
            $dayInfo[] = "{$h}:{$d['block']}blok/{$d['avail']}free";
        }
    }
    echo implode(', ', $dayInfo) . "\n";

    // Can fit with max 7/day?
    $minDays = (int) ceil($load / $maxJamHari);
    $daysWithCapacity = 0;
    $capacitySum = 0;
    foreach ($byDay as $d) {
        $cap = min($d['avail'], $maxJamHari);
        if ($cap > 0) {
            $daysWithCapacity++;
            $capacitySum += $cap;
        }
    }
    if ($capacitySum < $load) {
        $issues[] = ['KRITIS', 'GURU_KAPASITAS', "{$kode}: kapasitas efektif {$capacitySum} jam (dengan max 7/hari) < beban {$load}"];
        echo "  → MUSTAHIL muat dengan max 7 jam/hari + preset (kapasitas efektif {$capacitySum})\n";
    } else {
        echo "  → Muat teoretis (min {$minDays} hari, kapasitas efektif {$capacitySum})\n";
    }
}

echo "\n--- 4. RELAKSASI ATURAN (estimasi) ---\n";
$scenarios = [
    'A' => '792/792 + bentrok saja (no preset, no JTM, no 7h)',
    'B' => '792/792 + preset + 7h + BTQ (no JTM struktur)',
    'C' => '792/792 + SEMUA aturan',
];

echo "Skenario A (fill only): ";
$okA = ($totalJtm === $totalSlots);
foreach ($guruLoad as $gid => $load) {
    if ($load > 44) {
        $okA = false;
    }
}
echo ($okA ? "FEASIBLE (pre-check OK)\n" : "INFEASIBLE\n");

echo "Skenario B (+ preset, 7h, BTQ): ";
$okB = $okA;
foreach ($issues as $iss) {
    if ($iss[0] === 'KRITIS' && in_array($iss[1], ['GURU_BEBAN', 'GURU_BLOKIR', 'GURU_KAPASITAS'], true)) {
        $okB = false;
    }
}
echo ($okB ? "FEASIBLE (pre-check OK, perlu solver untuk bukti pasti)\n" : "INFEASIBLE atau SANGAT SULIT\n");

echo "Skenario C (+ struktur JTM): ";
echo ($okB ? "MUNGKIN (JTM menambah constraint, perlu CP-SAT — pre-check tidak menolak)\n" : "INFEASIBLE (selesaikan masalah guru dulu)\n");

echo "\n--- 5. RINGKASAN MASALAH KRITIS ---\n";
$crit = array_filter($issues, fn($i) => $i[0] === 'KRITIS');
if (empty($crit)) {
    echo "Tidak ada pelanggaran kondisi perlu di pre-check.\n";
    echo "Kesimpulan pre-check: jadwal 792/792 + aturan B/C KEMUNGKINAN BESAR ADA.\n";
    echo "Perlu CP-SAT/OR-Tools untuk bukti 100% dan contoh jadwal ideal.\n";
} else {
    foreach ($crit as $i) {
        echo "[{$i[1]}] {$i[2]}\n";
    }
    echo "\nKesimpulan pre-check: ada bottleneck data — perbaiki beban/preset sebelum expect 100%.\n";
}

echo "\n--- 6. MAPel HAMPIR PASTI SULIT (guru padat) ---\n";
$heavy = array_filter($guruLoad, fn($l) => $l >= 30);
arsort($heavy);
foreach ($heavy as $gid => $load) {
    $kode = $gurus[$gid]['kode'] ?? '?';
    echo "  {$kode}: {$load} jam/minggu\n";
}

echo "\nTotal unit KBM: " . count($units) . "\n";
echo "Selesai.\n";
