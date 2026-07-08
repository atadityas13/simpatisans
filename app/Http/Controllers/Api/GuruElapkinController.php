<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Guru;
use App\Models\TugasTambahan;
use App\Models\User;
use App\Services\SemesterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GuruElapkinController extends Controller
{
    public function __construct(private SemesterService $semesterService)
    {
    }

    /**
     * Terbitkan ticket SSO + profil guru dari SimpatiSans.
     */
    public function ssoToken(Request $request): JsonResponse
    {
        $ticket = $this->buildSsoTicket($request->user());

        return response()->json([
            'success' => true,
            ...$ticket,
            'expires_in' => 300,
        ]);
    }

    /**
     * Buka sesi e-Lapkin dari server (hindari masalah serialisasi JSON di Android).
     */
    public function bridgeSession(Request $request): JsonResponse
    {
        $ticket = $this->buildSsoTicket($request->user());
        $apiUrl = rtrim(config('services.elapkin.mobile_url'), '/').'/api/auth/sso.php';

        $mobileToken = $this->generateMobileToken();

        try {
            $response = Http::timeout(20)
                ->asJson()
                ->withHeaders([
                    'User-Agent' => config('services.elapkin.talim_user_agent'),
                    'X-Mobile-Token' => $mobileToken,
                    'X-App-Package' => config('services.elapkin.talim_package'),
                    'Accept' => 'application/json',
                ])
                ->post($apiUrl, [
                    'nip' => $ticket['nip'],
                    'timestamp' => $ticket['timestamp'],
                    'signature' => $ticket['signature'],
                    'profile_hash' => $ticket['profile_hash'],
                    'profile' => $ticket['profile'],
                ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat menghubungi server e-Lapkin.',
            ], 502);
        }

        $payload = null;
        $rawBody = (string) $response->body();
        try {
            $payload = $response->json();
        } catch (\Throwable $e) {
            $payload = null;
        }

        if (! $response->successful() || ! ($payload['success'] ?? false)) {
            $message = $payload['message']
                ?? $payload['error']
                ?? null;

            if (! is_string($message) || $message === '') {
                $message = 'SSO e-Lapkin ditolak.';
            }

            // Bantuan debug (agar kita tahu kenapa ditolak)
            $debug = mb_substr(trim($rawBody), 0, 400);
            if ($debug !== '') {
                $message .= " (HTTP {$response->status()}: {$debug})";
            }

            Log::error('elapkin-bridge rejected', [
                'elapkin_http_status' => $response->status(),
                'nip' => $ticket['nip'] ?? null,
                'timestamp' => $ticket['timestamp'] ?? null,
                'profile_hash' => $ticket['profile_hash'] ?? null,
                'elapkin_body_preview' => $debug,
                'app_package' => config('services.elapkin.talim_package'),
                'user_agent' => config('services.elapkin.talim_user_agent'),
            ]);

            return response()->json([
                'success' => false,
                'message' => $message,
            ], 401);
        }

        $cookies = $this->extractCookies($response);
        if ($cookies === '') {
            return response()->json([
                'success' => false,
                'message' => 'SSO berhasil tetapi cookie sesi tidak diterima dari e-Lapkin.',
            ], 502);
        }

        return response()->json([
            'success' => true,
            'message' => 'Sesi Kinerja berhasil dibuka.',
            'cookies' => $cookies,
            'kepala_madrasah' => $ticket['kepala_madrasah'],
        ]);
    }

    private function buildSsoTicket(User $user): array
    {
        $guru = Guru::where('username', $user->username)
            ->with(['mapelDiampu:id,nama_mapel', 'tugasTambahans:id,nama_tugas'])
            ->first();

        $semester = $this->semesterService->getActiveSemester();
        $kepalaMadrasah = $this->resolveKepalaMadrasah($semester?->id);

        $profile = [
            'nip' => $user->username,
            'nama' => $guru?->nama_lengkap ?? $user->getRawOriginal('nama_lengkap') ?? $user->username,
            'jabatan' => $guru?->jabatan ?? $user->getRawOriginal('jabatan') ?? 'Guru',
            'kode_guru' => $guru?->kode_guru,
            'guru_id' => $guru?->id,
            'nuptk' => $guru?->nuptk,
            'golongan' => $guru?->golongan,
            'unit_kerja' => 'MTsN 11 Majalengka',
            'nip_penilai' => $kepalaMadrasah?->username,
            'nama_penilai' => $kepalaMadrasah?->nama_lengkap,
            'jabatan_penilai' => $kepalaMadrasah?->jabatan ?? 'Kepala Madrasah',
            'penilai_peran' => 'Kepala Madrasah',
            'mapel' => $guru?->mapelDiampu?->pluck('nama_mapel')->values()->all() ?? [],
            'tugas_tambahan' => $guru?->tugasTambahans?->pluck('nama_tugas')->values()->all() ?? [],
        ];

        $kepala = $kepalaMadrasah ? [
            'nip' => $kepalaMadrasah->username,
            'nama' => $kepalaMadrasah->nama_lengkap,
            'jabatan' => $kepalaMadrasah->jabatan ?? 'Kepala Madrasah',
            'peran' => 'Penilai LKH/RKB',
        ] : null;

        $timestamp = time();
        $profileJson = json_encode($profile, JSON_UNESCAPED_UNICODE);
        $profileHash = hash('sha256', $profileJson);
        $payload = $user->username.'|'.$timestamp.'|'.$profileHash;
        $secret = config('services.elapkin.sso_secret');
        $signature = hash_hmac('sha256', $payload, $secret);

        return [
            'nip' => $user->username,
            'timestamp' => $timestamp,
            'signature' => $signature,
            'profile_hash' => $profileHash,
            'profile' => $profile,
            'kepala_madrasah' => $kepala,
        ];
    }

    private function generateMobileToken(): string
    {
        $date = now('Asia/Jakarta')->format('Y-m-d');
        $secret = config('services.elapkin.mobile_secret');

        return md5($secret.$date);
    }

    private function extractCookies(\Illuminate\Http\Client\Response $response): string
    {
        $parts = [];

        foreach ($response->cookies() as $cookie) {
            $parts[] = $cookie->getName().'='.$cookie->getValue();
        }

        if ($parts === []) {
            $headers = $response->header('Set-Cookie');
            $lines = is_array($headers) ? $headers : ($headers ? [$headers] : []);
            foreach ($lines as $line) {
                $pair = trim(explode(';', (string) $line)[0]);
                if (str_contains($pair, '=')) {
                    $parts[] = $pair;
                }
            }
        }

        return implode("\n", array_unique($parts));
    }

    private function resolveKepalaMadrasah(?int $semesterId): ?Guru
    {
        if (! $semesterId) {
            return Guru::whereHas('tugasTambahans', fn ($q) => $q->where('tugas_tambahan_id', TugasTambahan::KEPALA_MADRASAH_ID))->first();
        }

        return Guru::whereHas('tugasTambahans', function ($q) use ($semesterId) {
            $q->where('tugas_tambahan_id', TugasTambahan::KEPALA_MADRASAH_ID)
                ->where('guru_tugas_tambahans.semester_id', $semesterId);
        })->first();
    }
}
