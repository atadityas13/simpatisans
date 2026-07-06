<?php
/**
 * Uji fill-only 792/792 dari dump SQL (tanpa Laravel).
 * Usage: php scripts/test_offline_schedule.php "path/to/dump.sql"
 */

function parseInsertRows(string $content, string $table): array
{
    $pattern = '/INSERT INTO `' . preg_quote($table, '/') . '`\s*\([^)]+\)\s*VALUES\s*(.+?);/s';
    if (!preg_match($pattern, $content, $m)) {
        return [];
    }
    preg_match_all('/\(([^)]*(?:\'[^\']*\'[^)]*)*)\)/', $m[1], $matches);
    $rows = [];
    foreach ($matches[1] as $rowStr) {
        $fields = str_getcsv($rowStr, ',', "'", '\\');
        $rows[] = array_map(static fn($v) => trim($v) === 'NULL' ? null : trim($v), $fields);
    }
    return $rows;
}

$sqlFile = $argv[1] ?? '';
if ($sqlFile === '' || !is_file($sqlFile)) {
    fwrite(STDERR, "Usage: php test_offline_schedule.php <dump.sql>\n");
    exit(1);
}

$content = file_get_contents($sqlFile);
$strukturHari = ['Senin' => 9, 'Selasa' => 10, 'Rabu' => 10, 'Kamis' => 10, 'Jumat' => 5];

$bebanRows = parseInsertRows($content, 'beban_mengajars');
$mapelRows = parseInsertRows($content, 'mapels');
$kelasRows = parseInsertRows($content, 'kelas');

$mapels = [];
foreach ($mapelRows as $r) {
    $mapels[(int) $r[0]] = $r[1];
}

$kelasIds = [];
foreach ($kelasRows as $r) {
    $kelasIds[] = (int) $r[0];
}
sort($kelasIds);

$units = [];
foreach ($bebanRows as $r) {
    if ((int) $r[1] !== 1 || (int) $r[6] !== 1) {
        continue;
    }
    $nama = $mapels[(int) $r[3]] ?? '';
    $n = strtolower($nama);
    $units[] = [
        'id' => (int) $r[0],
        'guru_id' => (int) $r[2],
        'kelas_id' => (int) $r[4],
        'jtm' => (int) $r[5],
        'btq' => str_contains($n, 'btq') || str_contains($n, 'baca tulis'),
    ];
}

$totalJtm = array_sum(array_column($units, 'jtm'));
echo "=== OFFLINE SCHEDULE TEST ===\n";
echo "Units: " . count($units) . " | Total JTM: {$totalJtm}\n\n";

function runAttempt(array $units, array $kelasIds, array $strukturHari, int $seed): array
{
    $grid = [];
    $guruOcc = [];
    foreach ($strukturHari as $hari => $max) {
        for ($j = 1; $j <= $max; $j++) {
            foreach ($kelasIds as $k) {
                $grid[$hari][$j][$k] = null;
            }
        }
    }

    $list = $units;
    usort($list, function ($a, $b) {
        if ($a['btq'] !== $b['btq']) {
            return $b['btq'] <=> $a['btq'];
        }
        return $b['jtm'] <=> $a['jtm'];
    });
    if ($seed > 0) {
        $n = count($list);
        $list = array_merge(array_slice($list, $seed % $n), array_slice($list, 0, $seed % $n));
    }

    // BTQ first
    foreach ($list as $u) {
        if (!$u['btq']) {
            continue;
        }
        $sisa = $u['jtm'];
        $start = 5 - $sisa + 1;
        for ($j = $start; $j <= 5; $j++) {
            $grid['Jumat'][$j][$u['kelas_id']] = $u['id'];
            $guruOcc['Jumat'][$j][$u['guru_id']] = true;
        }
    }

    foreach ($list as $u) {
        if ($u['btq']) {
            continue;
        }
        $placed = 0;
        while ($placed < $u['jtm']) {
            $ok = false;
            foreach ($strukturHari as $hari => $max) {
                for ($j = 1; $j <= $max; $j++) {
                    if ($grid[$hari][$j][$u['kelas_id']] !== null) {
                        continue;
                    }
                    if (isset($guruOcc[$hari][$j][$u['guru_id']])) {
                        continue;
                    }
                    $grid[$hari][$j][$u['kelas_id']] = $u['id'];
                    $guruOcc[$hari][$j][$u['guru_id']] = true;
                    $placed++;
                    $ok = true;
                    break 2;
                }
            }
            if (!$ok) {
                break;
            }
        }
    }

    $terisi = 0;
    foreach ($grid as $jd) {
        foreach ($jd as $kd) {
            foreach ($kd as $s) {
                if ($s !== null) {
                    $terisi++;
                }
            }
        }
    }

    $partial = [];
    foreach ($units as $u) {
        $p = 0;
        foreach ($strukturHari as $hari => $max) {
            for ($j = 1; $j <= $max; $j++) {
                if ($grid[$hari][$j][$u['kelas_id']] === $u['id']) {
                    $p++;
                }
            }
        }
        if ($p < $u['jtm']) {
            $partial[] = ['id' => $u['id'], 'guru' => $u['guru_id'], 'kelas' => $u['kelas_id'], 'need' => $u['jtm'], 'got' => $p];
        }
    }

    return ['terisi' => $terisi, 'partial' => $partial, 'grid' => $grid, 'guruOcc' => $guruOcc];
}

