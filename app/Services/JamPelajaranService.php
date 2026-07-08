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
                1 => '07.35-08.10',
                2 => '08.10-08.45',
                3 => '08.45-09.20',
                4 => '09.50-10.25',
                5 => '10.25-11.00',
                6 => '11.00-11.35',
                7 => '13.05-13.40',
                8 => '13.40-14.15',
                9 => '14.15-14.50',
            ],
            'Jumat' => [
                1 => '08.00-08.30',
                2 => '08.30-09.00',
                3 => '09.00-09.30',
                4 => '09.50-10.20',
                5 => '10.20-10.50',
            ],
            default => [
                1 => '07.00-07.35',
                2 => '07.35-08.10',
                3 => '08.10-08.45',
                4 => '08.45-09.20',
                5 => '09.50-10.25',
                6 => '10.25-11.00',
                7 => '11.00-11.35',
                8 => '13.05-13.40',
                9 => '13.40-14.15',
                10 => '14.15-14.50',
            ],
        };

        return $this->cache[$hari] = $slots;
    }
}
