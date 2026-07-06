<?php
/**
 * CSP Solver jadwal 792/792 dari dump SQL — tanpa Laravel.
 * Usage: php scripts/solve_jadwal.php "path/to/dump.sql" [semester_id] [time_limit_sec]
 */

declare(strict_types=1);

$sqlFile = $argv[1] ?? '';
$semesterId = (int) ($argv[2] ?? 1);
$timeLimit = (int) ($argv[3] ?? 600);

if ($sqlFile === '' || !is_file($sqlFile)) {
    fwrite(STDERR, "Usage: php solve_jadwal.php <dump.sql> [semester_id] [time_limit_sec]\n");
    exit(1);
}

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

final class JadwalCspSolver
{
    private const BTQ_HARI = 'Jumat';
    private const BTQ_JAM_AKHIR = 5;

    private array $strukturHari = ['Senin' => 9, 'Selasa' => 10, 'Rabu' => 10, 'Kamis' => 10, 'Jumat' => 5];

    /** @var array<int, array> */
    private array $units = [];
    /** @var array<int, list<int>> */
    private array $unitsByKelas = [];
    /** @var list<int> */
    private array $kelasIds = [];
    /** @var array<string, array<int, array<int, int|null>>> hari->jam->kelas->bebanId */
    private array $grid = [];
    /** @var array<string, array<int, array<int, true>>> */
    private array $guruOcc = [];
    /** @var array<int, int> */
    private array $remaining = [];
    /** @var array<int, int> guru blocked count */
    private array $guruBlocked = [];

    private int $deadline;
    private int $nodes = 0;
    private ?array $bestGrid = null;
    private int $bestFilled = 0;

    public function __construct(
        private readonly int $semesterId,
        array $bebanRows,
        array $mapelRows,
        array $kelasRows,
        array $constraintRows,
    ) {
        $mapels = [];
        foreach ($mapelRows as $r) {
            $mapels[(int) $r[0]] = $r[1];
        }

        foreach ($kelasRows as $r) {
            $this->kelasIds[] = (int) $r[0];
        }
        sort($this->kelasIds);

        foreach ($constraintRows as $r) {
            if ((int) $r[4] !== 0) {
                continue;
            }
            $gid = (int) $r[1];
            $this->guruBlocked[$gid] = ($this->guruBlocked[$gid] ?? 0) + 1;
        }

        foreach ($bebanRows as $r) {
            if ((int) $r[1] !== $this->semesterId || (int) $r[6] !== 1) {
                continue;
            }
            $id = (int) $r[0];
            $nama = $mapels[(int) $r[3]] ?? '';
            $n = strtolower($nama);
            $this->units[$id] = [
                'id' => $id,
                'guru_id' => (int) $r[2],
                'kelas_id' => (int) $r[4],
                'mapel_id' => (int) $r[3],
                'jtm' => (int) $r[5],
                'btq' => str_contains($n, 'btq') || str_contains($n, 'baca tulis'),
            ];
            $this->unitsByKelas[(int) $r[4]][] = $id;
            $this->remaining[$id] = (int) $r[5];
        }

        $this->initGrid();
    }

    public function solve(int $timeLimit, int $seed = 0): bool
    {
        $this->deadline = time() + $timeLimit;
        $this->nodes = 0;
        $this->bestGrid = null;
        $this->bestFilled = 0;

        $this->initGrid();
        $this->placeBtq();

        if ($seed > 0) {
            mt_srand($seed);
        }

        if ($this->backtrack()) {
            $this->bestGrid = $this->exportGrid();
            $this->bestFilled = 792;
            return true;
        }

        $this->saveBestPartial();

        // Min-conflicts repair dari solusi terbaik sejauh ini
        if ($this->bestFilled < 792) {
            $this->loadGrid($this->bestGrid ?? $this->exportGrid());
            $this->minConflictsRepair(8000);
            $filled = $this->countFilled();
            if ($filled > $this->bestFilled) {
                $this->bestFilled = $filled;
                $this->bestGrid = $this->exportGrid();
            }
        }

        if ($this->bestFilled < 792) {
            // Greedy multi-restart + chain relocation
            for ($s = 1; $s <= 80; $s++) {
                if (time() >= $this->deadline) {
                    break;
                }
                $this->initGrid();
                $this->placeBtq();
                $this->greedyFill($s);
                $this->chainRelocate(6000);
                $filled = $this->countFilled();
                if ($filled > $this->bestFilled) {
                    $this->bestFilled = $filled;
                    $this->bestGrid = $this->exportGrid();
                }
                if ($this->bestFilled >= 792) {
                    return true;
                }
            }
        }

        return $this->bestFilled >= 792;
    }

