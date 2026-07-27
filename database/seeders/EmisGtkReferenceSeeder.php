<?php

namespace Database\Seeders;

use App\Services\EmisGtk\EmisGtkReferenceService;
use Illuminate\Database\Seeder;

class EmisGtkReferenceSeeder extends Seeder
{
    public function run(EmisGtkReferenceService $service): void
    {
        $path = database_path('emis_references');
        if (! is_dir($path)) {
            return;
        }

        $service->importAll($path);
    }
}
