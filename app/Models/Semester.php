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
        'is_locked',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_locked' => 'boolean',
    ];

    /**
     * Apakah pembagian tugas & jadwal boleh diedit (level semester).
     */
    public function isEditable(): bool
    {
        return ! (bool) ($this->is_locked ?? false);
    }

    /**
     * Editable untuk versi tertentu: semester terbuka + versi terbuka.
     */
    public function isVersionEditable(?JadwalVersion $version = null): bool
    {
        if (! $this->isEditable()) {
            return false;
        }

        if ($version && (bool) ($version->is_locked ?? false)) {
            return false;
        }

        return true;
    }

    /**
     * Get the display name for the semester.
     */
    public function getFullLabelAttribute(): string
    {
        return "{$this->nama_tahun} - {$this->tipe}";
    }

    public function versions(): HasMany
    {
        return $this->hasMany(JadwalVersion::class);
    }

    public function defaultVersion(): HasMany
    {
        return $this->hasMany(JadwalVersion::class)->where('is_default', true);
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
