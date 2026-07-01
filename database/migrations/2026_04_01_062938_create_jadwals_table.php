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
        Schema::create('jadwals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('beban_mengajar_id')->constrained('beban_mengajars')->cascadeOnDelete();
            $table->string('hari');
            $table->integer('jam_ke');
            $table->timestamps();
            
            // Unik untuk mencegah bentrok pada database level jika memungkinkan,
            // (Walaupun kita handles di SAO)
            $table->unique(['beban_mengajar_id', 'hari', 'jam_ke']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jadwals');
    }
};
