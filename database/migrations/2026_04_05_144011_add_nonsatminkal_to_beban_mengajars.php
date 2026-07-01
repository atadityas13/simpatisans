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
        Schema::table('beban_mengajars', function (Blueprint $table) {
            $table->boolean('is_satminkal')->default(true)->after('jtm');
            $table->integer('jumlah_kelas')->nullable()->after('is_satminkal');
            $table->json('hari')->nullable()->after('jumlah_kelas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('beban_mengajars', function (Blueprint $table) {
            $table->dropColumn(['is_satminkal', 'jumlah_kelas', 'hari']);
        });
    }
};