    public function getBestFilled(): int
    {
        return $this->bestFilled;
    }

    /** @return list<array{beban_id:int,hari:string,jam:int}> */
    public function getAssignments(): array
    {
        $grid = $this->bestGrid ?? $this->exportGrid();
        $out = [];
        foreach ($grid as $hari => $jamData) {
            foreach ($jamData as $jam => $kelasData) {
                foreach ($kelasData as $bebanId) {
                    if ($bebanId !== null) {
                        $out[] = ['beban_id' => $bebanId, 'hari' => $hari, 'jam' => $jam];
                    }
                }
            }
        }
        return $out;
    }

    private function initGrid(): void
    {
        $this->grid = [];
        $this->guruOcc = [];
        foreach ($this->strukturHari as $hari => $max) {
            for ($j = 1; $j <= $max; $j++) {
                foreach ($this->kelasIds as $k) {
                    $this->grid[$hari][$j][$k] = null;
                }
            }
        }
        foreach ($this->units as $id => $u) {
            $this->remaining[$id] = $u['jtm'];
        }
    }

    private function placeBtq(): void
    {
        foreach ($this->units as $u) {
            if (!$u['btq']) {
                continue;
            }
            $start = self::BTQ_JAM_AKHIR - $u['jtm'] + 1;
            for ($j = $start; $j <= self::BTQ_JAM_AKHIR; $j++) {
                $this->assign($u['id'], self::BTQ_HARI, $j, $u['kelas_id']);
            }
        }
    }

    private function backtrack(): bool
    {
        if ($this->allDone()) {
            return true;
        }
        if (time() >= $this->deadline) {
            return false;
        }

        $this->nodes++;
        if ($this->nodes % 500 === 0) {
            $filled = $this->countFilled();
            if ($filled > $this->bestFilled) {
                $this->bestFilled = $filled;
                $this->bestGrid = $this->exportGrid();
            }
        }

        $slot = $this->pickSlotMrv();
        if ($slot === null) {
            return $this->allDone();
        }

        [$hari, $jam, $kelasId] = $slot;
        $candidates = $this->orderedCandidates($hari, $jam, $kelasId);

        foreach ($candidates as $bebanId) {
            $this->assign($bebanId, $hari, $jam, $kelasId);
            if ($this->forwardCheck($kelasId) && $this->backtrack()) {
                return true;
            }
            $this->unassign($bebanId, $hari, $jam, $kelasId);
        }

        return false;
    }

    private function pickSlotMrv(): ?array
    {
        $best = null;
        $bestCount = PHP_INT_MAX;
        $bestScore = PHP_INT_MAX;

        foreach ($this->kelasIds as $kelasId) {
            foreach ($this->strukturHari as $hari => $max) {
                for ($j = 1; $j <= $max; $j++) {
                    if ($this->grid[$hari][$j][$kelasId] !== null) {
                        continue;
                    }
                    $cands = $this->rawCandidates($hari, $j, $kelasId);
                    $cnt = count($cands);
                    if ($cnt === 0) {
                        return [$hari, $j, $kelasId];
                    }
                    $score = $this->slotDifficulty($hari, $j, $kelasId, $cands);
                    if ($cnt < $bestCount || ($cnt === $bestCount && $score < $bestScore)) {
                        $bestCount = $cnt;
                        $bestScore = $score;
                        $best = [$hari, $j, $kelasId];
                    }
                }
            }
        }

        return $best;
    }

