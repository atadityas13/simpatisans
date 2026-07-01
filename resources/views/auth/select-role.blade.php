<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pilih Akses | MTsN 11 Majalengka - SIMPATISANS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .glass { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1); }
        .glow-card:hover { box-shadow: 0 0 30px rgba(99, 102, 241, 0.3); border-color: rgba(99, 102, 241, 0.4); }
    </style>
</head>
<body class="bg-[#020617] text-slate-200 min-h-screen flex items-center justify-center p-6 sm:p-12">

    <!-- Background Decoration -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute top-[-10%] left-[-10%] w-[50%] h-[50%] bg-indigo-600 rounded-full blur-[150px] opacity-10"></div>
        <div class="absolute bottom-[-10%] right-[-10%] w-[50%] h-[50%] bg-blue-700 rounded-full blur-[150px] opacity-10"></div>
    </div>

    <div class="w-full max-w-4xl relative z-10 text-center">
        
        <div class="mb-12">
            <h1 class="text-4xl sm:text-5xl font-extrabold text-white mb-4 tracking-tight">Selamat Datang, {{ Auth::user()->nama_lengkap }}</h1>
            <p class="text-slate-500 text-lg font-medium max-w-2xl mx-auto">Silakan pilih hak akses yang ingin Anda gunakan untuk sesi ini di <span class="text-indigo-400 font-bold">MTsN 11 Majalengka</span>.</p>
        </div>

        <form action="{{ route('select-role.post') }}" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-8">
            @csrf
            
            <!-- ADMIN OPTION -->
            <button type="submit" name="role" value="admin" 
                class="group text-left p-8 rounded-[32px] bg-slate-900/60 border border-slate-800 transition-all duration-500 transform hover:-translate-y-2 glow-card flex flex-col justify-between h-80 overflow-hidden relative">
                
                <div class="relative z-10">
                    <div class="w-16 h-16 rounded-2xl bg-indigo-600 flex items-center justify-center mb-6 shadow-xl shadow-indigo-600/20 group-hover:scale-110 transition-transform">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    </div>
                    <h3 class="text-2xl font-extrabold text-white mb-3">Akses Administrator</h3>
                    <p class="text-slate-500 font-medium leading-relaxed">Kelola data master, pembagian tugas guru, penjadwalan, dan pengaturan sistem sekolah.</p>
                </div>

                <div class="relative z-10 flex items-center text-indigo-400 font-bold text-sm tracking-wide uppercase">
                    Pilih Mode Admin
                    <svg class="w-4 h-4 ml-2 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                </div>

                <!-- Subtle Icon Pattern -->
                <svg class="absolute -right-10 -bottom-10 w-48 h-48 text-indigo-600/5 rotate-12" fill="currentColor" viewBox="0 0 24 24"><path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path></svg>
            </button>

            <!-- GURU OPTION -->
            <button type="submit" name="role" value="guru" 
                class="group text-left p-8 rounded-[32px] bg-slate-900/60 border border-slate-800 transition-all duration-500 transform hover:-translate-y-2 glow-card flex flex-col justify-between h-80 overflow-hidden relative">
                
                <div class="relative z-10">
                    <div class="w-16 h-16 rounded-2xl bg-blue-600 flex items-center justify-center mb-6 shadow-xl shadow-blue-600/20 group-hover:scale-110 transition-transform">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </div>
                    <h3 class="text-2xl font-extrabold text-white mb-3">Akses Guru</h3>
                    <p class="text-slate-500 font-medium leading-relaxed">Lihat jadwal mengajar pribadi, informasi kelas, dan pengaturan profil pengajar Anda.</p>
                </div>

                <div class="relative z-10 flex items-center text-blue-400 font-bold text-sm tracking-wide uppercase">
                    Pilih Mode Guru
                    <svg class="w-4 h-4 ml-2 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                </div>

                <!-- Subtle Icon Pattern -->
                <svg class="absolute -right-10 -bottom-10 w-48 h-48 text-blue-600/5 rotate-12" fill="currentColor" viewBox="0 0 24 24"><path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
            </button>
        </form>

        <div class="mt-20">
            <p class="text-[10px] text-slate-700 uppercase tracking-[0.3em] font-black">SIMPATISANS v1.0 • MTsN 11 MAJALENGKA</p>
        </div>

    </div>

</body>
</html>
