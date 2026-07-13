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
                1 => '07.45-08.20',
                2 => '08.20-08.55',
                3 => '08.55-09.30',
                4 => '09.30-10.05',
                5 => '10.35-11.10',
                6 => '11.10-11.45',
                7 => '12.45-13.20',
                8 => '13.20-13.55',
                9 => '13.55-14.30'
            ],
            'Selasa-Kamis' => [
                1 => '07.15-07.50',
                2 => '07.50-08.25',
                3 => '08.25-09.00',
                4 => '09.00-09.35',
                5 => '10.05-10.40',
                6 => '10.40-11.15',
                7 => '11.15-11.50',
                8 => '13.00-13.35',
                9 => '13.35-14.10',
                10 => '14.10-14.45'
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
        $bebanCounts = $jadwals->groupBy('beban_mengajar_id')->map->count();
        $bebanPerKelas = BebanMengajar::where('semester_id', $semesterId)
            ->where('is_satminkal', 1)
            ->with(['guru', 'mapel', 'kelas'])
            ->get()
            ->map(function ($b) use ($bebanCounts) {
                return [
                    'id' => $b->id,
                    'kelas_id' => $b->kelas_id,
                    'guru_id' => $b->guru_id,
                    'kg' => $b->guru->kode_guru,
                    'guru' => $b->guru->nama_guru,
                    'mapel' => $b->mapel->nama_mapel,
                    'is_btq' => $this->jadwalService->isMapelBtqName($b->mapel->nama_mapel ?? ''),
                    'jtm' => (int) $b->jtm,
                    'placed' => (int) ($bebanCounts->get($b->id, 0)),
                ];
            })
            ->groupBy('kelas_id');

        $slotData = [];
        foreach ($jadwals as $j) {
            if (!$j->bebanMengajar) {
                continue;
            }
            $h = ucfirst(strtolower(trim($j->hari)));
            $slotData[$h][$j->jam_ke][$j->bebanMengajar->kelas_id] = [
                'beban_id' => $j->beban_mengajar_id,
                'kg' => $j->bebanMengajar->guru->kode_guru,
                'guru_id' => $j->bebanMengajar->guru_id,
                'guru' => $j->bebanMengajar->guru->nama_guru,
                'mapel' => $j->bebanMengajar->mapel->nama_mapel,
                'is_btq' => $this->jadwalService->isMapelBtqName($j->bebanMengajar->mapel->nama_mapel ?? ''),
                'kelas_id' => $j->bebanMengajar->kelas_id,
                'kelas' => $j->bebanMengajar->kelas->nama_kelas,
            ];
        }

        $kelasFlat = $kelasList->flatten()->map(fn ($k) => [
            'id' => $k->id,
            'nama' => $k->nama_kelas,
            'tingkat' => $k->tingkat,
        ])->values();

        // DATA GURU & CONSTRAINTS
        $gurus = Guru::orderBy('nama_guru')->get();
        $constraints = GuruConstraint::get()->groupBy('guru_id');

        $guruList = $gurus->map(fn ($g) => [
            'id' => $g->id,
            'kode' => $g->kode_guru,
            'nama' => $g->nama_guru,
        ])->values();

        // LOGIKA ANALISA JADWAL (DELEGATED TO SERVICE)
        $analisa = $this->jadwalService->analisaPenuh($semesterId);
        $slotIssueMap = $this->jadwalService->buildSlotIssueMap($semesterId, $analisa);
        $totalWarnings = $analisa['summary']['total_warnings'] ?? 0;
        $criticalWarnings = $analisa['summary']['critical_warnings'] ?? 0;
        $hasWarnings = $totalWarnings > 0;
        $hasCriticalWarnings = $criticalWarnings > 0;

        return view('jadwal.index', compact('grid', 'kelasList', 'strukturHari', 'jamLabels', 'jadwals', 'bebanPerKelas', 'slotData', 'slotIssueMap', 'kelasFlat', 'guruList', 'analisa', 'gurus', 'constraints', 'allSemesters', 'selectedSemester', 'totalWarnings', 'hasWarnings', 'criticalWarnings', 'hasCriticalWarnings'));
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
            $beban = BebanMengajar::with(['guru', 'mapel'])->find($request->beban_mengajar_id);

            $isBtq = $this->jadwalService->isMapelBtqName($beban->mapel->nama_mapel ?? '');
            if ($hariNormalized === 'Jumat' && (int) $request->jam_ke === 5 && !$isBtq) {
                return response()->json([
                    'success' => false,
                    'message' => 'Jumat jam ke-5 hanya untuk mapel BTQ.',
                ], 422);
            }
            if ($isBtq && !($hariNormalized === 'Jumat' && (int) $request->jam_ke === 5)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mapel BTQ hanya boleh di Jumat jam ke-5.',
                ], 422);
            }

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

    public function updateSlotsBatch(Request $request)
    {
        $request->validate([
            'semester_id' => 'required|exists:semesters,id',
            'slots' => 'required|array|min:1',
            'slots.*.hari' => 'required|string',
            'slots.*.jam_ke' => 'required|integer|min:1',
            'slots.*.kelas_id' => 'required|integer',
            'slots.*.beban_mengajar_id' => 'required|exists:beban_mengajars,id',
        ]);

        $slots = $request->input('slots');

        if (!$request->boolean('force')) {
            $result = $this->jadwalService->validatePlacements((int) $request->semester_id, $slots);
            if (!empty($result['warnings'])) {
                return response()->json([
                    'success' => false,
                    'has_warnings' => true,
                    'has_critical' => $result['has_critical'],
                    'warnings' => $result['warnings'],
                ]);
            }
        }

        $this->jadwalService->applyPlacements((int) $request->semester_id, $slots);

        return response()->json(['success' => true, 'message' => 'Jadwal disimpan.']);
    }

    public function generate(Request $request, JadwalSAOService $saoService)
    {
        @ini_set('memory_limit', '512M');
        set_time_limit(120);
        @ini_set('max_execution_time', '120');

        $semesterId = (int) $request->get('semester_id');
        if (!$semesterId) {
            return redirect()->back()->with('error', 'Semester tidak valid.');
        }

        try {
            $result = $saoService->generate($semesterId);

            $analisa = $this->jadwalService->analisaPenuh($semesterId);
            $criticalWarnings = $analisa['summary']['critical_warnings'] ?? 0;
            $infoWarnings = $analisa['summary']['info_warnings'] ?? 0;
            $terisi = $result['total_slot_terisi'];
            $target = $result['total_target'] ?? $terisi;
            $kosong = $result['slot_kosong'] ?? 0;
            $presetViolations = count($analisa['pelanggaran_ketentuan'] ?? []);

            if ($result['status'] === 'partial') {
                $belum = count($analisa['belum_terisi'] ?? []);
                $msg = "<b>Jadwal disimpan sebagian.</b><br>Jam Terisi: {$terisi}/{$target} ({$kosong} slot kosong, {$belum} mapel belum penuh)";
                $msg .= "<br>Perlu perhatian: {$criticalWarnings} | Penanda kualitas: {$infoWarnings}";
                if ($presetViolations > 0) {
                    $msg .= "<br>Preset dilanggar: {$presetViolations} slot (penanda, bukan error generate).";
                }
                return redirect()->route('jadwal.index', ['semester_id' => $semesterId])->with('error', $msg);
            }

            $msg = "<b>Penjadwalan Selesai!</b><br>Jam Terisi: {$terisi}/{$target}";
            if ($criticalWarnings === 0) {
                $msg .= '<br>Semua mapel teralokasi.';
            }
            if ($infoWarnings > 0) {
                $msg .= "<br>Penanda kualitas (preset/JTM/kelelahan): {$infoWarnings} — sesuaikan manual bila perlu.";
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
