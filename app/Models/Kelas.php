<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kelas extends Model
{
    protected $fillable = ['nama_kelas', 'tingkat', 'tingkat_emis', 'rombel_emis'];

    public function bebanMengajars() { return $this->hasMany(BebanMengajar::class, 'kelas_id'); }
}
