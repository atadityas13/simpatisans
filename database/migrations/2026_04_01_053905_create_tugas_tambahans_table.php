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
        Schema::create('tugas_tambahans', function (Blueprint $table) {
            $table->id();
            $table->string('nama_tugas');
            $table->integer('jtm_ekuivalen')->default(12);
            $table->enum('tipe', ['system', 'custom'])->default('custom');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tugas_tambahans');
    }
};
