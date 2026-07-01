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
        Schema::table('gurus', function (Blueprint $table) {
            $table->string('gelar_depan')->nullable()->after('nama_guru');
            $table->renameColumn('gelar', 'gelar_belakang');
            $table->boolean('is_asn')->default(true)->after('duk');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gurus', function (Blueprint $table) {
            $table->dropColumn(['gelar_depan', 'is_asn']);
            $table->renameColumn('gelar_belakang', 'gelar');
        });
    }
};
