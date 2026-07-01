@extends('layouts.app')
@section('header', 'Master Data Guru')
@section('content')

    <div x-data="{ 
        modalOpen: false, 
        isEdit: false, 
        activeTab: 'identitas',
        formAction: '{{ route('guru.store') }}',
        formData: {
            id: '',
            username: '',
            kode_guru: '',
            duk: '',
            status_pegawai: 'PNS',
            nama_guru: '',
            gelar_depan: '',
            gelar_belakang: '',
            nuptk: '',
            jabatan: '',
            golongan: '',
            ijazah_selection: '',
            status_sertifikasi: 0,
            is_bk: 0,
            mapel_sertifikasi_id: '',
            mapel_diampu: []
        },
        openAdd() {
            this.isEdit = false;
            this.activeTab = 'identitas';
            this.formAction = '{{ route('guru.store') }}';
            this.formData = {
                id: '',
                username: '',
                kode_guru: '',
                duk: '',
                status_pegawai: 'PNS',
                nama_guru: '',
                gelar_depan: '',
                gelar_belakang: '',
                nuptk: '',
                jabatan: '',
                golongan: '',
                ijazah_selection: '',
                status_sertifikasi: 0,
                is_bk: 0,
                mapel_sertifikasi_id: '',
                mapel_diampu: []
            };
            this.modalOpen = true;
        },
        openEdit(guru, mapelIds) {
            this.isEdit = true;
            this.activeTab = 'identitas';
            this.formAction = '/master/guru/' + guru.id;
            this.formData = {
                id: guru.id,
                username: guru.username || '',
                kode_guru: guru.kode_guru || '',
                duk: guru.duk || '',
                status_pegawai: guru.status_pegawai || 'NON_ASN',
                nama_guru: guru.nama_guru || '',
                gelar_depan: guru.gelar_depan || '',
                gelar_belakang: guru.gelar_belakang || '',
                nuptk: guru.nuptk || '',
                jabatan: guru.jabatan || '',
                golongan: guru.golongan || '',
                ijazah_selection: guru.mapel_ijazah_id ? `mapel_${guru.mapel_ijazah_id}` : (guru.rumpun_ijazah_id ? `rumpun_${guru.rumpun_ijazah_id}` : ''),
                status_sertifikasi: guru.status_sertifikasi ? 1 : 0,
                is_bk: guru.is_bk ? 1 : 0,
                mapel_sertifikasi_id: guru.mapel_sertifikasi_id || '',
                mapel_diampu: mapelIds || []
            };
            this.modalOpen = true;
        }
    }">
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Daftar Guru</h2>
                <p class="text-gray-600 mt-1 text-sm">Manajemen data rinci tenaga pendidik. Status, jabatan dan mapel.</p>
            </div>
            <button @click="openAdd()"
                class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2.5 px-5 rounded-xl shadow-sm transition inline-flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Tambah Guru
            </button>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
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
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($gurus as $guru)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-4 text-center">
                                    <div class="font-black text-indigo-700 mb-2 truncate">{{ strtoupper($guru->kode_guru) }}</div>
                                    <div class="flex items-center justify-center space-x-1">
                                        <button @click="openEdit({{ $guru->toJson() }}, {{ $guru->mapelDiampu->pluck('id')->toJson() }})"
                                            class="p-1.5 text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors border border-indigo-100" title="Edit Profil Guru">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <form action="{{ route('guru.destroy', $guru->id) }}" method="POST" class="inline-block" data-confirm="Hapus data guru ini?">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="p-1.5 text-red-500 hover:bg-red-50 rounded-lg transition-colors border border-red-100" title="Hapus">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v2m3 4h.01" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
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
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-10 text-center text-gray-400 italic">Belum ada data guru.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($gurus->hasPages())
                <div class="px-5 py-4 border-t border-gray-100 bg-gray-50">
                    {{ $gurus->links() }}
                </div>
            @endif
        </div>

        {{-- MODAL DATA GURU --}}
        <div x-show="modalOpen" x-cloak class="fixed inset-0 bg-black/60 z-[100] flex items-center justify-center p-4 backdrop-blur-sm"
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            
            <div @click.away="modalOpen = false" 
                class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full max-h-[90vh] flex flex-col overflow-hidden"
                x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="scale-95 opacity-0" x-transition:enter-end="scale-100 opacity-100">
                
                {{-- Header --}}
                <div class="flex items-center justify-between p-6 border-b border-gray-100">
                    <div>
                        <h3 class="font-bold text-gray-900 text-xl" x-text="isEdit ? 'Edit Data Guru' : 'Tambah Guru Baru'"></h3>
                        <p class="text-xs text-gray-500 mt-1">Lengkapi informasi tenaga pendidik secara detail.</p>
                    </div>
                    <button @click="modalOpen = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Tab Navigation --}}
                <div class="flex gap-8 border-b border-gray-50 px-8 py-1 bg-gray-50/50">
                    <button type="button" @click="activeTab = 'identitas'" 
                        :class="activeTab === 'identitas' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-400 hover:text-gray-600'"
                        class="py-3 border-b-2 text-xs font-black uppercase tracking-widest transition-all">
                        01. Identitas Kepegawaian
                    </button>
                    <button type="button" @click="activeTab = 'kompetensi'" 
                        :class="activeTab === 'kompetensi' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-400 hover:text-gray-600'"
                        class="py-3 border-b-2 text-xs font-black uppercase tracking-widest transition-all">
                        02. Mapel & Sertifikasi
                    </button>
                </div>

                {{-- Form Content --}}
                <form :action="formAction" method="POST" class="flex-1 flex flex-col h-full max-h-full overflow-hidden">
                    @csrf
                    <template x-if="isEdit">
                        @method('PUT')
                    </template>

                    {{-- Scrollable Body --}}
                    <div class="flex-1 overflow-y-auto custom-scrollbar">
                        {{-- TAB 1: Identitas Kepegawaian --}}
                        <div x-show="activeTab === 'identitas'" class="p-8 space-y-8">
                            {{-- Baris 1: Status Kepegawaian, Username, NUPTK, DUK --}}
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 items-end">
                                {{-- a. Radio ASN --}}
                                <div>
                                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3 ml-1">Status Kepegawaian</label>
                                    <div class="flex flex-wrap items-center gap-3 bg-gray-50/50 p-2 rounded-xl border border-gray-100">
                                        <label class="flex items-center cursor-pointer group">
                                            <input type="radio" name="status_pegawai" value="PNS" 
                                                x-model="formData.status_pegawai" 
                                                class="w-4 h-4 text-indigo-600 border-gray-300 focus:ring-indigo-500">
                                            <span class="ml-2 text-[11px] font-bold text-gray-600 group-hover:text-indigo-600 transition-colors uppercase">PNS</span>
                                        </label>
                                        <label class="flex items-center cursor-pointer group">
                                            <input type="radio" name="status_pegawai" value="PPPK" 
                                                x-model="formData.status_pegawai" 
                                                class="w-4 h-4 text-indigo-600 border-gray-300 focus:ring-indigo-500">
                                            <span class="ml-2 text-[11px] font-bold text-gray-600 group-hover:text-indigo-600 transition-colors uppercase"> PPPK</span>
                                        </label>
                                        <label class="flex items-center cursor-pointer group">
                                            <input type="radio" name="status_pegawai" value="NON_ASN" 
                                                x-model="formData.status_pegawai" 
                                                class="w-4 h-4 text-indigo-600 border-gray-300 focus:ring-indigo-500">
                                            <span class="ml-2 text-[11px] font-bold text-gray-600 group-hover:text-indigo-600 transition-colors uppercase tracking-widest">Non ASN</span>
                                        </label>
                                    </div>
                                </div>
                                {{-- b. Username (NIP/NIK) --}}
                                <div class="md:col-span-1">
                                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1" 
                                        x-text="(formData.status_pegawai === 'PNS' || formData.status_pegawai === 'PPPK') ? 'NIP' : 'NIK'"></label>
                                    <input type="text" name="username" x-model="formData.username" required
                                        class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:ring-indigo-500 focus:border-indigo-500 text-sm p-3 font-mono transition-all">
                                </div>
                                {{-- c. NUPTK --}}
                                <div>
                                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">NUPTK/PegID</label>
                                    <input type="text" name="nuptk" x-model="formData.nuptk"
                                        class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:ring-indigo-500 focus:border-indigo-500 text-sm p-3 transition-all">
                                </div>
                                {{-- d. DUK --}}
                                <div>
                                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">DUK (No. Urut)</label>
                                    <input type="number" name="duk" x-model="formData.duk"
                                        class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:ring-indigo-500 focus:border-indigo-500 text-sm p-3 transition-all">
                                </div>
                            </div>

                            {{-- Baris 2: Nama Lengkap, Gelar Depan, Gelar Belakang --}}
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                                {{-- a. Nama Lengkap --}}
                                <div class="md:col-span-2">
                                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Nama Lengkap (Tanpa Gelar)</label>
                                    <input type="text" name="nama_guru" x-model="formData.nama_guru" required
                                        class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:ring-indigo-500 focus:border-indigo-500 text-sm p-3 font-bold transition-all">
                                </div>
                                {{-- b. Gelar Depan --}}
                                <div>
                                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Gelar Depan</label>
                                    <input type="text" name="gelar_depan" x-model="formData.gelar_depan" placeholder="Drs."
                                        class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:ring-indigo-500 focus:border-indigo-500 text-sm p-3 transition-all">
                                </div>
                                {{-- c. Gelar Belakang --}}
                                <div>
                                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Gelar Belakang</label>
                                    <input type="text" name="gelar_belakang" x-model="formData.gelar_belakang" placeholder="M.Pd."
                                        class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:ring-indigo-500 focus:border-indigo-500 text-sm p-3 transition-all">
                                </div>
                            </div>

                            {{-- Baris 3: KG, Jabatan, Golongan --}}
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                                {{-- a. KG --}}
                                <div>
                                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Kode Guru</label>
                                    <input type="text" name="kode_guru" x-model="formData.kode_guru" required maxlength="2" placeholder="KG"
                                        class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:ring-indigo-500 focus:border-indigo-500 text-sm p-3 font-black text-center transition-all uppercase">
                                </div>
                                {{-- b. Jabatan --}}
                                <div class="md:col-span-2">
                                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Jabatan</label>
                                    <input type="text" name="jabatan" x-model="formData.jabatan" placeholder="Guru Ahli Pertama - Guru TIK"
                                        class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:ring-indigo-500 focus:border-indigo-500 text-sm p-3 transition-all">
                                </div>
                                {{-- c. Golongan --}}
                                <div>
                                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Golongan / Grade</label>
                                    <select name="golongan" x-model="formData.golongan"
                                        class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:ring-indigo-500 focus:border-indigo-500 text-sm p-3 transition-all">
                                        <option value="">-- Pilih --</option>
                                        @foreach(['III/a', 'III/b', 'III/c', 'III/d', 'IV/a', 'IV/b', 'IV/c','IX', 'GTT'] as $gol)
                                            <option value="{{ $gol }}">{{ $gol }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        {{-- TAB 2: Kompetensi & Sertifikasi --}}
                        <div x-show="activeTab === 'kompetensi'" class="p-8 flex flex-col md:flex-row gap-8">
                            {{-- Kolom 1: Profil Kompetensi --}}
                            <div class="flex-1 space-y-6">
                                {{-- a. Mapel Ijazah (Combined) --}}
                                <div>
                                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Mapel Ijazah (S1)</label>
                                    <select name="ijazah_selection" x-model="formData.ijazah_selection"
                                        class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:ring-indigo-500 focus:border-indigo-500 text-sm p-3 transition-all">
                                        <option value="">— Pilih Mapel Ijazah —</option>
                                        @foreach($rumpuns as $rumpun)
                                            <option value="rumpun_{{ $rumpun->id }}">{{ $rumpun->nama_rumpun }}</option>
                                        @endforeach
                                        @foreach($mapels as $mapel)
                                            <option value="mapel_{{ $mapel->id }}">{{ $mapel->nama_mapel }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- b. Radio Tersertifikasi --}}
                                <div class="bg-gray-50 p-4 rounded-2xl border border-gray-100">
                                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3 ml-1">Tersertifikasi (TPG)</label>
                                    <div class="flex items-center gap-6">
                                        <label class="flex items-center cursor-pointer group">
                                            <input type="radio" name="status_sertifikasi" value="1" 
                                                x-model.number="formData.status_sertifikasi" 
                                                class="w-4 h-4 text-green-600 border-gray-300 focus:ring-green-500">
                                            <span class="ml-2 text-sm font-bold text-gray-600 group-hover:text-green-600 transition-colors">Ya</span>
                                        </label>
                                        <label class="flex items-center cursor-pointer group">
                                            <input type="radio" name="status_sertifikasi" value="0" 
                                                x-model.number="formData.status_sertifikasi" 
                                                @change="formData.mapel_sertifikasi_id = ''"
                                                class="w-4 h-4 text-gray-400 border-gray-300 focus:ring-gray-500">
                                            <span class="ml-2 text-sm font-bold text-gray-600 group-hover:text-gray-400 transition-colors">Tidak</span>
                                        </label>
                                    </div>

                                    {{-- c. Mapel Sertifikasi --}}
                                    <div x-show="formData.status_sertifikasi" x-collapse class="mt-4 pt-4 border-t border-gray-200">
                                        <label class="block text-[10px] font-black text-indigo-500 uppercase tracking-widest mb-2 ml-1">Mapel Sertifikasi</label>
                                        <select name="mapel_sertifikasi_id" x-model="formData.mapel_sertifikasi_id"
                                            class="w-full rounded-xl border-indigo-200 bg-white focus:ring-indigo-500 focus:border-indigo-500 text-sm p-3 transition-all shadow-sm">
                                            <option value="">-- Pilih Mapel Sertifikasi --</option>
                                            @foreach($mapels as $m)
                                                <option value="{{ $m->id }}">{{ $m->nama_mapel }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="bg-indigo-50 p-4 rounded-2xl border border-indigo-100 mt-4">
                                    <label class="block text-[10px] font-black text-indigo-400 uppercase tracking-widest mb-3 ml-1">Tugas Utama BK</label>
                                    <label class="flex items-center cursor-pointer group">
                                        <input type="checkbox" name="is_bk" value="1" 
                                            x-model.number="formData.is_bk" 
                                            class="w-5 h-5 text-indigo-600 border-gray-300 rounded-lg focus:ring-indigo-500">
                                        <span class="ml-3 text-sm font-bold text-gray-700 group-hover:text-indigo-600 transition-colors">Guru Bimbingan dan Konseling (BK)</span>
                                    </label>
                                    <p class="text-[10px] text-indigo-400 mt-2 ml-8 leading-tight italic">* Jika dicentang, guru secara otomatis dianggap memiliki beban 24 Jam Bimbingan di semua laporan.</p>
                                </div>
                            </div>

                            {{-- Kolom 2: Daftar Mapel Diampu (LIMIT 5 ITEMS + INTERNAL SCROLL) --}}
                            <div class="flex-1 flex flex-col bg-gray-50/50 rounded-2xl border border-gray-100 overflow-hidden h-[280px]">
                                <div class="p-4 bg-gray-100/50 border-b border-gray-100 shrink-0">
                                    <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest">Pilih Mapel Diampu</label>
                                </div>
                                <div class="flex-1 overflow-y-auto p-4 space-y-2 custom-scrollbar">
                                    @foreach($mapels as $m)
                                        <label class="flex items-center p-3 bg-white rounded-xl border border-gray-100 hover:border-indigo-200 hover:shadow-sm transition-all cursor-pointer group">
                                            <input type="checkbox" name="mapel_diampu[]" value="{{ $m->id }}"
                                                x-model="formData.mapel_diampu"
                                                class="w-5 h-5 text-indigo-600 rounded-lg border-gray-300 focus:ring-indigo-500 transition-all">
                                            <span class="ml-3 text-sm font-bold text-gray-700 group-hover:text-indigo-700 transition-colors">{{ $m->nama_mapel }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Fixed Footer (Following Tambah Tugas Mengajar Pattern) --}}
                    <div class="px-5 pb-5 pt-2 flex justify-end gap-2 bg-white shrink-0">
                        <button type="button" @click="modalOpen = false"
                            class="px-5 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-600 hover:bg-gray-50 transition">
                            Batal
                        </button>
                        <button type="submit"
                            class="px-6 py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 transition"
                            x-text="isEdit ? 'Simpan Perubahan' : 'Simpan'"></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection