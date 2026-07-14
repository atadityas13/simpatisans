<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pastikan tabel pengumuman tersedia di production
 * (meski migrasi sebelumnya belum/gagal dijalankan).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pengumumen') && ! Schema::hasTable('pengumuman')) {
            Schema::rename('pengumumen', 'pengumuman');

            return;
        }

        if (! Schema::hasTable('pengumuman')) {
            Schema::create('pengumuman', function (Blueprint $table) {
                $table->id();
                $table->string('judul');
                $table->text('isi');
                $table->boolean('is_active')->default(true);
                $table->timestamp('published_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->index('is_active');
                $table->index('published_at');
            });
        }
    }

    public function down(): void
    {
        // Tidak drop — data pengumuman jangan hilang saat rollback.
    }
};
