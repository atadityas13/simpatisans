@extends('layouts.app')
@section('header', 'Manajemen Pengguna (Guru)')
@section('content')

<div x-data="{ 
    photoModalOpen: false, 
    approvalModalOpen: false,
    activeUser: null,
    activeRequest: null,
    showPlain: {}
}">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Akun Pengguna Guru</h2>
            <p class="text-gray-600 mt-1 text-sm">Kelola akses login, reset password, dan status aktifitas akun guru.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded shadow-sm">
            <p class="font-bold">Berhasil</p>
            <p>{{ session('success') }}</p>
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full whitespace-nowrap text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        <th class="px-6 py-4 w-12 text-center">Foto</th>
                        <th class="px-6 py-4">Username</th>
                        <th class="px-6 py-4">Nama Lengkap</th>
                        <th class="px-6 py-4">Jabatan</th>
                        <th class="px-6 py-4 text-center">Password</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($gurus as $guru)
                        @php $user = $guru->user; @endphp
                        <tr class="hover:bg-gray-50 transition border-b border-gray-50 last:border-0">
                            <td class="px-6 py-4">
                                <div class="flex justify-center">
                                    @if($user && $user->foto)
                                        <img src="{{ Storage::url($user->foto) }}" alt="Foto {{ $guru->nama_guru }}" class="w-10 h-10 rounded-full object-cover border border-gray-200">
                                    @else
                                        <div class="w-10 h-10 rounded-full bg-indigo-50 flex items-center justify-center border border-indigo-100 text-indigo-400">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-bold text-gray-900">{{ $guru->username }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-gray-900 font-medium">{{ $guru->nama_lengkap }}</div>
                            </td>
                            <td class="px-6 py-4 text-gray-600">
                                {{ $guru->jabatan ?? '-' }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($user)
                                    @if($user->plain_password)
                                        <div class="inline-flex items-center gap-2 bg-gray-50 px-3 py-1.5 rounded-lg border border-gray-100 shadow-inner">
                                            <span class="font-mono text-xs text-gray-600" x-text="showPlain['{{ $user->id }}'] ? '{{ $user->plain_password }}' : '••••••••'"></span>
                                            <button @click="showPlain['{{ $user->id }}'] = !showPlain['{{ $user->id }}']" class="text-gray-400 hover:text-indigo-600 focus:outline-none transition-colors">
                                                <svg x-show="!showPlain['{{ $user->id }}']" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                                <svg x-show="showPlain['{{ $user->id }}']" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"></path></svg>
                                            </button>
                                        </div>
                                    @else
                                        <span class="px-3 py-1 rounded-full bg-emerald-50 text-emerald-600 text-[10px] font-bold uppercase tracking-wider border border-emerald-100">✔ Sudah Diubah</span>
                                    @endif
                                @else
                                    <span class="text-gray-300 italic">Akun belum dibuat</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($user)
                                    @if($user->isSuperAdmin() || $user->isAdminKurikulum())
                                        <span class="px-3 py-1 rounded-full bg-indigo-100 text-indigo-700 text-[10px] font-black uppercase tracking-widest border border-indigo-200">ADMIN</span>
                                    @elseif($user->is_active)
                                        <span class="px-3 py-1 rounded-full bg-green-50 text-green-700 text-[10px] font-bold uppercase tracking-widest border border-green-100">AKTIF</span>
                                    @else
                                        <span class="px-3 py-1 rounded-full bg-red-50 text-red-700 text-[10px] font-bold uppercase tracking-widest border border-red-100">NON-AKTIF</span>
                                    @endif
                                @else
                                    <span class="px-3 py-1 rounded-full bg-gray-50 text-gray-400 text-[10px] font-bold uppercase tracking-widest border border-gray-100">TANPA AKUN</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right space-x-1">
                                @if(!$user)
                                    <form action="{{ route('pengguna.generate', $guru->id) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="bg-indigo-600 text-white text-xs font-bold px-4 py-2 rounded-lg hover:bg-indigo-700 transition shadow-sm inline-flex items-center">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                                            Generate
                                        </button>
                                    </form>
                                @else
                                    <div class="flex justify-end gap-1">
                                        <!-- RESET PASSWORD BUTTON (With Approval Details) -->
                                        @if($user->reset_requested_at)
                                            <button @click="approvalModalOpen = true; 
                                                        activeRequest = { 
                                                            id: '{{ $user->id }}', 
                                                            nama: '{{ $guru->nama_guru }}',
                                                            question: '{{ $user->security_question }}',
                                                            answer: '{{ $user->security_answer }}',
                                                            provided: '{{ $user->reset_answer_provided }}'
                                                        }"
                                                class="p-2 rounded-lg bg-amber-100 text-amber-700 hover:bg-amber-200 border border-amber-200"
                                                title="Setujui Permintaan Reset">
                                                <span class="flex items-center text-[10px] font-black uppercase tracking-tighter">
                                                    <svg class="w-4 h-4 mr-1 animate-spin-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                                    Approve Reset
                                                </span>
                                            </button>
                                        @else
                                            <form action="{{ route('pengguna.reset', $user->id) }}" method="POST" class="inline" data-confirm="Apakah Anda yakin ingin mereset password user ini ke default (NIP/NIK)?">
                                                @csrf
                                                <button type="submit" 
                                                    class="p-2 rounded-lg bg-gray-100 text-gray-500 hover:bg-gray-200"
                                                    title="Reset Password ke Default">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                                </button>
                                            </form>
                                        @endif

                                        <!-- TOGGLE STATUS BUTTON -->
                                        <form action="{{ route('pengguna.toggle', $user->id) }}" method="POST" class="inline">
                                            @csrf @method('PATCH')
                                            <button type="submit" 
                                                class="p-2 rounded-lg transition-all {{ $user->is_active ? 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200' : 'bg-red-100 text-red-700 hover:bg-red-200' }}"
                                                title="{{ $user->is_active ? 'Nonaktifkan Akun' : 'Aktifkan Akun' }}">
                                                @if($user->is_active)
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                @else
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                @endif
                                            </button>
                                        </form>

                                        <!-- EDIT PHOTO BUTTON -->
                                        <button @click="photoModalOpen = true; activeUser = { id: '{{ $user->id }}', nama: '{{ $guru->nama_guru }}' }" 
                                            class="p-2 bg-indigo-50 text-indigo-600 rounded-lg hover:bg-indigo-100 transition-all"
                                            title="Ganti Foto">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                        </button>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-20 text-center">
                                <div class="flex flex-col items-center">
                                    <svg class="w-16 h-16 text-gray-200 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                                    <p class="text-gray-400 italic">Belum ada data guru ditemukan.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100">
            {{ $gurus->links() }}
        </div>
    </div>

    <!-- PHOTO MODAL -->
    <div x-show="photoModalOpen" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" x-transition>
        <div @click.away="photoModalOpen = false" class="bg-white rounded-2xl w-full max-w-sm shadow-2xl overflow-hidden" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="scale-95 opacity-0">
            <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <div>
                    <h3 class="font-bold text-gray-900">Unggah Foto Profil</h3>
                    <p class="text-[10px] text-gray-500" x-text="activeUser ? activeUser.nama : ''"></p>
                </div>
                <button @click="photoModalOpen = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <form :action="activeUser ? '/config/pengguna/' + activeUser.id + '/photo' : ''" method="POST" enctype="multipart/form-data" class="p-8">
                @csrf
                <div class="space-y-4">
                    <div class="relative group">
                        <label class="block text-center border-2 border-dashed border-gray-200 rounded-2xl p-10 hover:border-indigo-400 hover:bg-indigo-50/30 transition-all cursor-pointer group-hover:shadow-inner">
                            <input type="file" name="foto" class="hidden" accept="image/*" required @change="filename = $event.target.files[0].name" x-data="{ filename: '' }">
                            <svg class="w-12 h-12 text-gray-300 mx-auto mb-3 transition-colors group-hover:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                            <span class="text-xs font-bold text-gray-400 block" x-text="filename || 'Klik untuk pilih foto'"></span>
                            <span class="text-[10px] text-gray-300 mt-2 block italic">JPEG/PNG, Maks. 2MB</span>
                        </label>
                    </div>
                </div>
                <div class="mt-8 flex gap-2">
                    <button type="button" @click="photoModalOpen = false" class="flex-1 px-4 py-2.5 border border-gray-200 rounded-xl text-xs font-bold text-gray-500 hover:bg-gray-50 transition">Batal</button>
                    <button type="submit" class="flex-1 px-4 py-2.5 bg-indigo-600 text-white rounded-xl text-xs font-bold hover:bg-indigo-700 transition shadow-indigo-100 shadow-xl">Unggah Sekarang</button>
                </div>
            </form>
        </div>
    </div>

    <!-- PASSOWRD RESET APPROVAL MODAL -->
    <div x-show="approvalModalOpen" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" x-transition>
        <div @click.away="approvalModalOpen = false" class="bg-white rounded-2xl w-full max-w-md shadow-2xl overflow-hidden" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="scale-95 opacity-0">
            <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-amber-50/50">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-amber-100 rounded-full flex items-center justify-center text-amber-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 leading-tight">Verifikasi Reset Password</h3>
                        <p class="text-[10px] text-amber-700 font-bold uppercase tracking-wider" x-text="activeRequest ? activeRequest.nama : ''"></p>
                    </div>
                </div>
                <button @click="approvalModalOpen = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <div class="p-6 space-y-5">
                <div class="bg-gray-50 rounded-xl p-4 border border-gray-100">
                    <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-2">Pertanyaan Keamanan User:</p>
                    <p class="text-sm font-bold text-gray-800 italic" x-text="activeRequest ? '“' + activeRequest.question + '”' : ''"></p>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-emerald-50 rounded-xl p-4 border border-emerald-100">
                        <p class="text-[9px] font-black text-emerald-600 uppercase tracking-widest mb-1">Jawaban Benar:</p>
                        <p class="text-xs font-bold text-emerald-800" x-text="activeRequest ? activeRequest.answer : ''"></p>
                    </div>
                    <div class="bg-blue-50 rounded-xl p-4 border border-blue-100 shadow-inner">
                        <p class="text-[9px] font-black text-blue-600 uppercase tracking-widest mb-1">Jawaban User:</p>
                        <p class="text-xs font-bold text-blue-800" x-text="activeRequest ? activeRequest.provided : ''"></p>
                    </div>
                </div>

                <div class="flex items-start gap-3 p-3 bg-red-50 rounded-lg border border-red-100">
                    <svg class="w-5 h-5 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <p class="text-[10px] text-red-700 leading-relaxed font-medium">
                        Setujui jika jawaban sudah sesuai. Password akan direset kembali ke **NIP/NIK** guru sebagai password default.
                    </p>
                </div>
            </div>

            <div class="p-4 bg-gray-50 border-t border-gray-100 flex gap-3">
                <button type="button" @click="approvalModalOpen = false" class="flex-1 px-4 py-2.5 border border-gray-200 rounded-xl text-xs font-bold text-gray-500 hover:bg-white transition shadow-sm">Batal</button>
                <form :action="activeRequest ? '/config/pengguna/' + activeRequest.id + '/reset' : ''" method="POST" class="flex-1">
                    @csrf
                    <button type="submit" class="w-full px-4 py-2.5 bg-amber-600 text-white rounded-xl text-xs font-bold hover:bg-amber-700 transition shadow-xl shadow-amber-200">Setujui & Reset</button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
.animate-spin-pulse { animation: pulse 1.5s cubic-bezier(0.4, 0, 0.6, 1) infinite; }
</style>

@endsection
