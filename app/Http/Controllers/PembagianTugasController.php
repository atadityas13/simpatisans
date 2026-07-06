<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\Mapel;
use App\Models\Kelas;
use App\Models\TugasTambahan;
use App\Models\BebanMengajar;
use App\Models\Semester;
use App\Services\GuruService;
use App\Services\SemesterService;
use Illuminate\Http\Request;

class PembagianTugasController extends Controller
{
    protected $guruService;
    protected $semesterService;

    public function __construct(GuruService $guruService, SemesterService $semesterService)
    {
        $this->guruService = $guruService;
        $this->semesterService = $semesterService;
    }

    public function index(Request $request)
    {
        $allSemesters = Semester::orderBy('nama_tahun', 'desc')->orderBy('tipe', 'desc')->get();
        $activeSemester = $this->semesterService->getActiveSemester();
        
        $semesterId = $request->get('semester_id', $activeSemester?->id);

        if (!$semesterId) {
            $selectedSemester = null;
            $gurus = collect([]);
            $rows = [];
            $guruSearchBlobs = [];
            return view('pembagian.index', compact('gurus', 'rows', 'allSemesters', 'selectedSemester', 'guruSearchBlobs'));
        }

        $selectedSemester = Semester::findOrFail($semesterId);

        $gurus = Guru::with([
            'bebanMengajars' => fn($q) => $q->where('semester_id', $semesterId),
            'bebanMengajars.mapel',
            'tugasTambahans' => fn($q) => $q->wherePivot('semester_id', $semesterId),
            'mapelSertifikasi'
        ])
        ->orderByRaw('duk IS NULL ASC, duk ASC')
        ->get();

        $rows = [];
        foreach ($gurus as $guru) {
            /** @var \App\Models\Guru $guru */
            $rows[] = $this->guruService->hitungMetrik($guru, $semesterId);
        }

        $guruSearchBlobs = $this->buildGuruSearchBlobs($gurus);

        return view('pembagian.index', compact('gurus', 'rows', 'allSemesters', 'selectedSemester', 'guruSearchBlobs'));
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Guru>  $gurus
     * @return array<int, string>
     */
    private function buildGuruSearchBlobs($gurus): array
    {
        return $gurus->map(fn (Guru $g) => $g->searchBlob())->values()->all();
    }

    public function show(Request $request, $id)
    {
        $allSemesters = Semester::orderBy('nama_tahun', 'desc')->orderBy('tipe', 'desc')->get();
        $activeSemester = $this->semesterService->getActiveSemester();
        
        $semesterId = $request->get('semester_id', $activeSemester?->id);

        if (!$semesterId) {
            $selectedSemester = null;
            $guru = Guru::findOrFail($id);
            $bebanMengajars = collect([]);
            $tugasTambahans = collect([]);
            $nonSatminkal = collect([]);
            $metrik = ['layak' => false, 'TARGET' => 24, 'totalLinear' => 0, 'totalTugas' => 0, 'SisaKekuranganTarget' => 24, 'totalSatminkal' => 0, 'totalNonSatminkal' => 0];
            return view('pembagian.show', compact('guru', 'bebanMengajars', 'tugasTambahans', 'nonSatminkal', 'metrik', 'allSemesters', 'selectedSemester'));
        }

        $selectedSemester = Semester::findOrFail($semesterId);

        $guru = Guru::with([
            'bebanMengajars' => fn($q) => $q->where('semester_id', $semesterId),
            'bebanMengajars.mapel',
            'bebanMengajars.kelas',
            'tugasTambahans' => fn($q) => $q->wherePivot('semester_id', $semesterId),
            'mapelSertifikasi',
            'mapelDiampu',
        ])->findOrFail($id);

        $kelas   = Kelas::orderByRaw("FIELD(tingkat, 'VII', 'VIII', 'IX')")->orderBy('nama_kelas')->get();
        
        // Hanya tampilkan mapel yang diampu oleh bapak guru ybs
        $mapels  = $guru->mapelDiampu;

        // Peta keterisian: [mapel_id][kelas_id] => Nama Guru
        $allBeban = BebanMengajar::where('semester_id', $semesterId)->with('guru')->get();
        $occupiedMap = [];
        foreach ($allBeban as $b) {
            $occupiedMap[$b->mapel_id][$b->kelas_id] = $b->guru->nama_lengkap;
        }

        $tugases = TugasTambahan::orderBy('id', 'asc')->get();
        $metrik  = $this->guruService->hitungMetrik($guru, $semesterId);

        // Cari tugas ekuivalen yang sudah ada di semester terpilih (exclude Guru Piket karena ia bersifat aditif/bisa double)
        $existingEkuivalen = $guru->tugasTambahans
            ->where('pivot.semester_id', $semesterId)
            ->where('pivot.is_ekuivalen', 1)
            ->where('id', '!=', TugasTambahan::GURU_PIKET_ID)
            ->first();

        $occupiedMap = (object)$occupiedMap;

        return view('pembagian.show', compact('guru', 'kelas', 'mapels', 'occupiedMap', 'tugases', 'metrik', 'existingEkuivalen', 'allSemesters', 'selectedSemester'));
    }



    // ── Tambah Mengajar (KBM) ──
    public function storeKbm(Request $request, $id)
    {
        $request->validate([
            'mapel_id'    => 'required|exists:mapels,id',
            'kelas_ids'   => 'required|array|min:1',
            'kelas_ids.*' => 'exists:kelas,id',
            'semester_id' => 'required|exists:semesters,id',
        ]);

        $mapel = Mapel::findOrFail($request->mapel_id);
        foreach ($request->kelas_ids as $kelasId) {
            if (!BebanMengajar::where('guru_id', $id)
                ->where('mapel_id', $request->mapel_id)
                ->where('kelas_id', $kelasId)
                ->where('semester_id', $request->semester_id)
                ->exists()) {
                BebanMengajar::create([
                    'guru_id' => $id, 
                    'mapel_id' => $request->mapel_id, 
                    'kelas_id' => $kelasId, 
                    'semester_id' => $request->semester_id,
                    'jtm' => $mapel->jtm_default
                ]);
            }
        }
        return redirect()->route('pembagian.show', ['guru' => $id, 'semester_id' => $request->semester_id])
            ->with('success', 'Penugasan mengajar berhasil ditambahkan!');
    }

    public function destroyKbm(Request $request, $id)
    {
        $kbm = BebanMengajar::findOrFail($id);
        $guru_id = $kbm->guru_id;
        $semester_id = $kbm->semester_id;
        $kbm->delete();
        return redirect()->route('pembagian.show', ['guru' => $guru_id, 'semester_id' => $semester_id])
            ->with('success', 'Penugasan dihapus.');
    }

    // ── Non-Satminkal ──
    public function storeNonSatminkal(Request $request, $id)
    {
        $request->validate([
            'mapel_id'      => 'required|exists:mapels,id',
            'jumlah_kelas'  => 'required|integer|min:1',
            'hari'          => 'required|array|min:1',
            'semester_id'   => 'required|exists:semesters,id',
        ]);

        $mapel = Mapel::findOrFail($request->mapel_id);
        
        $beban = BebanMengajar::create([
            'guru_id'      => $id,
            'mapel_id'     => $request->mapel_id,
            'semester_id'  => $request->semester_id,
            'jtm'          => $mapel->jtm_default * $request->jumlah_kelas,
            'is_satminkal' => false,
            'jumlah_kelas' => $request->jumlah_kelas,
            'hari'         => $request->hari,
        ]);

        // Integrasi dengan GuruConstraint (Blackout)
        foreach ($request->hari as $hariName) {
            for ($jam = 1; $jam <= 10; $jam++) {
                \App\Models\GuruConstraint::updateOrCreate(
                    ['guru_id' => $id, 'hari' => $hariName, 'jam_ke' => $jam],
                    ['type' => 0] // Block
                );
            }
        }

        return redirect()->route('pembagian.show', ['guru' => $id, 'semester_id' => $request->semester_id])
            ->with('success', 'Data Mengajar Non-satminkal berhasil disimpan & hari diblokir di penjadwalan!');
    }

    public function destroyNonSatminkal(Request $request, $id)
    {
        $beban = BebanMengajar::findOrFail($id);
        $guruId = $beban->guru_id;
        $hariArr = $beban->hari;
        $semesterId = $beban->semester_id;

        $beban->delete();

        // Bersihkan GuruConstraint jika tidak ada beban non-satminkal lain di hari tersebut
        if (is_array($hariArr)) {
            foreach ($hariArr as $hariName) {
                $stillBusy = BebanMengajar::where('guru_id', $guruId)
                    ->where('is_satminkal', false)
                    ->whereJsonContains('hari', $hariName)
                    ->exists();
                
                if (!$stillBusy) {
                    \App\Models\GuruConstraint::where('guru_id', $guruId)
                        ->where('hari', $hariName)
                        ->where('type', 0)
                        ->delete();
                }
            }
        }

        return redirect()->route('pembagian.show', ['guru' => $guruId, 'semester_id' => $semesterId])
            ->with('success', 'Data Non-satminkal dihapus.');
    }

    // ── Tambah Tugas Tambahan (Modal style) ──
    public function storeTugas(Request $request, $id)
    {
        $request->validate([
            'tugas_id'    => 'required|exists:tugas_tambahans,id',
            'semester_id' => 'required|exists:semesters,id',
            'detail'      => 'nullable|string',
            'is_ekuivalen' => 'nullable|boolean',
            'hari'        => 'nullable|array',
        ]);

        $guru = Guru::findOrFail($id);
        $tugas = TugasTambahan::find($request->tugas_id);

        // Wali Kelas & Waka & Piket: otomatis ekuivalen jika tipe system
        $isEkuivalen = ($tugas->isSystem()) ? 1 : ($request->boolean('is_ekuivalen') ? 1 : 0);

        if ($isEkuivalen) {
            // Jika yang ditambahkan BUKAN Guru Piket, cabut ekuivalen dari yang lain (KECUALI Guru Piket)
            if ($tugas->id != TugasTambahan::GURU_PIKET_ID) {
                \Illuminate\Support\Facades\DB::table('guru_tugas_tambahans')
                    ->where('guru_id', $guru->id)
                    ->where('semester_id', $request->semester_id)
                    ->where('tugas_tambahan_id', '!=', TugasTambahan::GURU_PIKET_ID)
                    ->update(['is_ekuivalen' => 0]);
            }
        }

        $exists = \Illuminate\Support\Facades\DB::table('guru_tugas_tambahans')
            ->where('guru_id', $guru->id)
            ->where('tugas_tambahan_id', $request->tugas_id)
            ->where('semester_id', $request->semester_id)
            ->exists();

        $hariData = ($tugas->id == TugasTambahan::GURU_PIKET_ID && $request->hari) ? json_encode($request->hari) : null;

        if ($exists) {
            \Illuminate\Support\Facades\DB::table('guru_tugas_tambahans')
                ->where('guru_id', $guru->id)
                ->where('tugas_tambahan_id', $request->tugas_id)
                ->where('semester_id', $request->semester_id)
                ->update([
                    'is_ekuivalen' => $isEkuivalen,
                    'detail'       => $request->detail,
                    'hari'         => $hariData,
                ]);
            $msg = 'Tugas tambahan berhasil diperbarui!';
        } else {
            $guru->tugasTambahans()->attach($request->tugas_id, [
                'is_ekuivalen' => $isEkuivalen,
                'detail'       => $request->detail,
                'hari'         => $hariData,
                'semester_id'  => $request->semester_id,
            ]);
            $msg = 'Tugas tambahan berhasil ditambahkan!';
        }

        return redirect()->route('pembagian.show', ['guru' => $id, 'semester_id' => $request->semester_id])
            ->with('success', $msg);
    }

    public function destroyTugas(Request $request, $guruId, $tugasId)
    {
        $semester_id = $request->query('semester_id');
        $guru = Guru::findOrFail($guruId);
        
        $guru->tugasTambahans()
            ->wherePivot('semester_id', $semester_id)
            ->wherePivot('tugas_tambahan_id', $tugasId)
            ->detach($tugasId);

        // Auto-promote another system task to ekuivalen if there is no main ekuivalen left
        $hasMainEkuivalen = \Illuminate\Support\Facades\DB::table('guru_tugas_tambahans')
            ->where('guru_id', $guruId)
            ->where('semester_id', $semester_id)
            ->where('is_ekuivalen', 1)
            ->where('tugas_tambahan_id', '!=', TugasTambahan::GURU_PIKET_ID)
            ->exists();

        if (!$hasMainEkuivalen) {
            $sysTugasToPromote = \Illuminate\Support\Facades\DB::table('guru_tugas_tambahans')
                ->join('tugas_tambahans', 'guru_tugas_tambahans.tugas_tambahan_id', '=', 'tugas_tambahans.id')
                ->where('guru_tugas_tambahans.guru_id', $guruId)
                ->where('guru_tugas_tambahans.semester_id', $semester_id)
                ->where('tugas_tambahans.tipe', 'system')
                ->where('tugas_tambahans.id', '!=', TugasTambahan::GURU_PIKET_ID)
                ->select('guru_tugas_tambahans.tugas_tambahan_id as pivot_id')
                ->first();

            if ($sysTugasToPromote) {
                \Illuminate\Support\Facades\DB::table('guru_tugas_tambahans')
                    ->where('guru_id', $guruId)
                    ->where('semester_id', $semester_id)
                    ->where('tugas_tambahan_id', $sysTugasToPromote->pivot_id)
                    ->update(['is_ekuivalen' => 1]);
            }
        }

        return redirect()->route('pembagian.show', ['guru' => $guruId, 'semester_id' => $semester_id])
            ->with('success', 'Tugas tambahan dihapus.');
    }

    public function clearKbm(Request $request, $id)
    {
        $semesterId = $request->query('semester_id');
        $bebanMengajars = BebanMengajar::where('guru_id', $id)
            ->where('semester_id', $semesterId)
            ->get();

        foreach ($bebanMengajars as $bm) {
            /** @var \App\Models\BebanMengajar $bm */
            // Clean up constraints if Non-Satminkal
            if (!$bm->is_satminkal && !empty($bm->hari)) {
                $hariArr = is_array($bm->hari) ? $bm->hari : json_decode($bm->hari, true);
                if ($hariArr) {
                    foreach ($hariArr as $hariName) {
                        // Check if this teacher still has other non-satminkal tasks on this day (excluding current one)
                        $stillBusy = BebanMengajar::where('guru_id', $id)
                            ->where('is_satminkal', false)
                            ->where('id', '!=', $bm->id)
                            ->whereJsonContains('hari', $hariName)
                            ->exists();
                        
                        if (!$stillBusy) {
                            \App\Models\GuruConstraint::where('guru_id', $id)
                                ->where('hari', $hariName)
                                ->where('type', 0) // Blocking type
                                ->delete();
                        }
                    }
                }
            }
            $bm->delete();
        }

        return redirect()->route('pembagian.show', ['guru' => $id, 'semester_id' => $semesterId])
            ->with('success', 'Semua penugasan KBM berhasil dibersihkan.');
    }
}
