<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Master Data (Tabel tanpa foreign key atau dependency utama)
        $this->call(UsersTableSeeder::class);
        $this->call(SemestersTableSeeder::class);
        $this->call(MapelsTableSeeder::class);
        $this->call(KelasTableSeeder::class);
        $this->call(TugasTambahansTableSeeder::class);
        $this->call(RumpunsTableSeeder::class);
        
        // 2. Data Utama (Tabel yang bergantung pada master)
        $this->call(GurusTableSeeder::class); // butuh mapel_ijazah_id (nullable, tapi aman kalau di bawah)

        // 3. Relasi & Pivot (Bergantung pada Guru, Mapel, Kelas, dll)
        $this->call(GuruMapelsTableSeeder::class);
        $this->call(GuruTugasTambahansTableSeeder::class);
        $this->call(BebanMengajarsTableSeeder::class);
        $this->call(GuruConstraintsTableSeeder::class);
        $this->call(MapelRumpunTableSeeder::class);
        
        // 4. Data Transaksional (Jadwal bergantung pada semuanya)
        $this->call(JadwalsTableSeeder::class);
    }
}
