<?php

namespace App\Http\Controllers;

use App\Models\TugasTambahan;
use App\Models\Guru;
use App\Services\CetakPresetService;
use App\Services\JadwalVersionService;
use App\Services\SemesterService;
use App\Models\Jadwal;
use App\Models\JadwalVersion;
use App\Models\Kelas;
use Illuminate\Http\Request;

class CetakController extends Controller
{
    protected $semesterService;
    protected $cetakPresetService;
    protected $versionService;

    public function __construct(SemesterService $semesterService, CetakPresetService $cetakPresetService, JadwalVersionService $versionService)
    {
        $this->semesterService = $semesterService;
        $this->cetakPresetService = $cetakPresetService;
        $this->versionService = $versionService;
    }

    /**
     * Display the print menu page.
     */
    public function index(Request $request)
    {
        $activeSemester = $this->semesterService->getActiveSemester();
        $versions = collect();
        $selectedVersion = null;
        if ($activeSemester) {
            $versions = $this->versionService->listForSemester($activeSemester->id);
            $selectedVersion = $this->versionService->resolveForSemester(
                $activeSemester->id,
                $request->integer('version_id') ?: null
            );
        }

        $presets = [
            'ttd_kepala' => \Illuminate\Support\Facades\Storage::disk('public')->exists('presets/ttd_kepala.png') ? asset('storage/presets/ttd_kepala.png') : null,
            'ttd_waka' => \Illuminate\Support\Facades\Storage::disk('public')->exists('presets/ttd_waka.png') ? asset('storage/presets/ttd_waka.png') : null,
            'stempel' => \Illuminate\Support\Facades\Storage::disk('public')->exists('presets/stempel.png') ? asset('storage/presets/stempel.png') : null,
        ];

        return view('admin.cetak.index', array_merge(
            compact('activeSemester', 'versions', 'selectedVersion', 'presets'),
            $this->cetakPresetService->viewData()
        ));
    }

    /**
     * @return array{0: \App\Models\Semester, 1: JadwalVersion}|null
     */
    private function resolveActiveVersion(Request $request): ?array
    {
        $activeSemester = $this->semesterService->getActiveSemester();
        if (!$activeSemester) {
            return null;
        }

        $version = $this->versionService->resolveForSemester(
            $activeSemester->id,
            $request->integer('version_id') ?: null
        );

        return [$activeSemester, $version];
    }

    /**
     * Print the master schedule (Jadwal Pelajaran).
     */
    public function jadwalPelajaran(Request $request)
    {
        $resolved = $this->resolveActiveVersion($request);
        if (!$resolved) {
            return redirect()->back()->with('error', 'Tidak ada semester aktif.');
        }
        [$activeSemester, $selectedVersion] = $resolved;
        $semesterId = $activeSemester->id;
        $versionId = $selectedVersion->id;

        $kelasList = Kelas::orderByRaw("FIELD(tingkat, 'VII', 'VIII', 'IX')")
            ->orderBy('nama_kelas')
            ->get()
            ->groupBy('tingkat');

        $allKelas = Kelas::orderByRaw("FIELD(tingkat, 'VII', 'VIII', 'IX')")
            ->orderBy('nama_kelas')
            ->get();

        $jadwals = Jadwal::where('semester_id', $semesterId)
            ->where('version_id', $versionId)
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

        $kepalaMadrasah = Guru::whereHas('tugasTambahans', function ($q) use ($semesterId, $versionId) {
            $q->where('tugas_tambahan_id', TugasTambahan::KEPALA_MADRASAH_ID)
              ->where('semester_id', $semesterId)
              ->where('version_id', $versionId);
        })->first();

        $wakaKurikulum = Guru::whereHas('tugasTambahans', function ($q) use ($semesterId, $versionId) {
            $q->where('tugas_tambahan_id', TugasTambahan::WAKA_ID)
              ->where('detail', 'LIKE', '%Kurikulum%')
              ->where('semester_id', $semesterId)
              ->where('version_id', $versionId);
        })->first();

        return view('admin.cetak.jadwal-pelajaran', array_merge(
            compact(
                'activeSemester',
                'selectedVersion',
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

    public function jadwalBesar(Request $request)
    {
        $resolved = $this->resolveActiveVersion($request);
        if (!$resolved) {
            return redirect()->back()->with('error', 'Tidak ada semester aktif.');
        }
        [$activeSemester, $selectedVersion] = $resolved;
        $semesterId = $activeSemester->id;
        $versionId = $selectedVersion->id;

        $kelasList = Kelas::orderByRaw("FIELD(tingkat, 'VII', 'VIII', 'IX')")
            ->orderBy('nama_kelas')
            ->get()
            ->groupBy('tingkat');

        $allKelas = Kelas::orderByRaw("FIELD(tingkat, 'VII', 'VIII', 'IX')")
            ->orderBy('nama_kelas')
            ->get();

        $jadwals = Jadwal::where('semester_id', $semesterId)
            ->where('version_id', $versionId)
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

        $kepalaMadrasah = Guru::whereHas('tugasTambahans', function ($q) use ($semesterId, $versionId) {
            $q->where('tugas_tambahan_id', TugasTambahan::KEPALA_MADRASAH_ID)
              ->where('semester_id', $semesterId)
              ->where('version_id', $versionId);
        })->first();

        $wakaKurikulum = Guru::whereHas('tugasTambahans', function ($q) use ($semesterId, $versionId) {
            $q->where('tugas_tambahan_id', TugasTambahan::WAKA_ID)
              ->where('detail', 'LIKE', '%Kurikulum%')
              ->where('semester_id', $semesterId)
              ->where('version_id', $versionId);
        })->first();

        return view('admin.cetak.jadwal-besar', array_merge(
            compact(
                'activeSemester',
                'selectedVersion',
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
    public function jadwalPiket(Request $request)
    {
        $resolved = $this->resolveActiveVersion($request);
        if (!$resolved) {
            return redirect()->back()->with('error', 'Tidak ada semester aktif.');
        }
        [$activeSemester, $selectedVersion] = $resolved;
        $semesterId = $activeSemester->id;
        $versionId = $selectedVersion->id;

        $piketData = Guru::whereHas('tugasTambahans', function ($q) use ($semesterId, $versionId) {
            $q->where('tugas_tambahan_id', TugasTambahan::GURU_PIKET_ID)
              ->where('semester_id', $semesterId)
              ->where('version_id', $versionId);
        })->get();

        $days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
        $schedule = array_fill_keys($days, []);

        foreach ($piketData as $guru) {
            $tugas = $guru->tugasTambahans()
                ->where('tugas_tambahan_id', TugasTambahan::GURU_PIKET_ID)
                ->wherePivot('semester_id', $semesterId)->wherePivot('version_id', $versionId)
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

        $kepalaMadrasah = Guru::whereHas('tugasTambahans', function ($q) use ($semesterId, $versionId) {
            $q->where('tugas_tambahan_id', TugasTambahan::KEPALA_MADRASAH_ID)
              ->where('semester_id', $semesterId)
              ->where('version_id', $versionId);
        })->first();

        return view('admin.cetak.jadwal-piket', array_merge(
            compact('activeSemester', 'selectedVersion', 'schedule', 'days', 'kepalaMadrasah'),
            $this->cetakPresetService->viewData()
        ));
    }

    /**
     * Print homeroom teacher list (Daftar Wali Kelas).
     */
    public function daftarWaliKelas(Request $request)
    {
        $resolved = $this->resolveActiveVersion($request);
        if (!$resolved) {
            return redirect()->back()->with('error', 'Tidak ada semester aktif.');
        }
        [$activeSemester, $selectedVersion] = $resolved;
        $semesterId = $activeSemester->id;
        $versionId = $selectedVersion->id;

        $kelasList = Kelas::orderByRaw("FIELD(tingkat, 'VII', 'VIII', 'IX')")
            ->orderBy('nama_kelas')
            ->get();

        $waliGurus = Guru::whereHas('tugasTambahans', function ($q) use ($semesterId, $versionId) {
            $q->where('tugas_tambahan_id', TugasTambahan::WALI_KELAS_ID)
              ->where('semester_id', $semesterId)
              ->where('version_id', $versionId);
        })->with(['tugasTambahans' => function ($q) use ($semesterId, $versionId) {
            $q->where('tugas_tambahan_id', TugasTambahan::WALI_KELAS_ID)
              ->wherePivot('semester_id', $semesterId)->wherePivot('version_id', $versionId);
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

        $kepalaMadrasah = Guru::whereHas('tugasTambahans', function ($q) use ($semesterId, $versionId) {
            $q->where('tugas_tambahan_id', TugasTambahan::KEPALA_MADRASAH_ID)
              ->where('semester_id', $semesterId)
              ->where('version_id', $versionId);
        })->first();

        return view('admin.cetak.daftar-wali-kelas', array_merge(
            compact('activeSemester', 'selectedVersion', 'rows', 'kepalaMadrasah'),
            $this->cetakPresetService->viewData()
        ));
    }

    /**
     * Print additional duties list (Daftar Tugas Tambahan), excluding Wali Kelas.
     */
    public function daftarTugasTambahan(Request $request)
    {
        $resolved = $this->resolveActiveVersion($request);
        if (!$resolved) {
            return redirect()->back()->with('error', 'Tidak ada semester aktif.');
        }
        [$activeSemester, $selectedVersion] = $resolved;
        $semesterId = $activeSemester->id;
        $versionId = $selectedVersion->id;

        $assignments = \Illuminate\Support\Facades\DB::table('guru_tugas_tambahans as gtt')
            ->join('tugas_tambahans as tt', 'tt.id', '=', 'gtt.tugas_tambahan_id')
            ->join('gurus as g', 'g.id', '=', 'gtt.guru_id')
            ->where('gtt.semester_id', $semesterId)
            ->where('gtt.version_id', $versionId)
            ->where('gtt.tugas_tambahan_id', '!=', TugasTambahan::WALI_KELAS_ID)
            ->orderBy('tt.id')
            ->orderBy('g.nama_guru')
            ->select([
                'g.nama_guru',
                'g.gelar_depan',
                'g.gelar_belakang',
                'tt.nama_tugas',
                'tt.jtm_ekuivalen',
                'gtt.is_ekuivalen',
                'gtt.detail',
                'gtt.hari',
            ])
            ->get();

        $rows = $assignments->values()->map(function ($row, $index) {
            $prefix = ($row->gelar_depan && $row->gelar_depan !== '-') ? $row->gelar_depan.' ' : '';
            $suffix = ($row->gelar_belakang && $row->gelar_belakang !== '-') ? ', '.$row->gelar_belakang : '';
            $namaGuru = $prefix.$row->nama_guru.$suffix;

            $tugasLabel = $row->nama_tugas;
            if (! empty($row->detail)) {
                $tugasLabel .= ' — '.$row->detail;
            } elseif (! empty($row->hari)) {
                $hariArr = is_string($row->hari) ? json_decode($row->hari, true) : $row->hari;
                if (is_array($hariArr) && count($hariArr) > 0) {
                    $tugasLabel .= ' ('.implode(', ', $hariArr).')';
                }
            }

            $ekuivalen = ((int) $row->is_ekuivalen === 1)
                ? ((int) $row->jtm_ekuivalen).' JTM'
                : 'Non-ekuivalen';

            return [
                'no' => $index + 1,
                'nama_guru' => $namaGuru,
                'tugas' => $tugasLabel,
                'ekuivalen' => $ekuivalen,
            ];
        });

        $kepalaMadrasah = Guru::whereHas('tugasTambahans', function ($q) use ($semesterId, $versionId) {
            $q->where('tugas_tambahan_id', TugasTambahan::KEPALA_MADRASAH_ID)
              ->where('semester_id', $semesterId)
              ->where('version_id', $versionId);
        })->first();

        return view('admin.cetak.daftar-tugas-tambahan', array_merge(
            compact('activeSemester', 'selectedVersion', 'rows', 'kepalaMadrasah'),
            $this->cetakPresetService->viewData()
        ));
    }

    /**
     * Print Surat Pernyataan zakat profesi for all teachers (one letter per A4 page).
     */
    public function suratPernyataanZakat()
    {
        $gurus = Guru::orderedByDuk()->get();

        $bulanId = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        $now = now('Asia/Jakarta');
        $tanggalSurat = $now->day.' '.($bulanId[(int) $now->month] ?? $now->format('F')).' '.$now->year;

        return view('admin.cetak.surat-pernyataan-zakat', compact('gurus', 'tanggalSurat'));
    }

    public function lampiranSk(Request $request)
    {
        $resolved = $this->resolveActiveVersion($request);
        if (!$resolved) {
            return redirect()->back()->with('error', 'Tidak ada semester aktif.');
        }
        [$activeSemester, $selectedVersion] = $resolved;
        $semesterId = $activeSemester->id;
        $versionId = $selectedVersion->id;

        $kelasList = Kelas::orderByRaw("FIELD(tingkat, 'VII', 'VIII', 'IX')")
            ->orderBy('nama_kelas')
            ->get()
            ->groupBy('tingkat');

        $allKelas = Kelas::orderByRaw("FIELD(tingkat, 'VII', 'VIII', 'IX')")
            ->orderBy('nama_kelas')
            ->get();

        $gurus = Guru::with([
            'bebanMengajars' => function ($q) use ($semesterId, $versionId) {
                $q->where('semester_id', $semesterId)->where('version_id', $versionId)->with(['mapel.rumpuns', 'kelas']);
            },
            'tugasTambahans' => function ($q) use ($semesterId, $versionId) {
                $q->where('semester_id', $semesterId)
                  ->where('version_id', $versionId)
                  ->orderByPivot('is_ekuivalen', 'desc');
            },
            'mapelSertifikasi',
        ])
        ->orderedByDuk()
        ->get();

        $kepalaMadrasah = Guru::whereHas('tugasTambahans', function ($q) use ($semesterId, $versionId) {
            $q->where('tugas_tambahan_id', TugasTambahan::KEPALA_MADRASAH_ID)
              ->where('semester_id', $semesterId)
              ->where('version_id', $versionId);
        })->first();

        return view('admin.cetak.lampiran-sk', array_merge(
            compact('activeSemester', 'selectedVersion', 'kelasList', 'allKelas', 'gurus', 'kepalaMadrasah'),
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
