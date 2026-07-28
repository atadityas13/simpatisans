<?php

namespace App\Http\Middleware;

use App\Models\BebanMengajar;
use App\Models\JadwalVersion;
use App\Models\Semester;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProtectActiveSemester
{
    /**
     * Blokir mutasi pembagian tugas / jadwal jika semester atau versi terkunci.
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
            return $this->deny($request, 'Semester terkunci. Buka kunci semester di Pengaturan Semester untuk mengedit.');
        }

        $versionId = $this->resolveVersionId($request);
        if ($versionId !== null) {
            $version = JadwalVersion::find($versionId);
            if ($version && (int) $version->semester_id === (int) $semesterId && $version->is_locked) {
                return $this->deny($request, "Versi \"{$version->name}\" terkunci. Buka kunci versi di Pengaturan Semester untuk mengedit.");
            }
        }

        return $next($request);
    }

    private function deny(Request $request, string $message): Response
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['message' => $message], 403);
        }

        return redirect()->back()->with('error', $message);
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

    private function resolveVersionId(Request $request): ?int
    {
        if ($request->filled('version_id')) {
            return (int) $request->input('version_id');
        }

        $beban = $request->route('beban') ?? $request->route('kbm');
        if ($beban instanceof BebanMengajar) {
            return $beban->version_id ? (int) $beban->version_id : null;
        }

        if (is_numeric($beban)) {
            $found = BebanMengajar::find($beban);

            return $found?->version_id ? (int) $found->version_id : null;
        }

        return null;
    }
}
