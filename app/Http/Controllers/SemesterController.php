<?php

namespace App\Http\Controllers;

use App\Models\Semester;
use App\Models\BebanMengajar;
use App\Models\Jadwal;
use App\Models\Guru;
use App\Services\SemesterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SemesterController extends Controller
{
    protected $semesterService;

    public function __construct(SemesterService $semesterService)
    {
        $this->semesterService = $semesterService;
    }

    public function index()
    {
        // Urutkan berdasarkan tahun terbaru, lalu tipe (Genap > Ganjil)
        $semesters = Semester::orderBy('nama_tahun', 'desc')->orderBy('tipe', 'desc')->get();
        return view('semester.index', compact('semesters'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_tahun' => 'required|string', // Format: 2025/2026
            'tipe' => 'required|in:Ganjil,Genap',
            'is_active' => 'nullable|boolean',
            'clone_from_id' => 'nullable|exists:semesters,id',
        ]);

        try {
            DB::beginTransaction();

            $isFirst = Semester::count() === 0;
            $isActive = $request->boolean('is_active') ?: $isFirst;

            // Jika semester baru diatur aktif, nonaktifkan yang lain
            if ($isActive) {
                Semester::query()->update(['is_active' => false]);
            }

            $semester = Semester::create([
                'nama_tahun' => $request->nama_tahun,
                'tipe' => $request->tipe,
                'is_active' => $isActive,
            ]);

            // Jika ada permintaan klon data
            if ($request->clone_from_id) {
                $this->cloneData($request->clone_from_id, $semester->id);
            }

            $this->semesterService->clearCache();
            DB::commit();

            return redirect()->route('semester.index')->with('success', 'Semester berhasil ditambahkan!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal menyimpan semester: ' . $e->getMessage());
        }
    }

    public function update(Request $request, Semester $semester)
    {
        $request->validate([
            'nama_tahun' => 'required|string',
            'tipe' => 'required|in:Ganjil,Genap',
            'is_active' => 'boolean',
        ]);

        if ($request->is_active && !$semester->is_active) {
            Semester::query()->update(['is_active' => false]);
        }

        $semester->update($request->only('nama_tahun', 'tipe', 'is_active'));
        $this->semesterService->clearCache();

        return redirect()->route('semester.index')->with('success', 'Semester berhasil diperbarui!');
    }

    public function destroy(Semester $semester)
    {
        if ($semester->is_active) {
            return redirect()->back()->with('error', 'Tidak dapat menghapus semester yang sedang aktif.');
        }

        $semester->delete();
        $this->semesterService->clearCache();

        return redirect()->route('semester.index')->with('success', 'Semester berhasil dihapus.');
    }

    public function activate(Semester $semester)
    {
        DB::beginTransaction();
        Semester::query()->update(['is_active' => false]);
        $semester->update(['is_active' => true]);
        DB::commit();

        $this->semesterService->clearCache();

        return redirect()->route('semester.index')->with('success', "Semester {$semester->nama_tahun} - {$semester->tipe} kini aktif.");
    }

    /**
     * Menyalin data KBM, Tugas Tambahan, dan Jadwal dari satu semester ke semester lain.
     */
    private function cloneData(int $sourceId, int $targetId)
    {
        // 1. Salin BebanMengajar
        $oldBebans = BebanMengajar::where('semester_id', $sourceId)->get();
        $bebanMapping = []; // [OldID => NewID]

        foreach ($oldBebans as $old) {
            $new = BebanMengajar::create([
                'semester_id' => $targetId,
                'guru_id' => $old->guru_id,
                'mapel_id' => $old->mapel_id,
                'kelas_id' => $old->kelas_id,
                'jtm' => $old->jtm,
            ]);
            $bebanMapping[$old->id] = $new->id;
        }

        // 2. Salin Tugas Tambahan (Pivot Table)
        $gurus = Guru::with(['tugasTambahans' => function($q) use ($sourceId) {
            $q->wherePivot('semester_id', $sourceId);
        }])->get();

        foreach ($gurus as $guru) {
            foreach ($guru->tugasTambahans as $tugas) {
                // Attach ke semester baru dengan meta data yang sama
                $guru->tugasTambahans()->attach($tugas->id, [
                    'semester_id' => $targetId,
                    'is_ekuivalen' => $tugas->pivot->is_ekuivalen,
                    'detail' => $tugas->pivot->detail,
                ]);
            }
        }

        // 3. Salin Jadwal
        $oldJadwals = Jadwal::where('semester_id', $sourceId)->get();
        foreach ($oldJadwals as $oldJ) {
            if (isset($bebanMapping[$oldJ->beban_mengajar_id])) {
                Jadwal::create([
                    'semester_id' => $targetId,
                    'beban_mengajar_id' => $bebanMapping[$oldJ->beban_mengajar_id],
                    'hari' => $oldJ->hari,
                    'jam_ke' => $oldJ->jam_ke,
                ]);
            }
        }
    }
}
