<?php

namespace App\Http\Controllers;

use App\Services\Madani\TarikGuruService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Throwable;

class MadaniGuruSyncController extends Controller
{
    public function index(TarikGuruService $tarik): Response|RedirectResponse
    {
        try {
            $hasil = $tarik->fetchAndRemember();

            return response(view('guru.madani', $hasil)->render());
        } catch (Throwable $e) {
            report($e);

            return redirect()
                ->route('guru.index')
                ->with('error', 'Tarik dan sync gagal: '.$e->getMessage());
        }
    }

    public function tarik(TarikGuruService $layanan): RedirectResponse
    {
        try {
            $hasil = $layanan->tarikDariSesi();
        } catch (Throwable $e) {
            report($e);

            return redirect()
                ->route('guru.madani')
                ->with('error', 'Tarik gagal: '.$e->getMessage());
        }

        return redirect()
            ->route('guru.madani')
            ->with('success', sprintf(
                'Tarik dari Madani selesai: %d guru ditambahkan. Mapel diampu dan jadwal tidak diubah.',
                $hasil['ditarik'],
            ));
    }

    public function sync(TarikGuruService $layanan): RedirectResponse
    {
        try {
            $hasil = $layanan->syncDariSesi();
        } catch (Throwable $e) {
            report($e);

            return redirect()
                ->route('guru.madani')
                ->with('error', 'Sync gagal: '.$e->getMessage());
        }

        return redirect()
            ->route('guru.madani')
            ->with('success', sprintf(
                'Sync dari Madani selesai: %d guru diperbarui, %d guru yang hanya ada di Simpatisans dihapus beserta jadwalnya. Akun admin tidak ikut terhapus.',
                $hasil['diperbarui'],
                $hasil['dihapus'],
            ));
    }
}
