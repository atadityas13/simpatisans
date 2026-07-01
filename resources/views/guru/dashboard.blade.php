@extends('layouts.app')
@section('header', 'Dashboard Guru')
@section('content')

<div class="space-y-8">
    
    <!-- WELCOME CARD -->
    <div class="relative overflow-hidden bg-indigo-600 rounded-[32px] p-8 sm:p-12 text-white shadow-2xl shadow-indigo-200">
        <div class="relative z-10 flex flex-col md:flex-row justify-between items-center gap-8">
            <div class="text-center md:text-left">
                <h2 class="text-3xl sm:text-4xl font-extrabold mb-3">Selamat Datang, {{ Auth::user()->nama_lengkap }}!</h2>
                <p class="text-indigo-100 text-lg opacity-90 font-medium">Anda sedang masuk sebagai <span class="bg-white/20 px-3 py-1 rounded-lg">Guru</span> di MTsN 11 Majalengka.</p>
                <div class="mt-8 flex flex-wrap gap-4 justify-center md:justify-start">
                    <div class="bg-white/10 backdrop-blur-md px-6 py-3 rounded-2xl border border-white/10">
                        <p class="text-[10px] uppercase font-black tracking-widest text-indigo-200 mb-1">NIP / Username</p>
                        <p class="font-bold text-xl">{{ Auth::user()->username }}</p>
                    </div>
                </div>
            </div>
            
            <div class="w-48 h-48 sm:w-56 sm:h-56 relative group">
                @if(Auth::user()->foto)
                    <img src="{{ Storage::url(Auth::user()->foto) }}" class="w-full h-full object-cover rounded-[40px] shadow-2xl border-4 border-white/20 transform group-hover:scale-105 transition-transform duration-500">
                @else
                    <div class="w-full h-full bg-indigo-500 rounded-[40px] flex items-center justify-center border-4 border-white/20 shadow-2xl">
                        <svg class="w-24 h-24 text-white/50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    </div>
                @endif
                <!-- Abstract Glow -->
                <div class="absolute -inset-4 bg-white/20 blur-3xl rounded-full -z-10 group-hover:bg-white/30 transition-colors"></div>
            </div>
        </div>
        
        <!-- Decoration Icons -->
        <svg class="absolute -right-20 -bottom-20 w-96 h-96 text-white/5 opacity-10 rotate-12" fill="currentColor" viewBox="0 0 24 24"><path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
    </div>

    <!-- MAIN CONTENT GRID -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- PERSONAL SCHEDULE PREVIEW (Placeholder for now) -->
        <div class="lg:col-span-2 space-y-6">
            <div class="flex justify-between items-end px-2">
                <h3 class="text-2xl font-extrabold text-slate-900 tracking-tight">Jadwal Hari Ini</h3>
                <a href="#" class="text-sm font-bold text-indigo-600 hover:text-indigo-700 underline decoration-2 underline-offset-4 decoration-indigo-600/30">Lihat Semua Jadwal</a>
            </div>

            <div class="bg-white rounded-[32px] border border-slate-100 shadow-sm overflow-hidden p-4">
                <div class="flex flex-col items-center justify-center py-20 px-8 text-center bg-slate-50/50 rounded-[24px] border border-dashed border-slate-200">
                    <div class="w-20 h-20 bg-white rounded-2xl flex items-center justify-center shadow-md mb-6">
                        <svg class="w-10 h-10 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2-2v14a2 2 0 002 2z"></path></svg>
                    </div>
                    <h4 class="text-xl font-bold text-slate-800 mb-2">Belum Ada Jadwal Mengajar Aktif</h4>
                    <p class="text-slate-500 max-w-sm mx-auto">Admin belum merilis jadwal untuk semester ini. Silakan hubungi bagian kurikulum untuk informasi lebih lanjut.</p>
                </div>
            </div>
        </div>

        <!-- QUICK INFO / SIDEBAR -->
        <div class="space-y-6 text-sm font-medium">
             <div class="bg-white rounded-[32px] border border-slate-100 shadow-sm p-8">
                <h3 class="text-lg font-black uppercase tracking-widest text-slate-400 mb-6 border-b border-slate-50 pb-4">Status Pengajar</h3>
                <ul class="space-y-6">
                    <li class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <div>
                            <p class="text-slate-400 text-[10px] font-black uppercase tracking-wider">Status Akun</p>
                            <p class="text-slate-900 font-bold">Aktif</p>
                        </div>
                    </li>
                    <li class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        </div>
                        <div>
                            <p class="text-slate-400 text-[10px] font-black uppercase tracking-wider">Unit Kerja</p>
                            <p class="text-slate-900 font-bold">MTsN 11 Majalengka</p>
                        </div>
                    </li>
                </ul>
                
                <div class="mt-10 pt-6 border-t border-slate-50 text-center">
                    <button class="w-full py-4 px-6 bg-slate-50 text-slate-600 font-bold rounded-2xl hover:bg-slate-100 transition-colors border border-slate-100 flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        Lengkapi Profil
                    </button>
                    <p class="mt-4 text-[10px] text-slate-400 font-medium">Bantu kami memperlengkap data Anda.</p>
                </div>
            </div>
        </div>

    </div>
</div>

@endsection
