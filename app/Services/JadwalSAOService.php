<?php

namespace App\Services;

use App\Models\BebanMengajar;
use App\Models\Jadwal;
use App\Models\Kelas;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Entry point Generate Jadwal — delegasi ke CSP solver (backtracking MRV).
 */
class JadwalSAOService
{
    private const TIME_BUDGET = 120;

    public function generate(int $semesterId): array
    {
        @ini_set('memory_limit', '512M');
        @set_time_limit(self::TIME_BUDGET + 30);

        $beban = BebanMengajar::where('semester_id', $semesterId)
            ->where('is_satminkal', 1)
            ->count();

        if ($beban === 0) {
            throw new \Exception('Data Beban Mengajar (KBM) kosong. Distribusikan jam terlebih dahulu.');
        }

        if (Kelas::count() === 0) {
            throw new \Exception('Data Kelas kosong.');
        }

        $solver = JadwalCspSolver::fromSemester($semesterId);
        $target = $solver->getTargetCapacity();

        $ok = false;
        for ($attempt = 0; $attempt < 3; $attempt++) {
            $solver = JadwalCspSolver::fromSemester($semesterId);
            if ($solver->solve((int) max(45, self::TIME_BUDGET / 3), $attempt * 23 + 1)) {
                $ok = true;
                break;
            }
            if ($solver->getBestFilled() >= $target) {
                $ok = true;
                break;
            }
        }

        if (!$ok) {
            $solver = JadwalCspSolver::fromSemester($semesterId);
            $solver->solve(self::TIME_BUDGET, 99);
        }

        $terisi = $solver->getBestFilled();
        $kosong = $target - $terisi;

        if ($terisi === 0) {
            throw new \Exception('Gagal membuat jadwal. Periksa beban mengajar.');
        }

        return $this->simpan($semesterId, $solver->getAssignments(), $terisi, $target, $kosong);
    }

    /** @param list<array{beban_id:int,hari:string,jam:int}> $assignments */
    private function simpan(int $semesterId, array $assignments, int $terisi, int $target, int $kosong): array
    {
        DB::table('jadwals')->where('semester_id', $semesterId)->delete();
        DB::beginTransaction();
        try {
            $rows = [];
            $now = now();
            foreach ($assignments as $a) {
                $rows[] = [
                    'semester_id' => $semesterId,
                    'beban_mengajar_id' => $a['beban_id'],
                    'hari' => ucfirst(strtolower(trim($a['hari']))),
                    'jam_ke' => $a['jam'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            foreach (array_chunk($rows, 500) as $chunk) {
                Jadwal::insert($chunk);
            }
            DB::commit();

            return [
                'status' => $kosong === 0 ? 'success' : 'partial',
                'biaya_penalti' => 0,
                'total_slot_terisi' => $terisi,
                'total_target' => $target,
                'slot_kosong' => $kosong,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Jadwal save: ' . $e->getMessage());
            throw new \Exception('Gagal menyimpan jadwal: ' . $e->getMessage());
        }
    }
}
