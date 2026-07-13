@extends('layouts.app')

@section('header', 'Menu Cetak Laporan')

@section('content')
<div x-data="{ showPresets: {{ session('success') ? 'true' : 'false' }} }" class="space-y-8">
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

        {{-- 6. Daftar Wali Kelas --}}
        <div class="group bg-white rounded-2xl p-6 border border-gray-100 shadow-sm hover:shadow-xl hover:border-indigo-100 transition-all duration-300 flex flex-col justify-between">
            <div>
                <div class="w-12 h-12 bg-rose-50 text-rose-600 rounded-xl flex items-center justify-center mb-5 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Daftar Wali Kelas</h3>
                <p class="text-sm text-gray-500 mb-6 italic">Mencetak daftar wali kelas per rombel dari pembagian tugas semester aktif.</p>
            </div>
            <a href="{{ route('cetak.daftar-wali-kelas') }}" target="_blank" class="w-full py-3 bg-gray-50 text-indigo-600 font-black text-[11px] uppercase tracking-widest rounded-xl hover:bg-indigo-600 hover:text-white transition-colors border border-indigo-50 flex items-center justify-center gap-2">
                <span>Cetak Wali Kelas</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            </a>
        </div>

    </div>

    <!-- PRESET MODAL -->
    <template x-teleport="body">
        <div x-show="showPresets"
             x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-[9999] bg-slate-900/50 flex items-center justify-center p-4"
             @keydown.escape.window="showPresets = false">

            <div @click.away="showPresets = false"
                 class="bg-white w-full rounded-2xl shadow-2xl border border-gray-100 flex flex-col overflow-hidden"
                 style="max-width: 24rem;">

                {{-- Header --}}
                <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between shrink-0">
                    <div class="min-w-0 pr-2">
                        <h3 class="text-base font-black text-gray-900">Preset Cetak</h3>
                        <p class="text-[11px] text-gray-500">Tanggal, pejabat, TTD & stempel</p>
                    </div>
                    <button type="button" @click="showPresets = false"
                        class="p-1.5 text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded-lg shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l18 18"/></svg>
                    </button>
                </div>

                {{-- Body --}}
                <div class="overflow-y-auto px-4 py-4 space-y-4" style="max-height: min(80vh, 32rem);">

                    @if(session('success'))
                        <div class="text-xs font-bold text-emerald-700 bg-emerald-50 border border-emerald-100 rounded-lg px-3 py-2">
                            {{ session('success') }}
                        </div>
                    @endif

                    {{-- Titimangsa --}}
                    <form action="{{ route('cetak.presets.store') }}" method="POST" class="space-y-3">
                        @csrf
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Titimangsa</p>

                        <div>
                            <label for="tanggal_cetak" class="block text-xs font-bold text-gray-600 mb-1">Tanggal cetak</label>
                            <input type="date" name="tanggal_cetak" id="tanggal_cetak"
                                value="{{ $cetakSettings['tanggal_cetak'] ?? now()->format('Y-m-d') }}"
                                class="w-full h-9 border border-gray-200 rounded-lg px-3 text-sm text-gray-800 bg-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-400">
                        </div>

                        <div>
                            <label for="pejabat_penandatangan" class="block text-xs font-bold text-gray-600 mb-1">Pejabat penandatangan</label>
                            <select name="pejabat_penandatangan" id="pejabat_penandatangan"
                                class="w-full h-9 border border-gray-200 rounded-lg px-3 text-sm text-gray-800 bg-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-400">
                                <option value="kepala" {{ ($cetakSettings['pejabat_penandatangan'] ?? 'kepala') === 'kepala' ? 'selected' : '' }}>Kepala Madrasah</option>
                                <option value="plt_kepala" {{ ($cetakSettings['pejabat_penandatangan'] ?? 'kepala') === 'plt_kepala' ? 'selected' : '' }}>Plt. Kepala Madrasah</option>
                            </select>
                        </div>

                        <p class="text-[10px] text-gray-400 leading-snug">
                            Pratinjau: <span class="text-indigo-600 font-bold">{{ $cetakTanggalLokasi ?? '' }}</span>
                            · <span class="text-indigo-600 font-bold">{{ $cetakPejabatLabel ?? 'Kepala Madrasah' }}</span>
                        </p>

                        <button type="submit"
                            class="w-full h-9 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-black uppercase tracking-wide rounded-lg transition-colors">
                            Simpan
                        </button>
                    </form>

                    <hr class="border-gray-100">

                    {{-- TTD & Stempel --}}
                    <form action="{{ route('cetak.presets.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">TTD & Stempel</p>
                        <p class="text-[10px] text-gray-400 mb-3">Ketuk kotak untuk unggah PNG transparan</p>

                        <div class="flex justify-between gap-2">
                            {{-- Kepala --}}
                            <div class="flex flex-col items-center flex-1 min-w-0">
                                <button type="button" onclick="document.getElementById('input_ttd_kepala_new').click()"
                                    class="w-20 h-20 bg-gray-50 border border-gray-200 rounded-xl flex items-center justify-center overflow-hidden hover:border-indigo-300 hover:bg-indigo-50/40 transition-colors relative shrink-0">
                                    @if($presets['ttd_kepala'])
                                        <img src="{{ $presets['ttd_kepala'] }}?v={{ time() }}" alt="TTD Kepala" class="w-full h-full object-contain p-1">
                                        <span class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-emerald-500 ring-2 ring-white"></span>
                                    @else
                                        <svg class="w-7 h-7 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v16m8-8H4"/></svg>
                                    @endif
                                </button>
                                <p class="mt-1.5 text-[10px] font-bold text-gray-500 text-center">Kepala</p>
                                <input type="file" name="ttd_kepala" id="input_ttd_kepala_new" class="hidden" accept="image/*" onchange="this.form.submit()">
                            </div>

                            {{-- Waka --}}
                            <div class="flex flex-col items-center flex-1 min-w-0">
                                <button type="button" onclick="document.getElementById('input_ttd_waka_new').click()"
                                    class="w-20 h-20 bg-gray-50 border border-gray-200 rounded-xl flex items-center justify-center overflow-hidden hover:border-blue-300 hover:bg-blue-50/40 transition-colors relative shrink-0">
                                    @if($presets['ttd_waka'])
                                        <img src="{{ $presets['ttd_waka'] }}?v={{ time() }}" alt="TTD Waka" class="w-full h-full object-contain p-1">
                                        <span class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-emerald-500 ring-2 ring-white"></span>
                                    @else
                                        <svg class="w-7 h-7 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v16m8-8H4"/></svg>
                                    @endif
                                </button>
                                <p class="mt-1.5 text-[10px] font-bold text-gray-500 text-center">Waka Kur.</p>
                                <input type="file" name="ttd_waka" id="input_ttd_waka_new" class="hidden" accept="image/*" onchange="this.form.submit()">
                            </div>

                            {{-- Stempel --}}
                            <div class="flex flex-col items-center flex-1 min-w-0">
                                <button type="button" onclick="document.getElementById('input_stempel_new').click()"
                                    class="w-20 h-20 bg-gray-50 border border-gray-200 rounded-xl flex items-center justify-center overflow-hidden hover:border-purple-300 hover:bg-purple-50/40 transition-colors relative shrink-0">
                                    @if($presets['stempel'])
                                        <img src="{{ $presets['stempel'] }}?v={{ time() }}" alt="Stempel" class="w-full h-full object-contain p-1">
                                        <span class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-emerald-500 ring-2 ring-white"></span>
                                    @else
                                        <svg class="w-7 h-7 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v16m8-8H4"/></svg>
                                    @endif
                                </button>
                                <p class="mt-1.5 text-[10px] font-bold text-gray-500 text-center">Stempel</p>
                                <input type="file" name="stempel" id="input_stempel_new" class="hidden" accept="image/*" onchange="this.form.submit()">
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </template>
</div>
@endsection
