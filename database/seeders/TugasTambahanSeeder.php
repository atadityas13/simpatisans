<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TugasTambahanSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('tugas_tambahans')->insert([
            [
                'id'             => 1,
                'nama_tugas'     => 'Kepala Madrasah',
                'jtm_ekuivalen'  => 24,
                'tipe'           => 'system',
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
            [
                'id'             => 2,
                'nama_tugas'     => 'Wakil Kepala Madrasah',
                'jtm_ekuivalen'  => 12,
                'tipe'           => 'system',
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
            [
                'id'             => 3,
                'nama_tugas'     => 'Wali Kelas',
                'jtm_ekuivalen'  => 6,
                'tipe'           => 'system',
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
            [
                'id'             => 4,
                'nama_tugas'     => 'Guru Piket',
                'jtm_ekuivalen'  => 1,
                'tipe'           => 'system',
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
        ]);
    }
}
