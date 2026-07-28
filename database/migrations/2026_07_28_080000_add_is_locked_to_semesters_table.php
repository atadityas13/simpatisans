<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('semesters', 'is_locked')) {
            Schema::table('semesters', function (Blueprint $table) {
                $table->boolean('is_locked')->default(false)->after('is_active');
            });
        }

        // Default: semester aktif → tidak terkunci; non-aktif → terkunci
        DB::table('semesters')->where('is_active', true)->update(['is_locked' => false]);
        DB::table('semesters')->where('is_active', false)->update(['is_locked' => true]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('semesters', 'is_locked')) {
            Schema::table('semesters', function (Blueprint $table) {
                $table->dropColumn('is_locked');
            });
        }
    }
};
