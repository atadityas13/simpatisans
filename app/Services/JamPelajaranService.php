<?php

namespace App\Services;

class JamPelajaranService
{
    /** @var array<string, array<int, string>> */
    private array $cache = [];

    public function waktuFor(string $hari, int $jamKe): ?string
    {
        $hari = $this->normalizeHari($hari);
        $map = $this->mapForDay($hari);

        return $map[$jamKe] ?? null;
    }

    private function normalizeHari(string $hari): string
    {
        return ucfirst(strtolower(trim($hari)));
    }

    /** @return array<int, string> */
    private function mapForDay(string $hari): array
    {
        if (isset($this->cache[$hari])) {
            return $this->cache[$hari];
        }

        $slots = match ($hari) {
            'Senin' => [
                1 => '07.45-08.20',
                2 => '08.20-08.55',
                3 => '08.55-09.30',
                4 => '09.30-10.05',
                5 => '10.35-11.10',
                6 => '11.10-11.45',
                7 => '12.45-13.20',
                8 => '13.20-13.55',
                9 => '13.55-14.30',
            ],
            'Jumat' => [
                1 => '08.00-08.30',
                2 => '08.30-09.00',
                3 => '09.00-09.30',
                4 => '09.50-10.20',
                5 => '10.20-10.50',
            ],
            default => [
                1 => '07.15-07.50',
                2 => '07.50-08.25',
                3 => '08.25-09.00',
                4 => '09.00-09.35',
                5 => '10.05-10.40',
                6 => '10.40-11.15',
                7 => '11.15-11.50',
                8 => '13.00-13.35',
                9 => '13.35-14.10',
                10 => '14.10-14.45',
            ],
        };

        return $this->cache[$hari] = $slots;
    }
}
