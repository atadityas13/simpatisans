<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MadaniTokenIntrospector
{
    /**
     * @return array{active: bool, username?: string, nip?: string, name?: string}|null
     *         null = Madani unreachable / misconfigured
     */
    public function inspect(string $plainTextToken): ?array
    {
        $baseUrl = rtrim((string) config('services.madani.url', ''), '/');
        $secret = (string) config('services.madani.introspect_secret', '');

        if ($baseUrl === '' || $secret === '') {
            return null;
        }

        $cacheKey = 'madani_introspect:'.hash('sha256', $plainTextToken);
        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        try {
            $response = Http::timeout(8)
                ->acceptJson()
                ->withHeaders(['X-Madani-Introspect-Secret' => $secret])
                ->post($baseUrl.'/v1/token/introspect', [
                    'token' => $plainTextToken,
                ]);
        } catch (\Throwable $e) {
            Log::warning('Madani introspect gagal: '.$e->getMessage());

            return null;
        }

        if ($response->status() === 401) {
            Log::warning('Madani introspect secret ditolak.');

            return null;
        }

        if (! $response->successful()) {
            $inactive = ['active' => false];
            Cache::put($cacheKey, $inactive, now()->addSeconds(30));

            return $inactive;
        }

        $body = $response->json();
        if (! is_array($body)) {
            return ['active' => false];
        }

        Cache::put($cacheKey, $body, now()->addSeconds(60));

        return $body;
    }
}
