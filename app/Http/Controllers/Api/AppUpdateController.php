<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppUpdate;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;

class AppUpdateController extends Controller
{
    public function show(string $platform): JsonResponse
    {
        if (! in_array($platform, ['android'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Platform tidak didukung.',
            ], 404);
        }

        try {
            $item = AppUpdate::activePlatform($platform)->first();
        } catch (QueryException $e) {
            $item = null;
        }

        return response()->json([
            'success' => true,
            'data' => $item ? [
                'platform' => $item->platform,
                'latest_version_code' => $item->latest_version_code,
                'latest_version_name' => $item->latest_version_name,
                'minimum_version_code' => $item->minimum_version_code,
                'title' => $item->title,
                'message' => $item->message,
                'changelog' => $item->changelog,
                'play_store_url' => $item->play_store_url,
                'updated_at' => $item->updated_at?->copy()->timezone('Asia/Jakarta')->toIso8601String(),
            ] : null,
        ]);
    }
}
