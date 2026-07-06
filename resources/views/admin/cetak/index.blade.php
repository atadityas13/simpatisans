@extends('layouts.app')

@section('header', 'Menu Cetak Laporan')

@section('content')
<div x-data="{ showPresets: false }" class="space-y-8">
    <div class="bg-white rounded-3xl p-8 border border-gray-100 shadow-sm relative overflow-hidden">
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-black text-gray-900 mb-2">Pusat Pencetakan Dokumen</h2>
                <p class="text-gray-500 font-medium max-w-2xl">Silakan pilih dokumen atau jadwal yang ingin Anda cetak. Pastikan data pembagian tugas dan penjadwalan sudah final sebelum mencetak.</p>
            </div>
            <button @click="showPresets = true" class="px-6 py-3 bg-indigo-600 text-white font-bold rounded-2xl hover:bg-indigo-700 transition-all flex items-center gap-2 shadow-lg shadow-indigo-100">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                <span>Preset Cetak</span>
            </button>
        </div>
        <!-- Decorative background element -->
        <div class="absolute top-0 right-0 -translate-y-12 translate-x-12 w-64 h-64 bg-indigo-50 rounded-full blur-3xl opacity-50"></div>
    </div>

    <!-- PRINT CARDS GRID -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        
        {{-- 1. SK Pembagian Tugas --}}
        <div class="group bg-white rounded-2xl p-6 border border-gray-100 shadow-sm hover:shadow-xl hover:border-indigo-100 transition-all duration-300 flex flex-col justify-between">
            <div>
                <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center mb-5 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">SK Pembagian Tugas</h3>
                <p class="text-sm text-gray-500 mb-6 italic">Mencetak Surat Keputusan penetapan beban kerja guru.</p>
            </div>
            <button class="w-full py-3 bg-gray-50 text-indigo-600 font-black text-[11px] uppercase tracking-widest rounded-xl hover:bg-indigo-600 hover:text-white transition-colors border border-indigo-50 flex items-center justify-center gap-2">
                <span>Cetak SK</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            </button>
        </div>

        {{-- 2. Lampiran SK --}}
        <div class="group bg-white rounded-2xl p-6 border border-gray-100 shadow-sm hover:shadow-xl hover:border-indigo-100 transition-all duration-300 flex flex-col justify-between">
            <div>
                <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center mb-5 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Lampiran SK</h3>
                <p class="text-sm text-gray-500 mb-6 italic">Daftar rincian beban mengajar dan tugas tambahan guru.</p>
            </div>
            <a href="{{ route('cetak.lampiran-sk') }}" target="_blank" class="w-full py-3 bg-gray-50 text-indigo-600 font-black text-[11px] uppercase tracking-widest rounded-xl hover:bg-indigo-600 hover:text-white transition-colors border border-indigo-50 flex items-center justify-center gap-2">
                <span>Cetak Lampiran</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            </a>
        </div>

        {{-- 3. Jadwal Pelajaran --}}
        <div class="group bg-white rounded-2xl p-6 border border-gray-100 shadow-sm hover:shadow-xl hover:border-indigo-100 transition-all duration-300 flex flex-col justify-between">
            <div>
                <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center mb-5 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Jadwal Pelajaran</h3>
                <p class="text-sm text-gray-500 mb-6 italic">Mencetak seluruh matriks jadwal pelajaran dalam satu lembar A4.</p>
            </div>
            <a href="{{ route('cetak.jadwal-pelajaran') }}" target="_blank" class="w-full py-3 bg-gray-50 text-indigo-600 font-black text-[11px] uppercase tracking-widest rounded-xl hover:bg-indigo-600 hover:text-white transition-colors border border-indigo-50 flex items-center justify-center gap-2">
                <span>Cetak Jadwal</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            </a>
        </div>

        {{-- 4. Jadwal Besar (Master Schedule) --}}
        <div class="group bg-white rounded-2xl p-6 border border-gray-100 shadow-sm hover:shadow-xl hover:border-indigo-100 transition-all duration-300 flex flex-col justify-between">
            <div>
                <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-xl flex items-center justify-center mb-5 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path></svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Jadwal Besar</h3>
                <p class="text-sm text-gray-500 mb-6 italic">Format poster (split 12 halaman A4) untuk ditempel di ruang guru atau papan pengumuman.</p>
            </div>
            <a href="{{ route('cetak.jadwal-besar') }}" target="_blank" class="w-full py-3 bg-gray-50 text-indigo-600 font-black text-[11px] uppercase tracking-widest rounded-xl hover:bg-indigo-600 hover:text-white transition-colors border border-indigo-50 flex items-center justify-center gap-2">
                <span>Cetak Poster</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            </a>
        </div>

        {{-- 5. Jadwal Piket Guru --}}
        <div class="group bg-white rounded-2xl p-6 border border-gray-100 shadow-sm hover:shadow-xl hover:border-indigo-100 transition-all duration-300 flex flex-col justify-between">
            <div>
                <div class="w-12 h-12 bg-purple-50 text-purple-600 rounded-xl flex items-center justify-center mb-5 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Jadwal Piket Guru</h3>
                <p class="text-sm text-gray-500 mb-6 italic">Mencetak daftar guru piket harian selama satu semester.</p>
            </div>
            <a href="{{ route('cetak.jadwal-piket') }}" target="_blank" class="w-full py-3 bg-gray-50 text-indigo-600 font-black text-[11px] uppercase tracking-widest rounded-xl hover:bg-indigo-600 hover:text-white transition-colors border border-indigo-50 flex items-center justify-center gap-2">
                <span>Cetak Piket</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            </a>
        </div>

    </div>

    <!-- PRESET MODAL -->
    <template x-teleport="body">
        <div x-show="showPresets" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="fixed inset-0 z-[9999] bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 md:p-6"
             @keydown.escape.window="showPresets = false"
             style="display: none;">
            
            <div @click.away="showPresets = false" 
                 class="bg-white rounded-[2rem] md:rounded-[2.5rem] shadow-2xl w-full max-w-4xl overflow-hidden animate-in fade-in slide-in-from-bottom-8 duration-500 border border-gray-100">
                
                <div class="px-6 md:px-10 py-6 md:py-8 border-b border-gray-100 flex justify-between items-start bg-white">
                    <div>
                        <h3 class="text-xl md:text-2xl font-black text-gray-900 tracking-tight">Pengaturan Atribut Cetak</h3>
                        <p class="text-xs md:text-sm text-gray-500 font-medium mt-1">Kelola tanda tangan digital dan stempel resmi madrasah untuk dokumen otomatis.</p>
                    </div>
                    <button @click="showPresets = false" class="p-2 md:p-3 bg-gray-50 text-gray-400 hover:text-gray-900 rounded-2xl transition-all">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l18 18"></path></svg>
                    </button>
                </div>

                <div class="p-6 md:p-10 bg-white space-y-8">
                    {{-- Pengaturan tanggal & pejabat --}}
                    <form action="{{ route('cetak.presets.store') }}" method="POST" class="bg-slate-50 rounded-3xl p-6 border border-slate-100">
                        @csrf
                        <h4 class="text-sm font-black text-gray-800 uppercase tracking-wide mb-4">Titimangsa & Pejabat Penandatangan</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="tanggal_cetak" class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">Tanggal Cetak Dokumen</label>
                                <input type="date" name="tanggal_cetak" id="tanggal_cetak"
                                    value="{{ $cetakSettings['tanggal_cetak'] ?? now()->format('Y-m-d') }}"
                                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm font-bold text-gray-800 bg-white focus:ring-2 focus:ring-indigo-500/25 focus:border-indigo-400">
                                <p class="mt-1.5 text-[10px] text-gray-400">Dipakai di semua dokumen bertanda tangan (jadwal, lampiran SK, piket).</p>
                            </div>
                            <div>
                                <span class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">Pejabat Penandatangan</span>
                                <div class="space-y-2">
                                    <label class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition-colors {{ ($cetakSettings['pejabat_penandatangan'] ?? 'kepala') === 'kepala' ? 'border-indigo-300 bg-indigo-50' : 'border-gray-200 bg-white hover:border-gray-300' }}">
                                        <input type="radio" name="pejabat_penandatangan" value="kepala" class="text-indigo-600 focus:ring-indigo-500"
                                            {{ ($cetakSettings['pejabat_penandatangan'] ?? 'kepala') === 'kepala' ? 'checked' : '' }}>
                                        <span class="text-sm font-bold text-gray-800">Kepala Madrasah</span>
                                    </label>
                                    <label class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition-colors {{ ($cetakSettings['pejabat_penandatangan'] ?? 'kepala') === 'plt_kepala' ? 'border-indigo-300 bg-indigo-50' : 'border-gray-200 bg-white hover:border-gray-300' }}">
                                        <input type="radio" name="pejabat_penandatangan" value="plt_kepala" class="text-indigo-600 focus:ring-indigo-500"
                                            {{ ($cetakSettings['pejabat_penandatangan'] ?? 'kepala') === 'plt_kepala' ? 'checked' : '' }}>
                                        <span class="text-sm font-bold text-gray-800">Plt. Kepala Madrasah</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="mt-5 flex flex-wrap items-center justify-between gap-3">
                            <p class="text-[10px] text-gray-500">
                                Pratinjau: <span class="font-bold text-indigo-700">{{ $cetakTanggalLokasi ?? '' }}</span>
                                · <span class="font-bold text-indigo-700">{{ $cetakPejabatLabel ?? 'Kepala Madrasah' }}</span>
                            </p>
                            <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-black uppercase tracking-widest rounded-xl transition-colors shadow-sm">
                                Simpan Pengaturan
                            </button>
                        </div>
                    </form>

                    {{-- Upload TTD & stempel --}}
                    <form action="{{ route('cetak.presets.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <h4 class="text-sm font-black text-gray-800 uppercase tracking-wide mb-4">Tanda Tangan & Stempel</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 md:gap-8">
                            
                            {{-- 1. TTD KEPALA CARD --}}
                            <div class="group bg-slate-50 rounded-3xl p-6 border border-slate-100 hover:border-indigo-200 hover:bg-white hover:shadow-xl transition-all duration-300 relative overflow-hidden">
                                <div class="relative z-10 space-y-4">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Kepala Madrasah</span>
                                        @if($presets['ttd_kepala'])
                                            <span class="flex h-2 w-2 rounded-full bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.5)]"></span>
                                        @else
                                            <span class="flex h-2 w-2 rounded-full bg-slate-300"></span>
                                        @endif
                                    </div>
                                    <div class="aspect-[4/3] bg-white rounded-2xl border border-slate-200 shadow-inner flex items-center justify-center relative overflow-hidden group-hover:border-indigo-100 transition-colors">
                                        @if($presets['ttd_kepala'])
                                            <img src="{{ $presets['ttd_kepala'] }}?v={{ time() }}" class="w-full h-full object-contain p-4 group-hover:scale-105 transition-transform duration-500">
                                        @else
                                            <svg class="w-10 h-10 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                        @endif
                                        <!-- Action Overlay -->
                                        <div class="absolute inset-0 bg-indigo-600/5 backdrop-blur-[2px] opacity-0 group-hover:opacity-100 transition-opacity flex flex-col items-center justify-center cursor-pointer" onclick="document.getElementById('input_ttd_kepala_new').click()">
                                            <div class="bg-white p-2 rounded-xl shadow-lg transform translate-y-4 group-hover:translate-y-0 transition-transform duration-300">
                                                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-center">
                                        <h4 class="text-xs font-bold text-slate-700 uppercase tracking-tight">Tanda Tangan</h4>
                                        <p class="text-[9px] text-slate-400 mt-0.5">PNG Transparan direkomendasikan</p>
                                    </div>
                                    <input type="file" name="ttd_kepala" id="input_ttd_kepala_new" class="hidden" accept="image/*" onchange="this.form.submit()">
                                </div>
                            </div>

                            {{-- 2. TTD WAKA CARD --}}
                            <div class="group bg-slate-50 rounded-3xl p-6 border border-slate-100 hover:border-blue-200 hover:bg-white hover:shadow-xl transition-all duration-300 relative overflow-hidden">
                                <div class="relative z-10 space-y-4">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Waka Kurikulum</span>
                                        @if($presets['ttd_waka'])
                                            <span class="flex h-2 w-2 rounded-full bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.5)]"></span>
                                        @else
                                            <span class="flex h-2 w-2 rounded-full bg-slate-300"></span>
                                        @endif
                                    </div>
                                    <div class="aspect-[4/3] bg-white rounded-2xl border border-slate-200 shadow-inner flex items-center justify-center relative overflow-hidden group-hover:border-blue-100 transition-colors">
                                        @if($presets['ttd_waka'])
                                            <img src="{{ $presets['ttd_waka'] }}?v={{ time() }}" class="w-full h-full object-contain p-4 group-hover:scale-105 transition-transform duration-500">
                                        @else
                                            <svg class="w-10 h-10 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                        @endif
                                        <!-- Action Overlay -->
                                        <div class="absolute inset-0 bg-blue-600/5 backdrop-blur-[2px] opacity-0 group-hover:opacity-100 transition-opacity flex flex-col items-center justify-center cursor-pointer" onclick="document.getElementById('input_ttd_waka_new').click()">
                                            <div class="bg-white p-2 rounded-xl shadow-lg transform translate-y-4 group-hover:translate-y-0 transition-transform duration-300">
                                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-center">
                                        <h4 class="text-xs font-bold text-slate-700 uppercase tracking-tight">Tanda Tangan</h4>
                                        <p class="text-[9px] text-slate-400 mt-0.5">PNG Transparan direkomendasikan</p>
                                    </div>
                                    <input type="file" name="ttd_waka" id="input_ttd_waka_new" class="hidden" accept="image/*" onchange="this.form.submit()">
                                </div>
                            </div>

                            {{-- 3. STEMPEL CARD --}}
                            <div class="group bg-slate-50 rounded-3xl p-6 border border-slate-100 hover:border-purple-200 hover:bg-white hover:shadow-xl transition-all duration-300 relative overflow-hidden">
                                <div class="relative z-10 space-y-4">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Stempel Resmi</span>
                                        @if($presets['stempel'])
                                            <span class="flex h-2 w-2 rounded-full bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.5)]"></span>
                                        @else
                                            <span class="flex h-2 w-2 rounded-full bg-slate-300"></span>
                                        @endif
                                    </div>
                                    <div class="aspect-[4/3] bg-white rounded-2xl border border-slate-200 shadow-inner flex items-center justify-center relative overflow-hidden group-hover:border-purple-100 transition-colors">
                                        @if($presets['stempel'])
                                            <img src="{{ $presets['stempel'] }}?v={{ time() }}" class="w-full h-full object-contain p-4 group-hover:scale-105 transition-transform duration-500">
                                        @else
                                            <svg class="w-10 h-10 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                                        @endif
                                        <!-- Action Overlay -->
                                        <div class="absolute inset-0 bg-purple-600/5 backdrop-blur-[2px] opacity-0 group-hover:opacity-100 transition-opacity flex flex-col items-center justify-center cursor-pointer" onclick="document.getElementById('input_stempel_new').click()">
                                            <div class="bg-white p-2 rounded-xl shadow-lg transform translate-y-4 group-hover:translate-y-0 transition-transform duration-300">
                                                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-center">
                                        <h4 class="text-xs font-bold text-slate-700 uppercase tracking-tight">Cap/Stempel</h4>
                                        <p class="text-[9px] text-slate-400 mt-0.5">PNG Transparan direkomendasikan</p>
                                    </div>
                                    <input type="file" name="stempel" id="input_stempel_new" class="hidden" accept="image/*" onchange="this.form.submit()">
                                </div>
                            </div>

                        </div>

                        <div class="mt-8 flex items-center justify-center bg-indigo-50 p-4 rounded-3xl border border-indigo-100">
                            <div class="flex items-center gap-3 text-center">
                                <svg class="w-4 h-4 text-indigo-500 animate-pulse shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <p class="text-[10px] md:text-xs text-indigo-600 font-bold tracking-tight">Klik pada kotak gambar untuk memperbarui aset secara otomatis.</p>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </template>
</div>
@endsection
