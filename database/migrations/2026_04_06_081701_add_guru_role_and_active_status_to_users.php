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
            $table->boolean('is_active')->default(true)->after('password');
            $table->string('plain_password')->nullable()->after('is_active');
            $table->timestamp('reset_requested_at')->nullable()->after('plain_password');
        });

        // Since Laravel struggles with modifying ENUMs across different DB engines, 
        // string conversion is the safest bet to add a new role type securely or we change it to 'string'.
        // For simplicity, we just alter it to string. 
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('admin_kurikulum')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'plain_password', 'reset_requested_at']);
        });
    }
};
