<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mapel extends Model
{
    protected $fillable = ['nama_mapel', 'jtm_default', 'is_linear'];

    public function rumpuns() { return $this->belongsToMany(Rumpun::class, 'mapel_rumpun'); }
    public function guruDiampu() { return $this->belongsToMany(Guru::class, 'guru_mapels', 'mapel_id', 'guru_id'); }
    public function bebanMengajars() { return $this->hasMany(BebanMengajar::class, 'mapel_id'); }
}
