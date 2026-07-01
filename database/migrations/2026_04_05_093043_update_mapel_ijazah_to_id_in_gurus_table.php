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
            $table->foreignId('mapel_ijazah_id')->nullable()->after('status_sertifikasi')->constrained('mapels')->nullOnDelete();
        });

        // Migrate existing string data to the new relation
        $gurus = DB::table('gurus')->get();
        foreach ($gurus as $guru) {
            if ($guru->mapel_ijazah) {
                $mapel = DB::table('mapels')->where('nama_mapel', $guru->mapel_ijazah)->first();
                if ($mapel) {
                    DB::table('gurus')->where('id', $guru->id)->update(['mapel_ijazah_id' => $mapel->id]);
                }
            }
        }

        Schema::table('gurus', function (Blueprint $table) {
            $table->dropColumn('mapel_ijazah');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gurus', function (Blueprint $table) {
            $table->string('mapel_ijazah')->nullable()->after('status_sertifikasi');
        });

        // Restore string values from relations
        $gurus = DB::table('gurus')->get();
        foreach ($gurus as $guru) {
            if ($guru->mapel_ijazah_id) {
                $mapel = DB::table('mapels')->where('id', $guru->mapel_ijazah_id)->first();
                if ($mapel) {
                    DB::table('gurus')->where('id', $guru->id)->update(['mapel_ijazah' => $mapel->nama_mapel]);
                }
            }
        }

        Schema::table('gurus', function (Blueprint $table) {
            $table->dropForeign(['mapel_ijazah_id']);
            $table->dropColumn('mapel_ijazah_id');
        });
    }
};
