<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GuruConstraint extends Model
{
    use HasFactory;

    protected $fillable = ['guru_id', 'hari', 'jam_ke', 'type'];

    public function guru()
    {
        return $this->belongsTo(Guru::class);
    }
}
