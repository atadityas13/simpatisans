<?php

namespace App\Http\Controllers;

use App\Models\TugasTambahan;
use Illuminate\Http\Request;

class TugasTambahanController extends Controller
{
    public function index()
    {
        $tugases = TugasTambahan::orderBy('id', 'asc')->paginate(100);
        return view('tugas-tambahan.index', compact('tugases'));
    }

    public function create()
    {
        return view('tugas-tambahan.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_tugas'    => 'required|string|max:255',
            'jtm_ekuivalen' => 'required|integer|min:0',
        ]);
        $validated['tipe'] = 'custom';
        TugasTambahan::create($validated);
        return redirect()->route('tugas-tambahan.index')->with('success', 'Tugas Tambahan ditambahkan!');
    }

    public function edit($id)
    {
        $tugas = TugasTambahan::findOrFail($id);
        if ($tugas->isSystem()) {
            return redirect()->route('tugas-tambahan.index')->with('error', 'Tugas sistem tidak dapat diedit.');
        }
        return view('tugas-tambahan.edit', compact('tugas'));
    }

    public function update(Request $request, $id)
    {
        $tugas = TugasTambahan::findOrFail($id);
        if ($tugas->isSystem()) {
            return redirect()->route('tugas-tambahan.index')->with('error', 'Tugas sistem tidak dapat diubah.');
        }
        $validated = $request->validate([
            'nama_tugas'    => 'required|string|max:255',
            'jtm_ekuivalen' => 'required|integer|min:0',
        ]);
        $tugas->update($validated);
        return redirect()->route('tugas-tambahan.index')->with('success', 'Tugas Tambahan diperbarui!');
    }

    public function destroy($id)
    {
        $tugas = TugasTambahan::findOrFail($id);
        if ($tugas->isSystem()) {
            return redirect()->route('tugas-tambahan.index')->with('error', 'Tugas sistem tidak dapat dihapus.');
        }
        $tugas->delete();
        return redirect()->route('tugas-tambahan.index')->with('success', 'Tugas dihapus!');
    }
}
