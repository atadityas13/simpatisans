<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class CetakPresetService
{
    private const SETTINGS_FILE = 'presets/cetak_settings.json';

    private const BULAN = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    public function getSettings(): array
    {
        if (!Storage::disk('public')->exists(self::SETTINGS_FILE)) {
            return $this->defaultSettings();
        }

        $raw = Storage::disk('public')->get(self::SETTINGS_FILE);
        $data = json_decode($raw, true);

        if (!is_array($data)) {
            return $this->defaultSettings();
        }

        return array_merge($this->defaultSettings(), $data);
    }

    public function saveSettings(array $settings): void
    {
        $merged = array_merge($this->getSettings(), $settings);
        Storage::disk('public')->put(
            self::SETTINGS_FILE,
            json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    public function defaultSettings(): array
    {
        return [
            'tanggal_cetak' => now()->format('Y-m-d'),
            'pejabat_penandatangan' => 'kepala',
        ];
    }

    public function tanggalCarbon(): Carbon
    {
        $settings = $this->getSettings();

        try {
            return Carbon::parse($settings['tanggal_cetak'] ?? now());
        } catch (\Throwable) {
            return now();
        }
    }

    /** Contoh: 6 Juli 2026 */
    public function formatTanggal(): string
    {
        $d = $this->tanggalCarbon();

        return $d->day . ' ' . (self::BULAN[$d->month] ?? '') . ' ' . $d->year;
    }

    /** Contoh: Cingambul, 6 Juli 2026 */
    public function formatTanggalLokasi(string $lokasi = 'Cingambul'): string
    {
        return $lokasi . ', ' . $this->formatTanggal();
    }

    /** Label jabatan pejabat penandatangan kepala madrasah. */
    public function pejabatLabel(): string
    {
        return $this->getSettings()['pejabat_penandatangan'] === 'plt_kepala'
            ? 'Plt. Kepala Madrasah'
            : 'Kepala Madrasah';
    }

    /** Untuk format "Plt. Kepala," di lampiran SK. */
    public function pejabatLabelSingkat(): string
    {
        return $this->getSettings()['pejabat_penandatangan'] === 'plt_kepala'
            ? 'Plt. Kepala'
            : 'Kepala';
    }

    /**
     * Data view standar untuk semua halaman cetak bertanda tangan.
     *
     * @return array<string, mixed>
     */
    public function viewData(): array
    {
        $settings = $this->getSettings();

        return [
            'cetakSettings' => $settings,
            'cetakTanggal' => $this->formatTanggal(),
            'cetakTanggalLokasi' => $this->formatTanggalLokasi(),
            'cetakPejabatLabel' => $this->pejabatLabel(),
            'cetakPejabatLabelSingkat' => $this->pejabatLabelSingkat(),
        ];
    }
}
