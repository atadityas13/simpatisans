@extends('layouts.app')
@section('header', 'Master Rumpun Mapel')
@section('content')
<div class="mb-6 flex justify-between items-center">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Daftar Rumpun Mapel</h2>
        <p class="text-gray-600 mt-1 text-sm">Kelompok induk mata pelajaran untuk mendeteksi linearitas beban tugas guru.</p>
    </div>
    <a href="{{ route('rumpun-mapel.create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-5 rounded-lg shadow-sm transition inline-flex items-center">
        Tambah Rumpun
    </a>
</div>
<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden max-w-4xl">
    <table class="w-full whitespace-nowrap">
        <thead>
            <tr class="bg-gray-50 border-b border-gray-200 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                <th class="px-6 py-4">Nama Rumpun</th>
                <th class="px-6 py-4 text-center">Jumlah Anggota Mapel</th>
                <th class="px-6 py-4 text-right">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 text-sm">
            @forelse($rumpuns as $rumpun)
            <tr class="hover:bg-gray-50 transition">
                <td class="px-6 py-4 font-bold text-indigo-900">{{ $rumpun->nama_rumpun }}</td>
                <td class="px-6 py-4 text-center text-gray-600 font-medium">{{ $rumpun->mapels_count }} Mapel Terdaftar</td>
                <td class="px-6 py-4 text-right space-x-2">
                    <a href="{{ route('rumpun-mapel.edit', $rumpun->id) }}" class="text-indigo-600 hover:text-indigo-900 transition-colors inline-block" title="Edit Rumpun">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </a>
                    <form action="{{ route('rumpun-mapel.destroy', $rumpun->id) }}" method="POST" class="inline-block" data-confirm="Menghapus rumpun ini akan membuat mapel di dalamnya berdiri sendiri. Lanjutkan?">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-red-500 hover:text-red-700 transition-colors" title="Hapus Rumpun">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v2m3 4h.01"/></svg>
                        </button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="3" class="px-6 py-8 text-center text-gray-500 italic">Belum ada kelompok rumpun mapel.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
