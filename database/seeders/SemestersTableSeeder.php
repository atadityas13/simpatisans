<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class SemestersTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('semesters')->delete();
        
        \DB::table('semesters')->insert(array (
            0 => 
            array (
                'id' => 1,
                'nama_tahun' => '2026/2027',
                'tipe' => 'Ganjil',
                'is_active' => 1,
                'created_at' => '2026-04-05 16:29:33',
                'updated_at' => '2026-04-05 16:29:33',
            ),
        ));
        
        
    }
}