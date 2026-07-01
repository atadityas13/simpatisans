<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('beban_mengajars', 'semester_id')) {
            Schema::table('beban_mengajars', function (Blueprint $table) {
                $table->foreignId('semester_id')->after('id')->nullable()->constrained('semesters')->nullOnDelete();
            });
        }

        if (!Schema::hasColumn('jadwals', 'semester_id')) {
            Schema::table('jadwals', function (Blueprint $table) {
                $table->foreignId('semester_id')->after('id')->nullable()->constrained('semesters')->nullOnDelete();
            });
        }

        if (!Schema::hasColumn('guru_tugas_tambahans', 'semester_id')) {
            Schema::table('guru_tugas_tambahans', function (Blueprint $table) {
                $table->foreignId('semester_id')->after('id')->nullable()->constrained('semesters')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('beban_mengajars', function (Blueprint $table) {
            $table->dropConstrainedForeignId('semester_id');
        });

        Schema::table('jadwals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('semester_id');
        });

        Schema::table('guru_tugas_tambahans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('semester_id');
        });
    }
};
