<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('jadwal_versions')) {
            Schema::create('jadwal_versions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('semester_id')->constrained('semesters')->cascadeOnDelete();
                $table->string('name');
                $table->boolean('is_default')->default(false);
                $table->timestamps();

                $table->unique(['semester_id', 'name']);
                $table->index(['semester_id', 'is_default']);
            });
        }

        $now = now();
        $semesters = DB::table('semesters')->select('id')->get();
        foreach ($semesters as $semester) {
            $exists = DB::table('jadwal_versions')
                ->where('semester_id', $semester->id)
                ->where('is_default', true)
                ->exists();

            if (! $exists) {
                DB::table('jadwal_versions')->insert([
                    'semester_id' => $semester->id,
                    'name' => 'Operasional',
                    'is_default' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $this->addVersionColumnIfMissing('beban_mengajars');
        $this->addVersionColumnIfMissing('jadwals');
        $this->addVersionColumnIfMissing('guru_tugas_tambahans');

        $defaults = DB::table('jadwal_versions')
            ->where('is_default', true)
            ->pluck('id', 'semester_id');

        foreach ($defaults as $semesterId => $versionId) {
            DB::table('beban_mengajars')
                ->where('semester_id', $semesterId)
                ->whereNull('version_id')
                ->update(['version_id' => $versionId]);

            DB::table('jadwals')
                ->where('semester_id', $semesterId)
                ->whereNull('version_id')
                ->update(['version_id' => $versionId]);

            DB::table('guru_tugas_tambahans')
                ->where('semester_id', $semesterId)
                ->whereNull('version_id')
                ->update(['version_id' => $versionId]);
        }

        $fallbackVersionId = $defaults->first();
        if ($fallbackVersionId) {
            DB::table('beban_mengajars')->whereNull('version_id')->update(['version_id' => $fallbackVersionId]);
            DB::table('jadwals')->whereNull('version_id')->update(['version_id' => $fallbackVersionId]);
            DB::table('guru_tugas_tambahans')->whereNull('version_id')->update(['version_id' => $fallbackVersionId]);
        }

        $this->replaceGuruTugasUniqueConstraint();
    }

    public function down(): void
    {
        if (Schema::hasTable('guru_tugas_tambahans')) {
            $this->dropForeignIfExists('guru_tugas_tambahans', ['guru_id']);
            $this->dropForeignIfExists('guru_tugas_tambahans', ['tugas_tambahan_id']);

            if ($this->indexExists('guru_tugas_tambahans', 'gtt_guru_tugas_semester_version_unique')) {
                Schema::table('guru_tugas_tambahans', function (Blueprint $table) {
                    $table->dropUnique('gtt_guru_tugas_semester_version_unique');
                });
            }

            if (! $this->indexExists('guru_tugas_tambahans', 'guru_tugas_tambahans_guru_id_tugas_tambahan_id_unique')) {
                Schema::table('guru_tugas_tambahans', function (Blueprint $table) {
                    $table->unique(['guru_id', 'tugas_tambahan_id']);
                });
            }

            Schema::table('guru_tugas_tambahans', function (Blueprint $table) {
                $table->foreign('guru_id')->references('id')->on('gurus')->cascadeOnDelete();
                $table->foreign('tugas_tambahan_id')->references('id')->on('tugas_tambahans')->cascadeOnDelete();
            });

            if (Schema::hasColumn('guru_tugas_tambahans', 'version_id')) {
                Schema::table('guru_tugas_tambahans', function (Blueprint $table) {
                    $table->dropConstrainedForeignId('version_id');
                });
            }
        }

        if (Schema::hasColumn('jadwals', 'version_id')) {
            Schema::table('jadwals', function (Blueprint $table) {
                $table->dropConstrainedForeignId('version_id');
            });
        }

        if (Schema::hasColumn('beban_mengajars', 'version_id')) {
            Schema::table('beban_mengajars', function (Blueprint $table) {
                $table->dropConstrainedForeignId('version_id');
            });
        }

        Schema::dropIfExists('jadwal_versions');
    }

    private function addVersionColumnIfMissing(string $table): void
    {
        if (Schema::hasColumn($table, 'version_id')) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) {
            $blueprint->foreignId('version_id')
                ->nullable()
                ->after('semester_id')
                ->constrained('jadwal_versions')
                ->cascadeOnDelete();
        });
    }

    /**
     * MySQL tidak bisa drop unique (guru_id, tugas_tambahan_id) selama masih dipakai FK.
     * Lepas FK dulu, pastikan index tunggal ada, lalu ganti unique.
     */
    private function replaceGuruTugasUniqueConstraint(): void
    {
        if ($this->indexExists('guru_tugas_tambahans', 'gtt_guru_tugas_semester_version_unique')) {
            return;
        }

        $this->dropForeignIfExists('guru_tugas_tambahans', ['guru_id']);
        $this->dropForeignIfExists('guru_tugas_tambahans', ['tugas_tambahan_id']);

        // Index tunggal agar FK bisa dipasang kembali tanpa bergantung pada unique lama.
        if (! $this->indexExists('guru_tugas_tambahans', 'guru_tugas_tambahans_guru_id_index')) {
            Schema::table('guru_tugas_tambahans', function (Blueprint $table) {
                $table->index('guru_id');
            });
        }
        if (! $this->indexExists('guru_tugas_tambahans', 'guru_tugas_tambahans_tugas_tambahan_id_index')) {
            Schema::table('guru_tugas_tambahans', function (Blueprint $table) {
                $table->index('tugas_tambahan_id');
            });
        }

        if ($this->indexExists('guru_tugas_tambahans', 'guru_tugas_tambahans_guru_id_tugas_tambahan_id_unique')) {
            Schema::table('guru_tugas_tambahans', function (Blueprint $table) {
                $table->dropUnique('guru_tugas_tambahans_guru_id_tugas_tambahan_id_unique');
            });
        }

        Schema::table('guru_tugas_tambahans', function (Blueprint $table) {
            $table->unique(
                ['guru_id', 'tugas_tambahan_id', 'semester_id', 'version_id'],
                'gtt_guru_tugas_semester_version_unique'
            );
        });

        Schema::table('guru_tugas_tambahans', function (Blueprint $table) {
            $table->foreign('guru_id')->references('id')->on('gurus')->cascadeOnDelete();
            $table->foreign('tugas_tambahan_id')->references('id')->on('tugas_tambahans')->cascadeOnDelete();
        });
    }

    private function dropForeignIfExists(string $table, array $columns): void
    {
        try {
            Schema::table($table, function (Blueprint $blueprint) use ($columns) {
                $blueprint->dropForeign($columns);
            });
        } catch (\Throwable) {
            // Nama FK bisa berbeda antar environment; abaikan jika sudah tidak ada.
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        if ($driver === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list('{$table}')");
            foreach ($indexes as $index) {
                if (($index->name ?? '') === $indexName) {
                    return true;
                }
            }

            return false;
        }

        $database = $connection->getDatabaseName();
        $row = DB::selectOne(
            'SELECT COUNT(*) AS cnt
             FROM information_schema.statistics
             WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$database, $table, $indexName]
        );

        return ((int) ($row->cnt ?? 0)) > 0;
    }
};
