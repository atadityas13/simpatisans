<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class GuruMapelsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('guru_mapels')->delete();
        
        \DB::table('guru_mapels')->insert(array (
            0 => 
            array (
                'id' => 1,
                'guru_id' => 1,
                'mapel_id' => 2,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            1 => 
            array (
                'id' => 3,
                'guru_id' => 2,
                'mapel_id' => 6,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
        ));
        
        
    }
}