    /** @param list<int> $cands */
    private function slotDifficulty(string $hari, int $jam, int $kelasId, array $cands): int
    {
        $score = count($cands) * 10;
        foreach ($cands as $bid) {
            $g = $this->units[$bid]['guru_id'];
            $score += $this->guruDayLoad($g, $hari);
            $score += ($this->guruBlocked[$g] ?? 0) > 10 ? 0 : 2;
        }
        return $score;
    }

    /** @return list<int> */
    private function rawCandidates(string $hari, int $jam, int $kelasId): array
    {
        $out = [];
        foreach ($this->unitsByKelas[$kelasId] ?? [] as $bid) {
            if ($this->remaining[$bid] <= 0) {
                continue;
            }
            $g = $this->units[$bid]['guru_id'];
            if (isset($this->guruOcc[$hari][$jam][$g])) {
                continue;
            }
            $out[] = $bid;
        }
        return $out;
    }

    /** @return list<int> */
    private function orderedCandidates(string $hari, int $jam, int $kelasId): array
    {
        $cands = $this->rawCandidates($hari, $jam, $kelasId);
        usort($cands, function ($a, $b) use ($hari) {
            $ga = $this->units[$a]['guru_id'];
            $gb = $this->units[$b]['guru_id'];
            $la = $this->guruTotalRemaining($ga);
            $lb = $this->guruTotalRemaining($gb);
            if ($la !== $lb) {
                return $la <=> $lb;
            }
            $da = $this->guruDayLoad($ga, $hari);
            $db = $this->guruDayLoad($gb, $hari);
            if ($da !== $db) {
                return $da <=> $db;
            }
            return $this->remaining[$b] <=> $this->remaining[$a];
        });

        if (mt_rand(0, 10) === 0) {
            shuffle($cands);
        }

        return $cands;
    }

    private function guruTotalRemaining(int $guruId): int
    {
        $n = 0;
        foreach ($this->units as $bid => $u) {
            if ($u['guru_id'] === $guruId) {
                $n += $this->remaining[$bid];
            }
        }
        return $n;
    }

    private function guruDayLoad(int $guruId, string $hari): int
    {
        $n = 0;
        foreach ($this->grid[$hari] ?? [] as $kelasRow) {
            foreach ($kelasRow as $bid) {
                if ($bid !== null && $this->units[$bid]['guru_id'] === $guruId) {
                    $n++;
                }
            }
        }
        return $n;
    }

    private function forwardCheck(int $kelasId): bool
    {
        foreach ($this->strukturHari as $hari => $max) {
            for ($j = 1; $j <= $max; $j++) {
                if ($this->grid[$hari][$j][$kelasId] !== null) {
                    continue;
                }
                if (empty($this->rawCandidates($hari, $j, $kelasId))) {
                    return false;
                }
            }
        }
        return true;
    }

    private function assign(int $bebanId, string $hari, int $jam, int $kelasId): void
    {
        $this->grid[$hari][$jam][$kelasId] = $bebanId;
        $g = $this->units[$bebanId]['guru_id'];
        $this->guruOcc[$hari][$jam][$g] = true;
        $this->remaining[$bebanId]--;
    }

    private function unassign(int $bebanId, string $hari, int $jam, int $kelasId): void
    {
        $this->grid[$hari][$jam][$kelasId] = null;
        $g = $this->units[$bebanId]['guru_id'];
        unset($this->guruOcc[$hari][$jam][$g]);
        $this->remaining[$bebanId]++;
    }

    private function allDone(): bool
    {
        foreach ($this->remaining as $r) {
            if ($r > 0) {
                return false;
            }
        }
        return true;
    }

