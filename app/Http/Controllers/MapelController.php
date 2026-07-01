<?php

namespace App\Http\Controllers;

use App\Models\Mapel;
use App\Models\Rumpun;
use Illuminate\Http\Request;

class MapelController extends Controller
{
    public function index()
    {
        $mapels = Mapel::with('rumpuns')->orderBy('id')->paginate(10);
        $rumpunList = Rumpun::orderBy('nama_rumpun')->get();
        return view('mapel.index', compact('mapels', 'rumpunList'));
    }

    public function create()
    {
        $rumpunList = Rumpun::orderBy('nama_rumpun')->get();
        $mapels = Mapel::orderBy('id')->get();
        return view('mapel.create', compact('rumpunList', 'mapels'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_mapel'  => 'required|string|max:255',
            'rumpun'      => 'nullable|array', // Now expects an array
            'jtm_default' => 'required|integer|min:1',
        ]);

        $mapel = Mapel::create($validated);

        // Handle Rumpun IDs
        if ($request->filled('rumpun')) {
            $rumpunIds = [];
            foreach ($request->rumpun as $val) {
                if (is_numeric($val)) {
                    $rumpunIds[] = $val;
                } else {
                    // It's a new name
                    $rumpun = Rumpun::firstOrCreate(['nama_rumpun' => $val]);
                    $rumpunIds[] = $rumpun->id;
                }
            }
            $mapel->rumpuns()->sync($rumpunIds);
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Mata Pelajaran berhasil ditambahkan!',
                'data'    => $mapel->load('rumpuns')
            ]);
        }

        return redirect()->route('mapel.index')->with('success', 'Mata Pelajaran ditambahkan!');
    }

    public function edit(Mapel $mapel)
    {
        $rumpunList = Rumpun::orderBy('nama_rumpun')->get();
        return view('mapel.edit', compact('mapel', 'rumpunList'));
    }

    public function update(Request $request, Mapel $mapel)
    {
        $validated = $request->validate([
            'nama_mapel'  => 'required|string|max:255',
            'rumpun'      => 'nullable|array',
            'jtm_default' => 'required|integer|min:1',
        ]);

        $mapel->update($validated);

        $rumpunIds = [];
        if ($request->filled('rumpun')) {
            foreach ($request->rumpun as $val) {
                if (is_numeric($val)) {
                    $rumpunIds[] = $val;
                } else {
                    $rumpun = Rumpun::firstOrCreate(['nama_rumpun' => $val]);
                    $rumpunIds[] = $rumpun->id;
                }
            }
        }
        $mapel->rumpuns()->sync($rumpunIds);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Mata Pelajaran berhasil diperbarui!',
                'data'    => $mapel->load('rumpuns')
            ]);
        }

        return redirect()->route('mapel.index')->with('success', 'Mata Pelajaran diperbarui!');
    }

    public function destroy(Mapel $mapel)
    {
        $mapel->delete();

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Mata Pelajaran berhasil dihapus!'
            ]);
        }

        return redirect()->route('mapel.index')->with('success', 'Mata Pelajaran dihapus!');
    }
}
