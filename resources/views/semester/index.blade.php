@extends('layouts.app')

@section('header', 'Pengaturan Semester')

@section('content')
    <div x-data="{ showAddModal: false, showEditModal: false, editData: {} }">
        <!-- Page Header -->
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Manajemen Semester</h2>
                <p class="text-gray-600 mt-1 text-sm">Kelola Semester Tahun ajaran dan periode aktif.</p>
            </div>
            <button @click="showAddModal = true"
                class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-5 rounded-lg shadow-sm transition inline-flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Tambah Semester
            </button>
        </div>

        <!-- Active Status Info -->
        @php $activeSem = $semesters->where('is_active', true)->first(); @endphp
        @if($activeSem)
            <div class="mb-6 bg-indigo-50 border border-indigo-100 p-4 rounded-xl flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-indigo-100 text-indigo-600 rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-indigo-400 uppercase tracking-wider">Periode Aktif Saat Ini</p>
                        <p class="text-indigo-900 font-bold">Tahun Ajaran {{ $activeSem->nama_tahun }} — Semester
                            {{ $activeSem->tipe }}
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Table List -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full whitespace-nowrap text-sm">
                    <thead>
                        <tr
                            class="bg-gray-50 border-b border-gray-200 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            <th class="px-6 py-4">Tahun Ajaran</th>
                            <th class="px-6 py-4">Semester</th>
                            <th class="px-6 py-4 text-center">Status</th>
                            <th class="px-6 py-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($semesters as $sem)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4">
                                    <span class="font-bold text-gray-900">{{ $sem->nama_tahun }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="px-2.5 py-1 rounded-md text-xs font-medium {{ $sem->tipe == 'Ganjil' ? 'bg-orange-50 text-orange-700 border border-orange-100' : 'bg-cyan-50 text-cyan-700 border border-cyan-100' }}">
                                        {{ $sem->tipe }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    @if($sem->is_active)
                                        <span
                                            class="px-2.5 py-1 bg-green-100 text-green-700 text-xs font-bold rounded-full border border-green-200 uppercase tracking-wider">Aktif</span>
                                    @else
                                        <span
                                            class="px-2.5 py-1 bg-gray-100 text-gray-500 text-xs font-medium rounded-full border border-gray-200 uppercase tracking-wider">Non-Aktif</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    @if(!$sem->is_active)
                                        <form action="{{ route('semester.activate', $sem->id) }}" method="POST" class="inline-block"
                                            data-confirm="Aktifkan semester ini?">
                                            @csrf
                                            <button type="submit"
                                                class="text-green-600 hover:text-green-900 transition-colors" title="Aktifkan Semester">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                            </button>
                                        </form>
                                    @endif

                                    <button
                                        @click="showEditModal = true; editData = { id: {{ $sem->id }}, nama_tahun: '{{ $sem->nama_tahun }}', tipe: '{{ $sem->tipe }}', is_active: {{ $sem->is_active ? 'true' : 'false' }} }"
                                        class="text-indigo-600 hover:text-indigo-900 transition-colors" title="Edit Detail">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>

                                    @if(!$sem->is_active)
                                        <form action="{{ route('semester.destroy', $sem->id) }}" method="POST" class="inline-block"
                                            data-confirm="Hapus semester ini?">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900 transition-colors" title="Hapus Permanen">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v2m3 4h.01"/></svg>
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal Tambah -->
        <div x-show="showAddModal" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm">
            <div @click.away="showAddModal = false" class="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                    <h3 class="font-bold text-gray-900">Tambah Semester Baru</h3>
                    <button @click="showAddModal = false" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form action="{{ route('semester.store') }}" method="POST" class="p-6 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1.5 ml-1">Tahun Ajaran</label>
                        <input type="text" name="nama_tahun" placeholder="2026/2027" required
                            class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1.5 ml-1">Tipe Semester</label>
                        <select name="tipe"
                            class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            <option value="Ganjil">Ganjil</option>
                            <option value="Genap">Genap</option>
                        </select>
                    </div>

                    <div class="p-3 bg-indigo-50 rounded-lg border border-indigo-100">
                        <label class="block text-xs font-bold text-indigo-700 uppercase mb-1">Salin Data</label>
                        <select name="clone_from_id"
                            class="w-full px-3 py-1.5 bg-white border border-indigo-200 rounded text-xs focus:ring-indigo-500">
                            <option value="">-- Tidak (Mulai dari Awal) --</option>
                            @foreach($semesters as $s)
                                <option value="{{ $s->id }}">Salin dari: {{ $s->nama_tahun }} - {{ $s->tipe }}</option>
                            @endforeach
                        </select>
                        <p class="text-[10px] text-indigo-500 mt-1 italic leading-tight">*Salin KBM, tugas, & struktur
                            jadwal.</p>
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="is_active" value="1" id="is_active_add"
                            class="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500">
                        <label for="is_active_add" class="text-sm font-medium text-gray-600">Jadikan Semester Aktif</label>
                    </div>

                    <button type="submit"
                        class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-bold shadow-sm transition">
                        Simpan Semester
                    </button>
                </form>
            </div>
        </div>

        <!-- Modal Edit -->
        <div x-show="showEditModal" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm">
            <div @click.away="showEditModal = false" class="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                    <h3 class="font-bold text-gray-900">Edit Periode</h3>
                    <button @click="showEditModal = false" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form :action="'{{ route('semester.index') }}/' + editData.id" method="POST" class="p-6 space-y-4">
                    @csrf @method('PUT')
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1.5 ml-1">Tahun Ajaran</label>
                        <input type="text" name="nama_tahun" x-model="editData.nama_tahun" required
                            class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1.5 ml-1">Tipe Semester</label>
                        <select name="tipe" x-model="editData.tipe"
                            class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            <option value="Ganjil">Ganjil</option>
                            <option value="Genap">Genap</option>
                        </select>
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="is_active" value="1" x-model="editData.is_active" id="is_active_edit"
                            class="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500">
                        <label for="is_active_edit" class="text-sm font-medium text-gray-600">Jadikan Semester Aktif</label>
                    </div>

                    <button type="submit"
                        class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-bold shadow-sm transition">
                        Perbarui Semester
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection