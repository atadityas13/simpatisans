<?php

namespace App\Http\Controllers;

use App\Models\TugasTambahan;
use App\Models\Guru;
use App\Services\CetakPresetService;
use App\Services\SemesterService;
use App\Models\Jadwal;
use App\Models\Kelas;
use Illuminate\Http\Request;

class CetakController extends Controller
{
    protected $semesterService;
    protected $cetakPresetService;

    public function __construct(SemesterService $semesterService, CetakPresetService $cetakPresetService)
    {
        $this->semesterService = $semesterService;
        $this->cetakPresetService = $cetakPresetService;
    }

    /**
     * Display the print menu page.
     */
    public function index()
    {
        $activeSemester = $this->semesterService->getActiveSemester();

        $presets = [
            'ttd_kepala' => \Illuminate\Support\Facades\Storage::disk('public')->exists('presets/ttd_kepala.png') ? asset('storage/presets/ttd_kepala.png') : null,
            'ttd_waka' => \Illuminate\Support\Facades\Storage::disk('public')->exists('presets/ttd_waka.png') ? asset('storage/presets/ttd_waka.png') : null,
            'stempel' => \Illuminate\Support\Facades\Storage::disk('public')->exists('presets/stempel.png') ? asset('storage/presets/stempel.png') : null,
        ];

        return view('admin.cetak.index', array_merge(
            compact('activeSemester', 'presets'),
            $this->cetakPresetService->viewData()
        ));
    }

    /**
     * Print the master schedule (Jadwal Pelajaran).
     */
    public function jadwalPelajaran()
    {
        $activeSemester = $this->semesterService->getActiveSemester();

        if (!$activeSemester) {
            return redirect()->back()->with('error', 'Tidak ada semester aktif.');
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

        $mapels = \App\Models\Mapel::orderBy('id')->get();
        $mapelNoMap = $mapels->pluck('id')->flip()->map(fn ($v) => str_pad($v + 1, 2, '0', STR_PAD_LEFT));

        $grid = [];
        foreach ($jadwals as $j) {
            if ($j && $j->bebanMengajar && $j->bebanMengajar->guru && $j->bebanMengajar->mapel) {
                $kg = $j->bebanMengajar->guru->kode_guru ?? '-';
                $mn = $mapelNoMap[$j->bebanMengajar->mapel_id] ?? '00';
                $grid[$j->hari][$j->jam_ke][$j->bebanMengajar->kelas_id] = $kg . '-' . $mn;
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

        return view('admin.cetak.jadwal-pelajaran', array_merge(
            compact(
                'activeSemester',
                'kelasList',
                'allKelas',
                'grid',
                'gurus',
                'mapels',
                'kepalaMadrasah',
                'wakaKurikulum'
            ),
            $this->cetakPresetService->viewData()
        ));
    }

    public function jadwalBesar()
    {
        $activeSemester = $this->semesterService->getActiveSemester();

        if (!$activeSemester) {
            return redirect()->back()->with('error', 'Tidak ada semester aktif.');
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

        $mapels = \App\Models\Mapel::orderBy('id')->get();
        $mapelNoMap = $mapels->pluck('id')->flip()->map(fn ($v) => str_pad($v + 1, 2, '0', STR_PAD_LEFT));

        $grid = [];
        foreach ($jadwals as $j) {
            if ($j && $j->bebanMengajar && $j->bebanMengajar->guru && $j->bebanMengajar->mapel) {
                $kg = $j->bebanMengajar->guru->kode_guru ?? '-';
                $mn = $mapelNoMap[$j->bebanMengajar->mapel_id] ?? '00';
                $grid[$j->hari][$j->jam_ke][$j->bebanMengajar->kelas_id] = $kg . '-' . $mn;
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

        return view('admin.cetak.jadwal-besar', array_merge(
            compact(
                'activeSemester',
                'kelasList',
                'allKelas',
                'grid',
                'gurus',
                'mapels',
                'kepalaMadrasah',
                'wakaKurikulum'
            ),
            $this->cetakPresetService->viewData()
        ));
    }

    /**
     * Print the Teacher Picket Schedule (Jadwal Piket Guru).
     */
    public function jadwalPiket()
    {
        $activeSemester = $this->semesterService->getActiveSemester();

        if (!$activeSemester) {
            return redirect()->back()->with('error', 'Tidak ada semester aktif.');
        }

        $semesterId = $activeSemester->id;

        $piketData = Guru::whereHas('tugasTambahans', function ($q) use ($semesterId) {
            $q->where('tugas_tambahan_id', TugasTambahan::GURU_PIKET_ID)
              ->where('semester_id', $semesterId);
        })->get();

        $days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
        $schedule = array_fill_keys($days, []);

        foreach ($piketData as $guru) {
            $tugas = $guru->tugasTambahans()
                ->where('tugas_tambahan_id', TugasTambahan::GURU_PIKET_ID)
                ->wherePivot('semester_id', $semesterId)
                ->first();

            if (!$tugas || !$tugas->pivot->hari) {
                continue;
            }

            $hariList = json_decode($tugas->pivot->hari, true);
            if (!is_array($hariList)) {
                $hariList = [$tugas->pivot->hari];
            }

            foreach ($hariList as $hari) {
                if (isset($schedule[$hari])) {
                    $schedule[$hari][] = $guru;
                }
            }
        }

        $kepalaMadrasah = Guru::whereHas('tugasTambahans', function ($q) use ($semesterId) {
            $q->where('tugas_tambahan_id', TugasTambahan::KEPALA_MADRASAH_ID)
              ->where('semester_id', $semesterId);
        })->first();

        return view('admin.cetak.jadwal-piket', array_merge(
            compact('activeSemester', 'schedule', 'days', 'kepalaMadrasah'),
            $this->cetakPresetService->viewData()
        ));
    }

    /**
     * Print homeroom teacher list (Daftar Wali Kelas).
     */
    public function daftarWaliKelas()
    {
        $activeSemester = $this->semesterService->getActiveSemester();

        if (!$activeSemester) {
            return redirect()->back()->with('error', 'Tidak ada semester aktif.');
        }

        $semesterId = $activeSemester->id;

        $kelasList = Kelas::orderByRaw("FIELD(tingkat, 'VII', 'VIII', 'IX')")
            ->orderBy('nama_kelas')
            ->get();

        $waliGurus = Guru::whereHas('tugasTambahans', function ($q) use ($semesterId) {
            $q->where('tugas_tambahan_id', TugasTambahan::WALI_KELAS_ID)
              ->where('semester_id', $semesterId);
        })->with(['tugasTambahans' => function ($q) use ($semesterId) {
            $q->where('tugas_tambahan_id', TugasTambahan::WALI_KELAS_ID)
              ->wherePivot('semester_id', $semesterId);
        }])->get();

        $waliByKelas = [];
        foreach ($waliGurus as $guru) {
            $tugas = $guru->tugasTambahans->first();
            if ($tugas && $tugas->pivot->detail) {
                $waliByKelas[$tugas->pivot->detail] = $guru->nama_lengkap;
            }
        }

        $rows = $kelasList->map(function ($kelas, $index) use ($waliByKelas) {
            return [
                'no' => $index + 1,
                'kelas' => str_replace('Kelas ', '', $kelas->nama_kelas),
                'nama_wali' => $waliByKelas[$kelas->nama_kelas] ?? '',
            ];
        });

        return view('admin.cetak.daftar-wali-kelas', compact('activeSemester', 'rows'));
    }

    public function lampiranSk()
    {
        $activeSemester = $this->semesterService->getActiveSemester();

        if (!$activeSemester) {
            return redirect()->back()->with('error', 'Tidak ada semester aktif.');
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

        return view('admin.cetak.lampiran-sk', array_merge(
            compact('activeSemester', 'kelasList', 'allKelas', 'gurus', 'kepalaMadrasah'),
            $this->cetakPresetService->viewData()
        ));
    }

    /**
     * Store print presets (signatures, stamp, tanggal & pejabat).
     */
    public function storePresets(Request $request)
    {
        $request->validate([
            'ttd_kepala' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            'ttd_waka' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            'stempel' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            'tanggal_cetak' => 'nullable|date',
            'pejabat_penandatangan' => 'nullable|in:kepala,plt_kepala',
        ]);

        if ($request->filled('tanggal_cetak') || $request->filled('pejabat_penandatangan')) {
            $this->cetakPresetService->saveSettings([
                'tanggal_cetak' => $request->input('tanggal_cetak', $this->cetakPresetService->getSettings()['tanggal_cetak']),
                'pejabat_penandatangan' => $request->input('pejabat_penandatangan', 'kepala'),
            ]);
        }

        if ($request->hasFile('ttd_kepala')) {
            $request->file('ttd_kepala')->storeAs('presets', 'ttd_kepala.png', 'public');
        }
        if ($request->hasFile('ttd_waka')) {
            $request->file('ttd_waka')->storeAs('presets', 'ttd_waka.png', 'public');
        }
        if ($request->hasFile('stempel')) {
            $request->file('stempel')->storeAs('presets', 'stempel.png', 'public');
        }

        $message = $request->filled('tanggal_cetak') || $request->filled('pejabat_penandatangan')
            ? 'Pengaturan cetak berhasil disimpan.'
            : 'Preset cetak (TTD & Stempel) berhasil diperbarui.';

        return redirect()->back()->with('success', $message);
    }
}
