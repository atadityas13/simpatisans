<?php

namespace App\Http\Middleware;

use App\Services\MadaniTokenIntrospector;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Auth API Ta'lim siswa: Bearer token Madani (introspect), tanpa akun lokal SimpatiSans.
 */
class AuthenticateMadaniSiswa
{
    public function __construct(private MadaniTokenIntrospector $madaniIntrospector) {}

    public function handle(Request $request, Closure $next): Response
    {
        $bearer = $request->bearerToken();
        if (! is_string($bearer) || $bearer === '') {
            return $this->unauthorized();
        }

        $remote = $this->madaniIntrospector->inspect($bearer);
        if ($remote === null) {
            return $this->unauthorized('Tidak dapat memvalidasi token Madani.');
        }

        if (! ($remote['active'] ?? false) || ($remote['role'] ?? null) !== 'siswa') {
            return $this->unauthorized();
        }

        $request->attributes->set('madani_siswa', $remote);

        return $next($request);
    }

    private function unauthorized(string $message = 'Unauthenticated.'): Response
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], 401);
    }
}
