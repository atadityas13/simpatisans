<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TugasTambahan extends Model
{
    protected $fillable = ['nama_tugas', 'jtm_ekuivalen', 'tipe'];

    // Konstanta ID sistem
    const KEPALA_MADRASAH_ID = 1;
    const WAKA_ID = 2;
    const WALI_KELAS_ID = 3;
    const GURU_PIKET_ID = 4;
    const WAKA_BIDANG = ['Kurikulum', 'Kesiswaan', 'Sarana Prasarana', 'Humas'];

    public function isSystem(): bool { return $this->tipe === 'system'; }

    public function gurus() { return $this->belongsToMany(Guru::class, 'guru_tugas_tambahans', 'tugas_tambahan_id', 'guru_id')->withPivot('is_ekuivalen', 'detail', 'hari'); }
}