$best = 0;
$bestPartial = [];
for ($seed = 0; $seed < 20; $seed++) {
    $r = runAttempt($units, $kelasIds, $strukturHari, $seed);
    if ($r['terisi'] > $best) {
        $best = $r['terisi'];
        $bestPartial = $r['partial'];
    }
    if ($best >= $totalJtm) {
        break;
    }
}

echo "Greedy sederhana terbaik: {$best}/{$totalJtm}\n";
if (!empty($bestPartial)) {
    echo "Mapel partial: " . count($bestPartial) . "\n";
    foreach (array_slice($bestPartial, 0, 10) as $p) {
        echo "  beban {$p['id']}: guru {$p['guru']} kelas {$p['kelas']} {$p['got']}/{$p['need']}\n";
    }
}

// Force-fill pass with relocation
function forceFill(array $units, array $kelasIds, array $strukturHari, int $seed): int
{
    $r = runAttempt($units, $kelasIds, $strukturHari, $seed);
    $grid = $r['grid'];
    $guruOcc = $r['guruOcc'];

    for ($pass = 0; $pass < 500; $pass++) {
        $progress = false;
        foreach ($units as $u) {
            $placed = 0;
            foreach ($strukturHari as $hari => $max) {
                for ($j = 1; $j <= $max; $j++) {
                    if ($grid[$hari][$j][$u['kelas_id']] === $u['id']) {
                        $placed++;
                    }
                }
            }
            while ($placed < $u['jtm']) {
                $ok = false;
                foreach ($strukturHari as $hari => $max) {
                    for ($j = 1; $j <= $max; $j++) {
                        if ($grid[$hari][$j][$u['kelas_id']] !== null) {
                            continue;
                        }
                        if (isset($guruOcc[$hari][$j][$u['guru_id']])) {
                            // Evict blocker
                            foreach ($kelasIds as $kId) {
                                if ($kId === $u['kelas_id']) {
                                    continue;
                                }
                                $bid = $grid[$hari][$j][$kId] ?? null;
                                if ($bid === null) {
                                    continue;
                                }
                                $blocker = null;
                                foreach ($units as $bu) {
                                    if ($bu['id'] === $bid) {
                                        $blocker = $bu;
                                        break;
                                    }
                                }
                                if ($blocker === null || $blocker['btq']) {
                                    continue;
                                }
                                $grid[$hari][$j][$kId] = null;
                                unset($guruOcc[$hari][$j][$blocker['guru_id']]);
                            }
                        }
                        if (isset($guruOcc[$hari][$j][$u['guru_id']])) {
                            continue;
                        }
                        $grid[$hari][$j][$u['kelas_id']] = $u['id'];
                        $guruOcc[$hari][$j][$u['guru_id']] = true;
                        $placed++;
                        $ok = true;
                        $progress = true;
                        break 2;
                    }
                }
                if (!$ok) {
                    break;
                }
            }
        }
        if (!$progress) {
            break;
        }
    }

    $terisi = 0;
    foreach ($grid as $jd) {
        foreach ($jd as $kd) {
            foreach ($kd as $s) {
                if ($s !== null) {
                    $terisi++;
                }
            }
        }
    }
    return $terisi;
}

$forceBest = 0;
for ($seed = 0; $seed < 10; $seed++) {
    $f = forceFill($units, $kelasIds, $strukturHari, $seed);
    $forceBest = max($forceBest, $f);
}

echo "Force-fill + evict terbaik: {$forceBest}/{$totalJtm}\n";
echo ($forceBest >= $totalJtm ? "KESIMPULAN: 792/792 FEASIBLE (data mendukung)\n" : "Perlu algoritma lebih kuat di production\n");
