<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProtectActiveSemester
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Allowed only for GET/HEAD or if it's the active semester
        if ($request->isMethod('GET') || $request->isMethod('HEAD')) {
            return $next($request);
        }

        $semesterService = app(\App\Services\SemesterService::class);
        $activeSemester = $semesterService->getActiveSemester();

        // If a semester_id is present in the request, it must match the active one
        if ($request->has('semester_id') && (int)$request->semester_id !== $activeSemester->id) {
            abort(403, 'Aksi dilarang pada semester tidak aktif.');
        }

        return $next($request);
    }
}
