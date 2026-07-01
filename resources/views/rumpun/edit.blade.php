@extends('layouts.app')
@section('header', 'Edit Rumpun Mapel')
@section('content')
<div class="mb-6"><a href="{{ route('rumpun-mapel.index') }}" class="text-indigo-600 hover:text-indigo-800 font-medium text-sm flex items-center">
    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
    Kembali
</a></div>
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 max-w-xl">
    <h2 class="text-lg font-bold text-gray-800 mb-6">Ubah Rumpun Mapel</h2>
    <form action="{{ route('rumpun-mapel.update', $rumpun->id) }}" method="POST">
        @csrf @method('PUT')
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-1">Nama Induk Rumpun</label>
            <input type="text" name="nama_rumpun" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 p-2.5 border" required value="{{ old('nama_rumpun', $rumpun->nama_rumpun) }}">
        </div>
        <button type="submit" class="bg-indigo-600 text-white py-2.5 px-6 rounded-lg font-medium">Simpan Perubahan</button>
    </form>
</div>
@endsection
