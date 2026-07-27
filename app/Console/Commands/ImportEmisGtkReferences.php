<?php

namespace App\Console\Commands;

use App\Services\EmisGtk\EmisGtkReferenceService;
use Illuminate\Console\Command;

class ImportEmisGtkReferences extends Command
{
    protected $signature = 'emis:import-references {--path= : Path folder referensi EMIS}';

    protected $description = 'Impor referensi EMIS-GTK (PTK, mapel, kode kelas) ke database SimpatiSans';

    public function handle(EmisGtkReferenceService $service): int
    {
        $path = $this->option('path') ?? database_path('emis_references');

        if (! is_dir($path)) {
            $this->error("Folder tidak ditemukan: {$path}");

            return self::FAILURE;
        }

        $summary = $service->importAll($path);

        $this->info('Mapel diperbarui: '.$summary['mapels']['updated']);
        if ($summary['mapels']['missing'] !== []) {
            $this->warn('Mapel tanpa ID EMIS: '.implode(', ', $summary['mapels']['missing']));
        }

        $this->info('Guru cocok ID GTK: '.$summary['gurus']['matched']);
        if ($summary['gurus']['ambiguous'] !== []) {
            $this->warn('Guru ambigu (perlu review manual): '.implode(', ', $summary['gurus']['ambiguous']));
        }
        if ($summary['gurus']['unmatched'] !== []) {
            $this->warn('PTK tidak cocok: '.implode(', ', $summary['gurus']['unmatched']));
        }

        $this->info('Kelas kode EMIS: '.$summary['kelas']['updated']);

        return self::SUCCESS;
    }
}
