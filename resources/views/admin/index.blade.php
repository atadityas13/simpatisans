@extends('layouts.app')
@section('header', 'Pengaturan Admin')
@section('content')

    <div x-data="{ 
        modalOpen: false, 
        isEdit: false,
        formAction: '{{ route('admin.store') }}',
        formData: {
            id: '',
            username: '',
            nama_lengkap: '',
            role: 'admin_kurikulum',
            jabatan: '',
            foto: ''
        },
        openAdd() {
            this.isEdit = false;
            this.formAction = '{{ route('admin.store') }}';
            this.formData = {
                id: '',
                username: '',
                nama_lengkap: '',
                role: 'admin_kurikulum',
                jabatan: '',
                foto: ''
            };
            this.modalOpen = true;
        },
        openEdit(admin) {
            this.isEdit = true;
            this.formAction = '/pengaturan/admin/' + admin.id;
            this.formData = {
                id: admin.id,
                username: admin.username,
                nama_lengkap: admin.nama_lengkap,
                role: admin.role,
                jabatan: admin.jabatan,
                foto: admin.foto
            };
            this.modalOpen = true;
        }
    }">
        <!-- HEADER SECTION -->
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Daftar Admin Sistem</h2>
                <p class="text-gray-600 mt-1 text-sm">Kelola akses pengguna untuk SIMPATISANS.</p>
            </div>
            <button @click="openAdd()"
                class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2.5 px-5 rounded-xl shadow-sm transition inline-flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Tambah Admin
            </button>
        </div>

        @if(session('success'))
            <div class="mb-4 bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded shadow-sm">
                <p class="font-bold">Berhasil</p>
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

        <!-- TABLE SECTION -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full whitespace-nowrap text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            <th class="px-6 py-4 w-12">Foto</th>
                            <th class="px-6 py-4">NIP / Username</th>
                            <th class="px-6 py-4">Nama Lengkap</th>
                            <th class="px-6 py-4">Jabatan</th>
                            <th class="px-6 py-4 text-center">Role</th>
                            <th class="px-6 py-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($users as $user)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4">
                                    @if($user->foto)
                                        <img src="{{ Storage::url($user->foto) }}" alt="Foto {{ $user->nama_lengkap }}" class="w-10 h-10 rounded-full object-cover border border-gray-200">
                                    @else
                                        <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center border border-gray-200">
                                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 font-semibold text-gray-900">{{ $user->username }}</td>
                                <td class="px-6 py-4">{{ $user->nama_lengkap }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ $user->jabatan ?? '-' }}</td>
                                <td class="px-6 py-4 text-center">
                                    @if($user->role === 'super_admin')
                                        <span class="px-2.5 py-1 rounded-md bg-purple-50 text-purple-700 text-[10px] font-bold uppercase tracking-widest">
                                            Super Admin
                                        </span>
                                    @else
                                        <span class="px-2.5 py-1 rounded-md bg-emerald-50 text-emerald-700 text-[10px] font-bold uppercase tracking-widest">
                                            Admin Kurikulum
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    <button @click="openEdit({{ json_encode($user) }})"
                                        class="text-indigo-600 hover:text-indigo-900 transition-colors inline-block"
                                        title="Edit Admin">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <form action="{{ route('admin.destroy', $user->id) }}" method="POST" class="inline-block"
                                        onsubmit="return confirm('Hapus Admin ini? {{ $user->role === 'super_admin' ? 'Peringatan: Ini adalah Super Admin!' : '' }}')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-500 hover:text-red-700 transition-colors"
                                            title="Hapus Admin">
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
                                <td colspan="6" class="px-6 py-10 text-center text-gray-400 italic">Belum ada data admin.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($users->hasPages())
                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50">
                    {{ $users->links() }}
                </div>
            @endif
        </div>

        {{-- MODAL FORM --}}
        <div x-show="modalOpen" x-cloak
            class="fixed inset-0 bg-black/60 z-[100] flex items-center justify-center p-4 backdrop-blur-sm"
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

            <div @click.away="modalOpen = false"
                class="bg-white rounded-2xl shadow-2xl max-w-md w-full flex flex-col overflow-hidden"
                x-transition:enter="transition ease-out duration-300 transform"
                x-transition:enter-start="scale-95 opacity-0" x-transition:enter-end="scale-100 opacity-100">

                <div class="flex items-center justify-between p-6 border-b border-gray-100">
                    <div>
                        <h3 class="font-bold text-gray-900 text-xl"
                            x-text="isEdit ? 'Edit Admin' : 'Tambah Admin'"></h3>
                        <p class="text-xs text-gray-500 mt-1">Isi formulir berikut dengan benar.</p>
                    </div>
                    <button @click="modalOpen = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form :action="formAction" method="POST" class="flex flex-col" enctype="multipart/form-data">
                    @csrf
                    <template x-if="isEdit">
                        @method('PUT')
                    </template>

                    <div class="p-8 space-y-5">
                        
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Username (NIP/ID) <span class="text-red-500">*</span></label>
                            <input type="text" name="username" x-model="formData.username"
                                class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:ring-indigo-500 focus:border-indigo-500 text-sm p-3 font-bold transition-all" required>
                        </div>
                        
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Nama Lengkap <span class="text-red-500">*</span></label>
                            <input type="text" name="nama_lengkap" x-model="formData.nama_lengkap"
                                class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:ring-indigo-500 focus:border-indigo-500 text-sm p-3 font-bold transition-all" required>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Role <span class="text-red-500">*</span></label>
                                <select name="role" x-model="formData.role"
                                    class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:ring-indigo-500 focus:border-indigo-500 text-sm p-3 font-bold transition-all" required>
                                    <option value="super_admin">Super Admin</option>
                                    <option value="admin_kurikulum">Admin Kurikulum</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Jabatan</label>
                                <input type="text" name="jabatan" x-model="formData.jabatan"
                                    class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:ring-indigo-500 focus:border-indigo-500 text-sm p-3 font-bold transition-all" placeholder="Opsional">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Password <span x-show="!isEdit" class="text-red-500">*</span></label>
                                <input type="password" name="password" :required="!isEdit"
                                    class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:ring-indigo-500 focus:border-indigo-500 text-sm p-3 font-bold transition-all placeholder-gray-400" 
                                    placeholder="Min 6 karakter">
                                <p x-show="isEdit" class="text-[10px] text-gray-400 mt-1 ml-1">Kosongkan jika tidak ubah password.</p>
                            </div>
                            
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Foto Profil</label>
                                <input type="file" name="foto" accept="image/*"
                                    class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:ring-indigo-500 focus:border-indigo-500 text-sm p-2 font-bold transition-all file:mr-4 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                            </div>
                        </div>
                        
                    </div>

                    <div class="px-5 pb-5 flex justify-end gap-2 bg-white border-t border-gray-50 pt-5 mt-auto">
                        <button type="button" @click="modalOpen = false"
                            class="px-5 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-600 hover:bg-gray-50 transition">Batal</button>
                        <button type="submit"
                            class="px-6 py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 transition shadow-sm"
                            x-text="isEdit ? 'Simpan Perubahan' : 'Simpan Admin'"></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection
