<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mapel extends Model
{
    protected $fillable = [
        'nama_mapel',
        'jtm_default',
        'is_linear',
        'id_mapel_emis_7',
        'id_mapel_emis_8',
        'id_mapel_emis_9',
    ];

    public function emisIdForTingkat(string|int $tingkatEmis): ?string
    {
        return match ((string) $tingkatEmis) {
            '7' => $this->id_mapel_emis_7,
            '8' => $this->id_mapel_emis_8,
            '9' => $this->id_mapel_emis_9,
            default => null,
        };
    }

    public function rumpuns() { return $this->belongsToMany(Rumpun::class, 'mapel_rumpun'); }
    public function guruDiampu() { return $this->belongsToMany(Guru::class, 'guru_mapels', 'mapel_id', 'guru_id'); }
    public function bebanMengajars() { return $this->hasMany(BebanMengajar::class, 'mapel_id'); }
}
