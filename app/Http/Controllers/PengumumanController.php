<?php

namespace App\Http\Controllers;

use App\Models\Pengumuman;
use Illuminate\Http\Request;

class PengumumanController extends Controller
{
    public function index()
    {
        $items = Pengumuman::orderByDesc('published_at')->orderByDesc('id')->get();

        return view('pengumuman.index', compact('items'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'judul' => 'required|string|max:200',
            'isi' => 'required|string|max:5000',
            'is_active' => 'nullable|boolean',
            'published_at' => 'nullable|date',
        ]);

        Pengumuman::create([
            'judul' => $data['judul'],
            'isi' => $data['isi'],
            'is_active' => $request->boolean('is_active', true),
            'published_at' => $data['published_at'] ?? now(),
            'created_by' => $request->user()?->id,
        ]);

        return redirect()->route('pengumuman.index')->with('success', 'Pengumuman berhasil dikirim.');
    }

    public function update(Request $request, Pengumuman $pengumuman)
    {
        $data = $request->validate([
            'judul' => 'required|string|max:200',
            'isi' => 'required|string|max:5000',
            'is_active' => 'nullable|boolean',
            'published_at' => 'nullable|date',
        ]);

        $pengumuman->update([
            'judul' => $data['judul'],
            'isi' => $data['isi'],
            'is_active' => $request->boolean('is_active'),
            'published_at' => $data['published_at'] ?? $pengumuman->published_at,
        ]);

        return redirect()->route('pengumuman.index')->with('success', 'Pengumuman diperbarui.');
    }

    public function destroy(Pengumuman $pengumuman)
    {
        $pengumuman->delete();

        return redirect()->route('pengumuman.index')->with('success', 'Pengumuman dihapus.');
    }
}
