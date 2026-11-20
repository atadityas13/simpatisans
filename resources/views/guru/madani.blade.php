@extends('layouts.app')
@section('header', 'Tarik dan Sync Madani')
@section('content')
    <div class="mb-6 flex justify-between items-center gap-3 flex-wrap">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Bandingkan dengan Madani</h2>
            <p class="text-gray-600 mt-1 text-sm">Periksa dulu. Tarik hanya menambah guru baru. Sync hanya mengubah data yang diisi di Madani.</p>
        </div>
        <a href="{{ route('guru.index') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700">Kembali ke daftar guru</a>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif

    <div class="mb-4 rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-600">
        Ditemukan {{ $total_madani }} guru di Madani. {{ $sama }} sudah sama.
        Cocok lewat NIP. Tendik tidak ikut. Guru yang hanya ada di Simpatisans tidak dihapus.
        Kode guru 2 huruf, mapel diampu, akun, dan jadwal tidak ditimpa saat sync.
    </div>

    @foreach ($peringatan as $pesan)
        <div class="mb-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">{{ $pesan }}</div>
    @endforeach
    @foreach ($lewati as $pesan)
        <div class="mb-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">{{ $pesan }}</div>
    @endforeach

    <div class="mb-6 flex gap-3 flex-wrap">
        @if (count($tarik) > 0)
            <form method="POST" action="{{ route('guru.madani.tarik') }}">
                @csrf
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2.5 px-5 rounded-xl shadow-sm">
                    Tarik {{ count($tarik) }} guru baru
                </button>
            </form>
        @endif
        @if (count($sinkron) > 0)
            <form method="POST" action="{{ route('guru.madani.sync') }}">
                @csrf
                <button type="submit" class="bg-white hover:bg-gray-50 text-indigo-700 font-medium py-2.5 px-5 rounded-xl border border-indigo-200">
                    Sync {{ count($sinkron) }} guru
                </button>
            </form>
        @endif
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-6">
        <div class="px-5 py-4 border-b border-gray-100 font-bold text-gray-900">Akan ditarik ({{ count($tarik) }})</div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 text-left text-xs font-semibold text-gray-500 uppercase">
                        <th class="px-5 py-3">Nama</th>
                        <th class="px-5 py-3">NIP</th>
                        <th class="px-5 py-3">Kode</th>
                        <th class="px-5 py-3">DUK</th>
                        <th class="px-5 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($tarik as $row)
                        <tr>
                            <td class="px-5 py-3">{{ $row['nama'] }}</td>
                            <td class="px-5 py-3 font-mono text-xs">{{ $row['nip'] }}</td>
                            <td class="px-5 py-3">{{ $row['kode'] }}</td>
                            <td class="px-5 py-3">{{ $row['duk'] ?: '—' }}</td>
                            <td class="px-5 py-3">{{ $row['status_pegawai'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-6 text-gray-400">Tidak ada guru baru.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-6">
        <div class="px-5 py-4 border-b border-gray-100 font-bold text-gray-900">Akan disinkronkan ({{ count($sinkron) }})</div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 text-left text-xs font-semibold text-gray-500 uppercase">
                        <th class="px-5 py-3">Guru</th>
                        <th class="px-5 py-3">NIP</th>
                        <th class="px-5 py-3">Perubahan dari Madani</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($sinkron as $item)
                        <tr>
                            <td class="px-5 py-3 font-semibold">{{ $item['guru']->nama_lengkap }}</td>
                            <td class="px-5 py-3 font-mono text-xs">{{ $item['guru']->username }}</td>
                            <td class="px-5 py-3">
                                @foreach ($item['perubahan'] as $ubah)
                                    <div>{{ $ubah['label'] }}: {{ $ubah['dari'] ?? 'kosong' }} → {{ $ubah['ke'] }}</div>
                                @endforeach
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-5 py-6 text-gray-400">Tidak ada pembaruan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 font-bold text-gray-900">Hanya ada di Simpatisans ({{ count($hanya_lokal) }})</div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 text-left text-xs font-semibold text-gray-500 uppercase">
                        <th class="px-5 py-3">Guru</th>
                        <th class="px-5 py-3">NIP</th>
                        <th class="px-5 py-3">Kode</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($hanya_lokal as $guru)
                        <tr>
                            <td class="px-5 py-3">{{ $guru->nama_lengkap }}</td>
                            <td class="px-5 py-3 font-mono text-xs">{{ $guru->username }}</td>
                            <td class="px-5 py-3">{{ $guru->kode_guru }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-5 py-6 text-gray-400">Semua guru Simpatisans ketemu di Madani.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
