<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Shared secret for Madani → SimpatiSans export endpoints (rombels, etc.).
 */
class AuthenticateMadaniExport
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('services.madani.introspect_secret', '');
        $provided = (string) $request->header('X-Madani-Introspect-Secret', '');

        if ($expected === '' || ! hash_equals($expected, $provided)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 401);
        }

        return $next($request);
    }
}
