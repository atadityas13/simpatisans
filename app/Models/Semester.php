<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Semester extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama_tahun',
        'tipe',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the display name for the semester.
     */
    public function getFullLabelAttribute(): string
    {
        return "{$this->nama_tahun} - {$this->tipe}";
    }

    public function bebanMengajars(): HasMany
    {
        return $this->hasMany(BebanMengajar::class);
    }

    public function jadwals(): HasMany
    {
        return $this->hasMany(Jadwal::class);
    }

    public function guruTugasTambahans(): HasMany
    {
        return $this->hasMany(GuruTugasTambahan::class);
    }
}
