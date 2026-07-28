<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JadwalVersion extends Model
{
    public const NAME_DEFAULT = 'Operasional';

    protected $fillable = [
        'semester_id',
        'name',
        'is_default',
        'is_locked',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_locked' => 'boolean',
    ];

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function bebanMengajars(): HasMany
    {
        return $this->hasMany(BebanMengajar::class, 'version_id');
    }

    public function jadwals(): HasMany
    {
        return $this->hasMany(Jadwal::class, 'version_id');
    }

    public function guruTugasTambahans(): HasMany
    {
        return $this->hasMany(GuruTugasTambahan::class, 'version_id');
    }

    /**
     * Boleh diedit hanya jika versi terbuka DAN semester terbuka.
     */
    public function isEditable(): bool
    {
        if ((bool) ($this->is_locked ?? false)) {
            return false;
        }

        $semester = $this->relationLoaded('semester')
            ? $this->semester
            : $this->semester()->first();

        return $semester ? $semester->isEditable() : true;
    }
}
