<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Guru;
use App\Models\TugasTambahan;
use App\Services\SemesterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GuruElapkinController extends Controller
{
    public function __construct(private SemesterService $semesterService)
    {
    }

    /**
     * Terbitkan ticket SSO + profil guru dari SimpatiSans.
     * Aturan bisnis: penilai LKH/RKB selalu Kepala Madrasah (semester aktif).
     */
    public function ssoToken(Request $request): JsonResponse
    {
        $user = $request->user();
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
            // Penilai = Kepala Madrasah (bukan Waka / role lain)
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

        return response()->json([
            'success' => true,
            'nip' => $user->username,
            'timestamp' => $timestamp,
            'signature' => $signature,
            'profile_hash' => $profileHash,
            'profile' => $profile,
            'kepala_madrasah' => $kepala,
            'expires_in' => 300,
        ]);
    }

    private function resolveKepalaMadrasah(?int $semesterId): ?Guru
    {
        // Penilai kinerja selalu guru yang memegang tugas Kepala Madrasah.
        if (!$semesterId) {
            return Guru::whereHas('tugasTambahans', fn ($q) => $q->where('tugas_tambahan_id', TugasTambahan::KEPALA_MADRASAH_ID))->first();
        }

        return Guru::whereHas('tugasTambahans', function ($q) use ($semesterId) {
            $q->where('tugas_tambahan_id', TugasTambahan::KEPALA_MADRASAH_ID)
                ->where('guru_tugas_tambahans.semester_id', $semesterId);
        })->first();
    }
}
