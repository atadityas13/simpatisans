<?php

namespace App\Http\Controllers;

use App\Services\Madani\TarikGuruService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use RuntimeException;

class MadaniGuruSyncController extends Controller
{
    public function index(TarikGuruService $tarik): View|RedirectResponse
    {
        try {
            $hasil = $tarik->fetchAndRemember();
        } catch (RuntimeException $e) {
            return redirect()
                ->route('guru.index')
                ->with('error', $e->getMessage());
        }

        return view('guru.madani', $hasil);
    }

    public function tarik(TarikGuruService $layanan): RedirectResponse
    {
        try {
            $hasil = $layanan->tarikDariSesi();
        } catch (RuntimeException $e) {
            return redirect()
                ->route('guru.madani')
                ->with('error', $e->getMessage());
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
        } catch (RuntimeException $e) {
            return redirect()
                ->route('guru.madani')
                ->with('error', $e->getMessage());
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
