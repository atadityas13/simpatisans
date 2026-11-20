<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Mapel;
use App\Models\Rumpun;
use Illuminate\Http\JsonResponse;

class MadaniReferensiController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'mapels' => Mapel::query()->orderBy('nama_mapel')->pluck('nama_mapel')->values(),
            'rumpuns' => Rumpun::query()->orderBy('nama_rumpun')->pluck('nama_rumpun')->values(),
        ]);
    }
}
