<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use Illuminate\Http\Request;

class KelasController extends Controller
{
    public function index()
    {
        $kelas = Kelas::orderByRaw("FIELD(tingkat, 'VII', 'VIII', 'IX')")->orderBy('nama_kelas')->paginate(100);
        return view('kelas.index', compact('kelas'));
    }

    public function create()
    {
        return view('kelas.create');
    }

    public function store(Request $request)
    {
        // Mode massal
        if ($request->has('bulk_create')) {
            $request->validate([
                'bulk_tingkat' => 'required|in:VII,VIII,IX',
                'bulk_jumlah'  => 'required|integer|min:1|max:20',
            ]);

            $tingkat = $request->bulk_tingkat;
            $jumlah  = (int) $request->bulk_jumlah;
            $created = 0;

            for ($i = 1; $i <= $jumlah; $i++) {
                $namaKelas = "Kelas {$tingkat}.{$i}";
                if (!Kelas::where('nama_kelas', $namaKelas)->exists()) {
                    Kelas::create(['nama_kelas' => $namaKelas, 'tingkat' => $tingkat]);
                    $created++;
                }
            }

            return redirect()->route('kelas.index')
                ->with('success', "{$created} rombel Kelas {$tingkat} berhasil dibuat!");
        }

        // Mode manual 1 kelas
        $validated = $request->validate([
            'nama_kelas' => 'required|string|max:50|unique:kelas,nama_kelas',
            'tingkat'    => 'required|in:VII,VIII,IX',
        ]);
        Kelas::create($validated);
        return redirect()->route('kelas.index')->with('success', 'Kelas berhasil ditambahkan!');
    }

    public function edit(Kelas $kela)
    {
        return view('kelas.edit', ['kelas' => $kela]);
    }

    public function update(Request $request, Kelas $kela)
    {
        $validated = $request->validate([
            'nama_kelas' => 'required|string|max:50|unique:kelas,nama_kelas,' . $kela->id,
            'tingkat'    => 'required|in:VII,VIII,IX',
        ]);
        $kela->update($validated);
        return redirect()->route('kelas.index')->with('success', 'Kelas diperbarui!');
    }

    public function destroy(Kelas $kela)
    {
        $kela->delete();
        return redirect()->route('kelas.index')->with('success', 'Kelas dihapus!');
    }
}
