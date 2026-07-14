<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pengumuman;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;

class GuruPengumumanController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $items = Pengumuman::published()
                ->orderByDesc('published_at')
                ->orderByDesc('id')
                ->limit(50)
                ->get()
                ->map(function (Pengumuman $p) {
                    $at = $p->published_at ?? $p->created_at;

                    return [
                        'id' => $p->id,
                        'judul' => $p->judul,
                        'isi' => $p->isi,
                        'published_at' => $at
                            ? $at->copy()->timezone('Asia/Jakarta')->toIso8601String()
                            : null,
                    ];
                });
        } catch (QueryException $e) {
            $items = collect();
        }

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }
}
