<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Services\Madani\TarikGuruService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Throwable;

class MadaniGuruSyncController extends Controller
{
    public function cari(Request $request, TarikGuruService $layanan): JsonResponse
    {
        $data = $request->validate([
            'nip' => ['required', 'string', 'max:30'],
        ], [
            'nip.required' => 'Isi NIP guru yang akan dicari.',
        ]);

        try {
            return response()->json($layanan->pratinjauNip($data['nip']));
        } catch (RuntimeException $e) {
            return response()->json([
                'status' => 'gagal',
                'pesan' => $e->getMessage(),
                'baris' => [],
                'peringatan' => [],
            ], 422);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'status' => 'gagal',
                'pesan' => 'Pencarian ke Madani gagal.',
                'baris' => [],
                'peringatan' => [],
            ], 500);
        }
    }

    public function tarik(Request $request, TarikGuruService $layanan): RedirectResponse
    {
        $data = $request->validate([
            'nip' => ['required', 'string', 'max:30'],
        ], [
            'nip.required' => 'Isi NIP guru yang akan ditarik.',
        ]);

        try {
            $hasil = $layanan->tarikNip($data['nip']);
        } catch (RuntimeException $e) {
            return redirect()
                ->route('guru.index')
                ->withInput()
                ->with('error', 'Tarik gagal: '.$e->getMessage());
        } catch (Throwable $e) {
            report($e);

            return redirect()
                ->route('guru.index')
                ->withInput()
                ->with('error', 'Tarik gagal: '.$e->getMessage());
        }

        $pesan = $hasil['nama'].' ditarik dari Madani dengan kode '.$hasil['kode'].'.';
        if ($hasil['peringatan'] !== []) {
            $pesan .= ' '.implode(' ', $hasil['peringatan']);
        }

        return redirect()
            ->route('guru.index')
            ->with('success', $pesan);
    }

    public function sync(Guru $guru, TarikGuruService $layanan): RedirectResponse
    {
        try {
            $hasil = $layanan->sinkronSatu($guru);
        } catch (RuntimeException $e) {
            return redirect()
                ->route('guru.index')
                ->with('error', 'Sync gagal: '.$e->getMessage());
        } catch (Throwable $e) {
            report($e);

            return redirect()
                ->route('guru.index')
                ->with('error', 'Sync gagal: '.$e->getMessage());
        }

        if ($hasil['dihapus']) {
            $pesan = $hasil['nama'].' tidak ditemukan di Madani, jadi dihapus beserta jadwalnya.';
        } elseif ($hasil['diperbarui']) {
            $label = collect($hasil['perubahan'])->pluck('label')->implode(', ');
            $pesan = $hasil['nama'].' disinkronkan: '.$label.'.';
        } else {
            $pesan = 'Data '.$hasil['nama'].' sudah sama dengan Madani.';
        }

        if ($hasil['peringatan'] !== []) {
            $pesan .= ' '.implode(' ', $hasil['peringatan']);
        }

        return redirect()
            ->route('guru.index')
            ->with('success', $pesan);
    }
}
