<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\Semester;
use App\Services\EmisGtk\EmisGtkExportService;
use App\Services\EmisGtk\EmisGtkReferenceService;
use App\Services\SemesterService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EmisGtkController extends Controller
{
    public function __construct(
        private readonly EmisGtkExportService $exportService,
        private readonly EmisGtkReferenceService $referenceService,
        private readonly SemesterService $semesterService,
    ) {}

    public function index(Request $request)
    {
        $allSemesters = Semester::orderBy('nama_tahun', 'desc')->orderBy('tipe', 'desc')->get();
        $activeSemester = $this->semesterService->getActiveSemester();
        $semesterId = $request->get('semester_id', $activeSemester?->id);
        $selectedSemester = $semesterId ? Semester::find($semesterId) : $activeSemester;

        $kelasList = Kelas::orderByRaw("FIELD(tingkat, 'VII', 'VIII', 'IX')")
            ->orderBy('nama_kelas')
            ->get()
            ->groupBy('tingkat');

        $stats = [
            'guru_with_gtk' => Guru::whereNotNull('id_gtk')->where('id_gtk', '!=', '')->count(),
            'guru_total' => Guru::count(),
            'mapel_ready' => Mapel::whereNotNull('id_mapel_emis_7')
                ->whereNotNull('id_mapel_emis_8')
                ->whereNotNull('id_mapel_emis_9')
                ->count(),
            'mapel_total' => Mapel::count(),
            'kelas_ready' => Kelas::whereNotNull('tingkat_emis')->whereNotNull('rombel_emis')->count(),
            'kelas_total' => Kelas::count(),
        ];

        return view('emis-gtk.index', compact(
            'allSemesters',
            'selectedSemester',
            'kelasList',
            'stats',
        ));
    }

    public function export(Request $request): BinaryFileResponse
    {
        $validated = $request->validate([
            'semester_id' => 'required|exists:semesters,id',
            'kelas_ids' => 'required|array|min:1',
            'kelas_ids.*' => 'integer|exists:kelas,id',
        ]);

        $result = $this->exportService->export(
            (int) $validated['semester_id'],
            array_map('intval', $validated['kelas_ids']),
        );

        session()->flash('emis_export_report', $result['report']);

        return response()->download($result['path'], $result['filename'])->deleteFileAfterSend();
    }

    public function importReferences(Request $request)
    {
        $basePath = database_path('emis_references');

        if (! is_dir($basePath)) {
            mkdir($basePath, 0755, true);
        }

        if ($request->hasFile('referensi_pelajaran')) {
            $request->file('referensi_pelajaran')->move($basePath, 'referensi_pelajaran.xlsx');
        }
        if ($request->hasFile('referensi_ptk')) {
            $request->file('referensi_ptk')->move($basePath, 'referensi_ptk.xlsx');
        }
        if ($request->hasFile('referensi_rombel')) {
            $request->file('referensi_rombel')->move($basePath, 'referensi_rombel.xlsx');
        }
        if ($request->hasFile('template_jadwal')) {
            $request->file('template_jadwal')->move($basePath, 'template_jadwal.xlsx');
        }

        $summary = $this->referenceService->importAll($basePath);

        return redirect()
            ->route('emis-gtk.index', ['semester_id' => $request->get('semester_id')])
            ->with('emis_import_summary', $summary)
            ->with('success', 'Referensi EMIS-GTK berhasil diimpor ke database.');
    }
}
