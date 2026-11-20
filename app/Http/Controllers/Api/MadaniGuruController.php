<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Guru;
use Illuminate\Http\JsonResponse;

class MadaniGuruController extends Controller
{
    public function index(): JsonResponse
    {
        $gurus = Guru::query()
            ->with([
                'mapelIjazah:id,nama_mapel',
                'rumpunIjazah:id,nama_rumpun',
                'mapelSertifikasi:id,nama_mapel',
            ])
            ->orderedByDuk()
            ->get();

        $data = $gurus->map(function (Guru $guru): array {
            return [
                'nip' => $guru->username,
                'kode_guru' => $guru->kode_guru,
                'duk' => $guru->duk,
                'nama' => $guru->nama_guru,
                'gelar_depan' => $this->gelar($guru->gelar_depan),
                'gelar_belakang' => $this->gelar($guru->gelar_belakang),
                'jenis_kelamin' => $guru->jenis_kelamin,
                'tempat_lahir' => $guru->tempat_lahir,
                'tanggal_lahir' => $guru->tanggal_lahir?->toDateString(),
                'agama' => $guru->agama,
                'nomor_hp' => $guru->nomor_hp,
                'email' => $guru->email,
                'alamat' => $guru->alamat,
                'nuptk' => $guru->nuptk,
                'jabatan' => $guru->jabatan,
                'golongan' => $guru->golongan,
                'status_pegawai' => $guru->status_pegawai,
                'status_sertifikasi' => (bool) $guru->status_sertifikasi,
                'is_bk' => (bool) $guru->is_bk,
                'mapel_ijazah' => $guru->mapelIjazah?->nama_mapel ?: $guru->rumpunIjazah?->nama_rumpun,
                'mapel_sertifikasi' => $guru->mapelSertifikasi?->nama_mapel,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'meta' => [
                'total' => $data->count(),
            ],
            'data' => $data,
        ]);
    }

    private function gelar(?string $nilai): ?string
    {
        $nilai = trim((string) $nilai);

        return ($nilai === '' || $nilai === '-') ? null : $nilai;
    }
}
