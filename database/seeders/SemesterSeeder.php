<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SemesterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $activeSemester = \App\Models\Semester::create([
            'nama_tahun' => '2024/2025',
            'tipe' => 'Ganjil',
            'is_active' => true,
        ]);

        // Update existing records to point to this semester
        \App\Models\BebanMengajar::whereNull('semester_id')->update(['semester_id' => $activeSemester->id]);
        \App\Models\Jadwal::whereNull('semester_id')->update(['semester_id' => $activeSemester->id]);
        \App\Models\GuruTugasTambahan::whereNull('semester_id')->update(['semester_id' => $activeSemester->id]);
    }
}
