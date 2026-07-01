@extends('layouts.app')
@section('header', 'Master Mata Pelajaran')
@section('content')

    <div x-data="{ 
        modalOpen: false, 
        isEdit: false,
        rumpunMode: 'select',
        formAction: '{{ route('mapel.store') }}',
        formData: {
            id: '',
            nama_mapel: '',
            rumpun: [], // Now an array
            jtm_default: 2
        },
        openAdd() {
            this.isEdit = false;
            this.rumpunMode = 'select';
            this.formAction = '{{ route('mapel.store') }}';
            this.formData = {
                id: '',
                nama_mapel: '',
                rumpun: [],
                jtm_default: 2
            };
            this.modalOpen = true;
        },
        openEdit(mapel) {
            this.isEdit = true;
            this.rumpunMode = 'select';
            this.formAction = '/master/mapel/' + mapel.id;
            this.formData = {
                id: mapel.id,
                nama_mapel: mapel.nama_mapel,
                rumpun: mapel.rumpuns ? mapel.rumpuns.map(r => r.id.toString()) : [],
                jtm_default: mapel.jtm_default
            };
            this.modalOpen = true;
        }
    }">
        <!-- HEADER SECTION -->
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Daftar Mata Pelajaran</h2>
                <p class="text-gray-600 mt-1 text-sm">Spesifikasi, rumpun, dan standar beban ajar per rombel.</p>
            </div>
            <button @click="openAdd()"
                class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2.5 px-5 rounded-xl shadow-sm transition inline-flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Tambah Mapel
            </button>
        </div>

        @if(session('success'))
            <div class="mb-4 bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded shadow-sm">
                <p class="font-bold">Berhasil</p>
                <p>{{ session('success') }}</p>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-4 bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded shadow-sm">
                <p class="font-bold">Error</p>
                <p>{{ session('error') }}</p>
            </div>
        @endif

        @if($errors->any())
            <div class="mb-4 bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded shadow-sm">
                <p class="font-bold">Terjadi Kesalahan</p>
                <ul class="list-disc ml-5 text-sm mt-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- TABLE SECTION -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full whitespace-nowrap text-sm">
                    <thead>
                        <tr
                            class="bg-gray-50 border-b border-gray-200 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            <th class="px-6 py-4">Mata Pelajaran & Rumpun</th>
                            <th class="px-6 py-4 text-center">Standar JTM / Rombel</th>
                            <th class="px-6 py-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($mapels as $mapel)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4">
                                    <div class="flex items-start gap-1">
                                        <span class="font-bold text-gray-900">{{ $mapel->nama_mapel }}</span>
                                        @foreach($mapel->rumpuns as $r)
                                            <span class="px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-600 text-[10px] font-bold uppercase tracking-tighter"
                                                title="Rumpun: {{ $r->nama_rumpun }}">
                                                {{ $r->nama_rumpun }}
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center font-bold text-indigo-600">{{ $mapel->jtm_default }} Jam</td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    <button @click="openEdit({{ json_encode($mapel) }})"
                                        class="text-indigo-600 hover:text-indigo-900 transition-colors inline-block"
                                        title="Edit Mata Pelajaran">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <form action="{{ route('mapel.destroy', $mapel->id) }}" method="POST" class="inline-block"
                                        onsubmit="return confirm('Hapus Mapel ini?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-500 hover:text-red-700 transition-colors"
                                            title="Hapus Mapel">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v2m3 4h.01" />
                                            </svg>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-10 text-center text-gray-400 italic">Belum ada data mata
                                    pelajaran.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($mapels->hasPages())
                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50">
                    {{ $mapels->links() }}
                </div>
            @endif
        </div>

        {{-- MODAL DATA MATA PELAJARAN --}}
        <div x-show="modalOpen" x-cloak
            class="fixed inset-0 bg-black/60 z-[100] flex items-center justify-center p-4 backdrop-blur-sm"
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

            <div @click.away="modalOpen = false"
                class="bg-white rounded-2xl shadow-2xl max-w-md w-full flex flex-col overflow-hidden"
                x-transition:enter="transition ease-out duration-300 transform"
                x-transition:enter-start="scale-95 opacity-0" x-transition:enter-end="scale-100 opacity-100">

                {{-- Header --}}
                <div class="flex items-center justify-between p-6 border-b border-gray-100">
                    <div>
                        <h3 class="font-bold text-gray-900 text-xl"
                            x-text="isEdit ? 'Edit Mata Pelajaran' : 'Tambah Mata Pelajaran'"></h3>
                        <p class="text-xs text-gray-500 mt-1"
                            x-text="isEdit ? 'Ubah informasi dan beban ajar mapel.' : 'Tambahkan mata pelajaran baru.'"></p>
                    </div>
                    <button @click="modalOpen = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Form Content --}}
                <form :action="formAction" method="POST" class="flex flex-col">
                    @csrf
                    <template x-if="isEdit">
                        @method('PUT')
                    </template>

                    <div class="p-8 space-y-6">
                        <div>
                            <label
                                class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Nama
                                Mata Pelajaran <span class="text-red-500">*</span></label>
                            <input type="text" name="nama_mapel" x-model="formData.nama_mapel"
                                placeholder="Contoh: Informatika"
                                class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:ring-indigo-500 focus:border-indigo-500 text-sm p-3 font-bold transition-all"
                                required>
                        </div>

                        <div>
                            <label
                                class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Rumpun
                                Mapel</label>
                            <div x-show="rumpunMode === 'select'">
                                <select x-model="formData.rumpun" name="rumpun[]" multiple
                                    @change="if([...$event.target.selectedOptions].some(o => o.value === '__new__')) { rumpunMode = 'input'; formData.rumpun = []; $nextTick(() => $refs.rumpunInput.focus()); }"
                                    class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:ring-indigo-500 focus:border-indigo-500 text-sm p-3 transition-all min-h-[120px]"
                                    :disabled="rumpunMode !== 'select'">
                                    @foreach($rumpunList as $r)
                                        <option value="{{ $r->id }}">{{ $r->nama_rumpun }}</option>
                                    @endforeach
                                    <option value="__new__" class="text-indigo-600 font-bold">+ TAMBAH RUMPUN BARU...
                                    </option>
                                </select>
                            </div>
                            <div x-show="rumpunMode === 'input'" x-cloak>
                                <div class="flex gap-2">
                                    <input type="text" x-model="formData.rumpun[0]" name="rumpun[]" x-ref="rumpunInput"
                                        class="flex-1 rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:ring-indigo-500 focus:border-indigo-500 text-sm p-3 font-bold transition-all"
                                        placeholder="Ketik rumpun baru..." :disabled="rumpunMode !== 'input'">
                                    <button type="button" @click="rumpunMode = 'select'; formData.rumpun = '';"
                                        class="p-3 bg-gray-100 text-gray-500 rounded-xl hover:bg-gray-200 transition"
                                        title="Batal tambah rumpun">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <p class="text-[10px] text-gray-400 mt-1 ml-1">Gunakan <b>Ctrl + Klik</b> untuk memilih lebih dari satu rumpun.</p>
                            <p class="text-[10px] text-gray-400 mt-1 ml-1">Pilih rumpun apabila digunakan sebagai validasi
                                linearitas sertifikasi.</p>
                        </div>

                        <div>
                            <label
                                class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Beban
                                Ajar Per Rombel <span class="text-red-500">*</span></label>
                            <div class="relative w-32">
                                <input type="number" name="jtm_default" x-model="formData.jtm_default" min="1"
                                    class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:ring-indigo-500 focus:border-indigo-500 text-sm p-3 font-bold transition-all pr-12"
                                    required>
                                <span
                                    class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 text-xs font-bold">JTM</span>
                            </div>
                        </div>
                    </div>

                    {{-- Fixed Footer --}}
                    <div class="px-5 pb-5 flex justify-end gap-2 bg-white border-t border-gray-50 pt-5 mt-auto">
                        <button type="button" @click="modalOpen = false"
                            class="px-5 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-600 hover:bg-gray-50 transition">Batal</button>
                        <button type="submit"
                            class="px-6 py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 transition shadow-sm"
                            x-text="isEdit ? 'Simpan Perubahan' : 'Simpan Mapel'"></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection