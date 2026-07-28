<?php

namespace App\Services;

use App\Models\BebanMengajar;
use App\Models\Guru;
use App\Models\Jadwal;
use App\Models\JadwalVersion;
use App\Models\Semester;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class JadwalVersionService
{
    public function ensureDefault(Semester $semester): JadwalVersion
    {
        $existing = JadwalVersion::where('semester_id', $semester->id)
            ->where('is_default', true)
            ->first();

        if ($existing) {
            return $existing;
        }

        return JadwalVersion::create([
            'semester_id' => $semester->id,
            'name' => JadwalVersion::NAME_DEFAULT,
            'is_default' => true,
        ]);
    }

    public function getDefaultForSemester(int $semesterId): ?JadwalVersion
    {
        return JadwalVersion::where('semester_id', $semesterId)
            ->where('is_default', true)
            ->first();
    }

    /**
     * Resolve version for a semester. Falls back to default Operasional.
     */
    public function resolveForSemester(int $semesterId, ?int $versionId = null): JadwalVersion
    {
        if ($versionId) {
            $version = JadwalVersion::where('semester_id', $semesterId)
                ->where('id', $versionId)
                ->first();

            if ($version) {
                return $version;
            }
        }

        $default = $this->getDefaultForSemester($semesterId);
        if ($default) {
            return $default;
        }

        $semester = Semester::findOrFail($semesterId);

        return $this->ensureDefault($semester);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, JadwalVersion>
     */
    public function listForSemester(int $semesterId)
    {
        return JadwalVersion::where('semester_id', $semesterId)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();
    }

    public function create(
        Semester $semester,
        string $name,
        bool $copyFromDefault = false,
    ): JadwalVersion {
        $name = trim($name);
        if ($name === '') {
            throw ValidationException::withMessages(['name' => 'Nama versi wajib diisi.']);
        }

        $this->ensureDefault($semester);

        $exists = JadwalVersion::where('semester_id', $semester->id)
            ->where('name', $name)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages(['name' => "Versi \"{$name}\" sudah ada di semester ini."]);
        }

        return DB::transaction(function () use ($semester, $name, $copyFromDefault) {
            $version = JadwalVersion::create([
                'semester_id' => $semester->id,
                'name' => $name,
                'is_default' => false,
            ]);

            if ($copyFromDefault) {
                $source = $this->getDefaultForSemester($semester->id);
                if ($source) {
                    $this->cloneVersionData($source, $version);
                }
            }

            return $version;
        });
    }

    public function delete(JadwalVersion $version): void
    {
        if ($version->is_default) {
            throw ValidationException::withMessages([
                'version' => 'Versi Operasional tidak dapat dihapus.',
            ]);
        }

        $version->delete();
    }

    /**
     * Salin pembagian tugas, tugas tambahan, dan jadwal antar versi (bisa beda semester).
     */
    public function cloneVersionData(JadwalVersion $source, JadwalVersion $target): void
    {
        $bebanMapping = [];

        $oldBebans = BebanMengajar::where('version_id', $source->id)->get();
        foreach ($oldBebans as $old) {
            $new = BebanMengajar::create([
                'semester_id' => $target->semester_id,
                'version_id' => $target->id,
                'guru_id' => $old->guru_id,
                'mapel_id' => $old->mapel_id,
                'kelas_id' => $old->kelas_id,
                'jtm' => $old->jtm,
                'is_linear' => $old->is_linear,
                'is_satminkal' => $old->is_satminkal,
                'jumlah_kelas' => $old->jumlah_kelas,
                'hari' => $old->hari,
            ]);
            $bebanMapping[$old->id] = $new->id;
        }

        $gurus = Guru::with(['tugasTambahans' => function ($q) use ($source) {
            $q->wherePivot('version_id', $source->id);
        }])->get();

        foreach ($gurus as $guru) {
            foreach ($guru->tugasTambahans as $tugas) {
                $guru->tugasTambahans()->attach($tugas->id, [
                    'semester_id' => $target->semester_id,
                    'version_id' => $target->id,
                    'is_ekuivalen' => $tugas->pivot->is_ekuivalen,
                    'detail' => $tugas->pivot->detail,
                    'hari' => $tugas->pivot->hari,
                ]);
            }
        }

        $oldJadwals = Jadwal::where('version_id', $source->id)->get();
        foreach ($oldJadwals as $oldJ) {
            if (! isset($bebanMapping[$oldJ->beban_mengajar_id])) {
                continue;
            }

            Jadwal::create([
                'semester_id' => $target->semester_id,
                'version_id' => $target->id,
                'beban_mengajar_id' => $bebanMapping[$oldJ->beban_mengajar_id],
                'hari' => $oldJ->hari,
                'jam_ke' => $oldJ->jam_ke,
            ]);
        }
    }
}
