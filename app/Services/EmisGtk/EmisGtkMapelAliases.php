<?php

namespace App\Services\EmisGtk;

/**
 * Nama mapel lokal SimpatiSans → nama di referensi EMIS-GTK.
 */
class EmisGtkMapelAliases
{
  /** @return array<string, string> */
    public static function localToEmis(): array
    {
        return [
            "Al-Qur'an Hadis" => "Alqur'an Hadis",
            'Akidah Akhlak' => 'Akidah Akhlaq',
            'Fikih' => 'Fiqih',
            'Sejarah Kebudayaan Islam' => 'Sejarah Kebudayaan Islam',
            'Bahasa Arab' => 'Bahasa Arab',
            'Pendidikan Pancasila' => 'Pendidikan Pancasila',
            'Bahasa Indonesia' => 'Bahasa Indonesia',
            'Matematika' => 'Matematika',
            'Ilmu Pengetahuan Alam' => 'Ilmu Pengetahuan Alam',
            'Ilmu Pengetahuan Sosial' => 'Ilmu Pengetahuan Sosial',
            'Bahasa Inggris' => 'Bahasa Inggris',
            'Pendidikan Jasmani, Olahraga dan Kesehatan' => 'Pendidikan Jasmani Olahraga Dan Kesehatan',
            'Informatika' => 'Informatika',
            'Seni dan Budaya' => 'Seni Budaya',
            'Bahasa Sunda' => 'Muatan Lokal Bahasa Sunda',
            "Tahfidz Al-Qur'an" => "Tahfidz Alqur'an",
            "Baca Tulis Al-Qur'an" => "Muatan Lokal Baca Tulis Al-Qur'an",
        ];
    }

    public static function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = str_replace(['’', '`'], "'", $value);
        $value = preg_replace('/[^a-z0-9\']+/', ' ', $value) ?? $value;

        return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
    }
}
