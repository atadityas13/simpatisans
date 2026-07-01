<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GuruTugasTambahan extends Model
{
    protected $fillable = [
        'guru_id',
        'tugas_tambahan_id',
        'semester_id'
    ];

    public function guru()
    {
        return $this->belongsTo(Guru::class);
    }

    public function tugasTambahan()
    {
        return $this->belongsTo(TugasTambahan::class);
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }
}
