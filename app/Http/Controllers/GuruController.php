<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\Mapel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GuruController extends Controller
{
    public function index(): View
    {
        $gurus = Guru::with(['mapelIjazah', 'rumpunIjazah', 'mapelSertifikasi', 'mapelDiampu'])
            ->orderedByDuk()
            ->get();

        $guruSearchBlobs = $gurus->map(fn (Guru $g) => $g->searchBlob())->values()->all();

        $mapels = Mapel::orderBy('id')->get();

        return view('guru.index', compact('gurus', 'mapels', 'guruSearchBlobs'));
    }

    public function create(): RedirectResponse
    {
        return redirect()
            ->route('guru.index')
            ->with('error', 'Guru baru hanya bisa ditarik dari Madani.');
    }

    public function store(Request $request): RedirectResponse
    {
        return redirect()
            ->route('guru.index')
            ->with('error', 'Guru baru hanya bisa ditarik dari Madani.');
    }

    public function edit(Guru $guru): RedirectResponse
    {
        return redirect()->route('guru.index');
    }

    public function update(Request $request, Guru $guru): RedirectResponse
    {
        $validated = $request->validate([
            'mapel_diampu' => ['nullable', 'array'],
            'mapel_diampu.*' => ['integer', 'exists:mapels,id'],
        ]);

        $guru->mapelDiampu()->sync($validated['mapel_diampu'] ?? []);

        return redirect()
            ->route('guru.index')
            ->with('success', 'Mapel diampu '.$guru->nama_lengkap.' diperbarui.');
    }

    public function destroy(Guru $guru): RedirectResponse
    {
        $guru->mapelDiampu()->detach();
        $guru->delete();

        return redirect()->route('guru.index')->with('success', 'Data Guru dihapus!');
    }
}
