<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class GurusTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('gurus')->delete();
        
        
    }
}