<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Guru;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ], [
            'username.required' => 'NIP/NIK harus diisi.',
            'password.required' => 'Password harus diisi.',
        ]);

        $username = $credentials['username'];
        $user = User::where('username', $username)->first();

        if (!$user) {
            $guruExists = Guru::where('username', $username)->exists();
            $message = $guruExists
                ? 'Akun Anda belum diaktifkan, silakan hubungi Admin.'
                : 'NIP/NIK belum terdaftar di sistem.';

            throw ValidationException::withMessages(['username' => [$message]]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'username' => ['Akun Anda dinonaktifkan. Silakan hubungi admin.'],
            ]);
        }

        if (!Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['Password salah. Coba lagi.'],
            ]);
        }

        $user->tokens()->where('name', 'talim-mobile')->delete();
        $token = $user->createToken('talim-mobile')->plainTextToken;

        return response()->json([
            'success' => true,
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $this->formatUser($user),
            'requires_password_change' => $user->plain_password !== null,
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'user' => $this->formatUser($request->user()),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['success' => true, 'message' => 'Logout berhasil.']);
    }

    private function formatUser(User $user): array
    {
        $guru = Guru::where('username', $user->username)
            ->with([
                'mapelDiampu:id,nama_mapel',
                'mapelIjazah:id,nama_mapel',
                'mapelSertifikasi:id,nama_mapel',
                'rumpunIjazah:id,nama_rumpun',
            ])
            ->first();

        return [
            'id' => $user->id,
            'username' => $user->username,
            'nip' => $user->username,
            'nama_lengkap' => $guru?->nama_lengkap ?? $user->getRawOriginal('nama_lengkap'),
            'jabatan' => $guru?->jabatan ?? $user->getRawOriginal('jabatan'),
            'role' => $user->role,
            'foto' => $user->foto ? asset('storage/' . $user->foto) : null,
            'guru' => $guru ? $this->formatGuru($guru) : null,
        ];
    }

    private function formatGuru(Guru $guru): array
    {
        return [
            'id' => $guru->id,
            'kode_guru' => $guru->kode_guru,
            'duk' => $guru->duk,
            'gelar_depan' => $guru->gelar_depan,
            'gelar_belakang' => $guru->gelar_belakang,
            'nuptk' => $guru->nuptk,
            'golongan' => $guru->golongan,
            'status_pegawai' => $guru->status_pegawai,
            'status_sertifikasi' => (bool) $guru->status_sertifikasi,
            'is_bk' => (bool) $guru->is_bk,
            'jenis_kelamin' => $guru->jenis_kelamin,
            'tempat_lahir' => $guru->tempat_lahir,
            'tanggal_lahir' => $guru->tanggal_lahir?->format('Y-m-d'),
            'agama' => $guru->agama,
            'nomor_hp' => $guru->nomor_hp,
            'email' => $guru->email,
            'alamat' => $guru->alamat,
            'mapel_ijazah' => $guru->kualifikasi_ijazah,
            'mapel_sertifikasi' => $guru->mapelSertifikasi?->nama_mapel,
            'mapel' => $guru->mapelDiampu->pluck('nama_mapel')->values(),
        ];
    }
}
