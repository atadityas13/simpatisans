<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('guru_constraints')) {
            return;
        }

        Schema::table('guru_constraints', function (Blueprint $table) {
            if (! Schema::hasColumn('guru_constraints', 'semester_id')) {
                $table->foreignId('semester_id')->nullable()->after('guru_id')->constrained('semesters')->cascadeOnDelete();
            }
            if (! Schema::hasColumn('guru_constraints', 'version_id')) {
                $table->foreignId('version_id')->nullable()->after('semester_id')->constrained('jadwal_versions')->cascadeOnDelete();
            }
        });

        $this->backfillConstraintsToVersions();

        // Constraint tanpa versi (gagal backfill) dibuang agar tidak residual global.
        DB::table('guru_constraints')->whereNull('version_id')->delete();

        if (! $this->indexExists('guru_constraints', 'guru_constraints_version_slot_unique')) {
            Schema::table('guru_constraints', function (Blueprint $table) {
                $table->unique(
                    ['guru_id', 'version_id', 'hari', 'jam_ke'],
                    'guru_constraints_version_slot_unique'
                );
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('guru_constraints')) {
            return;
        }

        if ($this->indexExists('guru_constraints', 'guru_constraints_version_slot_unique')) {
            Schema::table('guru_constraints', function (Blueprint $table) {
                $table->dropUnique('guru_constraints_version_slot_unique');
            });
        }

        Schema::table('guru_constraints', function (Blueprint $table) {
            if (Schema::hasColumn('guru_constraints', 'version_id')) {
                $table->dropConstrainedForeignId('version_id');
            }
            if (Schema::hasColumn('guru_constraints', 'semester_id')) {
                $table->dropConstrainedForeignId('semester_id');
            }
        });
    }

    /**
     * Constraint lama bersifat global — salin ke setiap versi agar perilaku awal tetap sama,
     * lalu edit selanjutnya terisolasi per versi.
     */
    private function backfillConstraintsToVersions(): void
    {
        if (! Schema::hasTable('jadwal_versions')) {
            return;
        }

        $versions = DB::table('jadwal_versions')->orderBy('id')->get(['id', 'semester_id']);
        if ($versions->isEmpty()) {
            return;
        }

        $orphans = DB::table('guru_constraints')->whereNull('version_id')->get();
        if ($orphans->isEmpty()) {
            return;
        }

        $now = now();

        foreach ($orphans as $row) {
            $first = true;
            foreach ($versions as $version) {
                if ($first) {
                    DB::table('guru_constraints')->where('id', $row->id)->update([
                        'semester_id' => $version->semester_id,
                        'version_id' => $version->id,
                        'updated_at' => $now,
                    ]);
                    $first = false;
                    continue;
                }

                DB::table('guru_constraints')->insert([
                    'guru_id' => $row->guru_id,
                    'semester_id' => $version->semester_id,
                    'version_id' => $version->id,
                    'hari' => $row->hari,
                    'jam_ke' => $row->jam_ke,
                    'type' => $row->type,
                    'created_at' => $row->created_at ?? $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        if (method_exists(Schema::class, 'hasIndex')) {
            return Schema::hasIndex($table, $indexName);
        }

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'sqlite') {
            $rows = DB::select("PRAGMA index_list('{$table}')");
            foreach ($rows as $row) {
                if (($row->name ?? '') === $indexName) {
                    return true;
                }
            }

            return false;
        }

        $database = Schema::getConnection()->getDatabaseName();
        $row = DB::selectOne(
            'SELECT COUNT(*) AS cnt FROM information_schema.statistics
             WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$database, $table, $indexName]
        );

        return ((int) ($row->cnt ?? 0)) > 0;
    }
};