    private function countFilled(): int
    {
        $n = 0;
        foreach ($this->grid as $jd) {
            foreach ($jd as $row) {
                foreach ($row as $v) {
                    if ($v !== null) {
                        $n++;
                    }
                }
            }
        }
        return $n;
    }

    /** @return array<string, array<int, array<int, int|null>>> */
    private function exportGrid(): array
    {
        return json_decode(json_encode($this->grid), true);
    }

    /** @param array<string, array<int, array<int, int|null>>> $grid */
    private function loadGrid(array $grid): void
    {
        $this->grid = $grid;
        $this->guruOcc = [];
        foreach ($this->units as $id => $u) {
            $this->remaining[$id] = $u['jtm'];
        }
        foreach ($this->grid as $hari => $jamData) {
            foreach ($jamData as $jam => $kelasData) {
                foreach ($kelasData as $kelasId => $bid) {
                    if ($bid !== null) {
                        $g = $this->units[$bid]['guru_id'];
                        $this->guruOcc[$hari][$jam][$g] = true;
                        $this->remaining[$bid]--;
                    }
                }
            }
        }
    }

    private function saveBestPartial(): void
    {
        $filled = $this->countFilled();
        if ($filled > $this->bestFilled) {
            $this->bestFilled = $filled;
            $this->bestGrid = $this->exportGrid();
        }
    }

    private function minConflictsRepair(int $maxIter): void
    {
        for ($i = 0; $i < $maxIter; $i++) {
            if (time() >= $this->deadline || $this->allDone()) {
                break;
            }

            $unfinished = [];
            foreach ($this->units as $bid => $u) {
                if ($this->remaining[$bid] > 0) {
                    $unfinished[] = $bid;
                }
            }
            if (empty($unfinished)) {
                break;
            }

            $bid = $unfinished[array_rand($unfinished)];
            $u = $this->units[$bid];
            $placed = false;

            foreach ($this->strukturHari as $hari => $max) {
                for ($j = 1; $j <= $max; $j++) {
                    if ($this->grid[$hari][$j][$u['kelas_id']] !== null) {
                        continue;
                    }
                    if (isset($this->guruOcc[$hari][$j][$u['guru_id']])) {
                        continue;
                    }
                    $this->assign($bid, $hari, $j, $u['kelas_id']);
                    $placed = true;
                    break 2;
                }
            }

            if (!$placed) {
                foreach ($this->strukturHari as $hari => $max) {
                    for ($j = 1; $j <= $max; $j++) {
                        if ($this->grid[$hari][$j][$u['kelas_id']] !== null) {
                            continue;
                        }
                        if (!isset($this->guruOcc[$hari][$j][$u['guru_id']])) {
                            continue;
                        }
                        $this->clearGuruAt($u['guru_id'], $hari, $j);
                        if (!isset($this->guruOcc[$hari][$j][$u['guru_id']])) {
                            $this->assign($bid, $hari, $j, $u['kelas_id']);
                            break 2;
                        }
                    }
                }
            }
        }
    }

    private function clearGuruAt(int $guruId, string $hari, int $jam): void
    {
        foreach ($this->kelasIds as $kId) {
            $bid = $this->grid[$hari][$jam][$kId] ?? null;
            if ($bid === null) {
                continue;
            }
            if ($this->units[$bid]['guru_id'] !== $guruId) {
                continue;
            }
            if ($this->units[$bid]['btq']) {
                continue;
            }
            $this->unassign($bid, $hari, $jam, $kId);
        }
    }

