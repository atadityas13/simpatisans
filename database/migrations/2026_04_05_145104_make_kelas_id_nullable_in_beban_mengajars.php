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
            $table->foreignId('kelas_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('beban_mengajars', function (Blueprint $table) {
            $table->foreignId('kelas_id')->nullable(false)->change();
        });
    }
};
