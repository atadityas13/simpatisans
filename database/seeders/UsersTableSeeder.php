<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('users')->delete();
        
        \DB::table('users')->insert(array (
            0 => 
            array (
                'id' => 1,
                'username' => '199802132025211005',
                'nama_lengkap' => 'Anzas Tio Aditya',
                'role' => 'super_admin',
                'jabatan' => 'Operator Layanan Operasional',
                'security_question' => 'Makanan/Minuman Kesukaan?',
                'security_answer' => 'Apapun yang mengandung Ketan',
                'reset_answer_provided' => NULL,
                'password' => '$2y$12$yQ/FKsEjN7MF9HC4k2f69.SjjfnB2YSDWpVih/lQZw6iufGof7aqe',
                'is_active' => 1,
                'plain_password' => NULL,
                'reset_requested_at' => NULL,
                'foto' => 'admin_photos/6FrHpz7lMAdgVN1q1ciyc0MW03J7CzPYwCVgF6cN.png',
                'remember_token' => NULL,
                'created_at' => '2026-04-09 05:13:26',
                'updated_at' => '2026-04-09 05:18:06',
            ),
        ));
        
        
    }
}