<?php

namespace App\Http\Middleware;

use App\Models\Guru;
use App\Models\User;
use App\Services\MadaniTokenIntrospector;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * Auth API Ta'lim: token Sanctum lokal ATAU token Madani (Opsi C introspect).
 */
class AuthenticateTalimApi
{
    public function __construct(private MadaniTokenIntrospector $madaniIntrospector) {}

    public function handle(Request $request, Closure $next): Response
    {
        $bearer = $request->bearerToken();
        if (! is_string($bearer) || $bearer === '') {
            return $this->unauthorized();
        }

        $local = PersonalAccessToken::findToken($bearer);
        if ($local && $local->tokenable instanceof User) {
            /** @var User $user */
            $user = $local->tokenable;
            if (! $user->is_active) {
                return $this->unauthorized('Akun dinonaktifkan.');
            }
            Auth::setUser($user);
            $request->setUserResolver(fn () => $user);

            return $next($request);
        }

        $remote = $this->madaniIntrospector->inspect($bearer);
        if ($remote === null) {
            return $this->unauthorized('Tidak dapat memvalidasi token Madani.');
        }

        if (! ($remote['active'] ?? false)) {
            return $this->unauthorized();
        }

        $username = trim((string) ($remote['nip'] ?? $remote['username'] ?? ''));
        if ($username === '') {
            return $this->unauthorized();
        }

        $user = $this->resolveLocalUser($username, (string) ($remote['name'] ?? ''));
        if (! $user) {
            return $this->unauthorized('Akun guru belum terdaftar di SimpatiSans (data jadwal).');
        }

        Auth::setUser($user);
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }

    private function resolveLocalUser(string $username, string $name): ?User
    {
        $user = User::where('username', $username)->first();
        if ($user) {
            if (! $user->is_active) {
                return null;
            }

            return $user;
        }

        $guru = Guru::where('username', $username)->first();
        if (! $guru) {
            return null;
        }

        // User ephemeral untuk request ini — cukup username agar controller resolve Guru.
        $ephemeral = new User([
            'username' => $username,
            'nama_lengkap' => $name !== '' ? $name : $guru->nama_guru,
            'role' => 'guru',
            'is_active' => true,
        ]);
        $ephemeral->exists = false;

        return $ephemeral;
    }

    private function unauthorized(string $message = 'Unauthenticated.'): Response
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], 401);
    }
}
