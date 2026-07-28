<?php

namespace App\Http\Middleware;

use App\Models\BebanMengajar;
use App\Models\Semester;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProtectActiveSemester
{
    /**
     * Blokir mutasi pembagian tugas / jadwal jika semester terkunci.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET') || $request->isMethod('HEAD')) {
            return $next($request);
        }

        $semesterId = $this->resolveSemesterId($request);
        if ($semesterId === null) {
            return $next($request);
        }

        $semester = Semester::find($semesterId);
        if ($semester && $semester->is_locked) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['message' => 'Semester terkunci. Buka kunci di Pengaturan Semester untuk mengedit.'], 403);
            }

            return redirect()->back()->with('error', 'Semester terkunci. Buka kunci di Pengaturan Semester untuk mengedit.');
        }

        return $next($request);
    }

    private function resolveSemesterId(Request $request): ?int
    {
        if ($request->filled('semester_id')) {
            return (int) $request->input('semester_id');
        }

        $beban = $request->route('beban') ?? $request->route('kbm');
        if ($beban instanceof BebanMengajar) {
            return (int) $beban->semester_id;
        }

        if (is_numeric($beban)) {
            return (int) (BebanMengajar::find($beban)?->semester_id);
        }

        return null;
    }
}
