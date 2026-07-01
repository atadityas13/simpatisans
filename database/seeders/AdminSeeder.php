<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Add default super admin based on user instructions
        // If it exists, update it. If not, insert.
        DB::table('users')->updateOrInsert(
            ['username' => '199802132025211005'],
            [
                'nama_lengkap' => 'Admin SIPASTI',
                'role' => 'super_admin',
                'jabatan' => 'Sistem Administrator',
                'password' => Hash::make('021398'),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
