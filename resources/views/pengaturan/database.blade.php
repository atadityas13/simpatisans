@extends('layouts.app')

@section('header', 'Database Management')

@section('content')
<div class="space-y-6">
    <!-- Header Summary Card -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-black text-gray-900 tracking-tight">Rekapitulasi Database</h2>
            <p class="text-gray-500 text-sm mt-1">Pantau penggunaan data dan kelola tabel sistem secara mendalam.</p>
        </div>
        <div class="bg-indigo-50 p-4 rounded-xl border border-indigo-100 flex items-center gap-4">
            <div class="bg-indigo-600 p-3 rounded-lg shadow-lg shadow-indigo-200">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                </svg>
            </div>
            <div>
                <span class="block text-[10px] font-black uppercase tracking-widest text-indigo-400 leading-none mb-1">Total Tabel</span>
                <span class="text-2xl font-black text-indigo-900 leading-none">{{ count($tables) }}</span>
            </div>
        </div>
    </div>

    <!-- Tables List Card -->
    <div class="bg-white rounded-2xl shadow-xl shadow-gray-200/50 border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50/50 border-b border-gray-100">
                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Nama Tabel</th>
                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest text-center">Tipe</th>
                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest text-center">Jumlah Baris</th>
                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest text-right">Aksi Manajemen</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($tables as $t)
                    <tr class="hover:bg-gray-50/50 transition-colors">
                        <td class="px-6 py-4">
                            <span class="font-bold text-gray-800 flex items-center gap-2">
                                <code class="bg-gray-100 px-2 py-0.5 rounded text-xs text-indigo-600">{{ $t['name'] }}</code>
                                @if($t['is_critical'])
                                <span class="text-[9px] bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded-full font-black uppercase tracking-tighter shadow-sm border border-amber-200">Kritis</span>
                                @endif
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            @php
                                $colorMap = [
                                    'Master / Core' => 'bg-indigo-50 text-indigo-600 border-indigo-100',
                                    'Operational' => 'bg-emerald-50 text-emerald-600 border-emerald-100',
                                    'Pivot / Map' => 'bg-purple-50 text-purple-600 border-purple-100'
                                ];
                                $class = $colorMap[$t['type']] ?? 'bg-gray-50 text-gray-600 border-gray-100';
                            @endphp
                            <span class="text-[10px] {{ $class }} px-2 py-1 rounded-lg font-bold border">
                                {{ $t['type'] }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="text-sm font-black {{ $t['is_empty'] ? 'text-gray-300' : 'text-gray-900' }}">
                                {{ number_format($t['count']) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <form action="{{ route('database.truncate') }}" method="POST" 
                                data-confirm="PERHATIAN: Mengosongkan tabel [{{ $t['name'] }}] akan menghapus SELURUH data secara permanen dan mereset penomoran (ID) ke angka 1. Lanjutkan?">
                                @csrf
                                <input type="hidden" name="table" value="{{ $t['name'] }}">
                                <button type="submit" 
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-50 text-red-600 hover:bg-red-600 hover:text-white rounded-lg text-xs font-bold transition-all border border-red-200 shadow-sm active:scale-95 group">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v2m3 4h.01" />
                                    </svg>
                                    <span>Kosongkan Tabel</span>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Alert Safety -->
    <div class="bg-amber-50 border-l-4 border-amber-400 p-4 rounded-xl shadow-sm">
        <div class="flex items-start gap-3">
            <div class="bg-amber-400 p-1 rounded-full text-white mt-0.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <div>
                <h4 class="text-sm font-black text-amber-900 uppercase tracking-wide">Peringatan Keamanan</h4>
                <p class="text-sm text-amber-800 mt-1 leading-relaxed">
                    Tindakan <strong>"Kosongkan Tabel"</strong> bersifat permanen dan tidak dapat dibatalkan. Mengosongkan tabel seperti <code>users</code> atau <code>gurus</code> dapat menyebabkan terganggunya fungsionalitas aplikasi jika data utama hilang. Gunakan dengan sangat hati-hati.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
