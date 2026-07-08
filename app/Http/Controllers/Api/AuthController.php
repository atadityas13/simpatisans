<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\FormatsApiUser;
use App\Http\Controllers\Controller;
use App\Models\Guru;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    use FormatsApiUser;

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

    public function updatePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => 'required|string',
            'password' => ['required', 'confirmed', Password::min(6)],
        ], [
            'current_password.required' => 'Password saat ini harus diisi.',
            'password.required' => 'Password baru harus diisi.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        if (!Hash::check($validated['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Password saat ini tidak cocok.'],
            ]);
        }

        $user->password = Hash::make($validated['password']);
        $user->plain_password = null;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diperbarui.',
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['success' => true, 'message' => 'Logout berhasil.']);
    }
}
