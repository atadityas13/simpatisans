<?php

namespace App\Services;

use App\Models\Semester;
use Illuminate\Support\Facades\Cache;

class SemesterService
{
    /**
     * Get the currently active semester.
     */
    public function getActiveSemester(): ?Semester
    {
        $cached = Cache::get('active_semester');

        // Jika ada di cache tapi bukan instance Semester (bisa jadi __PHP_Incomplete_Class)
        if ($cached && !($cached instanceof Semester)) {
            $this->clearCache();
            $cached = null;
        }

        if ($cached) {
            return $cached;
        }

        return Cache::remember('active_semester', 3600, function () {
            return Semester::where('is_active', true)->first();
        });
    }

    /**
     * Clear the active semester cache.
     */
    public function clearCache(): void
    {
        Cache::forget('active_semester');
    }

    /**
     * Set a new active semester.
     */
    public function setActiveSemester(int $id): bool
    {
        Semester::where('is_active', true)->update(['is_active' => false]);
        $updated = Semester::where('id', $id)->update(['is_active' => true]);
        
        $this->clearCache();
        
        return $updated > 0;
    }
}
