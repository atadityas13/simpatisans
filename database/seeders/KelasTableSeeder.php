<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class KelasTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('kelas')->delete();
        
        \DB::table('kelas')->insert(array (
            0 => 
            array (
                'id' => 1,
                'nama_kelas' => 'Kelas VII.1',
                'tingkat' => 'VII',
                'created_at' => '2026-04-05 16:37:09',
                'updated_at' => '2026-04-05 16:37:09',
            ),
            1 => 
            array (
                'id' => 2,
                'nama_kelas' => 'Kelas VII.2',
                'tingkat' => 'VII',
                'created_at' => '2026-04-05 16:37:09',
                'updated_at' => '2026-04-05 16:37:09',
            ),
            2 => 
            array (
                'id' => 3,
                'nama_kelas' => 'Kelas VII.3',
                'tingkat' => 'VII',
                'created_at' => '2026-04-05 16:37:09',
                'updated_at' => '2026-04-05 16:37:09',
            ),
            3 => 
            array (
                'id' => 4,
                'nama_kelas' => 'Kelas VII.4',
                'tingkat' => 'VII',
                'created_at' => '2026-04-05 16:37:09',
                'updated_at' => '2026-04-05 16:37:09',
            ),
            4 => 
            array (
                'id' => 5,
                'nama_kelas' => 'Kelas VII.5',
                'tingkat' => 'VII',
                'created_at' => '2026-04-05 16:37:09',
                'updated_at' => '2026-04-05 16:37:09',
            ),
            5 => 
            array (
                'id' => 6,
                'nama_kelas' => 'Kelas VII.6',
                'tingkat' => 'VII',
                'created_at' => '2026-04-05 16:37:09',
                'updated_at' => '2026-04-05 16:37:09',
            ),
            6 => 
            array (
                'id' => 7,
                'nama_kelas' => 'Kelas VIII.1',
                'tingkat' => 'VIII',
                'created_at' => '2026-04-05 16:42:37',
                'updated_at' => '2026-04-05 16:42:37',
            ),
            7 => 
            array (
                'id' => 8,
                'nama_kelas' => 'Kelas VIII.2',
                'tingkat' => 'VIII',
                'created_at' => '2026-04-05 16:42:37',
                'updated_at' => '2026-04-05 16:42:37',
            ),
            8 => 
            array (
                'id' => 9,
                'nama_kelas' => 'Kelas VIII.3',
                'tingkat' => 'VIII',
                'created_at' => '2026-04-05 16:42:37',
                'updated_at' => '2026-04-05 16:42:37',
            ),
            9 => 
            array (
                'id' => 10,
                'nama_kelas' => 'Kelas VIII.4',
                'tingkat' => 'VIII',
                'created_at' => '2026-04-05 16:42:37',
                'updated_at' => '2026-04-05 16:42:37',
            ),
            10 => 
            array (
                'id' => 11,
                'nama_kelas' => 'Kelas VIII.5',
                'tingkat' => 'VIII',
                'created_at' => '2026-04-05 16:42:37',
                'updated_at' => '2026-04-05 16:42:37',
            ),
            11 => 
            array (
                'id' => 12,
                'nama_kelas' => 'Kelas VIII.6',
                'tingkat' => 'VIII',
                'created_at' => '2026-04-05 16:42:37',
                'updated_at' => '2026-04-05 16:42:37',
            ),
            12 => 
            array (
                'id' => 19,
                'nama_kelas' => 'Kelas IX.1',
                'tingkat' => 'IX',
                'created_at' => '2026-07-01 02:12:21',
                'updated_at' => '2026-07-01 02:12:21',
            ),
            13 => 
            array (
                'id' => 20,
                'nama_kelas' => 'Kelas IX.2',
                'tingkat' => 'IX',
                'created_at' => '2026-07-01 02:12:21',
                'updated_at' => '2026-07-01 02:12:21',
            ),
            14 => 
            array (
                'id' => 21,
                'nama_kelas' => 'Kelas IX.3',
                'tingkat' => 'IX',
                'created_at' => '2026-07-01 02:12:21',
                'updated_at' => '2026-07-01 02:12:21',
            ),
            15 => 
            array (
                'id' => 22,
                'nama_kelas' => 'Kelas IX.4',
                'tingkat' => 'IX',
                'created_at' => '2026-07-01 02:12:21',
                'updated_at' => '2026-07-01 02:12:21',
            ),
            16 => 
            array (
                'id' => 23,
                'nama_kelas' => 'Kelas IX.5',
                'tingkat' => 'IX',
                'created_at' => '2026-07-01 02:12:21',
                'updated_at' => '2026-07-01 02:12:21',
            ),
            17 => 
            array (
                'id' => 24,
                'nama_kelas' => 'Kelas IX.6',
                'tingkat' => 'IX',
                'created_at' => '2026-07-01 02:12:21',
                'updated_at' => '2026-07-01 02:12:21',
            ),
        ));
        
        
    }
}