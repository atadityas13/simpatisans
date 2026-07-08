<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Guru;
use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\TugasTambahan;
use App\Services\CetakPresetService;
use App\Services\SemesterService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class GuruCetakController extends Controller
{
    public function __construct(
        private SemesterService $semesterService,
        private CetakPresetService $cetakPresetService,
    ) {
    }

    public function jadwalPelajaran(Request $request): Response
    {
        $guru = $this->resolveGuru($request);
        if (! $guru) {
            return response('Profil guru tidak ditemukan.', 404);
        }

        $activeSemester = $this->semesterService->getActiveSemester();
        if (! $activeSemester) {
            return response('Tidak ada semester aktif.', 404);
        }

        $semesterId = $activeSemester->id;

        $kelasList = Kelas::orderByRaw("FIELD(tingkat, 'VII', 'VIII', 'IX')")
            ->orderBy('nama_kelas')
            ->get()
            ->groupBy('tingkat');

        $allKelas = Kelas::orderByRaw("FIELD(tingkat, 'VII', 'VIII', 'IX')")
            ->orderBy('nama_kelas')
            ->get();

        $jadwals = Jadwal::where('semester_id', $semesterId)
            ->with(['bebanMengajar.guru', 'bebanMengajar.mapel'])
            ->get();

        $mapels = Mapel::orderBy('id')->get();
        $mapelNoMap = $mapels->pluck('id')->flip()->map(fn ($v) => str_pad($v + 1, 2, '0', STR_PAD_LEFT));

        $grid = [];
        foreach ($jadwals as $j) {
            if ($j && $j->bebanMengajar && $j->bebanMengajar->guru && $j->bebanMengajar->mapel) {
                $kg = $j->bebanMengajar->guru->kode_guru ?? '-';
                $mn = $mapelNoMap[$j->bebanMengajar->mapel_id] ?? '00';
                $grid[$j->hari][$j->jam_ke][$j->bebanMengajar->kelas_id] = $kg.'-'.$mn;
            }
        }

        $gurus = Guru::orderedByDuk()->get();

        $kepalaMadrasah = Guru::whereHas('tugasTambahans', function ($q) use ($semesterId) {
            $q->where('tugas_tambahan_id', TugasTambahan::KEPALA_MADRASAH_ID)
                ->where('semester_id', $semesterId);
        })->first();

        $wakaKurikulum = Guru::whereHas('tugasTambahans', function ($q) use ($semesterId) {
            $q->where('tugas_tambahan_id', TugasTambahan::WAKA_ID)
                ->where('detail', 'LIKE', '%Kurikulum%')
                ->where('semester_id', $semesterId);
        })->first();

        return response()->view('admin.cetak.jadwal-pelajaran', array_merge(
            compact(
                'activeSemester',
                'kelasList',
                'allKelas',
                'grid',
                'gurus',
                'mapels',
                'kepalaMadrasah',
                'wakaKurikulum',
            ),
            $this->cetakPresetService->viewData(),
            [
                'guruMobileView' => false,
                'preselectedGuru' => $guru,
            ]
        ));
    }

    public function lampiranSk(Request $request): Response
    {
        $guru = $this->resolveGuru($request);
        if (! $guru) {
            return response('Profil guru tidak ditemukan.', 404);
        }

        $activeSemester = $this->semesterService->getActiveSemester();
        if (! $activeSemester) {
            return response('Tidak ada semester aktif.', 404);
        }

        $semesterId = $activeSemester->id;

        $kelasList = Kelas::orderByRaw("FIELD(tingkat, 'VII', 'VIII', 'IX')")
            ->orderBy('nama_kelas')
            ->get()
            ->groupBy('tingkat');

        $allKelas = Kelas::orderByRaw("FIELD(tingkat, 'VII', 'VIII', 'IX')")
            ->orderBy('nama_kelas')
            ->get();

        $gurus = Guru::with([
            'bebanMengajars' => function ($q) use ($semesterId) {
                $q->where('semester_id', $semesterId)->with(['mapel.rumpuns', 'kelas']);
            },
            'tugasTambahans' => function ($q) use ($semesterId) {
                $q->where('semester_id', $semesterId)
                    ->orderByPivot('is_ekuivalen', 'desc');
            },
            'mapelSertifikasi',
        ])
            ->orderedByDuk()
            ->get();

        $kepalaMadrasah = Guru::whereHas('tugasTambahans', function ($q) use ($semesterId) {
            $q->where('tugas_tambahan_id', TugasTambahan::KEPALA_MADRASAH_ID)
                ->where('semester_id', $semesterId);
        })->first();

        return response()->view('admin.cetak.lampiran-sk', array_merge(
            compact('activeSemester', 'kelasList', 'allKelas', 'gurus', 'kepalaMadrasah'),
            $this->cetakPresetService->viewData(),
            [
                'preselectedGuru' => $guru,
            ]
        ));
    }

    private function resolveGuru(Request $request): ?Guru
    {
        return Guru::where('username', $request->user()->username)->first();
    }
}