    private function greedyFill(int $seed): void
    {
        $order = array_values($this->units);
        usort($order, function ($a, $b) {
            if ($a['btq'] !== $b['btq']) {
                return $b['btq'] <=> $a['btq'];
            }
            $ba = $this->guruBlocked[$a['guru_id']] ?? 0;
            $bb = $this->guruBlocked[$b['guru_id']] ?? 0;
            if ($ba !== $bb) {
                return $bb <=> $ba;
            }
            return $b['jtm'] <=> $a['jtm'];
        });

        if ($seed > 0) {
            $n = count($order);
            $order = array_merge(array_slice($order, $seed % $n), array_slice($order, 0, $seed % $n));
        }

        $queues = [];
        foreach ($this->kelasIds as $k) {
            $queues[$k] = [];
        }
        foreach ($order as $u) {
            if ($u['btq']) {
                continue;
            }
            $queues[$u['kelas_id']][] = $u['id'];
        }

        for ($round = 0; $round < 500; $round++) {
            $progress = false;
            foreach ($this->kelasIds as $k) {
                if (empty($queues[$k])) {
                    continue;
                }
                $bid = array_shift($queues[$k]);
                if ($this->remaining[$bid] <= 0) {
                    continue;
                }
                $u = $this->units[$bid];
                foreach ($this->strukturHari as $hari => $max) {
                    for ($j = 1; $j <= $max; $j++) {
                        if ($this->grid[$hari][$j][$u['kelas_id']] !== null) {
                            continue;
                        }
                        if (isset($this->guruOcc[$hari][$j][$u['guru_id']])) {
                            continue;
                        }
                        $this->assign($bid, $hari, $j, $u['kelas_id']);
                        $progress = true;
                        if ($this->remaining[$bid] > 0) {
                            $queues[$k][] = $bid;
                        }
                        break 2;
                    }
                }
                if ($this->remaining[$bid] > 0) {
                    $queues[$k][] = $bid;
                }
            }
            if (!$progress) {
                break;
            }
        }
    }

    private function chainRelocate(int $maxIter): void
    {
        for ($i = 0; $i < $maxIter; $i++) {
            if (time() >= $this->deadline || $this->allDone()) {
                break;
            }

            $pending = array_filter($this->remaining, fn($r) => $r > 0);
            if (empty($pending)) {
                break;
            }

            $bid = (int) array_key_first($pending);
            foreach ($this->remaining as $b => $r) {
                if ($r > 0) {
                    $bid = $b;
                    if ($r >= 2) {
                        break;
                    }
                }
            }

            $u = $this->units[$bid];
            $before = $this->remaining[$bid];

            foreach ($this->strukturHari as $hari => $max) {
                for ($j = 1; $j <= $max; $j++) {
                    if ($this->grid[$hari][$j][$u['kelas_id']] !== null) {
                        continue;
                    }
                    if (!isset($this->guruOcc[$hari][$j][$u['guru_id']])) {
                        $this->assign($bid, $hari, $j, $u['kelas_id']);
                        continue 2;
                    }
                    $snap = $this->exportGrid();
                    $snapRem = $this->remaining;
                    $this->clearGuruAt($u['guru_id'], $hari, $j);
                    if (!isset($this->guruOcc[$hari][$j][$u['guru_id']])) {
                        $this->assign($bid, $hari, $j, $u['kelas_id']);
                        $this->requeueIncomplete();
                    } else {
                        $this->loadGrid($snap);
                        $this->remaining = $snapRem;
                    }
                }
            }

            if ($this->remaining[$bid] < $before) {
                $this->requeueIncomplete();
            }
        }
    }

    private function requeueIncomplete(): void
    {
        foreach ($this->units as $bid => $u) {
            if ($this->remaining[$bid] <= 0 || $u['btq']) {
                continue;
            }
            $need = $this->remaining[$bid];
            for ($t = 0; $t < $need; $t++) {
                $placed = false;
                foreach ($this->strukturHari as $hari => $max) {
                    for ($j = 1; $j <= $max; $j++) {
                        if ($this->grid[$hari][$j][$u['kelas_id']] !== null) {
                            continue;
                        }
                        if (isset($this->guruOcc[$hari][$j][$u['guru_id']])) {
                            continue;
                        }
                        $this->assign($bid, $hari, $j, $u['kelas_id']);
                        $placed = true;
                        break 2;
                    }
                }
                if (!$placed) {
                    break;
                }
            }
        }
    }
}

