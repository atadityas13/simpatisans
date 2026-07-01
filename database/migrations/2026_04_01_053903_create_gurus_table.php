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
        Schema::create('gurus', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique()->comment('NIP / NIK');
            $table->string('kode_guru', 2)->unique()->comment('Kode 2 huruf');
            $table->unsignedSmallInteger('duk')->nullable()->comment('Nomor Urut Kepangkatan');
            $table->string('nama_guru');
            $table->string('gelar')->nullable();
            $table->string('nuptk')->nullable();
            $table->string('jabatan')->nullable();
            $table->string('golongan')->nullable();
            $table->boolean('status_sertifikasi')->default(false);
            $table->string('mapel_ijazah')->nullable();
            $table->foreignId('mapel_sertifikasi_id')->nullable()->constrained('mapels')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gurus');
    }
};
