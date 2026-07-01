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
        Schema::table('users', function (Blueprint $table) {
            // Drop unneeded columns
            $table->dropColumn(['name', 'email', 'email_verified_at']);

            // Add new custom columns
            $table->string('username')->unique()->after('id')->comment('NIP/Username login');
            $table->string('nama_lengkap')->after('username');
            $table->enum('role', ['super_admin', 'admin_kurikulum'])->default('admin_kurikulum')->after('nama_lengkap');
            $table->string('jabatan')->nullable()->after('role');
            $table->string('foto')->nullable()->after('password');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->after('id');
            $table->string('email')->unique()->after('name');
            $table->timestamp('email_verified_at')->nullable()->after('email');

            $table->dropColumn(['username', 'nama_lengkap', 'role', 'jabatan', 'foto']);
        });
    }
};
