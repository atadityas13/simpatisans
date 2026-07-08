<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Guru extends Model
{
    protected $fillable = [
        'username', 'kode_guru', 'duk', 'status_pegawai', 'nama_guru', 'gelar_depan', 'gelar_belakang',
        'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir', 'agama', 'nomor_hp', 'email', 'alamat',
        'nuptk', 'jabatan', 'golongan', 'status_sertifikasi', 'is_bk', 'mapel_ijazah_id', 'rumpun_ijazah_id', 'mapel_sertifikasi_id',
    ];

    protected $casts = [
        'status_sertifikasi' => 'boolean',
        'is_bk' => 'boolean',
        'mapel_ijazah_id' => 'integer',
        'rumpun_ijazah_id' => 'integer',
        'mapel_sertifikasi_id' => 'integer',
        'tanggal_lahir' => 'date',
    ];

    public function getNamaLengkapAttribute() {
        $prefix = ($this->gelar_depan && $this->gelar_depan !== '-') ? $this->gelar_depan . ' ' : '';
        $suffix = ($this->gelar_belakang && $this->gelar_belakang !== '-') ? ', ' . $this->gelar_belakang : '';
        return $prefix . $this->nama_guru . $suffix;
    }

    public function getKualifikasiIjazahAttribute() {
        return $this->mapelIjazah?->nama_mapel ?: ($this->rumpunIjazah?->nama_rumpun ?: '—');
    }

    public function mapelIjazah() { return $this->belongsTo(Mapel::class, 'mapel_ijazah_id'); }
    public function rumpunIjazah() { return $this->belongsTo(Rumpun::class, 'rumpun_ijazah_id'); }
    public function mapelSertifikasi() { return $this->belongsTo(Mapel::class, 'mapel_sertifikasi_id'); }

    /**
     * Cek apakah mata pelajaran yang diampu Linear.
     */
    public function isLinear(Mapel $mapel): bool
    {
        // Catatan: Jika belum sertifikasi, dianggap tidak layak (Sipasti Rule)
        if (!$this->status_sertifikasi) {
            return false;
        }

        $mapelRumpunIds = $mapel->rumpuns->pluck('id')->toArray();

        // 1. Cocok dengan Mapel Sertifikasi (Spesifik atau Rumpun yang sama)
        if ($this->mapel_sertifikasi_id === $mapel->id) {
            return true;
        }
        if ($this->mapelSertifikasi?->rumpuns->whereIn('id', $mapelRumpunIds)->count() > 0) {
            return true;
        }

        // 2. Cocok dengan Mapel Ijazah (Spesifik atau Rumpun yang sama)
        if ($this->mapel_ijazah_id === $mapel->id) {
            return true;
        }
        if ($this->mapelIjazah?->rumpuns->whereIn('id', $mapelRumpunIds)->count() > 0) {
            return true;
        }

        // 3. Cocok dengan Rumpun Ijazah (Direct ID)
        if ($this->rumpun_ijazah_id && in_array((int)$this->rumpun_ijazah_id, $mapelRumpunIds)) {
            return true;
        }

        return false;
    }
    /**
     * Dapatkan jenis linearitas (Ijazah / Sertifikasi)
     * @return array
     */
    public function getLinearityTypes(Mapel $mapel): array
    {
        if (!$this->status_sertifikasi) {
            return [];
        }

        $types = [];

        $mapelRumpunIds = $mapel->rumpuns->pluck('id')->toArray();

        // 1. Cek Sertifikasi
        $isSertifikasi = false;
        if ((int)$this->mapel_sertifikasi_id === (int)$mapel->id) {
            $isSertifikasi = true;
        } elseif ($this->mapelSertifikasi?->rumpuns->whereIn('id', $mapelRumpunIds)->count() > 0) {
            $isSertifikasi = true;
        }

        if ($isSertifikasi) {
            $types[] = 'Sertifikasi';
        }

        // 2. Cek Ijazah
        $isIjazah = false;
        if ((int)$this->mapel_ijazah_id === (int)$mapel->id) {
            $isIjazah = true;
        } elseif ($this->mapelIjazah?->rumpuns->whereIn('id', $mapelRumpunIds)->count() > 0) {
            $isIjazah = true;
        } elseif ($this->rumpun_ijazah_id && in_array((int)$this->rumpun_ijazah_id, $mapelRumpunIds)) {
            $isIjazah = true;
        }

        if ($isIjazah) {
            $types[] = 'Ijazah';
        }

        return $types;
    }

    public function user() { return $this->hasOne(User::class, 'username', 'username'); }
    public function mapelDiampu() { return $this->belongsToMany(Mapel::class, 'guru_mapels', 'guru_id', 'mapel_id')->orderBy('mapels.id'); }
    public function tugasTambahans() { return $this->belongsToMany(TugasTambahan::class, 'guru_tugas_tambahans', 'guru_id', 'tugas_tambahan_id')->withPivot('is_ekuivalen', 'detail', 'hari', 'semester_id'); }
    public function bebanMengajars() { return $this->hasMany(BebanMengajar::class, 'guru_id'); }

    /**
     * Pencarian guru by KG, nama, NIP, NUPTK, jabatan, mapel.
     */
    public function scopeSearch($query, ?string $term)
    {
        $term = trim($term ?? '');
        if ($term === '') {
            return $query;
        }

        $like = '%' . $term . '%';

        return $query->where(function ($q) use ($like) {
            $q->where('kode_guru', 'like', $like)
                ->orWhere('nama_guru', 'like', $like)
                ->orWhere('username', 'like', $like)
                ->orWhere('nuptk', 'like', $like)
                ->orWhere('jabatan', 'like', $like)
                ->orWhere('golongan', 'like', $like)
                ->orWhere('gelar_depan', 'like', $like)
                ->orWhere('gelar_belakang', 'like', $like)
                ->orWhereHas('mapelSertifikasi', fn ($mq) => $mq->where('nama_mapel', 'like', $like))
                ->orWhereHas('mapelIjazah', fn ($mq) => $mq->where('nama_mapel', 'like', $like))
                ->orWhereHas('mapelDiampu', fn ($mq) => $mq->where('nama_mapel', 'like', $like));
        });
    }

    /**
     * Teks pencarian untuk filter client-side (KG, nama, NIP, mapel, dll.).
     */
    public function searchBlob(): string
    {
        $parts = [
            $this->kode_guru,
            $this->nama_guru,
            $this->gelar_depan,
            $this->gelar_belakang,
            $this->username,
            $this->nuptk,
            $this->jabatan,
            $this->golongan,
            $this->mapelSertifikasi?->nama_mapel,
            $this->mapelIjazah?->nama_mapel,
            $this->rumpunIjazah?->nama_rumpun,
            $this->status_sertifikasi ? 'sertifikasi tersertifikasi' : 'belum sertifikasi',
            $this->status_pegawai,
        ];

        if ($this->relationLoaded('mapelDiampu')) {
            $parts[] = $this->mapelDiampu->pluck('nama_mapel')->join(' ');
        }

        return strtolower(implode(' ', array_filter($parts)));
    }

    /**
     * Urutan resmi Daftar Urut Kepegawaian (DUK).
     * Guru tanpa nomor DUK ditempatkan di akhir, diurutkan nama.
     */
    public function scopeOrderedByDuk($query)
    {
        return $query->orderByRaw('duk IS NULL ASC, duk ASC')
            ->orderBy('nama_guru');
    }
}
