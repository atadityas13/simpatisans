<?php

namespace App\Http\Controllers;

use App\Models\TugasTambahan;
use App\Models\Guru;
use App\Services\SemesterService;
use App\Models\Jadwal;
use App\Models\Kelas;
use Illuminate\Http\Request;

class CetakController extends Controller
{
    protected $semesterService;

    public function __construct(SemesterService $semesterService)
    {
        $this->semesterService = $semesterService;
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
        
        return view('admin.cetak.index', compact('activeSemester', 'presets'));
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

        // 1. Get classes grouped by level (for table columns)
        $kelasList = Kelas::orderByRaw("FIELD(tingkat, 'VII', 'VIII', 'IX')")
            ->orderBy('nama_kelas')
            ->get()
            ->groupBy('tingkat');

        // 2. Map all classes for easier grid building
        $allKelas = Kelas::orderByRaw("FIELD(tingkat, 'VII', 'VIII', 'IX')")
            ->orderBy('nama_kelas')
            ->get();

        // 3. Get all schedule items
        $jadwals = Jadwal::where('semester_id', $semesterId)
            ->with(['bebanMengajar.guru', 'bebanMengajar.mapel'])
            ->get();

        // 6. Get all mapels for legend and create a mapping for the grid
        $mapels = \App\Models\Mapel::orderBy('id')->get();
        $mapelNoMap = $mapels->pluck('id')->flip()->map(fn($v) => str_pad($v + 1, 2, '0', STR_PAD_LEFT));

        // 4. Build the grid [hari][jam_ke][kelas_id]
        $grid = [];
        foreach ($jadwals as $j) {
            if ($j && $j->bebanMengajar && $j->bebanMengajar->guru && $j->bebanMengajar->mapel) {
                $kg = $j->bebanMengajar->guru->kode_guru ?? '-';
                $mn = $mapelNoMap[$j->bebanMengajar->mapel_id] ?? '00';
                $grid[$j->hari][$j->jam_ke][$j->bebanMengajar->kelas_id] = $kg . '-' . $mn;
            }
        }

        // 5. Get all gurus for legend (urut DUK)
        $gurus = Guru::orderedByDuk()->get();

        // 7. Get Signatories (Kepala and Waka Kurikulum)
        $kepalaMadrasah = Guru::whereHas('tugasTambahans', function($q) use ($semesterId) {
            $q->where('tugas_tambahan_id', TugasTambahan::KEPALA_MADRASAH_ID)
              ->where('semester_id', $semesterId);
        })->first();

        $wakaKurikulum = Guru::whereHas('tugasTambahans', function($q) use ($semesterId) {
            $q->where('tugas_tambahan_id', TugasTambahan::WAKA_ID)
              ->where('detail', 'LIKE', '%Kurikulum%')
              ->where('semester_id', $semesterId);
        })->first();

        return view('admin.cetak.jadwal-pelajaran', compact(
            'activeSemester',
            'kelasList',
            'allKelas',
            'grid',
            'gurus',
            'mapels',
            'kepalaMadrasah',
            'wakaKurikulum'
        ));
    }

    public function jadwalBesar()
    {
        $activeSemester = $this->semesterService->getActiveSemester();
        
        if (!$activeSemester) {
            return redirect()->back()->with('error', 'Tidak ada semester aktif.');
        }

        $semesterId = $activeSemester->id;

        // Same data as jadwalPelajaran
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
        $mapelNoMap = $mapels->pluck('id')->flip()->map(fn($v) => str_pad($v + 1, 2, '0', STR_PAD_LEFT));

        $grid = [];
        foreach ($jadwals as $j) {
            if ($j && $j->bebanMengajar && $j->bebanMengajar->guru && $j->bebanMengajar->mapel) {
                $kg = $j->bebanMengajar->guru->kode_guru ?? '-';
                $mn = $mapelNoMap[$j->bebanMengajar->mapel_id] ?? '00';
                $grid[$j->hari][$j->jam_ke][$j->bebanMengajar->kelas_id] = $kg . '-' . $mn;
            }
        }

        // 5. Get all gurus for legend (urut DUK)
        $gurus = Guru::orderedByDuk()->get();

        // Signatories
        $kepalaMadrasah = Guru::whereHas('tugasTambahans', function($q) use ($semesterId) {
            $q->where('tugas_tambahan_id', TugasTambahan::KEPALA_MADRASAH_ID)
              ->where('semester_id', $semesterId);
        })->first();

        $wakaKurikulum = Guru::whereHas('tugasTambahans', function($q) use ($semesterId) {
            $q->where('tugas_tambahan_id', TugasTambahan::WAKA_ID)
              ->where('detail', 'LIKE', '%Kurikulum%')
              ->where('semester_id', $semesterId);
        })->first();

        return view('admin.cetak.jadwal-besar', compact(
            'activeSemester',
            'kelasList',
            'allKelas',
            'grid',
            'gurus',
            'mapels',
            'kepalaMadrasah',
            'wakaKurikulum'
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

        // Fetch teachers with picket duty (tugas_tambahan_id = 4)
        $piketData = Guru::whereHas('tugasTambahans', function($q) use ($semesterId) {
            $q->where('tugas_tambahan_id', TugasTambahan::GURU_PIKET_ID)
              ->where('semester_id', $semesterId);
        })->get();

        // Organize schedule by day
        $days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
        $schedule = array_fill_keys($days, []);

        foreach ($piketData as $guru) {
            // Get the specific picket task for this guru in this semester
            $tugas = $guru->tugasTambahans()
                ->where('tugas_tambahan_id', TugasTambahan::GURU_PIKET_ID)
                ->where('semester_id', $semesterId)
                ->first();
                
            if ($tugas && $tugas->pivot->hari) {
                $schedule[$tugas->pivot->hari][] = $guru;
            }
        }

        // Signatory (Kepala Madrasah)
        $kepalaMadrasah = Guru::whereHas('tugasTambahans', function($q) use ($semesterId) {
            $q->where('tugas_tambahan_id', TugasTambahan::KEPALA_MADRASAH_ID)
              ->where('semester_id', $semesterId);
        })->first();

        return view('admin.cetak.jadwal-piket', compact(
            'activeSemester',
            'schedule',
            'days',
            'kepalaMadrasah'
        ));
    }

    public function lampiranSk()
    {
        $activeSemester = $this->semesterService->getActiveSemester();
        
        if (!$activeSemester) {
            return redirect()->back()->with('error', 'Tidak ada semester aktif.');
        }

        $semesterId = $activeSemester->id;

        // 1. Get all classes grouped by level for the table columns
        $kelasList = Kelas::orderByRaw("FIELD(tingkat, 'VII', 'VIII', 'IX')")
            ->orderBy('nama_kelas')
            ->get()
            ->groupBy('tingkat');

        // 2. Map classes for the grid headers
        $allKelas = Kelas::orderByRaw("FIELD(tingkat, 'VII', 'VIII', 'IX')")
            ->orderBy('nama_kelas')
            ->get();

        // 3. Get all teachers with their workload and additional duties for this semester
        $gurus = Guru::with([
            'bebanMengajars' => function($q) use ($semesterId) {
                $q->where('semester_id', $semesterId)->with(['mapel.rumpuns', 'kelas']);
            },
            'tugasTambahans' => function($q) use ($semesterId) {
                $q->where('semester_id', $semesterId)
                  ->orderByPivot('is_ekuivalen', 'desc');
            },
            'mapelSertifikasi'
        ])
        ->orderedByDuk()
        ->get();

        // 4. Signatory (Kepala Madrasah)
        $kepalaMadrasah = Guru::whereHas('tugasTambahans', function($q) use ($semesterId) {
            $q->where('tugas_tambahan_id', TugasTambahan::KEPALA_MADRASAH_ID)
              ->where('semester_id', $semesterId);
        })->first();

        return view('admin.cetak.lampiran-sk', compact(
            'activeSemester',
            'kelasList',
            'allKelas',
            'gurus',
            'kepalaMadrasah'
        ));
    }

    /**
     * Store print presets (signatures and stamp).
     */
    public function storePresets(Request $request)
    {
        $request->validate([
            'ttd_kepala' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            'ttd_waka' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            'stempel' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
        ]);

        if ($request->hasFile('ttd_kepala')) {
            $request->file('ttd_kepala')->storeAs('presets', 'ttd_kepala.png', 'public');
        }
        if ($request->hasFile('ttd_waka')) {
            $request->file('ttd_waka')->storeAs('presets', 'ttd_waka.png', 'public');
        }
        if ($request->hasFile('stempel')) {
            $request->file('stempel')->storeAs('presets', 'stempel.png', 'public');
        }

        return redirect()->back()->with('success', 'Preset cetak (TTD & Stempel) berhasil diperbarui.');
    }
}
