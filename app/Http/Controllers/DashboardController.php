<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\BebanMengajar;
use App\Models\TugasTambahan;
use App\Services\GuruService;
use App\Services\JadwalService;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    protected $guruService;
    protected $jadwalService;
    protected $semesterService;

    public function __construct(GuruService $guruService, JadwalService $jadwalService, \App\Services\SemesterService $semesterService)
    {
        $this->guruService = $guruService;
        $this->jadwalService = $jadwalService;
        $this->semesterService = $semesterService;
    }

    public function index()
    {
        $activeSemester = $this->semesterService->getActiveSemester();

        if (!$activeSemester) {
            $stats = [
                'total_guru' => \App\Models\Guru::count(),
                'total_kelas' => \App\Models\Kelas::count(),
                'total_mapel' => \App\Models\Mapel::count(),
                'progres_jtm' => 0, 'jtm_terisi' => 0, 'jtm_total' => 0,
                'health_score' => 0, 'total_problems' => 0,
            ];
            $rekomendasi = ['wali_kelas_kosong' => [], 'defisit_tpg' => []];
            $analisa = ['bentrok' => [], 'fatigue' => [], 'pelanggaran_ketentuan' => [], 'struktur_jtm' => [], 'summary' => []];
            return view('welcome', compact('stats', 'rekomendasi', 'analisa', 'activeSemester'));
        }

        $semesterId = $activeSemester->id;

        $stats = [
            'total_guru'  => Guru::count(),
            'total_kelas' => Kelas::count(),
            'total_mapel' => Mapel::count(),
        ];

        // 1. Progress Alokasi JTM (Estimasi) - Hanya untuk semester aktif
        $totalKebutuhanJtm = Kelas::count() * Mapel::sum('jtm_default');
        $totalAlokasiJtm   = BebanMengajar::where('semester_id', $semesterId)->sum('jtm');
        $stats['progres_jtm'] = $totalKebutuhanJtm > 0 ? round(($totalAlokasiJtm / $totalKebutuhanJtm) * 100) : 0;
        $stats['jtm_terisi'] = $totalAlokasiJtm;
        $stats['jtm_total']  = $totalKebutuhanJtm;

        // 2. Analisa Jadwal (Integrity Score) - Hanya untuk semester aktif
        $analisa = $this->jadwalService->analisaPenuh($semesterId);
        $stats['health_score'] = $analisa['summary']['health_score'];
        $stats['total_problems'] = $analisa['summary']['total_warnings'];

        // 3. Rekomendasi: Rombel Tanpa Wali Kelas
        $waliKelasTerisi = DB::table('guru_tugas_tambahans')
            ->where('semester_id', $semesterId)
            ->where('tugas_tambahan_id', TugasTambahan::WALI_KELAS_ID)
            ->pluck('detail')
            ->filter()
            ->toArray();
        
        $rekomendasi['wali_kelas_kosong'] = Kelas::whereNotIn('nama_kelas', $waliKelasTerisi)
            ->orderByRaw("FIELD(tingkat, 'VII', 'VIII', 'IX')")
            ->orderBy('nama_kelas')
            ->get();

        // 4. Rekomendasi: Defisit JTM Linear (Guru Sertifikasi) - Berdasarkan semester aktif
        $guruSertifikasi = Guru::with([
            'bebanMengajars' => fn($q) => $q->where('semester_id', $semesterId),
            'bebanMengajars.mapel',
            'tugasTambahans' => fn($q) => $q->wherePivot('semester_id', $semesterId),
            'mapelSertifikasi'
        ])
        ->where('status_sertifikasi', true)
        ->get();

        $defisitTpg = [];
        foreach ($guruSertifikasi as $guru) {
            $metrik = $this->guruService->hitungMetrik($guru, $semesterId);
            if (!$metrik['layak']) {
                $defisitTpg[] = [
                    'id'     => $guru->id,
                    'nama'   => $guru->nama_lengkap,
                    'kurang' => $metrik['TARGET'] - $metrik['totalLinear'],
                    'total'  => $metrik['totalLinear']
                ];
            }
        }
        $rekomendasi['defisit_tpg'] = collect($defisitTpg)->sortByDesc('kurang');

        return view('welcome', compact('stats', 'rekomendasi', 'analisa', 'activeSemester'));
    }
}
