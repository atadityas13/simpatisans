<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\FormatsApiUser;
use App\Http\Controllers\Controller;
use App\Models\Guru;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GuruProfileController extends Controller
{
    use FormatsApiUser;

    public function updateBiodata(Request $request): JsonResponse
    {
        $user = $request->user();
        $guru = $this->resolveGuru($user);

        $validated = $request->validate([
            'jenis_kelamin' => 'nullable|in:L,P',
            'tempat_lahir' => 'nullable|string|max:100',
            'tanggal_lahir' => 'nullable|date',
            'agama' => 'nullable|string|max:50',
        ]);

        $guru->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Data pribadi berhasil diperbarui.',
            'user' => $this->formatUser($user->fresh()),
        ]);
    }

    public function updateKontak(Request $request): JsonResponse
    {
        $user = $request->user();
        $guru = $this->resolveGuru($user);

        $validated = $request->validate([
            'nomor_hp' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'alamat' => 'nullable|string|max:500',
        ]);

        $guru->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Data kontak berhasil diperbarui.',
            'user' => $this->formatUser($user->fresh()),
        ]);
    }

    private function resolveGuru($user): Guru
    {
        if (!$user->isGuru()) {
            abort(403, 'Hanya akun guru yang dapat memperbarui profil ini.');
        }

        return Guru::where('username', $user->username)->firstOrFail();
    }
}