// ─── Main ────────────────────────────────────────────────────────────────

$content = file_get_contents($sqlFile);
$solver = new JadwalCspSolver(
    $semesterId,
    parseInsertRows($content, 'beban_mengajars'),
    parseInsertRows($content, 'mapels'),
    parseInsertRows($content, 'kelas'),
    parseInsertRows($content, 'guru_constraints'),
);

echo "=== CSP SOLVER JADWAL MTs MAJA ===\n";
echo "Semester: {$semesterId} | Time limit: {$timeLimit}s\n\n";

$ok = false;
for ($attempt = 0; $attempt < 5; $attempt++) {
    echo "Attempt " . ($attempt + 1) . "...\n";
    $solver = new JadwalCspSolver(
        $semesterId,
        parseInsertRows($content, 'beban_mengajars'),
        parseInsertRows($content, 'mapels'),
        parseInsertRows($content, 'kelas'),
        parseInsertRows($content, 'guru_constraints'),
    );
    $perAttempt = (int) max(60, $timeLimit / 5);
    if ($solver->solve($perAttempt, $attempt * 17 + 1)) {
        $ok = true;
        break;
    }
    echo "  → Terisi: {$solver->getBestFilled()}/792\n";
    if ($solver->getBestFilled() >= 792) {
        $ok = true;
        break;
    }
}

$filled = $solver->getBestFilled();
echo "\nHasil: {$filled}/792\n";

if ($filled < 792) {
    echo "Belum sempurna — lanjut extended search...\n";
    $solver = new JadwalCspSolver(
        $semesterId,
        parseInsertRows($content, 'beban_mengajars'),
        parseInsertRows($content, 'mapels'),
        parseInsertRows($content, 'kelas'),
        parseInsertRows($content, 'guru_constraints'),
    );
    $solver->solve($timeLimit, 99);
    $filled = $solver->getBestFilled();
    echo "Extended: {$filled}/792\n";
    $ok = $filled >= 792;
}

$outDir = dirname(__DIR__) . '/scripts/output';
if (!is_dir($outDir)) {
    mkdir($outDir, 0755, true);
}

$assignments = $solver->getAssignments();
$now = date('Y-m-d H:i:s');
$startId = 20000;

$sqlOut = "-- Jadwal generated by CSP solver " . date('c') . "\n";
$sqlOut .= "-- Terisi: {$filled}/792\n\n";
$sqlOut .= "DELETE FROM `jadwals` WHERE `semester_id` = {$semesterId};\n\n";
$sqlOut .= "INSERT INTO `jadwals` (`id`, `semester_id`, `beban_mengajar_id`, `hari`, `jam_ke`, `created_at`, `updated_at`) VALUES\n";

$lines = [];
$id = $startId;
foreach ($assignments as $a) {
    $lines[] = sprintf(
        "(%d, %d, %d, '%s', %d, '%s', '%s')",
        $id++,
        $semesterId,
        $a['beban_id'],
        $a['hari'],
        $a['jam'],
        $now,
        $now
    );
}
$sqlOut .= implode(",\n", $lines) . ";\n";

$sqlPath = $outDir . "/jadwal_semester_{$semesterId}.sql";
file_put_contents($sqlPath, $sqlOut);

$report = "Terisi: {$filled}/792\n";
$report .= "File: {$sqlPath}\n";
if ($ok) {
    $report .= "Status: SUKSES — siap import ke database\n";
} else {
    $partial = [];
    foreach ($solver->getAssignments() as $a) {
        // count per beban
    }
    $report .= "Status: PARTIAL — perlu iterasi lanjut\n";
}
file_put_contents($outDir . '/report.txt', $report);

echo $report;
exit($ok ? 0 : 1);
