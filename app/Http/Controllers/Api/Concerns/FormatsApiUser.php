<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\Guru;
use App\Models\User;

trait FormatsApiUser
{
    protected function formatUser(User $user): array
    {
        $guru = Guru::where('username', $user->username)
            ->with([
                'mapelDiampu:id,nama_mapel',
                'mapelIjazah:id,nama_mapel',
                'mapelSertifikasi:id,nama_mapel',
                'rumpunIjazah:id,nama_rumpun',
            ])
            ->first();

        return [
            'id' => $user->id,
            'username' => $user->username,
            'nip' => $user->username,
            'nama_lengkap' => $guru?->nama_lengkap ?? $user->getRawOriginal('nama_lengkap'),
            'jabatan' => $guru?->jabatan ?? $user->getRawOriginal('jabatan'),
            'role' => $user->role,
            'foto' => $user->foto ? asset('storage/' . $user->foto) : null,
            'guru' => $guru ? $this->formatGuru($guru) : null,
        ];
    }

    protected function formatGuru(Guru $guru): array
    {
        return [
            'id' => $guru->id,
            'kode_guru' => $guru->kode_guru,
            'duk' => $guru->duk,
            'gelar_depan' => $guru->gelar_depan,
            'gelar_belakang' => $guru->gelar_belakang,
            'nuptk' => $guru->nuptk,
            'golongan' => $guru->golongan,
            'status_pegawai' => $guru->status_pegawai,
            'status_sertifikasi' => (bool) $guru->status_sertifikasi,
            'is_bk' => (bool) $guru->is_bk,
            'jenis_kelamin' => $guru->jenis_kelamin,
            'tempat_lahir' => $guru->tempat_lahir,
            'tanggal_lahir' => $guru->tanggal_lahir?->format('Y-m-d'),
            'agama' => $guru->agama,
            'nomor_hp' => $guru->nomor_hp,
            'email' => $guru->email,
            'alamat' => $guru->alamat,
            'mapel_ijazah' => $guru->kualifikasi_ijazah,
            'mapel_sertifikasi' => $guru->mapelSertifikasi?->nama_mapel,
            'mapel' => $guru->mapelDiampu->pluck('nama_mapel')->values(),
        ];
    }
}
