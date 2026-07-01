<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Guru;
use App\Models\Mapel;
use App\Models\TugasTambahan;

echo "JABATAN LIST:\n";
print_r(Guru::pluck('jabatan')->unique()->toArray());

echo "\nMAPEL LIST:\n";
print_r(Mapel::pluck('nama_mapel')->toArray());

echo "\nTUGAS TAMBAHAN LIST:\n";
print_r(TugasTambahan::pluck('nama_tugas')->toArray());
