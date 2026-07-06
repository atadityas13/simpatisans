<?php
/**
 * Validasi file jadwal SQL hasil solver.
 * Usage: php scripts/validate_jadwal.php scripts/output/jadwal_semester_1.sql dump.sql
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
    fwrite(STDERR, "Usage: php validate_jadwal.php <jadwal.sql> <dump.sql>\n");
    exit(1);
}

$jadwalContent = file_get_contents($jadwalFile);
$dumpContent = file_get_contents($dumpFile);

preg_match_all("/\(\d+,\s*(\d+),\s*(\d+),\s*'([^']+)',\s*(\d+),/", $jadwalContent, $m, PREG_SET_ORDER);
$slots = [];
$bentrok = [];
$bebanCount = [];

foreach ($m as $row) {
    $sem = (int) $row[1];
    $bm = (int) $row[2];
    $hari = $row[3];
    $jam = (int) $row[4];
    $bebanCount[$bm] = ($bebanCount[$bm] ?? 0) + 1;
    $slots[] = compact('sem', 'bm', 'hari', 'jam');
}

$bebanRows = parseInsertRows($dumpContent, 'beban_mengajars');
$mapelRows = parseInsertRows($dumpContent, 'mapels');
$guruRows = parseInsertRows($dumpContent, 'gurus');

$mapels = [];
foreach ($mapelRows as $r) {
    $mapels[(int) $r[0]] = $r[1];
}
$gurus = [];
foreach ($guruRows as $r) {
    $gurus[(int) $r[0]] = $r[2];
}

$beban = [];
foreach ($bebanRows as $r) {
    if ((int) $r[1] !== 1 || (int) $r[6] !== 1) {
        continue;
    }
    $id = (int) $r[0];
    $nama = $mapels[(int) $r[3]] ?? '';
    $n = strtolower($nama);
    $beban[$id] = [
        'guru_id' => (int) $r[2],
        'kelas_id' => (int) $r[4],
        'jtm' => (int) $r[5],
        'btq' => str_contains($n, 'btq') || str_contains($n, 'baca tulis'),
        'mapel' => $nama,
    ];
}

// Guru bentrok
$guruSlots = [];
foreach ($slots as $s) {
    $gid = $beban[$s['bm']]['guru_id'] ?? 0;
    $key = "{$s['hari']}-{$s['jam']}-{$gid}";
    if (isset($guruSlots[$key])) {
        $bentrok[] = $key;
    }
    $guruSlots[$key] = true;
}

// Beban incomplete
$incomplete = [];
foreach ($beban as $id => $b) {
    $got = $bebanCount[$id] ?? 0;
    if ($got !== $b['jtm']) {
        $incomplete[] = "beban {$id} ({$b['mapel']}): {$got}/{$b['jtm']}";
    }
}

// BTQ check
$btqBad = [];
foreach ($beban as $id => $b) {
    if (!$b['btq']) {
        continue;
    }
    $hours = [];
    foreach ($slots as $s) {
        if ($s['bm'] === $id) {
            $hours[] = $s['jam'];
        }
    }
    if (empty($hours)) {
        $btqBad[] = "beban {$id} kosong";
        continue;
    }
    $max = max($hours);
    if ($max !== 5) {
        $btqBad[] = "beban {$id} max jam {$max}, bukan 5";
    }
}

echo "=== VALIDASI JADWAL ===\n";
echo "Total slot: " . count($slots) . "\n";
echo "Bentrok guru: " . count($bentrok) . "\n";
echo "Beban incomplete: " . count($incomplete) . "\n";
echo "BTQ salah: " . count($btqBad) . "\n";

if (!empty($bentrok)) {
    echo "\nBentrok:\n";
    foreach (array_slice($bentrok, 0, 5) as $b) {
        echo "  {$b}\n";
    }
}
if (!empty($incomplete)) {
    echo "\nIncomplete:\n";
    foreach (array_slice($incomplete, 0, 10) as $i) {
        echo "  {$i}\n";
    }
}
if (!empty($btqBad)) {
    echo "\nBTQ:\n";
    foreach ($btqBad as $i) {
        echo "  {$i}\n";
    }
}

$ok = count($slots) === 792 && empty($bentrok) && empty($incomplete) && empty($btqBad);
echo $ok ? "\nVALIDASI OK\n" : "\nVALIDASI GAGAL\n";
exit($ok ? 0 : 1);
