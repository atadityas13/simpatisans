<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Jadwal extends Model
{
    use HasFactory;

    protected $fillable = [
        'beban_mengajar_id',
        'semester_id',
        'hari',
        'jam_ke'
    ];

    public function bebanMengajar()
    {
        return $this->belongsTo(BebanMengajar::class);
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }
}
