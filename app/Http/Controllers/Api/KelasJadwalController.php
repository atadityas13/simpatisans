<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Jadwal;
use App\Models\Kelas;
use App\Services\JamPelajaranService;
use App\Services\JadwalVersionService;
use App\Services\SemesterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KelasJadwalController extends Controller
{
    public function __construct(
        private SemesterService $semesterService,
        private JamPelajaranService $jamPelajaranService,
        private JadwalVersionService $versionService,
    ) {}

    public function show(Request $request): JsonResponse
    {
        /** @var array<string, mixed> $claims */
        $claims = $request->attributes->get('madani_siswa', []);
        $rombelClaim = is_array($claims['rombel'] ?? null) ? $claims['rombel'] : null;

        $nama = filled($rombelClaim['label'] ?? null) ? (string) $rombelClaim['label'] : null;
        $tingkat = filled($rombelClaim['tingkat'] ?? null) ? (string) $rombelClaim['tingkat'] : null;
        $rombel = filled($rombelClaim['nama'] ?? null) ? (string) $rombelClaim['nama'] : null;

        if ($nama === null && $tingkat === null && $rombel === null) {
            return response()->json([
                'success' => true,
                'kelas' => null,
                'jadwal' => [],
                'meta' => [
                    'total' => 0,
                    'matched' => false,
                    'message' => 'Siswa belum memiliki rombel aktif.',
                ],
            ]);
        }

        $kelas = $this->resolveKelas($nama, $tingkat, $rombel);

        if (! $kelas) {
            return response()->json([
                'success' => true,
                'kelas' => null,
                'jadwal' => [],
                'meta' => [
                    'total' => 0,
                    'matched' => false,
                    'message' => 'Kelas tidak ditemukan di SimpatiSans.',
                ],
            ]);
        }

        $semester = $this->semesterService->getActiveSemester();
        if (! $semester) {
            return response()->json([
                'success' => true,
                'kelas' => [
                    'id' => $kelas->id,
                    'nama_kelas' => $kelas->nama_kelas,
                    'tingkat' => $kelas->tingkat,
                ],
                'jadwal' => [],
                'meta' => [
                    'total' => 0,
                    'matched' => true,
                    'message' => 'Semester aktif belum diatur.',
                ],
            ]);
        }

        $versionId = $this->versionService->resolveForSemester($semester->id)->id;

        $jadwal = Jadwal::query()
            ->where('semester_id', $semester->id)
            ->where('version_id', $versionId)
            ->whereHas('bebanMengajar', fn ($q) => $q->where('kelas_id', $kelas->id))
            ->with([
                'bebanMengajar.mapel:id,nama_mapel',
                'bebanMengajar.kelas:id,nama_kelas',
                'bebanMengajar.guru:id,nama_guru,kode_guru',
            ])
            ->orderByRaw("FIELD(LOWER(TRIM(hari)), 'senin','selasa','rabu','kamis','jumat','sabtu')")
            ->orderBy('jam_ke')
            ->get()
            ->map(fn ($j) => [
                'jadwal_id' => $j->id,
                'kelas_id' => $j->bebanMengajar?->kelas_id,
                'mapel_id' => $j->bebanMengajar?->mapel_id,
                'hari' => $this->normalizeHari((string) $j->hari),
                'jam_ke' => (int) $j->jam_ke,
                'waktu' => $this->jamPelajaranService->waktuFor($j->hari, (int) $j->jam_ke),
                'mapel' => $j->bebanMengajar?->mapel?->nama_mapel,
                'kelas' => $j->bebanMengajar?->kelas?->nama_kelas,
                'guru' => $j->bebanMengajar?->guru?->nama_guru,
            ])
            ->values();

        return response()->json([
            'success' => true,
            'kelas' => [
                'id' => $kelas->id,
                'nama_kelas' => $kelas->nama_kelas,
                'tingkat' => $kelas->tingkat,
            ],
            'jadwal' => $jadwal,
            'meta' => [
                'total' => $jadwal->count(),
                'matched' => true,
                'semester_id' => $semester->id,
            ],
        ]);
    }

    private function resolveKelas(?string $nama, ?string $tingkat, ?string $rombel): ?Kelas
    {
        $candidates = collect([
            $nama,
            filled($tingkat) && filled($rombel) ? trim($tingkat).'.'.trim($rombel) : null,
            filled($tingkat) && filled($rombel) ? trim($tingkat).'-'.trim($rombel) : null,
            filled($tingkat) && filled($rombel) ? 'Kelas '.trim($tingkat).'.'.trim($rombel) : null,
        ])->filter()->unique()->values();

        if ($candidates->isEmpty()) {
            return null;
        }

        $keys = $candidates->map(fn (string $value) => $this->kunciKelas($value))->unique()->all();

        return Kelas::query()
            ->get(['id', 'nama_kelas', 'tingkat'])
            ->first(function (Kelas $kelas) use ($keys, $tingkat, $rombel) {
                $namaKey = $this->kunciKelas((string) $kelas->nama_kelas);
                if (in_array($namaKey, $keys, true)) {
                    return true;
                }

                if (filled($tingkat) && filled($rombel)) {
                    $tingkatKey = $this->kunciKelas((string) $tingkat);
                    $rombelKey = $this->kunciKelas((string) $rombel);

                    // "Kelas VII.1" / "VII-1" → tingkat VII + rombel 1
                    if (preg_match('/^(VIII|IX|VII|9|8|7)([A-Z0-9]+)$/i', $namaKey, $m)) {
                        return $this->kunciKelas($m[1]) === $tingkatKey
                            && $this->kunciKelas($m[2]) === $rombelKey;
                    }
                }

                return false;
            });
    }

    private function kunciKelas(string $raw): string
    {
        $s = strtoupper(trim($raw));
        $s = preg_replace('/^KELAS\s+/u', '', $s) ?? $s;
        $s = preg_replace('/[^A-Z0-9]/u', '', $s) ?? $s;

        if (preg_match('/^([789])(.*)$/', $s, $m) === 1) {
            $map = ['7' => 'VII', '8' => 'VIII', '9' => 'IX'];
            $s = $map[$m[1]].$m[2];
        }

        return $s;
    }

    private function normalizeHari(string $hari): string
    {
        $cleaned = strtolower(trim(str_replace(["'", '`'], '', $hari)));

        return match ($cleaned) {
            'senin' => 'Senin',
            'selasa' => 'Selasa',
            'rabu' => 'Rabu',
            'kamis' => 'Kamis',
            'jumat' => 'Jumat',
            'sabtu' => 'Sabtu',
            'minggu' => 'Minggu',
            default => ucfirst($cleaned),
        };
    }
}
