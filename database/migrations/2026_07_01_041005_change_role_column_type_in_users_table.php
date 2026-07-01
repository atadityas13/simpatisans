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
        // Menggunakan SQL mentah untuk mengubah ENUM menjadi VARCHAR agar MySQL patuh
        \DB::statement("ALTER TABLE users MODIFY COLUMN role VARCHAR(50) NOT NULL DEFAULT 'guru'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('super_admin', 'admin_kurikulum') NOT NULL DEFAULT 'admin_kurikulum'");
    }
};
