<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class RumpunsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('rumpuns')->delete();
        
        \DB::table('rumpuns')->insert(array (
            0 => 
            array (
                'id' => 1,
                'nama_rumpun' => 'Pendidikan Agama Islam',
                'created_at' => '2026-04-05 16:51:34',
                'updated_at' => '2026-04-05 16:51:34',
            ),
            1 => 
            array (
                'id' => 2,
                'nama_rumpun' => 'TIK',
                'created_at' => '2026-04-06 06:20:05',
                'updated_at' => '2026-04-06 06:20:05',
            ),
        ));
        
        
    }
}