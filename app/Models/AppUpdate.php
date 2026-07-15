<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppUpdate extends Model
{
    protected $fillable = [
        'platform',
        'latest_version_code',
        'latest_version_name',
        'minimum_version_code',
        'title',
        'message',
        'changelog',
        'play_store_url',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'latest_version_code' => 'integer',
        'minimum_version_code' => 'integer',
        'is_active' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeActivePlatform($query, string $platform)
    {
        return $query
            ->where('platform', $platform)
            ->where('is_active', true);
    }
}
