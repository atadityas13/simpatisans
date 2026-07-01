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
        DB::table('tugas_tambahans')->insert([
            'nama_tugas' => 'Guru Piket',
            'jtm_ekuivalen' => 1,
            'tipe' => 'system',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('tugas_tambahans')->where('nama_tugas', 'Guru Piket')->delete();
    }
};
