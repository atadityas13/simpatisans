<?php

namespace App\Http\Controllers;

use App\Models\JadwalVersion;
use App\Models\Semester;
use App\Services\JadwalVersionService;
use App\Services\SemesterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class SemesterController extends Controller
{
    public function __construct(
        protected SemesterService $semesterService,
        protected JadwalVersionService $versionService,
    ) {}

    public function index()
    {
        $query = Semester::query()
            ->orderBy('nama_tahun', 'desc')
            ->orderBy('tipe', 'desc');

        if (Schema::hasTable('jadwal_versions')) {
            $query->with(['versions' => function ($q) {
                $q->orderByDesc('is_default')->orderBy('name');
            }]);
        }

        $semesters = $query->get();

        if (! Schema::hasTable('jadwal_versions')) {
            $semesters->each(fn (Semester $s) => $s->setRelation('versions', collect()));
        }

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

            $payload = [
                'nama_tahun' => $request->nama_tahun,
                'tipe' => $request->tipe,
                'is_active' => $isActive,
            ];
            if (Schema::hasColumn('semesters', 'is_locked')) {
                $payload['is_locked'] = $isLocked;
            }

            $semester = Semester::create($payload);

            if (Schema::hasTable('jadwal_versions')) {
                $targetVersion = $this->versionService->ensureDefault($semester);

                if ($request->clone_from_id) {
                    $sourceVersion = $this->versionService->getDefaultForSemester((int) $request->clone_from_id);
                    if ($sourceVersion) {
                        $this->versionService->cloneVersionData($sourceVersion, $targetVersion);
                    }
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
            'is_active' => 'nullable|boolean',
            'is_locked' => 'nullable|boolean',
        ]);

        try {
            if ($request->boolean('is_active') && ! $semester->is_active) {
                Semester::query()->update(['is_active' => false]);
            }

            $payload = [
                'nama_tahun' => $request->nama_tahun,
                'tipe' => $request->tipe,
                'is_active' => $request->boolean('is_active'),
            ];
            if (Schema::hasColumn('semesters', 'is_locked')) {
                $payload['is_locked'] = $request->boolean('is_locked');
            }

            $semester->update($payload);

            if (Schema::hasTable('jadwal_versions')) {
                $this->versionService->ensureDefault($semester);
            }

            $this->semesterService->clearCache();

            return redirect()->route('semester.index')->with('success', 'Semester berhasil diperbarui!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memperbarui semester: '.$e->getMessage());
        }
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

        $payload = ['is_active' => true];
        if (Schema::hasColumn('semesters', 'is_locked')) {
            $payload['is_locked'] = false;
        }
        $semester->update($payload);

        if (Schema::hasTable('jadwal_versions')) {
            $this->versionService->ensureDefault($semester);
        }
        DB::commit();

        $this->semesterService->clearCache();

        return redirect()->route('semester.index')->with('success', "Semester {$semester->nama_tahun} - {$semester->tipe} kini aktif.");
    }

    public function toggleLock(Semester $semester)
    {
        if (! Schema::hasColumn('semesters', 'is_locked')) {
            return redirect()->route('semester.index')->with(
                'error',
                'Kolom kunci belum tersedia di database. Jalankan migrasi: php artisan migrate --path=database/migrations/2026_07_28_080000_add_is_locked_to_semesters_table.php --force'
            );
        }

        try {
            $semester->update(['is_locked' => ! (bool) $semester->is_locked]);
            $this->semesterService->clearCache();

            $status = $semester->is_locked ? 'dikunci' : 'dibuka';

            return redirect()->route('semester.index')
                ->with('success', "Pembagian tugas & jadwal semester {$semester->nama_tahun} - {$semester->tipe} {$status}.");
        } catch (\Exception $e) {
            return redirect()->route('semester.index')
                ->with('error', 'Gagal mengubah kunci semester: '.$e->getMessage());
        }
    }

    public function toggleVersionLock(Semester $semester, JadwalVersion $version)
    {
        if ((int) $version->semester_id !== (int) $semester->id) {
            return redirect()->back()->with('error', 'Versi tidak cocok dengan semester.');
        }

        if (! Schema::hasColumn('jadwal_versions', 'is_locked')) {
            return redirect()->route('semester.index')->with(
                'error',
                'Kolom kunci versi belum tersedia. Jalankan migrasi: php artisan migrate --path=database/migrations/2026_07_28_100000_add_is_locked_to_jadwal_versions_table.php --force'
            );
        }

        try {
            $version->update(['is_locked' => ! (bool) $version->is_locked]);
            $status = $version->is_locked ? 'dikunci' : 'dibuka';

            return redirect()->route('semester.index')
                ->with('success', "Versi \"{$version->name}\" {$status}.");
        } catch (\Exception $e) {
            return redirect()->route('semester.index')
                ->with('error', 'Gagal mengubah kunci versi: '.$e->getMessage());
        }
    }

    public function storeVersion(Request $request, Semester $semester)
    {
        if (! Schema::hasTable('jadwal_versions')) {
            return redirect()->back()->with(
                'error',
                'Tabel versi belum tersedia. Jalankan migrasi jadwal_versions terlebih dahulu.'
            );
        }

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
