<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BebanMengajar;
use App\Models\Guru;
use App\Models\JurnalPembelajaran;
use App\Models\Kelas;
use App\Models\TugasTambahan;
use App\Services\CetakPresetService;
use App\Services\SemesterService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class GuruJurnalController extends Controller
{
    public function __construct(
        private SemesterService $semesterService,
        private CetakPresetService $cetakPresetService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        [$guru, $semester, $error] = $this->resolveContext($request);
        if ($error) {
            return $error;
        }

        $beban = BebanMengajar::where('guru_id', $guru->id)
            ->where('semester_id', $semester->id)
            ->whereNotNull('kelas_id')
            ->with(['kelas:id,nama_kelas,tingkat', 'mapel:id,nama_mapel'])
            ->get();

        $kelasGroups = $beban->groupBy('kelas_id')->map(function ($items, $kelasId) use ($guru, $semester) {
            $kelas = $items->first()?->kelas;
            $mapels = $items
                ->filter(fn ($b) => $b->mapel)
                ->unique('mapel_id')
                ->values()
                ->map(fn ($b) => [
                    'id' => $b->mapel_id,
                    'nama' => $b->mapel?->nama_mapel,
                ]);

            $entryCount = JurnalPembelajaran::where('guru_id', $guru->id)
                ->where('semester_id', $semester->id)
                ->where('kelas_id', $kelasId)
                ->count();

            return [
                'kelas_id' => (int) $kelasId,
                'nama_kelas' => $kelas?->nama_kelas,
                'tingkat' => $kelas?->tingkat,
                'jumlah_entri' => $entryCount,
                'mapel' => $mapels,
            ];
        })->values()->sortBy('nama_kelas')->values();

        return response()->json([
            'success' => true,
            'semester' => [
                'id' => $semester->id,
                'nama_tahun' => $semester->nama_tahun,
                'tipe' => $semester->tipe,
            ],
            'data' => $kelasGroups,
            'message' => $kelasGroups->isEmpty()
                ? 'Belum ada kelas diampu pada semester aktif. Pastikan Pembagian Tugas (beban mengajar) sudah diisi untuk TA/semester ini.'
                : null,
        ]);
    }

    public function show(Request $request, Kelas $kelas): JsonResponse
    {
        [$guru, $semester, $error] = $this->resolveContext($request);
        if ($error) {
            return $error;
        }

        if (! $this->guruOwnsKelas($guru->id, $semester->id, $kelas->id)) {
            return response()->json(['success' => false, 'message' => 'Kelas tidak diampu pada semester aktif.'], 403);
        }

        $entries = JurnalPembelajaran::where('guru_id', $guru->id)
            ->where('semester_id', $semester->id)
            ->where('kelas_id', $kelas->id)
            ->with(['mapel:id,nama_mapel'])
            ->orderBy('tanggal')
            ->orderBy('jam_ke')
            ->get()
            ->map(fn ($item) => $this->formatEntry($item));

        $mapels = BebanMengajar::where('guru_id', $guru->id)
            ->where('semester_id', $semester->id)
            ->where('kelas_id', $kelas->id)
            ->with('mapel:id,nama_mapel')
            ->get()
            ->filter(fn ($b) => $b->mapel)
            ->unique('mapel_id')
            ->values()
            ->map(fn ($b) => [
                'id' => $b->mapel_id,
                'nama' => $b->mapel?->nama_mapel,
            ]);

        return response()->json([
            'success' => true,
            'semester' => [
                'id' => $semester->id,
                'nama_tahun' => $semester->nama_tahun,
                'tipe' => $semester->tipe,
            ],
            'kelas' => [
                'id' => $kelas->id,
                'nama_kelas' => $kelas->nama_kelas,
            ],
            'mapel' => $mapels,
            'data' => $entries,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        [$guru, $semester, $error] = $this->resolveContext($request);
        if ($error) {
            return $error;
        }

        $validated = $this->validatedPayload($request, $guru->id, $semester->id);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        $entry = JurnalPembelajaran::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Jurnal berhasil disimpan.',
            'data' => $this->formatEntry($entry->load('mapel:id,nama_mapel')),
        ], 201);
    }

    public function update(Request $request, JurnalPembelajaran $jurnal): JsonResponse
    {
        [$guru, $semester, $error] = $this->resolveContext($request);
        if ($error) {
            return $error;
        }

        if ((int) $jurnal->guru_id !== (int) $guru->id || (int) $jurnal->semester_id !== (int) $semester->id) {
            return response()->json(['success' => false, 'message' => 'Jurnal tidak ditemukan.'], 404);
        }

        $validated = $this->validatedPayload($request, $guru->id, $semester->id, $jurnal->id);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        $jurnal->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Jurnal berhasil diperbarui.',
            'data' => $this->formatEntry($jurnal->fresh()->load('mapel:id,nama_mapel')),
        ]);
    }

    public function destroy(Request $request, JurnalPembelajaran $jurnal): JsonResponse
    {
        [$guru, $semester, $error] = $this->resolveContext($request);
        if ($error) {
            return $error;
        }

        if ((int) $jurnal->guru_id !== (int) $guru->id || (int) $jurnal->semester_id !== (int) $semester->id) {
            return response()->json(['success' => false, 'message' => 'Jurnal tidak ditemukan.'], 404);
        }

        $jurnal->delete();

        return response()->json([
            'success' => true,
            'message' => 'Jurnal berhasil dihapus.',
        ]);
    }

    public function cetak(Request $request, Kelas $kelas): Response
    {
        $guru = Guru::where('username', $request->user()->username)->first();
        if (! $guru) {
            return response('Profil guru tidak ditemukan.', 404);
        }

        $semester = $this->semesterService->getActiveSemester();
        if (! $semester) {
            return response('Tidak ada semester aktif.', 404);
        }

        if (! $this->guruOwnsKelas($guru->id, $semester->id, $kelas->id)) {
            return response('Kelas tidak diampu pada semester aktif.', 403);
        }

        $entries = JurnalPembelajaran::where('guru_id', $guru->id)
            ->where('semester_id', $semester->id)
            ->where('kelas_id', $kelas->id)
            ->with(['mapel:id,nama_mapel'])
            ->orderBy('tanggal')
            ->orderBy('jam_ke')
            ->get();

        if ($entries->isEmpty()) {
            return response('Belum ada entri jurnal untuk kelas ini.', 422);
        }

        $kepalaMadrasah = Guru::whereHas('tugasTambahans', function ($q) use ($semester) {
            $q->where('tugas_tambahan_id', TugasTambahan::KEPALA_MADRASAH_ID)
                ->where('semester_id', $semester->id);
        })->first();

        return response()->view(
            'guru.cetak.jurnal-pembelajaran',
            array_merge(
                [
                    'activeSemester' => $semester,
                    'kelas' => $kelas,
                    'guru' => $guru,
                    'entries' => $entries,
                    'kepalaMadrasah' => $kepalaMadrasah,
                    'tempatCetak' => 'Majalengka',
                    'tanggalCetak' => now('Asia/Jakarta'),
                ],
                $this->cetakPresetService->viewData(),
            )
        );
    }

    /**
     * @return array{0:?Guru,1:?\App\Models\Semester,2:?JsonResponse}
     */
    private function resolveContext(Request $request): array
    {
        $guru = Guru::where('username', $request->user()->username)->first();
        if (! $guru) {
            return [null, null, response()->json(['success' => false, 'message' => 'Profil guru tidak ditemukan.'], 422)];
        }

        $semester = $this->semesterService->getActiveSemester();
        if (! $semester) {
            return [null, null, response()->json(['success' => false, 'message' => 'Tidak ada semester aktif.'], 422)];
        }

        return [$guru, $semester, null];
    }

    private function guruOwnsKelas(int $guruId, int $semesterId, int $kelasId): bool
    {
        return BebanMengajar::where('guru_id', $guruId)
            ->where('semester_id', $semesterId)
            ->where('kelas_id', $kelasId)
            ->exists();
    }

    private function guruOwnsMapelKelas(int $guruId, int $semesterId, int $kelasId, int $mapelId): bool
    {
        return BebanMengajar::where('guru_id', $guruId)
            ->where('semester_id', $semesterId)
            ->where('kelas_id', $kelasId)
            ->where('mapel_id', $mapelId)
            ->exists();
    }

    private function validatedPayload(Request $request, int $guruId, int $semesterId, ?int $ignoreId = null): array|JsonResponse
    {
        $validated = $request->validate([
            'kelas_id' => ['required', 'integer', 'exists:kelas,id'],
            'mapel_id' => ['required', 'integer', 'exists:mapels,id'],
            'jadwal_id' => ['nullable', 'integer', 'exists:jadwals,id'],
            'tanggal' => ['required', 'date'],
            'jam_ke' => ['nullable', 'integer', 'min:0', 'max:12'],
            'materi_pokok' => ['required', 'string', 'max:5000'],
            'ketercapaian' => ['required', Rule::in(['tercapai', 'belum'])],
            'penugasan_siswa' => ['nullable', 'string', 'max:5000'],
            'catatan_guru' => ['nullable', 'string', 'max:5000'],
        ]);

        $kelasId = (int) $validated['kelas_id'];
        $mapelId = (int) $validated['mapel_id'];
        $jamKe = (int) ($validated['jam_ke'] ?? 0);

        if (! $this->guruOwnsMapelKelas($guruId, $semesterId, $kelasId, $mapelId)) {
            return response()->json([
                'success' => false,
                'message' => 'Mapel/kelas tidak diampu pada semester aktif.',
            ], 422);
        }

        $tanggal = Carbon::parse($validated['tanggal'], 'Asia/Jakarta')->startOfDay();
        $today = now('Asia/Jakarta')->startOfDay();
        if ($tanggal->gt($today)) {
            return response()->json([
                'success' => false,
                'message' => 'Tanggal jurnal tidak boleh di masa depan.',
            ], 422);
        }

        $hari = $this->hariIndonesiaFromDate($tanggal);

        $duplicate = JurnalPembelajaran::where('guru_id', $guruId)
            ->where('semester_id', $semesterId)
            ->where('kelas_id', $kelasId)
            ->where('mapel_id', $mapelId)
            ->whereDate('tanggal', $tanggal->toDateString())
            ->where('jam_ke', $jamKe)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();

        if ($duplicate) {
            return response()->json([
                'success' => false,
                'message' => 'Jurnal untuk mapel, tanggal, dan jam ke tersebut sudah ada.',
            ], 422);
        }

        return [
            'guru_id' => $guruId,
            'semester_id' => $semesterId,
            'kelas_id' => $kelasId,
            'mapel_id' => $mapelId,
            'jadwal_id' => $validated['jadwal_id'] ?? null,
            'tanggal' => $tanggal->toDateString(),
            'hari' => $hari,
            'jam_ke' => $jamKe,
            'materi_pokok' => trim($validated['materi_pokok']),
            'ketercapaian' => $validated['ketercapaian'],
            'penugasan_siswa' => isset($validated['penugasan_siswa']) ? trim((string) $validated['penugasan_siswa']) : null,
            'catatan_guru' => isset($validated['catatan_guru']) ? trim((string) $validated['catatan_guru']) : null,
        ];
    }

    private function formatEntry(JurnalPembelajaran $item): array
    {
        return [
            'id' => $item->id,
            'kelas_id' => $item->kelas_id,
            'mapel_id' => $item->mapel_id,
            'mapel' => $item->mapel?->nama_mapel,
            'jadwal_id' => $item->jadwal_id,
            'tanggal' => optional($item->tanggal)->format('Y-m-d'),
            'hari' => $item->hari,
            'jam_ke' => $item->jam_ke,
            'materi_pokok' => $item->materi_pokok,
            'ketercapaian' => $item->ketercapaian,
            'penugasan_siswa' => $item->penugasan_siswa,
            'catatan_guru' => $item->catatan_guru,
        ];
    }

    private function hariIndonesiaFromDate(Carbon $date): string
    {
        $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

        return $days[(int) $date->dayOfWeek];
    }
}
