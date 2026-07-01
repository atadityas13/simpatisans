<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\Mapel;
use App\Models\Rumpun;
use Illuminate\Http\Request;

class GuruController extends Controller
{
    public function index()
    {
        $gurus = Guru::with(['mapelIjazah', 'rumpunIjazah', 'mapelSertifikasi', 'mapelDiampu'])
            ->orderByRaw('duk IS NULL ASC, duk ASC')
            ->paginate(10);
        $mapels = Mapel::orderBy('id')->get();
        $rumpuns = Rumpun::orderBy('nama_rumpun')->get();
        return view('guru.index', compact('gurus', 'mapels', 'rumpuns'));
    }

    public function create()
    {
        $mapels = Mapel::orderBy('id')->get();
        return view('guru.create', compact('mapels'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'username'             => 'required|string|max:30|unique:gurus,username',
            'kode_guru'            => 'required|string|size:2|unique:gurus,kode_guru',
            'duk'                  => 'nullable|integer|min:1|unique:gurus,duk',
            'status_pegawai'       => 'required|in:PNS,PPPK,NON_ASN',
            'nama_guru'            => 'required|string|max:255',
            'gelar_depan'          => 'nullable|string|max:100',
            'gelar_belakang'       => 'nullable|string|max:100',
            'nuptk'                => 'nullable|string|max:20',
            'jabatan'              => 'nullable|string|max:255',
            'golongan'             => 'nullable|string|max:20',
            'ijazah_selection'     => 'nullable|string',
            'mapel_sertifikasi_id' => 'nullable|exists:mapels,id',
            'is_bk'                => 'nullable|boolean',
            'mapel_diampu'         => 'nullable|array',
            'mapel_diampu.*'       => 'exists:mapels,id',
        ]);

        $validated['status_sertifikasi'] = $request->boolean('status_sertifikasi');
        $validated['is_bk'] = $request->boolean('is_bk');

        // Handle Combined Ijazah Selection
        $validated['mapel_ijazah_id'] = null;
        $validated['rumpun_ijazah_id'] = null;
        if ($request->filled('ijazah_selection')) {
            [$type, $id] = explode('_', $request->ijazah_selection);
            if ($type === 'mapel') $validated['mapel_ijazah_id'] = $id;
            if ($type === 'rumpun') $validated['rumpun_ijazah_id'] = $id;
        }

        if (!$validated['status_sertifikasi']) {
            $validated['mapel_sertifikasi_id'] = null;
        }

        $guru = Guru::create($validated);
        if ($request->has('mapel_diampu')) {
            $guru->mapelDiampu()->sync($request->mapel_diampu);
        }

        return redirect()->route('guru.index')->with('success', 'Data Guru berhasil ditambahkan!');
    }

    public function edit(Guru $guru)
    {
        $mapels = Mapel::orderBy('id')->get();
        $mapelDiampuIds = $guru->mapelDiampu->pluck('id')->toArray();
        return view('guru.edit', compact('guru', 'mapels', 'mapelDiampuIds'));
    }

    public function update(Request $request, Guru $guru)
    {
        $validated = $request->validate([
            'username'             => 'required|string|max:30|unique:gurus,username,' . $guru->id,
            'kode_guru'            => 'required|string|size:2|unique:gurus,kode_guru,' . $guru->id,
            'duk'                  => 'nullable|integer|min:1|unique:gurus,duk,' . $guru->id,
            'status_pegawai'       => 'required|in:PNS,PPPK,NON_ASN',
            'nama_guru'            => 'required|string|max:255',
            'gelar_depan'          => 'nullable|string|max:100',
            'gelar_belakang'       => 'nullable|string|max:100',
            'nuptk'                => 'nullable|string|max:20',
            'jabatan'              => 'nullable|string|max:255',
            'golongan'             => 'nullable|string|max:20',
            'ijazah_selection'     => 'nullable|string',
            'mapel_sertifikasi_id' => 'nullable|exists:mapels,id',
            'is_bk'                => 'nullable|boolean',
            'mapel_diampu'         => 'nullable|array',
            'mapel_diampu.*'       => 'exists:mapels,id',
        ]);

        $validated['status_sertifikasi'] = $request->boolean('status_sertifikasi');
        $validated['is_bk'] = $request->boolean('is_bk');

        // Handle Combined Ijazah Selection
        $validated['mapel_ijazah_id'] = null;
        $validated['rumpun_ijazah_id'] = null;
        if ($request->filled('ijazah_selection')) {
            [$type, $id] = explode('_', $request->ijazah_selection);
            if ($type === 'mapel') $validated['mapel_ijazah_id'] = $id;
            if ($type === 'rumpun') $validated['rumpun_ijazah_id'] = $id;
        }

        if (!$validated['status_sertifikasi']) {
            $validated['mapel_sertifikasi_id'] = null;
        }

        $guru->update($validated);
        $guru->mapelDiampu()->sync($request->mapel_diampu ?? []);

        return redirect()->route('guru.index')->with('success', 'Data Guru berhasil diperbarui!');
    }

    public function destroy(Guru $guru)
    {
        $guru->mapelDiampu()->detach();
        $guru->delete();
        return redirect()->route('guru.index')->with('success', 'Data Guru dihapus!');
    }
}
