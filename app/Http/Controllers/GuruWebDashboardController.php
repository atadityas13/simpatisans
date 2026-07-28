<?php

namespace App\Http\Controllers;

use App\Models\AppUpdate;
use App\Models\Guru;
use App\Models\Jadwal;
use App\Services\GuruService;
use App\Services\JamPelajaranService;
use App\Services\JadwalVersionService;
use App\Services\SemesterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class GuruWebDashboardController extends Controller
{
    public function __construct(
        private SemesterService $semesterService,
        private JamPelajaranService $jamPelajaranService,
        private GuruService $guruService,
        private JadwalVersionService $versionService,
    ) {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $guru = Guru::where('username', $user->username)
            ->with(['mapelSertifikasi', 'mapelIjazah'])
            ->first();
        $semester = $this->semesterService->getActiveSemester();
        $hariIni = $this->hariIndonesia();
        $jadwalHariIni = collect();
        $tpgStatus = null;
        $versionId = $semester ? $this->versionService->resolveForSemester($semester->id)->id : null;

        if ($guru && $semester && $versionId) {
            $jadwalHariIni = Jadwal::where('semester_id', $semester->id)
                ->where('version_id', $versionId)
                ->whereRaw('LOWER(hari) = ?', [strtolower($hariIni)])
                ->whereHas('bebanMengajar', fn ($q) => $q->where('guru_id', $guru->id))
                ->with(['bebanMengajar.mapel:id,nama_mapel', 'bebanMengajar.kelas:id,nama_kelas'])
                ->orderBy('jam_ke')
                ->get()
                ->map(fn ($j) => [
                    'jam_ke' => $j->jam_ke,
                    'waktu' => $this->jamPelajaranService->waktuFor($hariIni, (int) $j->jam_ke),
                    'mapel' => $j->bebanMengajar?->mapel?->nama_mapel,
                    'kelas' => $j->bebanMengajar?->kelas?->nama_kelas,
                ]);

            $tpgStatus = $this->buildTpgStatus($guru, $semester->id, $versionId);
        }

        $playStoreUrl = 'https://play.google.com/store/apps/details?id=com.atadevlabs.talim';
        if (Schema::hasTable('app_updates')) {
            $playStoreUrl = AppUpdate::activePlatform('android')->first()?->play_store_url ?: $playStoreUrl;
        }

        return view('guru.dashboard', [
            'guru' => $guru,
            'semester' => $semester,
            'hariIni' => $hariIni,
            'jadwalHariIni' => $jadwalHariIni,
            'tpgStatus' => $tpgStatus,
            'playStoreUrl' => $playStoreUrl,
        ]);
    }

    private function buildTpgStatus(Guru $guru, int $semesterId, ?int $versionId = null): array
    {
        $guru->loadMissing([
            'bebanMengajars' => fn ($q) => $q->where('semester_id', $semesterId)
                ->when($versionId, fn ($q2) => $q2->where('version_id', $versionId)),
            'bebanMengajars.mapel.rumpuns',
            'tugasTambahans' => fn ($q) => $q->wherePivot('semester_id', $semesterId)
                ->when($versionId, fn ($q2) => $q2->wherePivot('version_id', $versionId)),
            'mapelSertifikasi.rumpuns',
            'mapelIjazah.rumpuns',
        ]);

        $metrik = $this->guruService->hitungMetrik($guru, $semesterId, $versionId);
        $target = (int) ($metrik['TARGET'] ?? 24);
        $totalLinear = (int) ($metrik['totalLinear'] ?? 0);
        $deficit = max(0, $target - $totalLinear);
        $mapelTerkait = $guru->mapelSertifikasi?->nama_mapel;

        if (! $guru->status_sertifikasi) {
            return [
                'status' => 'not_certified',
                'message' => 'Mohon maaf Anda dinyatakan BELUM LAYAK karena belum tersertifikasi profesi guru.',
                'target_jam' => $target,
                'total_linear_jam' => $totalLinear,
                'deficit_jam' => $deficit,
                'mapel_terkait' => $mapelTerkait,
            ];
        }

        if ($deficit > 0) {
            $mapelText = $mapelTerkait ? " mapel {$mapelTerkait}" : ' mapel terkait';

            return [
                'status' => 'deficit',
                'message' => "Mohon maaf Anda dinyatakan BELUM LAYAK karena defisit {$deficit} jam{$mapelText}.",
                'target_jam' => $target,
                'total_linear_jam' => $totalLinear,
                'deficit_jam' => $deficit,
                'mapel_terkait' => $mapelTerkait,
            ];
        }

        return [
            'status' => 'eligible',
            'message' => 'Selamat anda dinyatakan LAYAK dan memenuhi syarat sebagai penerima TPG semester ini.',
            'target_jam' => $target,
            'total_linear_jam' => $totalLinear,
            'deficit_jam' => 0,
            'mapel_terkait' => $mapelTerkait,
        ];
    }

    private function hariIndonesia(): string
    {
        $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

        return $days[(int) date('w')];
    }
}
