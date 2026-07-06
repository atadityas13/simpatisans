<?php
/**
 * Ringkasan kualitas jadwal hasil solver.
 * Usage: php scripts/analyze_jadwal_output.php scripts/output/jadwal_semester_1.sql dump.sql
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

$jadwalFile = $argv[1] ?? '';
$dumpFile = $argv[2] ?? '';
if (!is_file($jadwalFile) || !is_file($dumpFile)) {
    exit(1);
}

$jadwalContent = file_get_contents($jadwalFile);
$dumpContent = file_get_contents($dumpFile);

preg_match_all("/\(\d+,\s*\d+,\s*(\d+),\s*'([^']+)',\s*(\d+),/", $jadwalContent, $m, PREG_SET_ORDER);

$bebanRows = parseInsertRows($dumpContent, 'beban_mengajars');
$guruRows = parseInsertRows($dumpContent, 'gurus');
$constraintRows = parseInsertRows($dumpContent, 'guru_constraints');

$gurus = [];
foreach ($guruRows as $r) {
    $gurus[(int) $r[0]] = $r[2];
}

$beban = [];
foreach ($bebanRows as $r) {
    if ((int) $r[1] !== 1 || (int) $r[6] !== 1) {
        continue;
    }
    $beban[(int) $r[0]] = ['guru_id' => (int) $r[2], 'kelas_id' => (int) $r[4]];
}

$blocked = [];
foreach ($constraintRows as $r) {
    if ((int) $r[4] !== 0) {
        continue;
    }
    $h = ucfirst(strtolower(trim($r[2])));
    $blocked[(int) $r[1]]["{$h}-{$r[3]}"] = true;
}

$presetViol = 0;
$fatigue = [];
$daily = [];

foreach ($m as $row) {
    $bm = (int) $row[1];
    $hari = $row[2];
    $jam = (int) $row[3];
    $gid = $beban[$bm]['guru_id'] ?? 0;
    $h = ucfirst(strtolower(trim($hari)));
    if (isset($blocked[$gid]["{$h}-{$jam}"])) {
        $presetViol++;
    }
    $key = "{$gid}-{$h}";
    $daily[$key][$jam] = true;
}

foreach ($daily as $key => $jams) {
    [$gid] = explode('-', $key, 2);
    $cnt = count($jams);
    if ($cnt >= 8) {
        $fatigue[] = ($gurus[(int) $gid] ?? "?") . " @ {$key}: {$cnt} jam";
    }
}

echo "=== ANALISA KUALITAS JADWAL HASIL ===\n";
echo "Slot terisi: " . count($m) . "/792\n";
echo "Pelanggaran preset (soft): {$presetViol}\n";
echo "Fatigue >=8 jam/hari (soft): " . count($fatigue) . "\n";
foreach (array_slice($fatigue, 0, 15) as $f) {
    echo "  {$f}\n";
}
