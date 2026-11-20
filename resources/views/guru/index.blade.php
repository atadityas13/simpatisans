@extends('layouts.app')
@section('header', 'Master Data Guru')
@section('content')

    @if(session('error'))
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>
    @endif
    @if(session('success'))
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif

    <div x-data="{
        modalOpen: false,
        formAction: '',
        namaGuru: '',
        mapelDipilih: [],
        guruSearch: '',
        guruSearchBlobs: @js($guruSearchBlobs ?? []),
        rowMatches(blob) {
            const q = this.guruSearch.trim().toLowerCase();
            return !q || blob.includes(q);
        },
        guruMatchCount() {
            const q = this.guruSearch.trim().toLowerCase();
            if (!q) return this.guruSearchBlobs.length;
            return this.guruSearchBlobs.filter(b => b.includes(q)).length;
        },
        guruFilterEmpty() {
            const q = this.guruSearch.trim().toLowerCase();
            return q && this.guruMatchCount() === 0;
        },
        bukaMapel(action, nama, mapelIds) {
            this.formAction = action;
            this.namaGuru = nama;
            this.mapelDipilih = (mapelIds || []).map(id => String(id));
            this.modalOpen = true;
        },
        tarikOpen: false,
        tarikNip: '',
        tarikMencari: false,
        tarikHasil: null,
        tarikError: '',
        csrf: '{{ csrf_token() }}',
        cariUrl: '{{ route('guru.tarik.cari') }}',
        bukaTarik() {
            this.tarikOpen = true;
            this.tarikNip = '';
            this.tarikHasil = null;
            this.tarikError = '';
            this.tarikMencari = false;
        },
        async cariTarik() {
            this.tarikMencari = true;
            this.tarikError = '';
            this.tarikHasil = null;
            try {
                const body = new FormData();
                body.append('_token', this.csrf);
                body.append('nip', this.tarikNip);
                const res = await fetch(this.cariUrl, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body
                });
                const json = await res.json();
                if (!res.ok) {
                    this.tarikError = json.pesan || json.errors?.nip?.[0] || json.message || 'Pencarian gagal.';
                    return;
                }
                this.tarikHasil = json;
            } catch (e) {
                this.tarikError = 'Tidak dapat menghubungi server.';
            } finally {
                this.tarikMencari = false;
            }
        }
    }">
        <div class="mb-6 flex items-center justify-between gap-4">
            <h2 class="text-2xl font-bold text-gray-900">Daftar Guru</h2>
            <button type="button" @click="bukaTarik()"
                class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2.5 px-5 rounded-xl shadow-sm transition whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m0 0l-6-6m6 6l6-6" />
                </svg>
                Madani
            </button>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            @include('partials.guru-search-bar')
            <div class="overflow-x-auto main-scrollbar">
                <table class="w-full whitespace-nowrap text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            <th class="px-4 py-4 text-center">KG</th>
                            <th class="px-5 py-4 text-center">Guru</th>
                            <th class="px-5 py-4 text-center">Nomor Induk</th>
                            <th class="px-5 py-4 text-center">Status</th>
                            <th class="px-5 py-4 text-center">Mapel Ijazah</th>
                            <th class="px-5 py-4 text-center">Sertifikasi</th>
                            <th class="px-5 py-4 text-center">Mapel Diampu</th>
                            <th class="px-5 py-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($gurus as $i => $guru)
                            <tr class="hover:bg-gray-50 transition-colors" data-search="{{ e($guruSearchBlobs[$i] ?? '') }}" x-show="rowMatches($el.dataset.search)">
                                <td class="px-4 py-4 text-center">
                                    <div class="font-black text-indigo-700 truncate">{{ strtoupper($guru->kode_guru) }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <p class="font-bold text-gray-900 leading-tight">{{ $guru->nama_lengkap }}</p>
                                    <p class="text-[10px] text-gray-400 mt-1 uppercase tracking-tighter">{{ $guru->jabatan ?: '—' }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    <p class="font-mono text-xs text-gray-600">{{ $guru->username }}</p>
                                    <p class="font-mono text-xs text-gray-600 mt-1 font-medium">{{ $guru->nuptk ?: '—' }}</p>
                                </td>
                                <td class="px-5 py-4 text-gray-600">
                                    <div class="flex items-center">
                                        @if($guru->status_pegawai === 'PNS')
                                            <span class="px-1.5 py-0.5 bg-blue-50 text-blue-600 text-[10px] font-black rounded uppercase tracking-tighter">PNS</span>
                                        @elseif($guru->status_pegawai === 'PPPK')
                                            <span class="px-1.5 py-0.5 bg-green-50 text-green-600 text-[10px] font-black rounded uppercase tracking-tighter">PPPK</span>
                                        @else
                                            <span class="px-1.5 py-0.5 bg-orange-50 text-orange-600 text-[10px] font-black rounded uppercase tracking-tighter">Non ASN</span>
                                        @endif
                                    </div>
                                    <div class="px-1.5 py-0.5 text-gray-400 text-[10px] font-black rounded tracking-tighter">{{ $guru->golongan ?: '—' }}</div>
                                </td>
                                <td class="px-5 py-4 text-gray-600 text-xs">{{ $guru->kualifikasi_ijazah }}</td>
                                <td class="px-5 py-4">
                                    @if($guru->status_sertifikasi)
                                        <div class="flex flex-col">
                                            <span class="px-2 py-0.5 bg-green-100 text-green-700 text-[9px] font-black rounded uppercase w-fit">Tersertifikasi</span>
                                            <p class="text-[10px] text-center text-gray-600 mt-1 font-bold truncate max-w-[100px]">{{ $guru->mapelSertifikasi?->nama_mapel ?? '—' }}</p>
                                        </div>
                                    @else
                                        <span class="px-2 py-0.5 bg-gray-50 text-gray-400 text-[9px] font-bold rounded uppercase w-fit">Belum</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex flex-wrap gap-1 max-w-[180px]">
                                        @forelse($guru->mapelDiampu as $mp)
                                            @php
                                                $linTypes = $guru->getLinearityTypes($mp);
                                                $is_linear = count($linTypes) > 0;
                                                $linTitle = count($linTypes) === 2 ? 'Linear' : ($is_linear ? 'Linear ' . implode('', $linTypes) : 'Tidak Linear / Belum Sertifikasi');
                                            @endphp
                                            <span class="px-1.5 py-0.5 {{ $is_linear ? 'bg-green-50 text-green-600 border-green-100' : 'bg-red-50 text-red-600 border-red-100' }} text-[9px] font-bold rounded border shadow-sm cursor-help" title="{{ $linTitle }}">
                                                {{ $mp->nama_mapel }}
                                            </span>
                                        @empty
                                            <span class="text-gray-300 text-xs italic">—</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-center gap-1">
                                        <form method="POST" action="{{ route('guru.sync', $guru) }}" data-confirm="Sinkronkan {{ $guru->nama_lengkap }} dari Madani? Jika NIP ini tidak ada di Madani, guru dihapus beserta jadwalnya.">
                                            @csrf
                                            <button type="submit" title="Sync dari Madani" class="p-1.5 text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors border border-indigo-100">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                </svg>
                                            </button>
                                        </form>
                                        <button type="button" title="Atur mapel diampu"
                                            data-action="{{ route('guru.update', $guru) }}"
                                            data-nama="{{ $guru->nama_lengkap }}"
                                            data-mapel="{{ $guru->mapelDiampu->pluck('id')->toJson() }}"
                                            @click="bukaMapel($el.dataset.action, $el.dataset.nama, JSON.parse($el.dataset.mapel || '[]'))"
                                            class="p-1.5 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors border border-gray-200">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-5 py-10 text-center text-gray-400 italic">Belum ada data guru.</td>
                            </tr>
                        @endforelse
                        <tr x-show="guruFilterEmpty()" x-cloak>
                            <td colspan="8" class="px-5 py-10 text-center text-gray-500 italic">
                                Tidak ada guru yang cocok dengan &ldquo;<span x-text="guruSearch"></span>&rdquo;
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div x-show="modalOpen" x-cloak class="fixed inset-0 bg-black/60 z-[100] flex items-center justify-center p-4 backdrop-blur-sm"
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <div @click.away="modalOpen = false"
                class="bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] flex flex-col overflow-hidden"
                x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="scale-95 opacity-0" x-transition:enter-end="scale-100 opacity-100">
                <div class="flex items-center justify-between p-6 border-b border-gray-100">
                    <div>
                        <h3 class="font-bold text-gray-900 text-xl">Atur mapel diampu</h3>
                        <p class="text-xs text-gray-500 mt-1" x-text="namaGuru"></p>
                    </div>
                    <button type="button" @click="modalOpen = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <form :action="formAction" method="POST" class="flex flex-col min-h-0">
                    @csrf
                    @method('PUT')
                    <div class="overflow-y-auto p-4 space-y-2 max-h-[55vh]">
                        @foreach($mapels as $m)
                            <label class="flex items-center p-3 bg-gray-50 rounded-xl border border-gray-100 hover:border-indigo-200 hover:shadow-sm transition-all cursor-pointer group">
                                <input type="checkbox" name="mapel_diampu[]" value="{{ $m->id }}"
                                    x-model="mapelDipilih"
                                    class="w-5 h-5 text-indigo-600 rounded-lg border-gray-300 focus:ring-indigo-500 transition-all">
                                <span class="ml-3 text-sm font-bold text-gray-700 group-hover:text-indigo-700 transition-colors">{{ $m->nama_mapel }}</span>
                            </label>
                        @endforeach
                    </div>
                    <div class="px-5 py-4 flex justify-end gap-2 border-t border-gray-100">
                        <button type="button" @click="modalOpen = false"
                            class="px-5 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-600 hover:bg-gray-50 transition">
                            Batal
                        </button>
                        <button type="submit"
                            class="px-6 py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 transition">
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div x-show="tarikOpen" x-cloak class="fixed inset-0 bg-black/60 z-[100] flex items-center justify-center p-4 backdrop-blur-sm">
            <div @click.away="tarikOpen = false" class="bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] flex flex-col overflow-hidden">
                <div class="flex items-center justify-between p-6 border-b border-gray-100">
                    <div>
                        <h3 class="font-bold text-gray-900 text-xl">Tarik dari Madani</h3>
                        <p class="text-xs text-gray-500 mt-1">Cari NIP dulu. Data baru disimpan setelah dikonfirmasi.</p>
                    </div>
                    <button type="button" @click="tarikOpen = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="p-6 overflow-y-auto">
                    <label for="tarik-nip" class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Masukkan NIP</label>
                    <div class="flex gap-2">
                        <input id="tarik-nip" type="text" x-model="tarikNip" maxlength="30" inputmode="numeric" placeholder="NIP"
                            @keydown.enter.prevent="cariTarik()"
                            class="flex-1 rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm font-mono text-gray-800 focus:border-indigo-500 focus:ring-indigo-500">
                        <button type="button" @click="cariTarik()" :disabled="tarikMencari"
                            class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl text-sm font-semibold hover:bg-indigo-700 disabled:opacity-60">
                            <span x-text="tarikMencari ? 'Mencari...' : 'Cari'"></span>
                        </button>
                    </div>
                    <p x-show="tarikError" x-cloak class="mt-3 text-sm text-red-700" x-text="tarikError"></p>
                    <div x-show="tarikHasil" x-cloak class="mt-4">
                        <p class="text-sm font-semibold"
                            :class="tarikHasil && tarikHasil.status === 'siap' ? 'text-emerald-700' : 'text-red-700'"
                            x-text="tarikHasil ? tarikHasil.pesan : ''"></p>
                        <dl x-show="tarikHasil && tarikHasil.status === 'siap'" class="mt-3 divide-y divide-gray-100 border border-gray-100 rounded-xl px-3">
                            <template x-for="baris in (tarikHasil ? tarikHasil.baris : [])" :key="baris.label">
                                <div class="flex justify-between gap-4 py-2 text-sm">
                                    <dt class="text-gray-500 shrink-0" x-text="baris.label"></dt>
                                    <dd class="text-gray-900 text-right" x-text="baris.nilai"></dd>
                                </div>
                            </template>
                        </dl>
                        <ul class="mt-3 space-y-1" x-show="tarikHasil && tarikHasil.peringatan && tarikHasil.peringatan.length">
                            <template x-for="(pesan, indeks) in (tarikHasil ? tarikHasil.peringatan : [])" :key="indeks">
                                <li class="text-xs text-amber-700" x-text="pesan"></li>
                            </template>
                        </ul>
                    </div>
                </div>
                <div class="px-6 py-4 flex justify-end gap-2 border-t border-gray-100">
                    <button type="button" @click="tarikOpen = false"
                        class="px-5 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-600 hover:bg-gray-50 transition">
                        Batal
                    </button>
                    <form method="POST" action="{{ route('guru.tarik') }}" x-show="tarikHasil && tarikHasil.status === 'siap'">
                        @csrf
                        <input type="hidden" name="nip" :value="tarikNip">
                        <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 transition">
                            Simpan
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection
