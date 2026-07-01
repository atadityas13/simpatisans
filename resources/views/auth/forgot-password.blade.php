<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password | SIMPATISANS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .glass-card { 
            background: rgba(15, 23, 42, 0.6); 
            backdrop-filter: blur(16px); 
            border: 1px solid rgba(255, 255, 255, 0.1); 
        }
        .bg-immersive {
            background-image: url('{{ asset('img/bg.jpg') }}');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }
    </style>
</head>
<body class="bg-immersive text-slate-200 min-h-screen relative overflow-x-hidden flex flex-col justify-center items-center p-4">

    <!-- Overall Dark Overlay -->
    <div class="fixed inset-0 bg-gradient-to-br from-slate-950/40 via-[#020617]/50 to-indigo-950/40 -z-10"></div>

    <div class="w-full max-w-lg relative z-10 px-6">
        
        <div class="text-center mb-8">
            <img src="{{ asset('img/logo.png') }}" alt="Logo" class="w-24 h-auto mx-auto mb-6 filter drop-shadow-lg">
            <h1 class="text-2xl font-extrabold text-white tracking-tight">Permintaan Reset Password</h1>
            <p class="text-slate-400 text-xs mt-2">Sistem Keamanan Verifikasi SIMPATISANS</p>
        </div>

        <div class="glass-card p-8 rounded-[40px] shadow-2xl relative overflow-hidden group border-indigo-500/20 transition-all duration-500">
            
            <!-- Inner Light Effect -->
            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-transparent via-indigo-500/50 to-transparent"></div>

            @if(!isset($step) || $step == 1)
                <!-- STEP 1: INPUT USERNAME -->
                <form action="{{ route('password.verify') }}" method="POST" class="space-y-6">
                    @csrf
                    <div class="text-center mb-6">
                        <p class="text-slate-300 text-sm font-medium">Langkah 1: Identifikasi Akun</p>
                        <p class="text-slate-500 text-[10px] mt-1 italic">Masukkan NIP atau NIK Anda untuk memulai.</p>
                    </div>

                    @if($errors->any())
                        <div class="bg-red-500/10 border border-red-500/40 text-red-400 p-4 rounded-2xl text-[10px] font-semibold">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <div class="space-y-1.5">
                        <label class="text-[9px] font-black uppercase tracking-[0.2em] text-slate-500 ml-1">NIP / NIK Anda</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-4 flex items-center text-slate-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            </span>
                            <input type="text" name="username" placeholder="Contoh: 1980..." required autofocus value="{{ old('username') }}"
                                class="w-full bg-slate-950/40 border border-white/5 rounded-[20px] py-3.5 pl-12 pr-4 text-white placeholder-slate-600 focus:outline-none focus:border-indigo-500 transition-all text-xs font-bold">
                        </div>
                    </div>

                    <button type="submit" 
                        class="w-full bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-500 hover:to-indigo-600 text-white font-extrabold py-3 rounded-[22px] shadow-xl transform hover:-translate-y-1 transition-all active:scale-[0.98] tracking-widest text-xs">
                        LANJUTKAN
                    </button>
                    
                    <div class="text-center pt-4">
                        <a href="{{ route('login') }}" class="text-[10px] font-bold text-slate-500 hover:text-indigo-400 transition-colors uppercase tracking-widest">Kembali ke Login</a>
                    </div>
                </form>
            @else
                <!-- STEP 2: SECURITY QUESTION -->
                <form action="{{ route('password.reset.request') }}" method="POST" class="space-y-6">
                    @csrf
                    <div class="text-center mb-6">
                        <p class="text-slate-300 text-sm font-medium">Langkah 2: Verifikasi Keamanan</p>
                        <p class="text-slate-500 text-[10px] mt-1 italic">Jawab pertanyaan rahasia Anda di bawah ini.</p>
                    </div>

                    <input type="hidden" name="username" value="{{ $username }}">

                    <div class="bg-indigo-500/5 border border-indigo-500/10 rounded-2xl p-6 text-center shadow-inner">
                        <p class="text-[9px] font-black uppercase tracking-[.2em] text-indigo-400 mb-2">Pertanyaan Rahasia Anda:</p>
                        <p class="text-white text-sm font-bold italic">"{{ $question }}"</p>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-[9px] font-black uppercase tracking-[0.2em] text-slate-500 ml-1">Jawaban Anda</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-4 flex items-center text-slate-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"></path></svg>
                            </span>
                            <input type="text" name="answer" placeholder="Ketik jawaban di sini..." required autofocus
                                class="w-full bg-slate-950/40 border border-white/5 rounded-[20px] py-3.5 pl-12 pr-4 text-white placeholder-slate-600 focus:outline-none focus:border-indigo-500 transition-all text-xs font-bold">
                        </div>
                    </div>

                    <button type="submit" 
                        class="w-full bg-gradient-to-r from-emerald-600 to-emerald-700 hover:from-emerald-500 hover:to-emerald-600 text-white font-extrabold py-3 rounded-[22px] shadow-xl transform hover:-translate-y-1 transition-all active:scale-[0.98] tracking-widest text-xs">
                        KIRIM PERMINTAAN RESET
                    </button>

                    <div class="text-center pt-2">
                        <a href="{{ route('password.request') }}" class="text-[9px] font-bold text-slate-500 hover:text-indigo-400 transition-colors uppercase tracking-widest">Bukan Akun Saya?</a>
                    </div>
                </form>
            @endif

        </div>

        <div class="mt-8 text-center">
            <p class="text-[9px] text-white/30 uppercase tracking-[0.4em] font-black italic">SIMPATISANS v1.0 • MTsN 11 MAJALENGKA</p>
        </div>
    </div>

</body>
</html>
