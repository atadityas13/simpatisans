<?php

namespace App\Services\EmisGtk;

/**
 * Pemetaan jam_ke SimpatiSans (hanya slot mengajar) ke nomor Jam ke di template EMIS-GTK.
 * Slot [TEMPLATE: …] di EMIS tidak diisi; urutan mengajar mengikuti slot kosong.
 */
class EmisGtkJamMapper
{
    /** @return list<int> */
    public static function teachingSlots(string $hari): array
    {
        $hari = ucfirst(strtolower(trim($hari)));

        return match ($hari) {
            'Senin' => [2, 3, 4, 5, 7, 8, 10, 11, 12],
            'Jumat' => [3, 4, 5, 7, 8],
            default => [2, 3, 4, 5, 7, 8, 9, 11, 12, 13],
        };
    }

    public static function emisJamFor(string $hari, int $jamKe): ?int
    {
        $slots = self::teachingSlots($hari);
        if ($jamKe < 1 || $jamKe > count($slots)) {
            return null;
        }

        return $slots[$jamKe - 1];
    }

    public static function maxJamKe(string $hari): int
    {
        return count(self::teachingSlots($hari));
    }

    public static function isTemplateMarker(?string $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        return str_contains($value, '[TEMPLATE:');
    }
}
