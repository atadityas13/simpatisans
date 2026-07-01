<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aktivasi Akun | MTsN 11 Majalengka - SIMPATISANS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .glass {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .glow-input:focus {
            box-shadow: 0 0 20px rgba(99, 102, 241, 0.2);
            border-color: rgba(99, 102, 241, 0.4);
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }
        .animate-shake { animation: shake 0.5s cubic-bezier(.36,.07,.19,.97) both; }
    </style>
</head>

<body
    class="bg-[#020617] text-slate-200 min-h-screen flex items-center justify-center p-6 sm:p-12 relative overflow-x-hidden">

    <!-- Background Decoration -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div
            class="absolute top-[-10%] left-[-10%] w-[50%] h-[50%] bg-indigo-600 rounded-full blur-[150px] opacity-[0.07]">
        </div>
        <div
            class="absolute bottom-[-10%] right-[-10%] w-[50%] h-[50%] bg-blue-700 rounded-full blur-[150px] opacity-[0.07]">
        </div>
    </div>

    <div class="w-full max-w-4xl relative z-10 text-center">

        <div class="mb-10 animate-fade-in">
            <h1 class="text-4xl sm:text-5xl font-extrabold text-white mb-4 tracking-tight">Konfigurasi Keamanan Akun
            </h1>
            <p class="text-slate-500 text-lg font-medium max-w-2xl mx-auto">Selamat datang, <span
                    class="text-white font-bold">{{ $user->nama_lengkap }}</span>. Silakan lengkapi pengaturan keamanan
                berikut sebelum menggunakan <span class="text-indigo-400 font-bold">SIMPATISANS</span>.</p>
        </div>

        <!-- ERROR ALERT BANNER -->
        @if($errors->any())
            <div class="mb-10 bg-red-600 border border-red-500 rounded-[32px] overflow-hidden shadow-2xl shadow-red-900/40 animate-shake">
                <div class="px-8 py-6 flex items-start gap-6">
                    <div class="w-12 h-12 bg-white/20 rounded-2xl flex items-center justify-center shrink-0 mt-1">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                    <div class="text-left">
                        <h4 class="text-white font-black text-base uppercase tracking-widest mb-1">Gagal Mengaktifkan Akun!</h4>
                        <ul class="text-red-100 text-xs font-bold space-y-1 opacity-95">
                            @foreach ($errors->all() as $error)
                                <li class="flex items-center gap-2">
                                    <span class="w-2 h-2 bg-white/40 rounded-full shrink-0"></span>
                                    <span>{{ $error }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <form action="{{ route('first-login.post') }}" method="POST"
            class="grid grid-cols-1 md:grid-cols-2 gap-8 text-left">
            @csrf

            <!-- PASSWORD CONFIG CARD -->
            <div class="glass p-8 rounded-[32px] bg-slate-900/40 relative overflow-hidden group">
                <div class="relative z-10">
                    <div class="flex items-center gap-4 mb-8">
                        <div
                            class="w-12 h-12 rounded-2xl bg-indigo-600 flex items-center justify-center shadow-lg shadow-indigo-600/20">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z">
                                </path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-white">Ubah Password</h3>
                            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest mt-0.5">Kredensial
                                Login</p>
                        </div>
                    </div>

                    <div class="space-y-5">
                        <div>
                            <label
                                class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-2 block ml-1">Password
                                Saat Ini</label>
                            <input type="password" name="current_password" required placeholder="NIP/NIK Anda"
                                class="w-full bg-slate-950/50 border border-slate-800 rounded-2xl py-3.5 px-5 text-white placeholder-slate-600 focus:outline-none glow-input transition-all text-sm font-medium">
                        </div>
                        <div>
                            <label
                                class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-2 block ml-1">Password
                                Baru</label>
                            <input type="password" name="password" required placeholder="Minimal 6 karakter"
                                class="w-full bg-slate-950/50 border border-slate-800 rounded-2xl py-3.5 px-5 text-white placeholder-slate-600 focus:outline-none glow-input transition-all text-sm font-medium">
                        </div>
                        <div>
                            <label
                                class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-2 block ml-1">Konfirmasi
                                Password</label>
                            <input type="password" name="password_confirmation" required
                                placeholder="Ulangi password baru"
                                class="w-full bg-slate-950/50 border border-slate-800 rounded-2xl py-3.5 px-5 text-white placeholder-slate-600 focus:outline-none glow-input transition-all text-sm font-medium">
                        </div>
                    </div>
                </div>
                <!-- Subtle Icon Pattern -->
                <svg class="absolute -right-6 -bottom-6 w-32 h-32 text-indigo-500/5 rotate-12" fill="currentColor"
                    viewBox="0 0 24 24">
                    <path
                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z">
                    </path>
                </svg>
            </div>

            <!-- SECURITY SECURITY CARD -->
            <div class="glass p-8 rounded-[32px] bg-slate-900/40 relative overflow-hidden group">
                <div class="relative z-10 flex flex-col h-full">
                    <div class="flex items-center gap-4 mb-8">
                        <div
                            class="w-12 h-12 rounded-2xl bg-blue-600 flex items-center justify-center shadow-lg shadow-blue-600/20">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                                </path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-white">Pertanyaan Rahasia</h3>
                            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest mt-0.5">Pemulihan
                                Akun</p>
                        </div>
                    </div>

                    <div class="space-y-5 flex-1">
                        <div>
                            <label
                                class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-2 block ml-1">Buat
                                Pertanyaan</label>
                            <input type="text" name="security_question" required
                                placeholder="Contoh: Makanan favorit saya?"
                                class="w-full bg-slate-950/50 border border-slate-800 rounded-2xl py-3.5 px-5 text-white placeholder-slate-600 focus:outline-none glow-input transition-all text-sm font-medium italic">
                        </div>
                        <div>
                            <label
                                class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-2 block ml-1">Jawaban
                                Anda</label>
                            <input type="text" name="security_answer" required
                                placeholder="Jawaban hanya diketahui oleh anda"
                                class="w-full bg-slate-950/50 border border-slate-800 rounded-2xl py-3.5 px-5 text-white placeholder-slate-600 focus:outline-none glow-input transition-all text-sm font-bold tracking-widest uppercase">
                        </div>
                    </div>

                    <div class="mt-8">
                        <button type="submit"
                            class="w-full bg-indigo-600 hover:bg-indigo-500 text-white font-extrabold py-4 px-8 rounded-2xl shadow-xl shadow-indigo-600/20 transform hover:-translate-y-1 transition-all active:scale-[0.98] tracking-widest text-xs">
                            SIMPAN & AKTIFKAN AKUN
                        </button>
                    </div>
                </div>
                <!-- Subtle Icon Pattern -->
                <svg class="absolute -right-6 -bottom-6 w-32 h-32 text-blue-500/5 rotate-12" fill="currentColor"
                    viewBox="0 0 24 24">
                    <path
                        d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                    </path>
                </svg>
            </div>
        </form>

        <div class="mt-12 flex flex-col items-center gap-6">


            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit"
                    class="px-8 py-3 rounded-2xl bg-red-600/10 border border-red-500/20 text-red-500 hover:bg-red-600 hover:text-white hover:border-red-600 font-bold text-[10px] uppercase tracking-[0.3em] transition-all duration-300">
                    Keluar Sesi
                </button>
            </form>

            <p class="text-[10px] text-slate-800 uppercase tracking-[0.5em] font-black mt-8">SIMPATISANS v1.2 • MTsN 11
                MAJALENGKA</p>
        </div>

    </div>

</body>

</html>