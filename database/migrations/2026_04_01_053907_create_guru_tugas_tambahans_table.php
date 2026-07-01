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
        Schema::create('guru_tugas_tambahans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guru_id')->constrained('gurus')->cascadeOnDelete();
            $table->foreignId('tugas_tambahan_id')->constrained('tugas_tambahans')->cascadeOnDelete();
            $table->boolean('is_ekuivalen')->default(false);
            $table->string('detail')->nullable()->comment('kelas_id for wali kelas, bidang for waka');
            $table->unique(['guru_id', 'tugas_tambahan_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guru_tugas_tambahans');
    }
};
