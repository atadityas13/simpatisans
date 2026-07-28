<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('jadwal_versions')) {
            return;
        }

        if (! Schema::hasColumn('jadwal_versions', 'is_locked')) {
            Schema::table('jadwal_versions', function (Blueprint $table) {
                $table->boolean('is_locked')->default(false)->after('is_default');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('jadwal_versions') && Schema::hasColumn('jadwal_versions', 'is_locked')) {
            Schema::table('jadwal_versions', function (Blueprint $table) {
                $table->dropColumn('is_locked');
            });
        }
    }
};
