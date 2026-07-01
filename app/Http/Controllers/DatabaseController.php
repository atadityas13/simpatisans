<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseController extends Controller
{
    public function index()
    {
        $databaseName = DB::getDatabaseName();
        $tablesRaw = DB::select('SHOW TABLES');
        $tables = [];

        // Protection List (Hidden from UI)
        $protected = ['migrations', 'failed_jobs', 'jobs', 'cache', 'sessions', 'password_reset_tokens', 'personal_access_tokens'];

        foreach ($tablesRaw as $tableObj) {
            $vars = get_object_vars($tableObj);
            $tableName = reset($vars); // Get first property value
            
            if (in_array($tableName, $protected)) continue;

            $count = DB::table($tableName)->count();
            
            // Heuristic categorization
            $type = 'Operational';
            if (in_array($tableName, ['users', 'gurus', 'mapels', 'kelas', 'tugas_tambahans', 'semesters', 'rumpuns'])) {
                $type = 'Master / Core';
            } elseif (str_contains($tableName, '_rumpun') || str_contains($tableName, 'guru_')) {
                $type = 'Pivot / Map';
            }

            $tables[] = [
                'name' => $tableName,
                'count' => $count,
                'type' => $type,
                'is_critical' => ($tableName === 'users' || $tableName === 'gurus'),
                'is_empty' => $count === 0
            ];
        }

        return view('pengaturan.database', compact('tables'));
    }

    public function truncate(Request $request)
    {
        $request->validate([
            'table' => 'required|string'
        ]);

        $table = $request->table;
        $protected = ['migrations'];

        if (in_array($table, $protected)) {
            return redirect()->back()->with('error', 'Tabel migrasi tidak boleh dihapus.');
        }

        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            DB::table($table)->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            return redirect()->back()->with('success', "Tabel [$table] berhasil dikosongkan dan Auto-Increment direset.");
        } catch (\Exception $e) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            return redirect()->back()->with('error', 'Gagal reset tabel: ' . $e->getMessage());
        }
    }
}
