<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Guru;
use App\Models\Jadwal;
use App\Services\JamPelajaranService;
use App\Services\SemesterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GuruDashboardController extends Controller
{
    public function __construct(
        private SemesterService $semesterService,
        private JamPelajaranService $jamPelajaranService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $guru = Guru::where('username', $user->username)->first();

        if (!$guru) {
            return response()->json([
                'success' => true,
                'jtm_hari_ini' => 0,
                'jadwal_hari_ini' => [],
                'semester' => null,
            ]);
        }

        $semester = $this->semesterService->getActiveSemester();
        $hariIni = $this->hariIndonesia();

        $jadwalHariIni = collect();
        if ($semester) {
            $jadwalHariIni = Jadwal::where('semester_id', $semester->id)
                ->whereRaw('LOWER(hari) = ?', [strtolower($hariIni)])
                ->whereHas('bebanMengajar', fn ($q) => $q->where('guru_id', $guru->id))
                ->with(['bebanMengajar.mapel:id,nama_mapel', 'bebanMengajar.kelas:id,nama_kelas'])
                ->orderBy('jam_ke')
                ->get();
        }

        return response()->json([
            'success' => true,
            'semester' => $semester ? [
                'id' => $semester->id,
                'nama' => $semester->full_label,
                'nama_tahun' => $semester->nama_tahun,
                'tipe' => $semester->tipe,
            ] : null,
            'hari_ini' => $hariIni,
            'jtm_hari_ini' => $jadwalHariIni->count(),
            'jadwal_hari_ini' => $jadwalHariIni->map(fn ($j) => [
                'jam_ke' => $j->jam_ke,
                'waktu' => $this->jamPelajaranService->waktuFor($hariIni, (int) $j->jam_ke),
                'mapel' => $j->bebanMengajar?->mapel?->nama_mapel,
                'kelas' => $j->bebanMengajar?->kelas?->nama_kelas,
            ])->values(),
        ]);
    }

    public function jadwal(Request $request): JsonResponse
    {
        $user = $request->user();
        $guru = Guru::where('username', $user->username)->first();

        if (!$guru) {
            return response()->json(['success' => true, 'jadwal' => []]);
        }

        $semester = $this->semesterService->getActiveSemester();
        if (!$semester) {
            return response()->json(['success' => true, 'jadwal' => []]);
        }

        $jadwal = Jadwal::where('semester_id', $semester->id)
            ->whereHas('bebanMengajar', fn ($q) => $q->where('guru_id', $guru->id))
            ->with(['bebanMengajar.mapel:id,nama_mapel', 'bebanMengajar.kelas:id,nama_kelas'])
            ->orderByRaw("FIELD(hari, 'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu')")
            ->orderBy('jam_ke')
            ->get()
            ->map(fn ($j) => [
                'hari' => $j->hari,
                'jam_ke' => $j->jam_ke,
                'waktu' => $this->jamPelajaranService->waktuFor($j->hari, (int) $j->jam_ke),
                'mapel' => $j->bebanMengajar?->mapel?->nama_mapel,
                'kelas' => $j->bebanMengajar?->kelas?->nama_kelas,
            ])
            ->values();

        return response()->json(['success' => true, 'jadwal' => $jadwal]);
    }

    private function hariIndonesia(): string
    {
        $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

        return $days[(int) date('w')];
    }
}
