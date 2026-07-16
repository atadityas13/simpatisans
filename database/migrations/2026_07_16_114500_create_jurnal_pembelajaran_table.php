<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jurnal_pembelajaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guru_id')->constrained('gurus')->cascadeOnDelete();
            $table->foreignId('semester_id')->constrained('semesters')->cascadeOnDelete();
            $table->foreignId('kelas_id')->constrained('kelas')->cascadeOnDelete();
            $table->foreignId('mapel_id')->constrained('mapels')->cascadeOnDelete();
            $table->foreignId('jadwal_id')->nullable()->constrained('jadwals')->nullOnDelete();
            $table->date('tanggal');
            $table->string('hari', 20);
            $table->unsignedTinyInteger('jam_ke')->default(0);
            $table->text('materi_pokok');
            $table->enum('ketercapaian', ['tercapai', 'belum'])->default('tercapai');
            $table->text('penugasan_siswa')->nullable();
            $table->text('catatan_guru')->nullable();
            $table->timestamps();

            $table->unique(
                ['guru_id', 'semester_id', 'kelas_id', 'mapel_id', 'tanggal', 'jam_ke'],
                'jurnal_pembelajaran_unique_slot'
            );
            $table->index(['guru_id', 'semester_id', 'kelas_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jurnal_pembelajaran');
    }
};
