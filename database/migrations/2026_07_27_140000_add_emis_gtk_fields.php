<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gurus', function (Blueprint $table) {
            $table->string('id_gtk', 32)->nullable()->after('nuptk');
            $table->index('id_gtk');
        });

        Schema::table('mapels', function (Blueprint $table) {
            $table->string('id_mapel_emis_7', 32)->nullable()->after('jtm_default');
            $table->string('id_mapel_emis_8', 32)->nullable()->after('id_mapel_emis_7');
            $table->string('id_mapel_emis_9', 32)->nullable()->after('id_mapel_emis_8');
        });

        Schema::table('kelas', function (Blueprint $table) {
            $table->string('tingkat_emis', 2)->nullable()->after('tingkat');
            $table->string('rombel_emis', 2)->nullable()->after('tingkat_emis');
        });
    }

    public function down(): void
    {
        Schema::table('gurus', function (Blueprint $table) {
            $table->dropIndex(['id_gtk']);
            $table->dropColumn('id_gtk');
        });

        Schema::table('mapels', function (Blueprint $table) {
            $table->dropColumn(['id_mapel_emis_7', 'id_mapel_emis_8', 'id_mapel_emis_9']);
        });

        Schema::table('kelas', function (Blueprint $table) {
            $table->dropColumn(['tingkat_emis', 'rombel_emis']);
        });
    }
};
