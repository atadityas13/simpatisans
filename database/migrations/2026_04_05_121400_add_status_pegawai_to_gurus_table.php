<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('gurus', function (Blueprint $table) {
            $table->string('status_pegawai')->after('duk')->default('NON_ASN');
        });

        // Migrate data
        DB::table('gurus')->where('is_asn', true)->update(['status_pegawai' => 'PNS']);
        DB::table('gurus')->where('is_asn', false)->update(['status_pegawai' => 'NON_ASN']);

        Schema::table('gurus', function (Blueprint $table) {
            $table->dropColumn('is_asn');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gurus', function (Blueprint $table) {
            $table->boolean('is_asn')->default(false)->after('duk');
        });

        // Reverse migration
        DB::table('gurus')->whereIn('status_pegawai', ['PNS', 'PPPK'])->update(['is_asn' => true]);
        DB::table('gurus')->where('status_pegawai', 'NON_ASN')->update(['is_asn' => false]);

        Schema::table('gurus', function (Blueprint $table) {
            $table->dropColumn('status_pegawai');
        });
    }
};
