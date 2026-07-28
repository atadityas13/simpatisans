<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GuruConstraint extends Model
{
    use HasFactory;

    protected $fillable = ['guru_id', 'semester_id', 'version_id', 'hari', 'jam_ke', 'type'];

    public function guru()
    {
        return $this->belongsTo(Guru::class);
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }

    public function version()
    {
        return $this->belongsTo(JadwalVersion::class, 'version_id');
    }

    public function scopeForVersion(Builder $query, int $semesterId, int $versionId): Builder
    {
        return $query->where('semester_id', $semesterId)->where('version_id', $versionId);
    }
}
