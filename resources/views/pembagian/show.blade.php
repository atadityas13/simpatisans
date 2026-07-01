@extends('layouts.app')
@section('header', 'Atur Penugasan: ' . $guru->nama_lengkap)
@section('content')

    @if(!$selectedSemester)
        <div
            class="bg-amber-50 border border-amber-200 text-amber-800 rounded-xl p-8 flex flex-col items-center justify-center text-center space-y-4 mb-8">
            <svg class="w-16 h-16 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div>
                <h3 class="text-xl font-black uppercase tracking-widest text-amber-900">Belum Ada Semester Aktif</h3>
                <p class="text-sm mt-2 font-medium">Silakan buat dan aktifkan semester pada menu pengaturan terlebih dahulu
                    untuk mengelola pembagian tugas.</p>
            </div>
            <a href="{{ route('semester.index') }}"
                class="mt-4 bg-amber-500 hover:bg-amber-600 text-black px-6 py-2.5 rounded-lg font-bold text-sm uppercase tracking-widest shadow-md transition transform hover:-translate-y-0.5">Atur
                Semester Sekarang</a>
        </div>
    @else

        <div class="mb-4 flex flex-col md:flex-row justify-between items-center gap-4">
            <a href="{{ route('pembagian.index', ['semester_id' => $selectedSemester->id]) }}"
                class="text-indigo-600 hover:text-indigo-800 text-sm font-medium flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Kembali ke Daftar Distribusi
            </a>

            <form action="{{ route('pembagian.show', $guru->id) }}" method="GET" class="flex items-center space-x-2">
                <select name="semester_id" onchange="this.form.submit()"
                    class="bg-white border border-gray-200 text-gray-900 text-xs rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 font-bold shadow-sm">
                    @foreach($allSemesters as $sem)
                        <option value="{{ $sem->id }}" {{ $selectedSemester->id == $sem->id ? 'selected' : '' }}>
                            {{ $sem->nama_tahun }} - {{ $sem->tipe }} {{ $sem->is_active ? '(Aktif)' : '' }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>

        @if(!$selectedSemester->is_active)
            <div class="mb-6 bg-amber-50 border border-amber-200 p-4 rounded-xl flex items-center gap-3">
                <div class="bg-amber-100 p-2 rounded-lg text-amber-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 15v2m0 0v3m0-3h3m-3 0H9m12-3a9 9 0 11-18 0 9 9 0 0118 0zM15 7h.01M9 7h.01M15 11h.01M9 11h.01" />
                    </svg>
                </div>
                <div>
                    <p class="text-amber-900 font-bold text-sm text-sm uppercase tracking-tight">ARSIP</p>
                    <p class="text-amber-700 text-xs mt-0.5">Anda sedang melihat data semester masa lain. Perubahan data tidak
                        diizinkan.</p>
                </div>
            </div>
        @endif


        {{-- 5 Metrik Utama --}}
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-6">
            <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm text-center">
                <p class="text-xs text-gray-500 uppercase font-semibold tracking-wider mb-1">JTM KBM</p>
                <p class="text-2xl font-bold text-gray-800">{{ $metrik['jtmKbm'] }}</p>
            </div>
            <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm text-center">
                <p class="text-xs text-gray-500 uppercase font-semibold tracking-wider mb-1">Tugas Tambahan</p>
                <p class="text-2xl font-bold text-gray-800">{{ $metrik['jtmTugas'] }}</p>
            </div>
            <div class="bg-white rounded-xl p-4 border border-indigo-100 shadow-sm text-center">
                <p class="text-xs text-indigo-600 uppercase font-semibold tracking-wider mb-1">Total Beban</p>
                <p class="text-2xl font-bold text-indigo-700">{{ $metrik['totalBeban'] }}</p>
            </div>
            <div
                class="bg-white rounded-xl p-4 border {{ $metrik['totalLinear'] >= $metrik['TARGET'] ? 'border-green-200' : 'border-orange-200' }} shadow-sm text-center">
                <p
                    class="text-xs uppercase font-semibold tracking-wider mb-1 {{ $metrik['totalLinear'] >= $metrik['TARGET'] ? 'text-green-600' : 'text-orange-500' }}">
                    JTM Linear</p>
                <p
                    class="text-2xl font-bold {{ $metrik['totalLinear'] >= $metrik['TARGET'] ? 'text-green-700' : 'text-orange-600' }}">
                    {{ $metrik['totalLinear'] }}<span class="text-sm font-normal text-gray-400">/{{ $metrik['TARGET'] }}</span>
                </p>
            </div>
            <div
                class="rounded-xl p-4 border shadow-sm text-center {{ $metrik['layak'] === null ? 'bg-gray-50 border-gray-200' : ($metrik['layak'] ? 'bg-green-50 border-green-300' : 'bg-red-50 border-red-300') }}">
                <p
                    class="text-xs uppercase font-semibold tracking-wider mb-1 {{ $metrik['layak'] === null ? 'text-gray-500' : ($metrik['layak'] ? 'text-green-700' : 'text-red-700') }}">
                    Status Kelayakan</p>
                @if($metrik['layak'] === null)
                    <p class="text-sm font-bold text-gray-500 mt-1">Non-Sertifikasi</p>
                @elseif($metrik['layak'])
                    <p class="text-lg font-bold text-green-700 mt-1">✓ Layak TPG</p>
                @else
                    <p class="text-sm font-bold text-red-600 mt-1">✗ Defisit {{ $metrik['TARGET'] - $metrik['totalLinear'] }} Jam
                    </p>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">

            {{-- Kolom Kiri: Tabel KBM --}}
            <div class="lg:col-span-3">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="flex items-center justify-between px-5 py-4 bg-indigo-50 border-b border-indigo-100">
                        <h3 class="font-bold text-indigo-900 leading-tight">Rekap Jam Tatap Muka (KBM)</h3>
                        <div class="flex gap-2">
                            @if($selectedSemester->is_active)
                                <button onclick="document.getElementById('modal-non-satminkal').classList.remove('hidden')"
                                    class="text-white text-xs font-semibold px-4 py-2 rounded-lg transition hover:opacity-90"
                                    style="background-color: #0ea5e9;">
                                    + Non-Satminkal
                                </button>
                                <button onclick="document.getElementById('modal-mengajar').classList.remove('hidden')"
                                    class="bg-indigo-600 text-white text-xs font-semibold px-4 py-2 rounded-lg hover:bg-indigo-700 transition">
                                    + Mengajar
                                </button>
                            @endif
                        </div>
                    </div>
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-left">Kelas</th>
                                <th class="px-4 py-3 text-left">Mata Pelajaran</th>
                                <th class="px-4 py-3 text-center">JTM</th>
                                <th class="px-4 py-3 text-center">Linearitas</th>
                                <th class="px-4 py-3 text-right">
                                    @if($selectedSemester->is_active && $guru->bebanMengajars->count() > 0)
                                        <form action="{{ route('pembagian.kbm.clear', $guru->id) }}?semester_id={{ $selectedSemester->id }}" method="POST"
                                            data-confirm="Hapus SEMUA penugasan KBM guru ini untuk semester ini?">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-red-500 hover:text-red-700 transition-colors" title="Bersihkan Semua KBM">
                                                <svg class="w-5 h-5 ml-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v2m3 4h.01" />
                                                </svg>
                                            </button>
                                        </form>
                                    @endif
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @php
                                $sertRumpun = $guru->mapelSertifikasi?->rumpun;
                                $sertMapelId = $guru->mapel_sertifikasi_id;
                            @endphp
                            @forelse($guru->bebanMengajars as $bm)
                                @php
                                    $isLinear = $guru->isLinear($bm->mapel);
                                @endphp
                                <tr class="hover:bg-gray-50 {{ !$bm->is_satminkal ? 'bg-amber-50/30' : '' }}">
                                    <td class="px-4 py-3 font-bold text-gray-800">
                                        @if($bm->is_satminkal)
                                            {{ $bm->kelas->nama_kelas }}
                                        @else
                                            <div class="flex flex-col">
                                                <span
                                                    class="text-amber-700 text-[10px] uppercase font-bold tracking-tighter">Non-Satminkal</span>
                                                <span class="text-gray-500 text-xs font-medium">{{ $bm->jumlah_kelas }} Rombel</span>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">
                                        {{ $bm->mapel->nama_mapel }}
                                        @if(!$bm->is_satminkal && !empty($bm->hari))
                                            <div class="flex gap-1 mt-1">
                                                @foreach($bm->hari as $h)
                                                    <span
                                                        class="bg-white border border-amber-200 text-amber-700 text-[9px] px-1 rounded font-bold uppercase">{{ $h }}</span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>
                                    <td
                                        class="px-4 py-3 text-center font-bold {{ $isLinear ? 'text-indigo-600' : 'text-gray-500' }}">
                                        {{ $bm->jtm }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="flex flex-col gap-1 items-center">
                                            @php $types = $guru->getLinearityTypes($bm->mapel); @endphp
                                            @if(!$guru->status_sertifikasi)
                                                <span class="text-[10px] text-gray-400">—</span>
                                            @elseif(empty($types))
                                                <span
                                                    class="bg-red-50 text-red-600 text-[10px] px-2 py-0.5 rounded-full font-bold border border-red-100">
                                                    ✗ Non-Linear
                                                </span>
                                            @elseif(count($types) === 2)
                                                <span
                                                    class="bg-green-100 text-green-700 text-[10px] px-2 py-0.5 rounded-full font-semibold border border-green-200">
                                                    ✓ Linear
                                                </span>
                                            @else
                                                @foreach($types as $type)
                                                    <span
                                                        class="bg-green-100 text-green-700 text-[10px] px-2 py-0.5 rounded-full font-semibold border border-green-200">
                                                        ✓ Linear {{ $type }}
                                                    </span>
                                                @endforeach
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        @if($selectedSemester->is_active)
                                            @if($bm->is_satminkal)
                                                <form action="{{ route('pembagian.kbm.destroy', $bm->id) }}" method="POST"
                                                    data-confirm="Hapus penugasan ini?">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="text-red-400 hover:text-red-600 transition-colors"
                                                        title="Hapus Penugasan KBM">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v2m3 4h.01" />
                                                        </svg>
                                                    </button>
                                                </form>
                                            @else
                                                <form action="{{ route('pembagian.non-satminkal.destroy', $bm->id) }}" method="POST"
                                                    data-confirm="Hapus data mengajar non-satminkal ini?">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="text-amber-400 hover:text-amber-600 transition-colors"
                                                        title="Hapus Non-Satminkal">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v2m3 4h.01" />
                                                        </svg>
                                                    </button>
                                                </form>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-gray-400 italic">Belum ada penugasan KBM.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Kolom Kanan: Tugas Tambahan (Ekuivalensi) --}}
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden sticky top-6">
                    <div class="px-5 py-4 bg-amber-50 border-b border-amber-100 flex justify-between items-center">
                        <h3 class="font-bold text-amber-900 leading-tight">Tugas Tambahan & Ekuivalen</h3>
                        @if($selectedSemester->is_active)
                            <button onclick="document.getElementById('modal-tugas').classList.remove('hidden')"
                                class="bg-amber-600 text-white text-[10px] font-bold px-3 py-1.5 rounded uppercase tracking-wider hover:bg-amber-700 transition">
                                + Tugas
                            </button>
                        @endif
                    </div>
                    <div class="p-4 space-y-3 max-h-[calc(100vh-300px)] overflow-y-auto">
                        @forelse($guru->tugasTambahans as $t)
                            <div
                                class="border rounded-lg p-3 relative {{ $t->isSystem() ? 'bg-amber-50/50 border-amber-200' : 'bg-gray-50 border-gray-200' }}">
                                <div class="flex justify-between items-start pr-12">
                                    <div>
                                        <p class="text-sm font-bold text-gray-800">{{ $t->nama_tugas }}</p>
                                        @if($t->pivot->detail)
                                            <p class="text-[11px] text-indigo-600 font-medium">{{ $t->pivot->detail }}</p>
                                        @endif
                                        @if($t->pivot->hari)
                                            @php $hariArr = is_array($t->pivot->hari) ? $t->pivot->hari : json_decode($t->pivot->hari, true); @endphp
                                            @if($hariArr)
                                                <div class="flex flex-wrap gap-1 mt-1">
                                                    @foreach($hariArr as $h)
                                                        <span
                                                            class="bg-amber-100 text-amber-700 text-[9px] px-1.5 py-0.5 rounded-full font-bold border border-amber-200">{{ $h }}</span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        @endif
                                        <div class="flex gap-2 mt-1 items-center">
                                            <span class="bg-indigo-100 text-indigo-700 text-[10px] px-1.5 py-0.5 rounded font-bold">
                                                {{ $t->pivot->is_ekuivalen ? '+' . $t->jtm_ekuivalen : '+0' }} Jam
                                            </span>
                                            @if($t->pivot->is_ekuivalen)
                                                <span
                                                    class="bg-green-100 text-green-700 text-[10px] px-1.5 py-0.5 rounded font-bold uppercase tracking-tighter">✓
                                                    Ekuivalen</span>
                                            @else
                                                <span
                                                    class="bg-gray-100 text-gray-400 text-[9px] px-1.5 py-0.5 rounded font-medium italic">✗
                                                    Non-Ekuivalen</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="absolute top-3 right-3">
                                    @if($selectedSemester->is_active)
                                        <form
                                            action="{{ route('pembagian.tugas.destroy', ['guru' => $guru->id, 'tugas' => $t->id, 'semester_id' => $selectedSemester->id]) }}"
                                            method="POST" data-confirm="Hapus tugas tambahan ini?">
                                            @csrf @method('DELETE')
                                            <button
                                                class="text-red-400 hover:text-red-600 p-1.5 rounded-full hover:bg-red-50 transition">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="py-10 text-center text-gray-400 italic text-xs">Belum ada tugas tambahan.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
        </div>

        {{-- MODAL 1: Tambah Mengajar (KBM) --}}
        <div id="modal-mengajar" x-data='{ 
                                                                                activeTab: "VII", 
                                                                                selectedMapel: "{{ count($mapels) === 1 ? $mapels->first()->id : '' }}",
                                                                                occupied: @json($occupiedMap)
                                                                             }'
            class="hidden fixed inset-0 bg-black/50 z-[100] flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full font-sans overflow-hidden">
                {{-- Header Modal --}}
                <div class="flex items-center justify-between p-5 border-b border-gray-100">
                    <h3 class="font-bold text-gray-900 text-lg">Tambah Penugasan Mengajar</h3>
                    <button onclick="document.getElementById('modal-mengajar').classList.add('hidden')"
                        class="text-gray-400 hover:text-gray-700 text-2xl font-bold leading-none">&times;</button>
                </div>

                <form action="{{ route('pembagian.kbm.store', $guru->id) }}" method="POST">
                    @csrf
                    <input type="hidden" name="semester_id" value="{{ $selectedSemester->id }}">
                    <div class="p-5 space-y-4">
                        {{-- 1. Pilih Mapel (Hanya muncul jika > 1) --}}
                        @if(count($mapels) > 1)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Pilih Mata Pelajaran <span
                                        class="text-red-500">*</span></label>
                                <select name="mapel_id" x-model="selectedMapel" required
                                    class="w-full rounded-lg border-gray-300 shadow-sm p-2.5 border text-sm focus:border-indigo-500">
                                    <option value="">-- Pilih Mapel --</option>
                                    @foreach($mapels as $m)
                                        <option value="{{ $m->id }}">{{ $m->nama_mapel }} ({{ $m->jtm_default }} JP)</option>
                                    @endforeach
                                </select>
                            </div>
                        @elseif(count($mapels) === 1)
                            <input type="hidden" name="mapel_id" value="{{ $mapels->first()->id }}">
                            <div class="bg-indigo-50 p-3 rounded-lg border border-indigo-100">
                                <p class="text-[10px] text-indigo-500 font-bold uppercase mb-1">Mata Pelajaran</p>
                                <p class="text-sm font-bold text-indigo-900">{{ $mapels->first()->nama_mapel }}
                                    ({{ $mapels->first()->jtm_default }} JP)</p>
                            </div>
                        @else
                            <div class="bg-red-50 p-4 rounded-lg border border-red-100 text-center">
                                <p class="text-sm font-bold text-red-600">Guru ini belum memiliki Mapel Diampu.</p>
                                <p class="text-[10px] text-red-400 mt-1">Silakan atur master data guru terlebih dahulu.</p>
                            </div>
                        @endif

                        {{-- 2. Pilih Kelas dengan TABS --}}
                        <div class="space-y-3" x-show="selectedMapel">
                            <label class="block text-sm font-medium text-gray-700">Pilih Kelas / Rombel <span
                                    class="text-red-500">*</span></label>

                            {{-- Navigasi Tab --}}
                            <div class="flex border-b border-gray-100">
                                @foreach(['VII', 'VIII', 'IX'] as $tingkat)
                                    <button type="button" @click="activeTab = '{{ $tingkat }}'"
                                        :class="activeTab === '{{ $tingkat }}' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                                        class="flex-1 py-2 text-xs font-bold border-b-2 transition-all">
                                        Kelas {{ $tingkat }}
                                    </button>
                                @endforeach
                            </div>

                            {{-- Konten Tab --}}
                            <div class="mt-2">
                                @foreach($kelas->groupBy('tingkat') as $tingkat => $listKelas)
                                    <div x-show="activeTab === '{{ $tingkat }}'" class="space-y-1">
                                        {{-- Select All --}}
                                        <div class="flex justify-end mb-2">
                                            <label class="flex items-center gap-1.5 cursor-pointer group">
                                                <span
                                                    class="text-[10px] font-bold text-indigo-600 group-hover:underline uppercase">Pilih
                                                    Semua</span>
                                                <input type="checkbox"
                                                    onclick="const checks = this.closest('[x-show]').querySelectorAll('.kelas-check:not(:disabled)'); checks.forEach(c => c.checked = this.checked)"
                                                    class="w-3 h-3 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                            </label>
                                        </div>
                                        {{-- List Kelas --}}
                                        <div
                                            class="grid grid-cols-2 gap-x-4 gap-y-1 max-h-[200px] overflow-y-auto pr-2 custom-scrollbar">
                                            @foreach($listKelas as $k)
                                                @php $cid = $k->id; @endphp
                                                <label
                                                    class="flex flex-col p-2 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors group"
                                                    :class="selectedMapel && (occupied[selectedMapel] || {})[{{ $cid }}] ? 'opacity-50 cursor-not-allowed bg-gray-50' : ''">
                                                    <div class="flex items-center gap-2">
                                                        <input type="checkbox" name="kelas_ids[]" value="{{ $cid }}"
                                                            class="kelas-check w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500"
                                                            :disabled="selectedMapel && (occupied[selectedMapel] || {})[{{ $cid }}]">
                                                        <span
                                                            class="text-sm text-gray-700 group-hover:text-indigo-700 font-medium">{{ $k->nama_kelas }}</span>
                                                    </div>
                                                    <template x-if="selectedMapel && (occupied[selectedMapel] || {})[{{ $cid }}]">
                                                        <span class="text-[9px] text-red-500 font-bold mt-1 ml-6 leading-none"
                                                            x-text="'Diampu: ' + (occupied[selectedMapel] || {})[{{ $cid }}]"></span>
                                                    </template>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="px-5 pb-5 flex justify-end gap-2">
                        <button type="button" onclick="document.getElementById('modal-mengajar').classList.add('hidden')"
                            class="px-5 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-600 hover:bg-gray-50 transition">Batal</button>
                        <button type="submit" x-show="selectedMapel"
                            class="px-6 py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 transition">Simpan
                            Penugasan</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- MODAL 2: Tambah Tugas Tambahan --}}
        <div id="modal-tugas" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full font-sans">
                <div class="flex items-center justify-between p-5 border-b border-gray-100">
                    <h3 class="font-bold text-gray-900 text-lg">Tambah Tugas Tambahan</h3>
                    <button onclick="document.getElementById('modal-tugas').classList.add('hidden')"
                        class="text-gray-400 hover:text-gray-700 text-2xl font-bold leading-none">&times;</button>
                </div>
                <form action="{{ route('pembagian.tugas.store', $guru->id) }}" method="POST">
                    @csrf
                    <input type="hidden" name="semester_id" value="{{ $selectedSemester->id }}">
                    <div class="p-5 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Jenis Tugas <span
                                    class="text-red-500">*</span></label>
                            <select name="tugas_id" id="modal_tugas_select" required
                                class="w-full rounded-lg border-gray-300 shadow-sm p-2.5 border text-sm focus:border-amber-500">
                                <option value="">-- Pilih Tugas --</option>
                                @foreach($tugases as $t)
                                    <option value="{{ $t->id }}" data-system="{{ $t->isSystem() ? 1 : 0 }}"
                                        data-jtm="{{ $t->jtm_ekuivalen }}">
                                        {{ $t->nama_tugas }} (+{{ $t->jtm_ekuivalen }} Jam)
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Row Detail (Hanya untuk Wali Kelas / Waka) --}}
                        <div id="modal_detail_wrapper" class="hidden">
                            <label class="block text-sm font-medium text-gray-700 mb-2" id="modal_detail_label">Detail
                                Penugasan</label>

                            {{-- Dropdown Bidang (Waka) --}}
                            <select name="detail_bidang" id="modal_detail_bidang"
                                class="hidden w-full rounded-lg border-gray-300 shadow-sm p-2.5 border text-sm focus:border-amber-500">
                                <option value="">-- Pilih Bidang --</option>
                                @foreach(\App\Models\TugasTambahan::WAKA_BIDANG as $b)
                                    <option value="Bidang {{ $b }}">Bidang {{ $b }}</option>
                                @endforeach
                            </select>

                            {{-- Dropdown Rombel (Wali Kelas) --}}
                            <select name="detail_kelas" id="modal_detail_kelas"
                                class="hidden w-full rounded-lg border-gray-300 shadow-sm p-2.5 border text-sm focus:border-amber-500">
                                <option value="">-- Pilih Rombel --</option>
                                @foreach($kelas as $k)
                                    <option value="{{ $k->nama_kelas }}">{{ $k->nama_kelas }}</option>
                                @endforeach
                            </select>

                            <input type="hidden" name="detail" id="modal_detail_final">
                        </div>

                        {{-- Row Hari (Hanya untuk Guru Piket) --}}
                        <div id="modal_hari_wrapper" class="hidden">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Pilih Hari Piket</label>
                            <div class="flex flex-wrap gap-2">
                                @foreach(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'] as $day)
                                    <label
                                        class="flex items-center gap-1.5 px-3 py-2 bg-gray-100/50 hover:bg-amber-50 rounded-lg cursor-pointer border border-transparent transition select-none">
                                        <input type="checkbox" name="hari[]" value="{{ $day }}"
                                            class="w-3.5 h-3.5 text-amber-600 rounded border-gray-300 focus:ring-amber-500">
                                        <span class="text-xs font-semibold text-gray-700">{{ $day }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        {{-- Ekuivalen Toggle (Locking & Takeover Logic) --}}
                        <div id="modal_ekuivalen_wrapper" class="pt-2">
                            <label id="modal_ekuivalen_label" class="flex items-center gap-2 select-none cursor-pointer">
                                <input type="checkbox" name="is_ekuivalen" value="1" id="modal_ekuivalen_checkbox"
                                    class="w-4 h-4 text-amber-600 rounded border-gray-300">
                                <span class="text-sm font-bold text-gray-700">Hitung sebagai Linearitas TPG</span>
                            </label>
                            <div id="modal_ekuivalen_warning"
                                class="hidden mt-2 bg-red-50 border-l-2 border-red-400 p-2 text-[10px] text-red-700">
                                <p class="font-bold">⚠ Konflik Ekuivalensi</p>
                                <p id="modal_ekuivalen_warning_text"></p>
                            </div>
                        </div>
                    </div>
                    <div class="px-5 pb-5 flex justify-end gap-2">
                        <button type="button" onclick="document.getElementById('modal-tugas').classList.add('hidden')"
                            class="px-5 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-600 hover:bg-gray-50 transition">Batal</button>
                        <button type="submit"
                            class="px-6 py-2.5 bg-amber-600 text-white rounded-lg text-sm font-semibold hover:bg-amber-700 transition">Simpan
                            Tugas</button>
                    </div>
                </form>
            </div>
        </div>

        @php
            $piket = $guru->tugasTambahans->where('pivot.semester_id', $selectedSemester->id)->where('id', \App\Models\TugasTambahan::GURU_PIKET_ID)->first();
            $existingHariStr = $piket ? $piket->pivot->hari : '[]';
        @endphp
        <script>
            const hasExistingEkuivalen = @json((bool) $existingEkuivalen);
            const existingEkuivalenName = @json($existingEkuivalen?->nama_tugas);
            const existingPiketDays = {!! $existingHariStr ?: '[]' !!};

            const tugasSelect = document.getElementById('modal_tugas_select');
            const ekCheckbox = document.getElementById('modal_ekuivalen_checkbox');
            const ekWarning = document.getElementById('modal_ekuivalen_warning');
            const ekWarningText = document.getElementById('modal_ekuivalen_warning_text');
            const ekLabel = document.getElementById('modal_ekuivalen_label');

            const detailWrapper = document.getElementById('modal_detail_wrapper');
            const fieldBidang = document.getElementById('modal_detail_bidang');
            const fieldKelas = document.getElementById('modal_detail_kelas');
            const fieldFinalData = document.getElementById('modal_detail_final');
            const hariWrapper = document.getElementById('modal_hari_wrapper');

            tugasSelect.addEventListener('change', function () {
                const isSystem = this.options[this.selectedIndex].dataset.system == 1;
                const val = this.value;

                // Reset visibility
                detailWrapper.classList.add('hidden');
                fieldBidang.classList.add('hidden');
                fieldKelas.classList.add('hidden');
                hariWrapper.classList.add('hidden');
                ekWarning.classList.add('hidden');
                ekCheckbox.disabled = false;
                ekLabel.classList.remove('opacity-50', 'cursor-not-allowed');
                
                // Clear all checkboxes to prevent ghost submission
                document.querySelectorAll('#modal_hari_wrapper input[type="checkbox"]').forEach(cb => cb.checked = false);

                if (!val) return;

                // 1. Logic Show Detail Fields
                if (val == "{{ \App\Models\TugasTambahan::WALI_KELAS_ID }}") {
                    detailWrapper.classList.remove('hidden');
                    fieldKelas.classList.remove('hidden');
                } else if (val == "{{ \App\Models\TugasTambahan::WAKA_ID }}") {
                    detailWrapper.classList.remove('hidden');
                    fieldBidang.classList.remove('hidden');
                } else if (val == "{{ \App\Models\TugasTambahan::GURU_PIKET_ID }}") {
                    hariWrapper.classList.remove('hidden');
                    // Auto-cek hari yang sudah tersimpan
                    document.querySelectorAll('#modal_hari_wrapper input[type="checkbox"]').forEach(cb => {
                        cb.checked = existingPiketDays.includes(cb.value);
                    });
                }

                // 2. Logic Ekuivalen Priority
                if (isSystem) {
                    ekCheckbox.checked = true;
                    // System always linear 
                    
                    if (val == "{{ \App\Models\TugasTambahan::GURU_PIKET_ID }}") {
                        // Guru piket is additive, no conflict warning.
                        ekCheckbox.disabled = true;
                    } else {
                        // Waka / Wali Kelas replaces main equivalent task, so show warning
                        ekCheckbox.disabled = true;
                        if (hasExistingEkuivalen) {
                            ekWarning.classList.remove('hidden');
                            ekWarningText.innerText = "Tugas sistem ini akan menggantikan \"" + existingEkuivalenName + "\" sebagai tugas ekuivalen utama.";
                        }
                    }
                } else {
                    if (hasExistingEkuivalen) {
                        ekCheckbox.checked = false;
                        ekCheckbox.disabled = true;
                        ekLabel.classList.add('opacity-50', 'cursor-not-allowed');
                        ekWarning.classList.remove('hidden');
                        ekWarningText.innerText = "Pilihan ditutup karena sudah ada tugas ekuivalen: \"" + existingEkuivalenName + "\".";
                    } else {
                        ekCheckbox.checked = false;
                        ekCheckbox.disabled = false;
                    }
                }
            });

            document.querySelectorAll('#modal_detail_bidang, #modal_detail_kelas').forEach(el => {
                el.addEventListener('change', () => fieldFinalData.value = el.value);
            });
        </script>

        {{-- MODAL 3: Tambah Mengajar Non-Satminkal --}}
        <div id="modal-non-satminkal" class="hidden fixed inset-0 bg-black/50 z-[100] flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full font-sans overflow-hidden">
                <div class="flex items-center justify-between p-5 border-b border-gray-100">
                    <h3 class="font-bold text-gray-900 text-lg">Mengajar Non-Satminkal</h3>
                    <button onclick="document.getElementById('modal-non-satminkal').classList.add('hidden')"
                        class="text-gray-400 hover:text-gray-700 text-2xl font-bold leading-none">&times;</button>
                </div>

                <form action="{{ route('pembagian.non-satminkal.store', $guru->id) }}" method="POST">
                    @csrf
                    <input type="hidden" name="semester_id" value="{{ $selectedSemester->id }}">
                    <div class="p-5 space-y-4">
                        <p class="text-[11px] text-gray-500">Gunakan fitur ini untuk mencatat beban mengajar guru di Madrasah
                            lain agar dapat dihitung kelayakan TPG-nya.</p>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Pilih Mata Pelajaran <span
                                    class="text-red-500">*</span></label>
                            <select name="mapel_id" required
                                class="w-full rounded-lg border-gray-300 shadow-sm p-2.5 border text-sm focus:border-indigo-500">
                                <option value="">-- Pilih Mapel --</option>
                                @foreach(\App\Models\Mapel::orderBy('nama_mapel')->get() as $m)
                                    <option value="{{ $m->id }}">{{ $m->nama_mapel }} ({{ $m->jtm_default }} JP)</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Jumlah Rombel <span
                                    class="text-red-500">*</span></label>
                            <input type="number" name="jumlah_kelas" min="1" value="1" required
                                class="w-full rounded-lg border-gray-300 shadow-sm p-2.5 border text-sm focus:border-indigo-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Hari Mengajar
                                <span class="text-red-500">*</span></label>
                            <div class="flex flex-wrap gap-2">
                                @foreach(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'] as $hari)
                                    <label
                                        class="flex items-center gap-2 px-3 py-1.5 border border-gray-200 rounded-lg hover:bg-indigo-50 cursor-pointer transition group">
                                        <input type="checkbox" name="hari[]" value="{{ $hari }}"
                                            class="rounded text-indigo-600 focus:ring-indigo-500 h-4 w-4">
                                        <span
                                            class="text-xs font-semibold text-gray-700 group-hover:text-indigo-900">{{ $hari }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="bg-indigo-50 p-3 rounded-lg border border-indigo-100 flex gap-2">
                            <svg class="w-4 h-4 text-indigo-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="text-[10px] text-indigo-800 leading-tight">
                                <strong>PENTING:</strong> Hari yang Anda pilih akan secara otomatis diblokir (Blackout) pada
                                jadwal. Guru tidak akan dijadwalkan di hari tersebut.
                            </p>
                        </div>
                    </div>

                    <div class="px-5 pb-5 flex justify-end gap-2">
                        <button type="button" onclick="document.getElementById('modal-non-satminkal').classList.add('hidden')"
                            class="px-5 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-600 hover:bg-gray-50 transition">Batal</button>
                        <button type="submit"
                            class="px-6 py-2.5 text-white rounded-lg text-sm font-semibold transition hover:opacity-90"
                            style="background-color: #0ea5e9;">Simpan
                            Non-Satminkal</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection