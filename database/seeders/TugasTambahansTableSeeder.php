<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TugasTambahansTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('tugas_tambahans')->delete();
        
        \DB::table('tugas_tambahans')->insert(array (
            0 => 
            array (
                'id' => 1,
                'nama_tugas' => 'Kepala Madrasah',
                'jtm_ekuivalen' => 24,
                'tipe' => 'system',
                'created_at' => '2026-04-05 17:50:50',
                'updated_at' => '2026-04-05 17:50:50',
            ),
            1 => 
            array (
                'id' => 2,
                'nama_tugas' => 'Wakil Kepala Madrasah',
                'jtm_ekuivalen' => 12,
                'tipe' => 'system',
                'created_at' => '2026-04-05 16:07:03',
                'updated_at' => '2026-04-05 16:07:03',
            ),
            2 => 
            array (
                'id' => 3,
                'nama_tugas' => 'Wali Kelas',
                'jtm_ekuivalen' => 6,
                'tipe' => 'system',
                'created_at' => '2026-04-05 16:07:03',
                'updated_at' => '2026-04-05 16:07:03',
            ),
            3 => 
            array (
                'id' => 4,
                'nama_tugas' => 'Guru Piket',
                'jtm_ekuivalen' => 1,
                'tipe' => 'system',
                'created_at' => '2026-04-05 16:07:03',
                'updated_at' => '2026-04-05 16:07:03',
            ),
            4 => 
            array (
                'id' => 5,
                'nama_tugas' => 'Kepala Asrama',
                'jtm_ekuivalen' => 12,
                'tipe' => 'custom',
                'created_at' => '2026-04-05 16:41:01',
                'updated_at' => '2026-04-05 18:00:55',
            ),
            5 => 
            array (
                'id' => 6,
                'nama_tugas' => 'Kepala Perpustakaan',
                'jtm_ekuivalen' => 12,
                'tipe' => 'custom',
                'created_at' => '2026-04-05 18:01:43',
                'updated_at' => '2026-04-05 18:01:43',
            ),
            6 => 
            array (
                'id' => 7,
                'nama_tugas' => 'Kepala Laboratorium',
                'jtm_ekuivalen' => 12,
                'tipe' => 'custom',
                'created_at' => '2026-04-05 18:02:16',
                'updated_at' => '2026-04-05 18:02:16',
            ),
            7 => 
            array (
                'id' => 8,
                'nama_tugas' => 'Pembina OSIS',
                'jtm_ekuivalen' => 6,
                'tipe' => 'custom',
                'created_at' => '2026-04-05 18:02:54',
                'updated_at' => '2026-04-05 18:02:54',
            ),
            8 => 
            array (
                'id' => 9,
                'nama_tugas' => 'Pembina Keagamaan',
                'jtm_ekuivalen' => 6,
                'tipe' => 'custom',
                'created_at' => '2026-04-05 18:03:15',
                'updated_at' => '2026-04-05 18:03:15',
            ),
            9 => 
            array (
                'id' => 10,
                'nama_tugas' => 'Pembina Pramuka',
                'jtm_ekuivalen' => 6,
                'tipe' => 'custom',
                'created_at' => '2026-04-05 18:03:31',
                'updated_at' => '2026-04-05 18:03:31',
            ),
            10 => 
            array (
                'id' => 11,
                'nama_tugas' => 'Pembina PMR',
                'jtm_ekuivalen' => 6,
                'tipe' => 'custom',
                'created_at' => '2026-04-05 18:04:18',
                'updated_at' => '2026-04-05 18:04:18',
            ),
            11 => 
            array (
                'id' => 12,
                'nama_tugas' => 'Pembina PKS',
                'jtm_ekuivalen' => 6,
                'tipe' => 'custom',
                'created_at' => '2026-04-05 18:06:38',
                'updated_at' => '2026-04-05 18:07:07',
            ),
            12 => 
            array (
                'id' => 13,
                'nama_tugas' => 'Pembina Marching Band',
                'jtm_ekuivalen' => 6,
                'tipe' => 'custom',
                'created_at' => '2026-04-05 18:07:24',
                'updated_at' => '2026-04-05 18:07:24',
            ),
        ));
        
        
    }
}