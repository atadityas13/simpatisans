<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TugasTambahanSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['id' => 1, 'nama_tugas' => 'Kepala Madrasah',      'jtm_ekuivalen' => 24, 'tipe' => 'system'],
            ['id' => 2, 'nama_tugas' => 'Wakil Kepala Madrasah', 'jtm_ekuivalen' => 12, 'tipe' => 'system'],
            ['id' => 3, 'nama_tugas' => 'Wali Kelas',            'jtm_ekuivalen' => 6,  'tipe' => 'system'],
            ['id' => 4, 'nama_tugas' => 'Guru Piket',            'jtm_ekuivalen' => 1,  'tipe' => 'system'],
        ];

        foreach ($data as $item) {
            DB::table('tugas_tambahans')->updateOrInsert(
                ['id' => $item['id']],
                [
                    'nama_tugas'    => $item['nama_tugas'],
                    'jtm_ekuivalen' => $item['jtm_ekuivalen'],
                    'tipe'          => $item['tipe'],
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]
            );
        }
    }
}
