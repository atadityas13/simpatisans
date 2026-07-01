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
        
        \DB::table('gurus')->insert(array (
            0 => 
            array (
                'id' => 1,
                'username' => '196801171992031002',
                'kode_guru' => 'DA',
                'duk' => 1,
                'status_pegawai' => 'PNS',
                'nama_guru' => 'H. DEDE APIP MUSTOPA',
                'gelar_depan' => NULL,
                'gelar_belakang' => 'S.Ag.',
                'nuptk' => '91000068110144',
                'jabatan' => 'Ahli Madya - Guru Akidah Akhlak',
                'golongan' => 'IV/a',
                'status_sertifikasi' => 1,
                'is_bk' => 0,
                'mapel_ijazah_id' => NULL,
                'rumpun_ijazah_id' => 1,
                'mapel_sertifikasi_id' => 2,
                'created_at' => '2026-04-13 02:22:51',
                'updated_at' => '2026-04-13 02:22:51',
            ),
            1 => 
            array (
                'id' => 2,
                'username' => '197112282023211002',
                'kode_guru' => 'MS',
                'duk' => 2,
                'status_pegawai' => 'PPPK',
                'nama_guru' => 'MAMAN SUPRATMAN',
                'gelar_depan' => NULL,
                'gelar_belakang' => 'S.Sos.',
                'nuptk' => '91000071125588',
                'jabatan' => 'Ahli Pertama - Guru PKN',
                'golongan' => 'IX',
                'status_sertifikasi' => 1,
                'is_bk' => 0,
                'mapel_ijazah_id' => 6,
                'rumpun_ijazah_id' => NULL,
                'mapel_sertifikasi_id' => 6,
                'created_at' => '2026-04-13 03:03:34',
                'updated_at' => '2026-07-01 02:08:12',
            ),
        ));
        
        
    }
}