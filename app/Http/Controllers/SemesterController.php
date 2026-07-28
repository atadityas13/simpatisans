<?php

namespace App\Http\Controllers;

use App\Models\JadwalVersion;
use App\Models\Semester;
use App\Services\JadwalVersionService;
use App\Services\SemesterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SemesterController extends Controller
{
    public function __construct(
        protected SemesterService $semesterService,
        protected JadwalVersionService $versionService,
    ) {}

    public function index()
    {
        $semesters = Semester::with(['versions' => function ($q) {
            $q->orderByDesc('is_default')->orderBy('name');
        }])
            ->orderBy('nama_tahun', 'desc')
            ->orderBy('tipe', 'desc')
            ->get();

        return view('semester.index', compact('semesters'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_tahun' => 'required|string',
            'tipe' => 'required|in:Ganjil,Genap',
            'is_active' => 'nullable|boolean',
            'is_locked' => 'nullable|boolean',
            'clone_from_id' => 'nullable|exists:semesters,id',
        ]);

        try {
            DB::beginTransaction();

            $isFirst = Semester::count() === 0;
            $isActive = $request->boolean('is_active') ?: $isFirst;

            $isLocked = $request->has('is_locked')
                ? $request->boolean('is_locked')
                : ! $isActive;

            if ($isActive) {
                Semester::query()->update(['is_active' => false]);
            }

            $semester = Semester::create([
                'nama_tahun' => $request->nama_tahun,
                'tipe' => $request->tipe,
                'is_active' => $isActive,
                'is_locked' => $isLocked,
            ]);

            $targetVersion = $this->versionService->ensureDefault($semester);

            if ($request->clone_from_id) {
                $sourceVersion = $this->versionService->getDefaultForSemester((int) $request->clone_from_id);
                if ($sourceVersion) {
                    $this->versionService->cloneVersionData($sourceVersion, $targetVersion);
                }
            }

            $this->semesterService->clearCache();
            DB::commit();

            return redirect()->route('semester.index')->with('success', 'Semester berhasil ditambahkan!');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Gagal menyimpan semester: '.$e->getMessage());
        }
    }

    public function update(Request $request, Semester $semester)
    {
        $request->validate([
            'nama_tahun' => 'required|string',
            'tipe' => 'required|in:Ganjil,Genap',
            'is_active' => 'boolean',
            'is_locked' => 'boolean',
        ]);

        if ($request->boolean('is_active') && ! $semester->is_active) {
            Semester::query()->update(['is_active' => false]);
        }

        $semester->update([
            'nama_tahun' => $request->nama_tahun,
            'tipe' => $request->tipe,
            'is_active' => $request->boolean('is_active'),
            'is_locked' => $request->boolean('is_locked'),
        ]);
        $this->versionService->ensureDefault($semester);
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
        $semester->update([
            'is_active' => true,
            'is_locked' => false,
        ]);
        $this->versionService->ensureDefault($semester);
        DB::commit();

        $this->semesterService->clearCache();

        return redirect()->route('semester.index')->with('success', "Semester {$semester->nama_tahun} - {$semester->tipe} kini aktif.");
    }

    public function toggleLock(Semester $semester)
    {
        $semester->update(['is_locked' => ! $semester->is_locked]);
        $this->semesterService->clearCache();

        $status = $semester->is_locked ? 'dikunci' : 'dibuka';

        return redirect()->route('semester.index')
            ->with('success', "Pembagian tugas & jadwal semester {$semester->nama_tahun} - {$semester->tipe} {$status}.");
    }

    public function storeVersion(Request $request, Semester $semester)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'copy_operational' => 'nullable|boolean',
        ]);

        try {
            $this->versionService->create(
                $semester,
                $request->input('name'),
                $request->boolean('copy_operational'),
            );

            return redirect()->route('semester.index')
                ->with('success', "Versi \"{$request->input('name')}\" berhasil ditambahkan.");
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menambah versi: '.$e->getMessage());
        }
    }

    public function destroyVersion(Semester $semester, JadwalVersion $version)
    {
        if ((int) $version->semester_id !== (int) $semester->id) {
            return redirect()->back()->with('error', 'Versi tidak cocok dengan semester.');
        }

        try {
            $name = $version->name;
            $this->versionService->delete($version);

            return redirect()->route('semester.index')
                ->with('success', "Versi \"{$name}\" berhasil dihapus beserta datanya.");
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors());
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus versi: '.$e->getMessage());
        }
    }
}
