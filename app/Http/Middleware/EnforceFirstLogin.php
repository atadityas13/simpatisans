<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceFirstLogin
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        // Jika user belum login, biarkan middleware 'auth' yang menangani
        if (!$user) {
            return $next($request);
        }

        // Jika user masih memiliki plain_password (belum ganti password pertama kali)
        if ($user->plain_password !== null) {
            // Izinkan akses hanya ke halaman first-login, update passwordnya, dan logout
            $allowedRoutes = ['first-login', 'first-login.post', 'logout'];
            
            if (!in_array($request->route()->getName(), $allowedRoutes)) {
                return redirect()->route('first-login')->with('warning', 'Anda wajib mengganti password default sebelum melanjutkan.');
            }
        }

        return $next($request);
    }
}
