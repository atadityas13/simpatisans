<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\TugasTambahan;
use App\Services\JadwalVersionService;
use App\Services\SemesterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class MadaniRombelController extends Controller
{
    public function __construct(
        private SemesterService $semesterService,
        private JadwalVersionService $versionService,
    ) {}

    public function index(): JsonResponse
    {
        $semester = $this->semesterService->getActiveSemester();
        if (! $semester) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada semester aktif.',
            ], 422);
        }

        $version = $this->versionService->resolveForSemester($semester->id);

        $kelasList = Kelas::query()
            ->orderByRaw("CASE tingkat WHEN 'VII' THEN 1 WHEN 'VIII' THEN 2 WHEN 'IX' THEN 3 ELSE 9 END")
            ->orderBy('nama_kelas')
            ->get(['id', 'nama_kelas', 'tingkat']);

        $waliRows = DB::table('guru_tugas_tambahans as gtt')
            ->join('gurus as g', 'g.id', '=', 'gtt.guru_id')
            ->where('gtt.tugas_tambahan_id', TugasTambahan::WALI_KELAS_ID)
            ->where('gtt.semester_id', $semester->id)
            ->where('gtt.version_id', $version->id)
            ->whereNotNull('gtt.detail')
            ->select([
                'gtt.detail',
                'g.username as nip',
                'g.nama_guru',
                'g.gelar_depan',
                'g.gelar_belakang',
            ])
            ->get();

        $waliByKelas = [];
        foreach ($waliRows as $row) {
            $detail = (string) $row->detail;
            if ($detail === '' || isset($waliByKelas[$detail])) {
                continue;
            }

            $prefix = ($row->gelar_depan && $row->gelar_depan !== '-') ? $row->gelar_depan.' ' : '';
            $suffix = ($row->gelar_belakang && $row->gelar_belakang !== '-') ? ', '.$row->gelar_belakang : '';

            $waliByKelas[$detail] = [
                'nip' => (string) $row->nip,
                'nama' => $prefix.$row->nama_guru.$suffix,
            ];
        }

        $data = $kelasList->map(function (Kelas $kelas) use ($waliByKelas) {
            $tingkat = (string) $kelas->tingkat;
            $namaKelas = (string) $kelas->nama_kelas;
            $wali = $waliByKelas[$namaKelas] ?? null;

            return [
                'kelas_id' => $kelas->id,
                'nama_kelas' => $namaKelas,
                'tingkat' => $tingkat,
                'nama' => $this->parseNamaRombel($namaKelas, $tingkat),
                'wali' => $wali,
            ];
        })->values();

        $denganWali = $data->filter(fn (array $item) => $item['wali'] !== null)->count();
        $total = $data->count();

        return response()->json([
            'success' => true,
            'semester' => [
                'id' => $semester->id,
                'nama' => $semester->full_label,
                'tahun' => (string) $semester->nama_tahun,
                'tipe' => (string) $semester->tipe,
            ],
            'tahun_ajaran' => [
                'nama' => (string) $semester->nama_tahun,
            ],
            'version' => [
                'id' => $version->id,
                'nama' => $version->name,
            ],
            'meta' => [
                'total' => $total,
                'dengan_wali' => $denganWali,
                'tanpa_wali' => $total - $denganWali,
            ],
            'data' => $data,
        ]);
    }

    private function parseNamaRombel(string $namaKelas, string $tingkat): string
    {
        $stripped = preg_replace('/^Kelas\s+/iu', '', trim($namaKelas)) ?? trim($namaKelas);
        $pattern = '/^'.preg_quote($tingkat, '/').'[\.\-\s]+(.+)$/iu';

        if (preg_match($pattern, $stripped, $matches) === 1) {
            return trim($matches[1]);
        }

        return $stripped;
    }
}
