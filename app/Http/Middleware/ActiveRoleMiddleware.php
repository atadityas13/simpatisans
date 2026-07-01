<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class ActiveRoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $activeRole = session('active_role');

        $hasAdminAccess = ($user->isSuperAdmin() || $user->isAdminKurikulum());
        $hasGuruRef = \App\Models\Guru::where('username', $user->username)->exists();

        // 1. AUTO-INITIALIZE SESSION IF MISSING
        if (!$activeRole) {
            if ($hasAdminAccess && $hasGuruRef) {
                return redirect()->route('auth.select-role');
            }

            if ($hasAdminAccess) {
                $activeRole = 'admin';
                session(['active_role' => 'admin']);
            } else {
                $activeRole = 'guru';
                session(['active_role' => 'guru']);
            }
        }

        // 2. SMART REDIRECTION & ROLE OVERRIDE
        if ($role === 'admin' && $activeRole !== 'admin') {
            // If they are attempting to access admin route but session says guru
            // Check if they even HAVE admin rights
            if (!$hasAdminAccess) {
                return redirect()->route('guru.dashboard')->with('error', 'Anda tidak memiliki hak akses Admin.');
            }
            
            // If they ONLY have admin access but session says guru (somehow), override it
            if (!$hasGuruRef) {
                session(['active_role' => 'admin']);
                return $next($request);
            }

            // Dual-role user in guru mode trying to access admin page
            return redirect()->route('guru.dashboard')->with('error', 'Silakan beralih ke Mode Admin untuk mengakses halaman ini.');
        }

        return $next($request);
    }
}
