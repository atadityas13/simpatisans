<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pengumuman;
use Illuminate\Http\JsonResponse;

class GuruPengumumanController extends Controller
{
    public function index(): JsonResponse
    {
        $items = Pengumuman::published()
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->map(fn (Pengumuman $p) => [
                'id' => $p->id,
                'judul' => $p->judul,
                'isi' => $p->isi,
                'published_at' => optional($p->published_at ?? $p->created_at)->toIso8601String(),
            ]);

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }
}
