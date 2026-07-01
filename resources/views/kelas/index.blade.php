@extends('layouts.app')
@section('header', 'Master Rombel')
@section('content')

    <div x-data="{ 
                    modalOpen: false, 
                    modalEditOpen: false,
                    mode: 'manual',
                    isEdit: false,
                    formAction: '{{ route('kelas.store') }}',
                    formData: {
                        id: '',
                        tingkat: 'VII',
                        nama_kelas: ''
                    },
                    openAdd() {
                        this.isEdit = false;
                        this.mode = 'manual';
                        this.formAction = '{{ route('kelas.store') }}';
                        this.formData = {
                            id: '',
                            tingkat: 'VII',
                            nama_kelas: ''
                        };
                        this.modalOpen = true;
                    },
                    openEdit(kelas) {
                        this.isEdit = true;
                        this.mode = 'manual';
                        this.formAction = '/master/kelas/' + kelas.id;
                        this.formData = {
                            id: kelas.id,
                            tingkat: kelas.tingkat,
                            nama_kelas: kelas.nama_kelas
                        };
                        this.modalOpen = true;
                    }
                }">
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Daftar Rombongan Belajar</h2>
                <p class="text-gray-600 mt-1 text-sm">Manajemen rombongan belajar berdasarkan tingkat kelas.</p>
            </div>
            <button @click="openAdd()"
                class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2.5 px-5 rounded-xl shadow-sm transition inline-flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Tambah Kelas
            </button>
        </div>

        @if(session('success'))
            <div class="mb-4 bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded shadow-sm">
                <p class="font-bold">Behasil</p>
                <p>{{ session('success') }}</p>
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

        @php
            $grouped = $kelas->groupBy('tingkat');
            $tingkatOrder = ['VII', 'VIII', 'IX'];
        @endphp

        <div class="space-y-4">
            @foreach($tingkatOrder as $tingkat)
                @if(isset($grouped[$tingkat]))
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="bg-indigo-50 border-b border-indigo-100 px-5 py-3 flex justify-between items-center">
                            <h3 class="font-bold text-indigo-900">Kelas {{ $tingkat }} — {{ $grouped[$tingkat]->count() }} Rombel
                            </h3>
                        </div>
                        <div class="flex flex-wrap gap-3 p-5">
                            @foreach($grouped[$tingkat] as $k)
                                <div class="flex items-center gap-2 bg-gray-50 border border-gray-200 rounded-lg px-4 py-2.5">
                                    <span class="font-bold text-gray-800 text-sm">{{ $k->nama_kelas }}</span>
                                    <div class="flex items-center gap-1.5 ml-2 border-l border-gray-200 pl-2">
                                        <button @click="openEdit({{ json_encode($k) }})"
                                            class="text-indigo-500 hover:text-indigo-700 transition-colors" title="Edit Kelas">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <form action="{{ route('kelas.destroy', $k->id) }}" method="POST"
                                            data-confirm="Hapus kelas ini? Penghapusan akan menghapus semua jadwal terkait.">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-red-400 hover:text-red-600 transition-colors"
                                                title="Hapus Kelas">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v2m3 4h.01" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach

            @if($kelas->isEmpty())
                <div class="bg-white rounded-xl border border-gray-100 p-10 text-center text-gray-400 italic">
                    Belum ada data kelas. Klik "Tambah Kelas" untuk membuat massal atau manual.
                </div>
            @endif
        </div>

        @if($kelas->hasPages())
            <div class="mt-6 bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                {{ $kelas->links() }}
            </div>
        @endif

        {{-- MODAL DATA KELAS (Using Guru Layout as standard) --}}
        <div x-show="modalOpen" x-cloak
            class="fixed inset-0 bg-black/60 z-[100] flex items-center justify-center p-4 backdrop-blur-sm"
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

            <div @click.away="modalOpen = false"
                class="bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] flex flex-col overflow-hidden"
                x-transition:enter="transition ease-out duration-300 transform"
                x-transition:enter-start="scale-95 opacity-0" x-transition:enter-end="scale-100 opacity-100">

                {{-- Header --}}
                <div class="flex items-center justify-between p-6 border-b border-gray-100">
                    <div>
                        <h3 class="font-bold text-gray-900 text-xl"
                            x-text="isEdit ? 'Edit Data Rombel' : 'Tambah Rombel Baru'"></h3>
                        <p class="text-xs text-gray-500 mt-1"
                            x-text="isEdit ? 'Ubah informasi rombongan belajar.' : 'Tambahkan rombongan belajar secara tunggal atau massal.'">
                        </p>
                    </div>
                    <button @click="modalOpen = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Tab Mode (Only shown when adding new) --}}
                <div x-show="!isEdit" class="flex gap-8 border-b border-gray-50 px-8 py-1 bg-gray-50/50">
                    <button type="button" @click="mode = 'manual'"
                        :class="mode === 'manual' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-400 hover:text-gray-600'"
                        class="py-3 border-b-2 text-xs font-black uppercase tracking-widest transition-all">
                        Satu Rombel
                    </button>
                    <button type="button" @click="mode = 'massal'"
                        :class="mode === 'massal' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-400 hover:text-gray-600'"
                        class="py-3 border-b-2 text-xs font-black uppercase tracking-widest transition-all">
                        Buat Massal
                    </button>
                </div>

                {{-- Form Content --}}
                <form :action="formAction" method="POST" class="flex-1 flex flex-col h-full max-h-full overflow-hidden">
                    @csrf
                    <template x-if="isEdit">
                        @method('PUT')
                    </template>

                    <div class="flex-1 overflow-y-auto custom-scrollbar p-8">
                        <div class="space-y-6">
                            {{-- Mode Manual / Edit --}}
                            <div x-show="mode === 'manual' || isEdit" class="space-y-6">
                                <div>
                                    <label
                                        class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Tingkat
                                        Kelas <span class="text-red-500">*</span></label>
                                    <select name="tingkat" x-model="formData.tingkat"
                                        class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:ring-indigo-500 focus:border-indigo-500 text-sm p-3 transition-all"
                                        :required="mode === 'manual' || isEdit">
                                        <option value="VII">Kelas VII (Tujuh)</option>
                                        <option value="VIII">Kelas VIII (Delapan)</option>
                                        <option value="IX">Kelas IX (Sembilan)</option>
                                    </select>
                                </div>
                                <div>
                                    <label
                                        class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Nama
                                        Rombel <span class="text-red-500">*</span></label>
                                    <input type="text" name="nama_kelas" x-model="formData.nama_kelas"
                                        placeholder="Contoh: Kelas VII.1"
                                        class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:ring-indigo-500 focus:border-indigo-500 text-sm p-3 font-bold transition-all"
                                        :required="mode === 'manual' || isEdit">
                                </div>
                            </div>

                            {{-- Mode Massal --}}
                            <div x-show="mode === 'massal' && !isEdit" class="space-y-6" style="display: none;">
                                <input type="hidden" name="bulk_create" value="1" :disabled="mode === 'manual' || isEdit">
                                <div class="bg-indigo-50/50 p-4 rounded-xl border border-indigo-100 flex gap-3">
                                    <svg class="w-5 h-5 text-indigo-600 flex-shrink-0 mt-0.5" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <p class="text-xs text-indigo-800 leading-relaxed">Pilih tingkat kelas dan masukkan
                                        jumlah rombel yang diinginkan, sistem akan otomatis membuatkannya (contoh:
                                        Kelas
                                        VII.1 - VII.6).</p>
                                </div>
                                <div>
                                    <label
                                        class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Tingkat
                                        Kelas <span class="text-red-500">*</span></label>
                                    <select name="bulk_tingkat" x-model="formData.tingkat"
                                        class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:ring-indigo-500 focus:border-indigo-500 text-sm p-3 transition-all"
                                        :required="mode === 'massal'" :disabled="mode === 'manual' || isEdit">
                                        <option value="VII">Kelas VII (Tujuh)</option>
                                        <option value="VIII">Kelas VIII (Delapan)</option>
                                        <option value="IX">Kelas IX (Sembilan)</option>
                                    </select>
                                </div>
                                <div>
                                    <label
                                        class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Jumlah
                                        Rombel <span class="text-red-500">*</span></label>
                                    <input type="number" name="bulk_jumlah" min="1" max="20" placeholder="Maks. 6 Rombel"
                                        class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:ring-indigo-500 focus:border-indigo-500 text-sm p-3 transition-all"
                                        :required="mode === 'massal'" :disabled="mode === 'manual' || isEdit">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Fixed Footer --}}
                    <div class="px-5 pb-5 pt-2 flex justify-end gap-2 bg-white shrink-0 border-t border-gray-50 mt-auto">
                        <button type="button" @click="modalOpen = false"
                            class="px-5 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-600 hover:bg-gray-50 transition">Batal</button>
                        <button type="submit"
                            class="px-6 py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 transition"
                            x-text="isEdit ? 'Simpan Perubahan' : 'Simpan Rombel'"></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection