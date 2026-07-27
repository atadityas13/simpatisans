<?php

namespace App\Http\Controllers;

use App\Services\EmisGtk\EmisGtkExportService;
use App\Services\EmisGtk\EmisGtkReferenceService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EmisGtkController extends Controller
{
    public function __construct(
        private readonly EmisGtkExportService $exportService,
        private readonly EmisGtkReferenceService $referenceService,
    ) {}

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
            ->route('jadwal.index', ['semester_id' => $request->get('semester_id')])
            ->with('emis_import_summary', $summary)
            ->with('success', 'Referensi EMIS-GTK berhasil diimpor ke database.');
    }
}
