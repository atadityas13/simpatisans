<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

class Pengumuman extends Model
{
    protected $table = 'pengumuman';

    protected static ?string $resolvedTable = null;

    protected $fillable = [
        'judul',
        'isi',
        'is_active',
        'published_at',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function getTable()
    {
        if (static::$resolvedTable !== null) {
            return static::$resolvedTable;
        }

        try {
            if (Schema::hasTable('pengumuman')) {
                return static::$resolvedTable = 'pengumuman';
            }

            if (Schema::hasTable('pengumumen')) {
                return static::$resolvedTable = 'pengumumen';
            }
        } catch (\Throwable) {
            // DB belum siap (mis. saat migrate); pakai default.
        }

        return static::$resolvedTable = 'pengumuman';
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePublished($query)
    {
        return $query
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }
}
