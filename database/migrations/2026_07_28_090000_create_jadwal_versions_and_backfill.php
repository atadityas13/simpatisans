<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jadwal_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('semester_id')->constrained('semesters')->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->unique(['semester_id', 'name']);
            $table->index(['semester_id', 'is_default']);
        });

        $now = now();
        $semesters = DB::table('semesters')->select('id')->get();
        foreach ($semesters as $semester) {
            DB::table('jadwal_versions')->insert([
                'semester_id' => $semester->id,
                'name' => 'Operasional',
                'is_default' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        Schema::table('beban_mengajars', function (Blueprint $table) {
            $table->foreignId('version_id')
                ->nullable()
                ->after('semester_id')
                ->constrained('jadwal_versions')
                ->cascadeOnDelete();
        });

        Schema::table('jadwals', function (Blueprint $table) {
            $table->foreignId('version_id')
                ->nullable()
                ->after('semester_id')
                ->constrained('jadwal_versions')
                ->cascadeOnDelete();
        });

        Schema::table('guru_tugas_tambahans', function (Blueprint $table) {
            $table->foreignId('version_id')
                ->nullable()
                ->after('semester_id')
                ->constrained('jadwal_versions')
                ->cascadeOnDelete();
        });

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

        // Orphan rows without semester: attach to first default version if any.
        $fallbackVersionId = $defaults->first();
        if ($fallbackVersionId) {
            DB::table('beban_mengajars')->whereNull('version_id')->update(['version_id' => $fallbackVersionId]);
            DB::table('jadwals')->whereNull('version_id')->update(['version_id' => $fallbackVersionId]);
            DB::table('guru_tugas_tambahans')->whereNull('version_id')->update(['version_id' => $fallbackVersionId]);
        }

        Schema::table('guru_tugas_tambahans', function (Blueprint $table) {
            $table->dropUnique(['guru_id', 'tugas_tambahan_id']);
            $table->unique(['guru_id', 'tugas_tambahan_id', 'semester_id', 'version_id'], 'gtt_guru_tugas_semester_version_unique');
        });
    }

    public function down(): void
    {
        Schema::table('guru_tugas_tambahans', function (Blueprint $table) {
            $table->dropUnique('gtt_guru_tugas_semester_version_unique');
            $table->unique(['guru_id', 'tugas_tambahan_id']);
        });

        Schema::table('guru_tugas_tambahans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('version_id');
        });

        Schema::table('jadwals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('version_id');
        });

        Schema::table('beban_mengajars', function (Blueprint $table) {
            $table->dropConstrainedForeignId('version_id');
        });

        Schema::dropIfExists('jadwal_versions');
    }
};
