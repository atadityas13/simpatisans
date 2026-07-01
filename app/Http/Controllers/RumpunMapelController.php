<?php

namespace App\Http\Controllers;

use App\Models\RumpunMapel;
use Illuminate\Http\Request;

class RumpunMapelController extends Controller
{
    public function index()
    {
        $rumpuns = RumpunMapel::withCount('mapels')->orderBy('nama_rumpun')->get();
        return view('rumpun.index', compact('rumpuns'));
    }

    public function create()
    {
        return view('rumpun.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate(['nama_rumpun' => 'required|string|max:255']);
        RumpunMapel::create($validated);
        return redirect()->route('rumpun-mapel.index')->with('success', 'Rumpun Mapel ditambahkan!');
    }

    public function edit($id)
    {
        $rumpun = RumpunMapel::findOrFail($id);
        return view('rumpun.edit', compact('rumpun'));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate(['nama_rumpun' => 'required|string|max:255']);
        RumpunMapel::findOrFail($id)->update($validated);
        return redirect()->route('rumpun-mapel.index')->with('success', 'Rumpun Mapel diupdate!');
    }

    public function destroy($id)
    {
        RumpunMapel::findOrFail($id)->delete();
        return redirect()->route('rumpun-mapel.index')->with('success', 'Rumpun Mapel dihapus!');
    }
}
