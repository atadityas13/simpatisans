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
        $this->call(BebanMengajarsTableSeeder::class);
        $this->call(GuruConstraintsTableSeeder::class);
        $this->call(GuruMapelsTableSeeder::class);
        $this->call(GuruTugasTambahansTableSeeder::class);
        $this->call(GurusTableSeeder::class);
        $this->call(JadwalsTableSeeder::class);
        $this->call(KelasTableSeeder::class);
        $this->call(MapelRumpunTableSeeder::class);
        $this->call(MapelsTableSeeder::class);
        $this->call(RumpunsTableSeeder::class);
        $this->call(SemestersTableSeeder::class);
        $this->call(TugasTambahansTableSeeder::class);
        $this->call(UsersTableSeeder::class);
    }
}
