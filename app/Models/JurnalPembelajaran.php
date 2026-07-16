<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JurnalPembelajaran extends Model
{
    protected $table = 'jurnal_pembelajaran';

    protected $fillable = [
        'guru_id',
        'semester_id',
        'kelas_id',
        'mapel_id',
        'jadwal_id',
        'tanggal',
        'hari',
        'jam_ke',
        'materi_pokok',
        'ketercapaian',
        'penugasan_siswa',
        'catatan_guru',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'jam_ke' => 'integer',
    ];

    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    public function mapel(): BelongsTo
    {
        return $this->belongsTo(Mapel::class);
    }

    public function jadwal(): BelongsTo
    {
        return $this->belongsTo(Jadwal::class);
    }

    public function isTercapai(): bool
    {
        return $this->ketercapaian === 'tercapai';
    }
}
