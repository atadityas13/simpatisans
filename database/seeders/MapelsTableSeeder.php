<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class MapelsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('mapels')->delete();
        
        \DB::table('mapels')->insert(array (
            0 => 
            array (
                'id' => 1,
                'nama_mapel' => 'Al-Qur\'an Hadis',
                'jtm_default' => 2,
                'created_at' => '2026-04-05 16:51:34',
                'updated_at' => '2026-04-05 16:51:34',
            ),
            1 => 
            array (
                'id' => 2,
                'nama_mapel' => 'Akidah Akhlak',
                'jtm_default' => 2,
                'created_at' => '2026-04-05 17:25:05',
                'updated_at' => '2026-04-05 17:25:05',
            ),
            2 => 
            array (
                'id' => 3,
                'nama_mapel' => 'Fikih',
                'jtm_default' => 2,
                'created_at' => '2026-04-06 05:56:24',
                'updated_at' => '2026-04-06 05:56:24',
            ),
            3 => 
            array (
                'id' => 4,
                'nama_mapel' => 'Sejarah Kebudayaan Islam',
                'jtm_default' => 2,
                'created_at' => '2026-04-06 05:56:37',
                'updated_at' => '2026-04-06 05:56:37',
            ),
            4 => 
            array (
                'id' => 5,
                'nama_mapel' => 'Bahasa Arab',
                'jtm_default' => 3,
                'created_at' => '2026-04-06 05:57:25',
                'updated_at' => '2026-04-06 05:57:25',
            ),
            5 => 
            array (
                'id' => 6,
                'nama_mapel' => 'Pendidikan Pancasila',
                'jtm_default' => 2,
                'created_at' => '2026-04-06 05:58:08',
                'updated_at' => '2026-04-06 05:58:08',
            ),
            6 => 
            array (
                'id' => 7,
                'nama_mapel' => 'Bahasa Indonesia',
                'jtm_default' => 5,
                'created_at' => '2026-04-06 05:58:27',
                'updated_at' => '2026-04-06 05:58:27',
            ),
            7 => 
            array (
                'id' => 8,
                'nama_mapel' => 'Matematika',
                'jtm_default' => 4,
                'created_at' => '2026-04-06 05:58:56',
                'updated_at' => '2026-04-06 05:58:56',
            ),
            8 => 
            array (
                'id' => 9,
                'nama_mapel' => 'Ilmu Pengetahuan Alam',
                'jtm_default' => 4,
                'created_at' => '2026-04-06 05:59:24',
                'updated_at' => '2026-04-06 05:59:24',
            ),
            9 => 
            array (
                'id' => 10,
                'nama_mapel' => 'Ilmu Pengetahuan Sosial',
                'jtm_default' => 3,
                'created_at' => '2026-04-06 06:00:04',
                'updated_at' => '2026-04-06 06:00:04',
            ),
            10 => 
            array (
                'id' => 11,
                'nama_mapel' => 'Bahasa Inggris',
                'jtm_default' => 3,
                'created_at' => '2026-04-06 06:00:51',
                'updated_at' => '2026-04-06 06:00:51',
            ),
            11 => 
            array (
                'id' => 12,
                'nama_mapel' => 'Pendidikan Jasmani, Olahraga dan Kesehatan',
                'jtm_default' => 2,
                'created_at' => '2026-04-06 06:01:27',
                'updated_at' => '2026-04-06 06:01:27',
            ),
            12 => 
            array (
                'id' => 13,
                'nama_mapel' => 'Informatika',
                'jtm_default' => 2,
                'created_at' => '2026-04-06 06:01:48',
                'updated_at' => '2026-04-06 06:01:48',
            ),
            13 => 
            array (
                'id' => 14,
                'nama_mapel' => 'Seni dan Budaya',
                'jtm_default' => 2,
                'created_at' => '2026-04-06 06:22:10',
                'updated_at' => '2026-04-06 06:22:10',
            ),
            14 => 
            array (
                'id' => 15,
                'nama_mapel' => 'Bahasa Sunda',
                'jtm_default' => 2,
                'created_at' => '2026-04-06 06:28:39',
                'updated_at' => '2026-04-06 06:28:39',
            ),
            15 => 
            array (
                'id' => 16,
                'nama_mapel' => 'Tahfidz Al-Qur\'an',
                'jtm_default' => 1,
                'created_at' => '2026-04-06 06:29:10',
                'updated_at' => '2026-04-06 06:29:10',
            ),
            16 => 
            array (
                'id' => 17,
                'nama_mapel' => 'Baca Tulis Al-Qur\'an',
                'jtm_default' => 1,
                'created_at' => '2026-04-06 06:29:50',
                'updated_at' => '2026-04-06 06:29:50',
            ),
        ));
        
        
    }
}