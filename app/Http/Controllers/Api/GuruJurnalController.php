<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BebanMengajar;
use App\Models\Guru;
use App\Models\Jadwal;
use App\Models\JurnalPembelajaran;
use App\Models\Kelas;
use App\Models\TugasTambahan;
use App\Services\CetakPresetService;
use App\Services\JamPelajaranService;
use App\Services\SemesterService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class GuruJurnalController extends Controller
{
    public function __construct(
        private SemesterService $semesterService,
        private CetakPresetService $cetakPresetService,
        private JamPelajaranService $jamPelajaranService,
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

            $kelasEntries = JurnalPembelajaran::where('guru_id', $guru->id)
                ->where('semester_id', $semester->id)
                ->where('kelas_id', $kelasId)
                ->get();

            $entryCount = $this->groupJournalEntries($kelasEntries)->count();

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
            ->orderByDesc('id')
            ->get();

        $entries = $this->groupJournalEntries($entries)
            ->map(fn (Collection $group) => $this->formatGroupedEntry($group))
            ->sortByDesc('id')
            ->values()
            ->all();

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

        $jamList = $validated['jam_list'];
        $jadwalIds = $validated['jadwal_ids'] ?? [];
        unset($validated['jam_list'], $validated['jadwal_ids']);

        $created = collect();
        foreach ($jamList as $index => $jamKe) {
            $payload = $validated;
            $payload['jam_ke'] = $jamKe;
            $payload['jadwal_id'] = $jadwalIds[$index] ?? $validated['jadwal_id'] ?? null;
            $created->push(JurnalPembelajaran::create($payload)->load('mapel:id,nama_mapel'));
        }

        return response()->json([
            'success' => true,
            'message' => $created->count() > 1
                ? 'Jurnal berhasil disimpan untuk '.$created->count().' jam pelajaran.'
                : 'Jurnal berhasil disimpan.',
            'data' => $this->formatGroupedEntry($created),
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

        $jamList = $validated['jam_list'];
        $jadwalIds = $validated['jadwal_ids'] ?? [];
        unset($validated['jam_list'], $validated['jadwal_ids']);

        $originalGroupIds = $this->findGroupForEntry($jurnal)->pluck('id')->all();

        $primary = null;
        foreach ($jamList as $index => $jamKe) {
            $payload = $validated;
            $payload['jam_ke'] = $jamKe;
            $payload['jadwal_id'] = $jadwalIds[$index] ?? $validated['jadwal_id'] ?? null;

            if ($index === 0) {
                $jurnal->update($payload);
                $primary = $jurnal->fresh()->load('mapel:id,nama_mapel');
                continue;
            }

            $existing = JurnalPembelajaran::where('guru_id', $guru->id)
                ->where('semester_id', $semester->id)
                ->where('kelas_id', $validated['kelas_id'])
                ->where('mapel_id', $validated['mapel_id'])
                ->whereDate('tanggal', $validated['tanggal'])
                ->where('jam_ke', $jamKe)
                ->first();

            if ($existing) {
                $existing->update($payload);
            } else {
                JurnalPembelajaran::create($payload);
            }
        }

        if ($originalGroupIds !== []) {
            JurnalPembelajaran::whereIn('id', $originalGroupIds)
                ->whereNotIn('jam_ke', $jamList)
                ->delete();
        }

        $primary = ($primary ?? $jurnal->fresh())->load('mapel:id,nama_mapel');
        $group = $this->findGroupForEntry($primary);

        return response()->json([
            'success' => true,
            'message' => 'Jurnal berhasil diperbarui.',
            'data' => $this->formatGroupedEntry($group),
        ]);
    }

    public function jamOptions(Request $request, Kelas $kelas): JsonResponse
    {
        [$guru, $semester, $error] = $this->resolveContext($request);
        if ($error) {
            return $error;
        }

        if (! $this->guruOwnsKelas($guru->id, $semester->id, $kelas->id)) {
            return response()->json(['success' => false, 'message' => 'Kelas tidak diampu pada semester aktif.'], 403);
        }

        $validated = $request->validate([
            'tanggal' => ['required', 'date'],
            'mapel_id' => ['required', 'integer', 'exists:mapels,id'],
        ]);

        $mapelId = (int) $validated['mapel_id'];
        if (! $this->guruOwnsMapelKelas($guru->id, $semester->id, $kelas->id, $mapelId)) {
            return response()->json([
                'success' => false,
                'message' => 'Mapel/kelas tidak diampu pada semester aktif.',
            ], 422);
        }

        $tanggal = Carbon::parse($validated['tanggal'], 'Asia/Jakarta')->startOfDay();
        $hari = $this->hariIndonesiaFromDate($tanggal);

        $slots = Jadwal::where('semester_id', $semester->id)
            ->where('hari', $hari)
            ->whereHas('bebanMengajar', function ($q) use ($guru, $kelas, $mapelId) {
                $q->where('guru_id', $guru->id)
                    ->where('kelas_id', $kelas->id)
                    ->where('mapel_id', $mapelId);
            })
            ->with(['bebanMengajar:id,guru_id,kelas_id,mapel_id'])
            ->orderBy('jam_ke')
            ->get();

        $groups = $this->groupConsecutiveJamSlots($slots, $hari);

        return response()->json([
            'success' => true,
            'hari' => $hari,
            'kelas' => [
                'id' => $kelas->id,
                'nama_kelas' => $kelas->nama_kelas,
            ],
            'mapel_id' => $mapelId,
            'tanggal' => $tanggal->toDateString(),
            'data' => $groups,
            'message' => $groups === []
                ? 'Tidak ada jadwal mengajar untuk mapel/hari ini di kelas tersebut.'
                : null,
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

        // Hapus seluruh sesi pertemuan (semua jam di tanggal+mapel+materi yang sama),
        // termasuk sisa baris orphan dari hapus sebagian sebelumnya.
        $deleted = JurnalPembelajaran::where('guru_id', $jurnal->guru_id)
            ->where('semester_id', $jurnal->semester_id)
            ->where('kelas_id', $jurnal->kelas_id)
            ->where('mapel_id', $jurnal->mapel_id)
            ->whereDate('tanggal', optional($jurnal->tanggal)->format('Y-m-d'))
            ->where('materi_pokok', $jurnal->materi_pokok)
            ->delete();

        if ($deleted === 0) {
            $jurnal->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Jurnal berhasil dihapus.',
        ]);
    }

    public function cetakSemua(Request $request): Response
    {
        $guru = Guru::where('username', $request->user()->username)->first();
        if (! $guru) {
            return response('Profil guru tidak ditemukan.', 404);
        }

        $semester = $this->semesterService->getActiveSemester();
        if (! $semester) {
            return response('Tidak ada semester aktif.', 404);
        }

        $entries = JurnalPembelajaran::where('guru_id', $guru->id)
            ->where('semester_id', $semester->id)
            ->with(['mapel:id,nama_mapel', 'kelas:id,nama_kelas,tingkat'])
            ->orderBy('kelas_id')
            ->orderBy('tanggal')
            ->orderBy('jam_ke')
            ->get();

        if ($entries->isEmpty()) {
            return response('Belum ada entri jurnal untuk dicetak. Isi jurnal minimal satu kelas terlebih dahulu.', 422);
        }

        $sections = $entries
            ->groupBy('kelas_id')
            ->map(function (Collection $kelasEntries) {
                $kelas = $kelasEntries->first()?->kelas;

                return [
                    'kelas' => $kelas,
                    'rows' => $this->buildCetakRows($kelasEntries),
                ];
            })
            ->sortBy(fn ($section) => $section['kelas']?->nama_kelas ?? '')
            ->values();

        return $this->renderJurnalCetak($guru, $semester, $sections);
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
            ->with(['mapel:id,nama_mapel', 'kelas:id,nama_kelas,tingkat'])
            ->orderBy('tanggal')
            ->orderBy('jam_ke')
            ->get();

        if ($entries->isEmpty()) {
            return response('Belum ada entri jurnal untuk kelas ini.', 422);
        }

        $sections = collect([[
            'kelas' => $kelas,
            'rows' => $this->buildCetakRows($entries),
        ]]);

        return $this->renderJurnalCetak($guru, $semester, $sections);
    }

    /**
     * @param  Collection<int, array{kelas:?Kelas, rows:list<array<string, mixed>>}>  $sections
     */
    private function renderJurnalCetak(Guru $guru, $semester, Collection $sections): Response
    {
        $kepalaMadrasah = Guru::whereHas('tugasTambahans', function ($q) use ($semester) {
            $q->where('tugas_tambahan_id', TugasTambahan::KEPALA_MADRASAH_ID)
                ->where('semester_id', $semester->id);
        })->first();

        return response()
            ->view(
                'guru.cetak.jurnal-pembelajaran',
                array_merge(
                    [
                        'activeSemester' => $semester,
                        'guru' => $guru,
                        'sections' => $sections,
                        'kepalaMadrasah' => $kepalaMadrasah,
                        'tempatCetak' => 'Majalengka',
                        'tanggalCetak' => now('Asia/Jakarta'),
                    ],
                    $this->cetakPresetService->viewData(),
                )
            )
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }

    private function buildCetakRows(Collection $entries): array
    {
        // PDF: urut dari yang terdahulu ke terbaru (tanggal lalu jam paling awal).
        return $this->groupJournalEntries($entries)
            ->map(function (Collection $group) {
                $primary = $group->sortBy([
                    ['jam_ke', 'asc'],
                    ['id', 'asc'],
                ])->first();
                $jamList = $group->pluck('jam_ke')->map(fn ($j) => (int) $j)->sort()->values()->all();
                $hari = (string) ($primary->hari ?? '');

                return [
                    'hari' => $hari,
                    'tanggal' => $primary->tanggal,
                    'waktu' => $this->jamPelajaranService->waktuRangeFor($hari, $jamList),
                    'mapel' => $primary->mapel?->nama_mapel,
                    'materi_pokok' => (string) $primary->materi_pokok,
                    'ketercapaian' => (string) $primary->ketercapaian,
                    'penugasan_siswa' => $group->pluck('penugasan_siswa')->first(fn ($v) => filled($v)),
                    'catatan_guru' => $group->pluck('catatan_guru')->first(fn ($v) => filled($v)),
                    '_sort_tanggal' => optional($primary->tanggal)->format('Y-m-d') ?? '',
                    '_sort_jam' => $jamList[0] ?? 0,
                ];
            })
            ->sortBy([
                ['_sort_tanggal', 'asc'],
                ['_sort_jam', 'asc'],
            ])
            ->map(function (array $row) {
                unset($row['_sort_tanggal'], $row['_sort_jam']);

                return $row;
            })
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, Collection<int, JurnalPembelajaran>>
     */
    private function groupJournalEntries(Collection $entries): Collection
    {
        // Jangan pakai sortBy([fn, fn, ...]) — Laravel menganggap itu sortByMultiple
        // dan menghasilkan urutan acak, sehingga jam 3-4-5 gagal digabung.
        $sorted = $entries
            ->sortBy(fn ($e) => sprintf(
                '%s-%010d-%03d-%010d',
                optional($e->tanggal)->format('Y-m-d') ?? '',
                (int) $e->mapel_id,
                (int) $e->jam_ke,
                (int) $e->id
            ))
            ->values();

        $groups = collect();
        $current = collect();

        foreach ($sorted as $entry) {
            if ($current->isEmpty()) {
                $current->push($entry);
                continue;
            }

            $last = $current->last();
            $tanggalKey = optional($entry->tanggal)->format('Y-m-d');
            $lastTanggalKey = optional($last->tanggal)->format('Y-m-d');
            $jamKe = (int) $entry->jam_ke;
            $lastJamKe = (int) $last->jam_ke;
            $canMerge = $lastTanggalKey === $tanggalKey
                && (int) $last->mapel_id === (int) $entry->mapel_id
                && (string) $last->materi_pokok === (string) $entry->materi_pokok
                && (string) $last->ketercapaian === (string) $entry->ketercapaian
                && $jamKe === ($lastJamKe + 1);

            if ($canMerge) {
                $current->push($entry);
                continue;
            }

            $groups->push($current);
            $current = collect([$entry]);
        }

        if ($current->isNotEmpty()) {
            $groups->push($current);
        }

        return $groups;
    }

    /**
     * @return Collection<int, JurnalPembelajaran>
     */
    private function findGroupForEntry(JurnalPembelajaran $jurnal): Collection
    {
        $entries = JurnalPembelajaran::where('guru_id', $jurnal->guru_id)
            ->where('semester_id', $jurnal->semester_id)
            ->where('kelas_id', $jurnal->kelas_id)
            ->where('mapel_id', $jurnal->mapel_id)
            ->whereDate('tanggal', optional($jurnal->tanggal)->format('Y-m-d'))
            ->with(['mapel:id,nama_mapel'])
            ->orderBy('jam_ke')
            ->orderBy('id')
            ->get();

        foreach ($this->groupJournalEntries($entries) as $group) {
            if ($group->contains('id', $jurnal->id)) {
                return $group;
            }
        }

        return collect([$jurnal]);
    }

    /**
     * @param  Collection<int, JurnalPembelajaran>  $group
     */
    private function formatGroupedEntry(Collection $group): array
    {
        $primary = $group->sortBy('id')->first();
        $jamList = $group->pluck('jam_ke')->map(fn ($j) => (int) $j)->sort()->values()->all();
        $jadwalIds = $group->sortBy('jam_ke')->pluck('jadwal_id')->filter()->map(fn ($id) => (int) $id)->values()->all();

        return [
            'id' => $primary->id,
            'kelas_id' => $primary->kelas_id,
            'mapel_id' => $primary->mapel_id,
            'mapel' => $primary->mapel?->nama_mapel,
            'jadwal_id' => $jadwalIds[0] ?? $primary->jadwal_id,
            'jadwal_ids' => $jadwalIds,
            'tanggal' => optional($primary->tanggal)->format('Y-m-d'),
            'hari' => $primary->hari,
            'jam_ke' => $jamList[0] ?? (int) $primary->jam_ke,
            'jam_list' => $jamList,
            'materi_pokok' => $primary->materi_pokok,
            'ketercapaian' => $primary->ketercapaian,
            'penugasan_siswa' => $group->pluck('penugasan_siswa')->first(fn ($v) => filled($v)),
            'catatan_guru' => $group->pluck('catatan_guru')->first(fn ($v) => filled($v)),
        ];
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
            'jadwal_ids' => ['nullable', 'array'],
            'jadwal_ids.*' => ['integer', 'exists:jadwals,id'],
            'tanggal' => ['required', 'date'],
            'jam_ke' => ['nullable', 'integer', 'min:0', 'max:12'],
            'jam_list' => ['nullable', 'array', 'min:1'],
            'jam_list.*' => ['integer', 'min:1', 'max:12'],
            'materi_pokok' => ['required', 'string', 'max:5000'],
            'ketercapaian' => ['required', Rule::in(['tercapai', 'belum'])],
            'penugasan_siswa' => ['nullable', 'string', 'max:5000'],
            'catatan_guru' => ['nullable', 'string', 'max:5000'],
        ]);

        $kelasId = (int) $validated['kelas_id'];
        $mapelId = (int) $validated['mapel_id'];

        $jamList = collect($validated['jam_list'] ?? [])
            ->map(fn ($j) => (int) $j)
            ->filter(fn ($j) => $j > 0)
            ->unique()
            ->values()
            ->all();

        if ($jamList === [] && isset($validated['jam_ke']) && (int) $validated['jam_ke'] > 0) {
            $jamList = [(int) $validated['jam_ke']];
        }

        if ($jamList === []) {
            return response()->json([
                'success' => false,
                'message' => 'Pilih jam pelajaran dari jadwal.',
            ], 422);
        }

        sort($jamList);
        for ($i = 1; $i < count($jamList); $i++) {
            if ($jamList[$i] !== $jamList[$i - 1] + 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Jam pelajaran harus berurutan.',
                ], 422);
            }
        }

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

        if ($ignoreId === null) {
            $duplicate = JurnalPembelajaran::where('guru_id', $guruId)
                ->where('semester_id', $semesterId)
                ->where('kelas_id', $kelasId)
                ->where('mapel_id', $mapelId)
                ->whereDate('tanggal', $tanggal->toDateString())
                ->whereIn('jam_ke', $jamList)
                ->exists();

            if ($duplicate) {
                return response()->json([
                    'success' => false,
                    'message' => 'Jurnal untuk mapel, tanggal, dan jam tersebut sudah ada.',
                ], 422);
            }
        }

        $jadwalIds = collect($validated['jadwal_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        return [
            'guru_id' => $guruId,
            'semester_id' => $semesterId,
            'kelas_id' => $kelasId,
            'mapel_id' => $mapelId,
            'jadwal_id' => $validated['jadwal_id'] ?? ($jadwalIds[0] ?? null),
            'jadwal_ids' => $jadwalIds,
            'tanggal' => $tanggal->toDateString(),
            'hari' => $hari,
            'jam_ke' => $jamList[0],
            'jam_list' => $jamList,
            'materi_pokok' => trim($validated['materi_pokok']),
            'ketercapaian' => $validated['ketercapaian'],
            'penugasan_siswa' => isset($validated['penugasan_siswa']) ? trim((string) $validated['penugasan_siswa']) : null,
            'catatan_guru' => isset($validated['catatan_guru']) ? trim((string) $validated['catatan_guru']) : null,
        ];
    }

    /**
     * @param  Collection<int, Jadwal>  $slots
     * @return list<array{jam_list:list<int>, label:string, waktu:?string, jadwal_ids:list<int>}>
     */
    private function groupConsecutiveJamSlots(Collection $slots, string $hari): array
    {
        $groups = [];
        $currentJam = [];
        $currentIds = [];

        $flush = function () use (&$groups, &$currentJam, &$currentIds, $hari) {
            if ($currentJam === []) {
                return;
            }
            $label = count($currentJam) === 1
                ? 'Jam ke '.$currentJam[0]
                : 'Jam ke '.$currentJam[0].'–'.$currentJam[array_key_last($currentJam)];

            $groups[] = [
                'jam_list' => $currentJam,
                'label' => $label,
                'waktu' => $this->jamPelajaranService->waktuRangeFor($hari, $currentJam),
                'jadwal_ids' => $currentIds,
            ];
            $currentJam = [];
            $currentIds = [];
        };

        foreach ($slots as $slot) {
            $jamKe = (int) $slot->jam_ke;
            if ($jamKe <= 0) {
                continue;
            }
            if ($currentJam === [] || $jamKe === $currentJam[array_key_last($currentJam)] + 1) {
                $currentJam[] = $jamKe;
                $currentIds[] = (int) $slot->id;
                continue;
            }
            $flush();
            $currentJam[] = $jamKe;
            $currentIds[] = (int) $slot->id;
        }
        $flush();

        return $groups;
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
