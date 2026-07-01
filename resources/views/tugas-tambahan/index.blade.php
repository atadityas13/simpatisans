@extends('layouts.app')
@section('header', 'Master Tugas Tambahan')
@section('content')

<div x-data="{ 
    modalOpen: false, 
    isEdit: false,
    formAction: '{{ route('tugas-tambahan.store') }}',
    formData: {
        id: '',
        nama_tugas: '',
        jtm_ekuivalen: ''
    },
    openAdd() {
        this.isEdit = false;
        this.formAction = '{{ route('tugas-tambahan.store') }}';
        this.formData = {
            id: '',
            nama_tugas: '',
            jtm_ekuivalen: ''
        };
        this.modalOpen = true;
    },
    openEdit(tugas) {
        this.isEdit = true;
        this.formAction = '/master/tugas-tambahan/' + tugas.id;
        this.formData = {
            id: tugas.id,
            nama_tugas: tugas.nama_tugas,
            jtm_ekuivalen: tugas.jtm_ekuivalen
        };
        this.modalOpen = true;
    }
}">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Tugas Tambahan & Ekuivalensi</h2>
            <p class="text-gray-600 mt-1 text-sm">Manajemen tugas tambahan dan ekuivalensi jam mengajar guru.</p>
        </div>
        <button @click="openAdd()"
            class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2.5 px-5 rounded-xl shadow-sm transition inline-flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            Tambah Tugas
        </button>
    </div>

    @if(session('success'))
    <div class="mb-4 bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded shadow-sm max-w-3xl">
        <p class="font-bold">Berhasil</p>
        <p>{{ session('success') }}</p>
    </div>
    @endif

    @if(session('error'))
    <div class="mb-4 bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded shadow-sm max-w-3xl">
        <p class="font-bold">Error</p>
        <p>{{ session('error') }}</p>
    </div>
    @endif

    @if($errors->any())
    <div class="mb-4 bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded shadow-sm max-w-3xl">
        <p class="font-bold">Terjadi Kesalahan</p>
        <ul class="list-disc ml-5 text-sm mt-1">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden max-w-3xl">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                    <th class="px-6 py-4">Nama Jabatan / Tugas</th>
                    <th class="px-6 py-4 text-center">Ekuivalen JTM</th>
                    <th class="px-6 py-4 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($tugases as $tugas)
                    <tr class="hover:bg-gray-50 transition {{ $tugas->isSystem() ? 'bg-amber-50/30' : '' }}">
                        <td class="px-6 py-4">
                            <span class="font-bold text-gray-900">{{ $tugas->nama_tugas }}</span>
                        </td>
                        <td class="px-6 py-4 text-center font-bold text-indigo-600">{{ $tugas->jtm_ekuivalen }} Jam</td>
                        <td class="px-6 py-4 text-right space-x-2">
                            @if($tugas->isSystem())
                                <span class="text-gray-300 text-xs italic">Terlindungi</span>
                            @else
                                <button @click="openEdit({{ json_encode($tugas) }})"
                                    class="text-indigo-600 hover:text-indigo-900 transition-colors inline-block" title="Edit Tugas">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                                <form action="{{ route('tugas-tambahan.destroy', $tugas->id) }}" method="POST" class="inline-block"
                                    data-confirm="Hapus tugas ini?">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-700 transition-colors"
                                        title="Hapus Tugas">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v2m3 4h.01" />
                                        </svg>
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-6 py-10 text-center text-gray-400 italic">Belum ada data.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if($tugases->hasPages())
            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50">
                {{ $tugases->links() }}
            </div>
        @endif
    </div>
    <p class="text-xs text-gray-400 mt-3">* Status ekuivalen ditentukan per guru saat mengatur Pembagian Tugas.</p>

    {{-- MODAL DATA TUGAS TAMBAHAN (Using Guru Layout as standard) --}}
    <div x-show="modalOpen" x-cloak class="fixed inset-0 bg-black/60 z-[100] flex items-center justify-center p-4 backdrop-blur-sm"
        x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        
        <div @click.away="modalOpen = false" 
            class="bg-white rounded-2xl shadow-2xl max-w-md w-full flex flex-col overflow-hidden"
            x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="scale-95 opacity-0" x-transition:enter-end="scale-100 opacity-100">
            
            {{-- Header --}}
            <div class="flex items-center justify-between p-6 border-b border-gray-100">
                <div>
                    <h3 class="font-bold text-gray-900 text-xl" x-text="isEdit ? 'Edit Tugas Tambahan' : 'Tambah Tugas Baru'"></h3>
                    <p class="text-xs text-gray-500 mt-1" x-text="isEdit ? 'Ubah informasi tugas tambahan.' : 'Tambahkan jenis tugas tambahan baru.'"></p>
                </div>
                <button @click="modalOpen = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
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
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Nama Jabatan / Tugas <span class="text-red-500">*</span></label>
                        <input type="text" name="nama_tugas" x-model="formData.nama_tugas" placeholder="Contoh: Kepala Perpustakaan" class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:ring-indigo-500 focus:border-indigo-500 text-sm p-3 font-bold transition-all" required>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Ekuivalen (JTM) <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input type="number" name="jtm_ekuivalen" x-model="formData.jtm_ekuivalen" min="0" placeholder="0" class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:ring-indigo-500 focus:border-indigo-500 text-sm p-3 transition-all pr-12" required>
                            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 text-xs font-bold uppercase">Jam</span>
                        </div>
                    </div>
                </div>

                {{-- Fixed Footer --}}
                <div class="px-5 pb-5 flex justify-end gap-2 bg-white border-t border-gray-50 pt-5 mt-auto">
                    <button type="button" @click="modalOpen = false" class="px-5 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-600 hover:bg-gray-50 transition">Batal</button>
                    <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 transition shadow-sm" x-text="isEdit ? 'Simpan Perubahan' : 'Simpan Tugas'"></button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection