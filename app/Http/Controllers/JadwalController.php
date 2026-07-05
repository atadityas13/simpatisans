<?php

namespace App\Http\Controllers;

use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\Guru;
use App\Models\BebanMengajar;
use App\Models\GuruConstraint;
use App\Models\Semester;
use App\Services\JadwalSAOService;
use App\Services\JadwalService;
use App\Services\SemesterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class JadwalController extends Controller
{
    protected $jadwalService;
    protected $semesterService;

    public function __construct(JadwalService $jadwalService, SemesterService $semesterService)
    {
        $this->jadwalService = $jadwalService;
        $this->semesterService = $semesterService;
    }

    public function index(Request $request)
    {
        $allSemesters = Semester::orderBy('nama_tahun', 'desc')->orderBy('tipe', 'desc')->get();
        $activeSemester = $this->semesterService->getActiveSemester();
        
        $semesterId = $request->get('semester_id', $activeSemester?->id);

        if (!$semesterId) {
            $selectedSemester = null;
            $jadwals = collect([]);
            $grid = [];
            $kelasList = \App\Models\Kelas::orderByRaw("FIELD(tingkat, 'VII', 'VIII', 'IX')")->orderBy('nama_kelas')->get()->groupBy('tingkat');
            return view('jadwal.index', compact('jadwals', 'grid', 'kelasList', 'allSemesters', 'selectedSemester'));
        }

        $selectedSemester = Semester::findOrFail($semesterId);

        $jadwals = Jadwal::where('semester_id', $semesterId)
            ->with(['bebanMengajar.guru', 'bebanMengajar.mapel', 'bebanMengajar.kelas'])
            ->get();

        $grid = [];
        $kelasList = Kelas::orderByRaw("FIELD(tingkat, 'VII', 'VIII', 'IX')")->orderBy('nama_kelas')->get()->groupBy('tingkat');

        foreach ($jadwals as $j) {
            if ($j->bebanMengajar) {
                $grid[$j->hari][$j->jam_ke][$j->bebanMengajar->kelas_id] = $j;
            }
        }

        $strukturHari = ['Senin' => 9, 'Selasa' => 10, 'Rabu' => 10, 'Kamis' => 10, 'Jumat' => 5];

        // Label jam pelajaran (berbeda per hari)
        $jamLabels = [
            'Senin' => [
                1 => '07.35-08.10',
                2 => '08.10-08.45',
                3 => '08.45-09.20',
                4 => '09.50-10.25',
                5 => '10.25-11.00',
                6 => '11.00-11.35',
                7 => '13.05-13.40',
                8 => '13.40-14.15',
                9 => '14.15-14.50'
            ],
            'Selasa-Kamis' => [
                1 => '07.00-07.35',
                2 => '07.35-08.10',
                3 => '08.10-08.45',
                4 => '08.45-09.20',
                5 => '09.50-10.25',
                6 => '10.25-11.00',
                7 => '11.00-11.35',
                8 => '13.05-13.40',
                9 => '13.40-14.15',
                10 => '14.15-14.50'
            ],
            'Jumat' => [
                1 => '08.00-08.30',
                2 => '08.30-09.00',
                3 => '09.00-09.30',
                4 => '09.50-10.20',
                5 => '10.20-10.50'
            ]
        ];

        // Ambil data beban mengajar per kelas UNTUK SEMESTER TERPILIH
        $bebanPerKelas = BebanMengajar::where('semester_id', $semesterId)
            ->where('is_satminkal', 1)
            ->with(['guru', 'mapel'])
            ->get()
            ->map(function ($b) {
                return [
                    'id' => $b->id,
                    'kelas_id' => $b->kelas_id,
                    'kg' => $b->guru->kode_guru,
                    'guru' => $b->guru->nama_guru,
                    'mapel' => $b->mapel->nama_mapel
                ];
            })
            ->groupBy('kelas_id');

        // DATA GURU & CONSTRAINTS
        $gurus = Guru::orderBy('nama_guru')->get();
        $constraints = GuruConstraint::get()->groupBy('guru_id');

        // LOGIKA ANALISA JADWAL (DELEGATED TO SERVICE)
        $analisa = $this->jadwalService->analisaPenuh($semesterId);
        $totalWarnings = $analisa['summary']['total_warnings'] ?? 0;
        $hasWarnings = $totalWarnings > 0;

        return view('jadwal.index', compact('grid', 'kelasList', 'strukturHari', 'jamLabels', 'jadwals', 'bebanPerKelas', 'analisa', 'gurus', 'constraints', 'allSemesters', 'selectedSemester', 'totalWarnings', 'hasWarnings'));
    }

    public function toggleConstraint(Request $request)
    {
        $request->validate([
            'guru_id' => 'required|exists:gurus,id',
            'hari' => 'required',
            'jam_ke' => 'required|integer',
            'type' => 'required|in:0,1,2', // 0: block, 1: preserve, 2: reset
        ]);

        if ($request->type == 2) {
            GuruConstraint::where('guru_id', $request->guru_id)
                ->where('hari', $request->hari)
                ->where('jam_ke', $request->jam_ke)
                ->delete();
            return response()->json(['success' => true, 'message' => 'Ketentuan dihapus.']);
        }

        GuruConstraint::updateOrCreate(
            ['guru_id' => $request->guru_id, 'hari' => $request->hari, 'jam_ke' => $request->jam_ke],
            ['type' => $request->type]
        );

        return response()->json(['success' => true, 'message' => 'Ketentuan diperbarui.']);
    }

    public function updateSlot(Request $request)
    {
        $request->validate([
            'hari' => 'required',
            'jam_ke' => 'required|integer',
            'kelas_id' => 'required|integer',
            'semester_id' => 'required|exists:semesters,id',
            'beban_mengajar_id' => 'nullable'
        ]);

        $hariNormalized = ucfirst(strtolower(trim($request->hari)));
        $warning = null;
        if ($request->beban_mengajar_id) {
            $beban = BebanMengajar::with('guru')->find($request->beban_mengajar_id);

            // CEK BENTROK GURU (Soft Warning) - Hanya di semester yang sama
            $bentrok = Jadwal::where('semester_id', $request->semester_id)
                ->where('hari', $hariNormalized)
                ->where('jam_ke', $request->jam_ke)
                ->whereHas('bebanMengajar', function ($q) use ($beban) {
                    $q->where('guru_id', $beban->guru_id);
                })
                ->with('bebanMengajar.kelas')
                ->first();

            if ($bentrok && $bentrok->bebanMengajar->kelas_id != $request->kelas_id) {
                $msg = "Guru [{$beban->guru->kode_guru}] juga mengajar di kelas {$bentrok->bebanMengajar->kelas->nama_kelas} pada jam ini.";
                
                // Jika tidak dipaksa (force), hentikan dan minta konfirmasi
                if (!$request->has('force') || !$request->force) {
                    return response()->json([
                        'success' => false,
                        'has_conflict' => true,
                        'message' => $msg
                    ]);
                }
                $warning = $msg;
            }
        }

        // Cari record jadwal lama untuk kelas ini di slot ini PADA SEMESTER TERPILIH
        $jadwal = Jadwal::where('semester_id', $request->semester_id)
            ->where('hari', $hariNormalized)
            ->where('jam_ke', $request->jam_ke)
            ->whereHas('bebanMengajar', function ($q) use ($request) {
                $q->where('kelas_id', $request->kelas_id);
            })->first();

        if (!$request->beban_mengajar_id) {
            if ($jadwal)
                $jadwal->delete();
            return response()->json(['success' => true, 'message' => 'Slot dikosongkan.']);
        }

        if ($jadwal) {
            $jadwal->update(['beban_mengajar_id' => $request->beban_mengajar_id]);
        } else {
            Jadwal::create([
                'semester_id' => $request->semester_id,
                'hari' => $hariNormalized,
                'jam_ke' => $request->jam_ke,
                'beban_mengajar_id' => $request->beban_mengajar_id
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Jadwal diperbarui!',
            'warning' => $warning
        ]);
    }

    public function generate(Request $request, JadwalSAOService $saoService)
    {
        @ini_set('memory_limit', '512M');
        set_time_limit(120);

        $semesterId = (int) $request->get('semester_id');
        if (!$semesterId) {
            return redirect()->back()->with('error', 'Semester tidak valid.');
        }

        try {
            $result = $saoService->generate($semesterId);

            $analisa = $this->jadwalService->analisaPenuh($semesterId);
            $totalWarnings = $analisa['summary']['total_warnings'] ?? 0;

            $msg = "<b>Penjadwalan Otomatis Selesai!</b><br>Masalah: {$totalWarnings}<br>Jam Terisi: {$result['total_slot_terisi']}";
            if ($totalWarnings > 0) {
                $msg .= "<br><small>Beberapa aturan belum sempurna — periksa Laporan Analisa atau kurangi preset blokir.</small>";
            }

            return redirect()->route('jadwal.index', ['semester_id' => $semesterId])->with('success', $msg);
        } catch (\Throwable $e) {
            Log::error('Generate jadwal gagal: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return redirect()->route('jadwal.index', ['semester_id' => $semesterId])
                ->with('error', $e->getMessage());
        }
    }

    public function clear(Request $request)
    {
        $semesterId = $request->get('semester_id');
        if (!$semesterId) {
            return redirect()->back()->with('error', 'Semester tidak valid.');
        }

        Jadwal::where('semester_id', $semesterId)->delete();

        return redirect()->route('jadwal.index', ['semester_id' => $semesterId])
            ->with('success', 'Semua jadwal di matriks berhasil dikosongkan.');
    }
}
