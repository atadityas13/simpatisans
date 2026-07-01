<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class MapelRumpunTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('mapel_rumpun')->delete();
        
        \DB::table('mapel_rumpun')->insert(array (
            0 => 
            array (
                'id' => 1,
                'mapel_id' => 1,
                'rumpun_id' => 1,
                'created_at' => '2026-04-06 06:15:16',
                'updated_at' => '2026-04-06 06:15:16',
            ),
            1 => 
            array (
                'id' => 2,
                'mapel_id' => 2,
                'rumpun_id' => 1,
                'created_at' => '2026-04-06 06:15:16',
                'updated_at' => '2026-04-06 06:15:16',
            ),
            2 => 
            array (
                'id' => 3,
                'mapel_id' => 3,
                'rumpun_id' => 1,
                'created_at' => '2026-04-06 06:15:16',
                'updated_at' => '2026-04-06 06:15:16',
            ),
            3 => 
            array (
                'id' => 4,
                'mapel_id' => 4,
                'rumpun_id' => 1,
                'created_at' => '2026-04-06 06:15:16',
                'updated_at' => '2026-04-06 06:15:16',
            ),
            4 => 
            array (
                'id' => 5,
                'mapel_id' => 13,
                'rumpun_id' => 2,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
        ));
        
        
    }
}