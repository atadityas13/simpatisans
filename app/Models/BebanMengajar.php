<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BebanMengajar extends Model
{
    protected $fillable = ['guru_id', 'mapel_id', 'kelas_id', 'semester_id', 'jtm', 'is_linear', 'is_satminkal', 'jumlah_kelas', 'hari'];
    
    protected $casts = [
        'is_linear' => 'boolean',
        'is_satminkal' => 'boolean',
        'hari' => 'array',
    ];

    public function guru() { return $this->belongsTo(Guru::class, 'guru_id'); }
    public function mapel() { return $this->belongsTo(Mapel::class, 'mapel_id'); }
    public function kelas() { return $this->belongsTo(Kelas::class, 'kelas_id'); }
    public function semester() { return $this->belongsTo(Semester::class, 'semester_id'); }
}
