<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class PublicSuratPernyataanController extends Controller
{
    public function form()
    {
        $bulanId = $this->bulanId();
        $now = now('Asia/Jakarta');

        return view('public.surat-pernyataan-zakat-form', [
            'tanggalDefault' => $now->toDateString(),
            'unitKerjaDefault' => 'MTsN 11 Majalengka',
            'bulanId' => $bulanId,
        ]);
    }

    public function cetak(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:200',
            'nip' => 'nullable|string|max:50',
            'golongan' => 'nullable|string|max:100',
            'jabatan' => 'nullable|string|max:150',
            'unit_kerja' => 'nullable|string|max:150',
            'tanggal' => 'nullable|date',
        ]);

        $tanggal = ! empty($validated['tanggal'])
            ? \Carbon\Carbon::parse($validated['tanggal'], 'Asia/Jakarta')
            : now('Asia/Jakarta');

        $bulanId = $this->bulanId();
        $tanggalSurat = $tanggal->day.' '.($bulanId[(int) $tanggal->month] ?? $tanggal->format('F')).' '.$tanggal->year;

        $gurus = new Collection([
            (object) [
                'nama_lengkap' => trim($validated['nama']),
                'username' => trim((string) ($validated['nip'] ?? '')),
                'golongan' => trim((string) ($validated['golongan'] ?? '')),
                'jabatan' => trim((string) ($validated['jabatan'] ?? '')),
                'unit_kerja' => trim((string) ($validated['unit_kerja'] ?? '')) ?: 'MTsN 11 Majalengka',
            ],
        ]);

        return view('admin.cetak.surat-pernyataan-zakat', compact('gurus', 'tanggalSurat'));
    }

    /** @return array<int, string> */
    private function bulanId(): array
    {
        return [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
    }
}
