<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | SIMPATISANS</title>
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
        /* Robot SIMPATISANS v3.2 - The Masterpiece Edition */
        @keyframes robot-hover {
            0%, 100% { transform: translateY(0) rotate(-1deg); }
            50% { transform: translateY(-15px) rotate(1deg); }
        }
        /* Responsive Robot Peek Logic */
        @keyframes robot-peek-ultimate {
            0% { transform: translateY(50px) scale(0.8); opacity: 0; }
            8% { transform: translateY(-160px) scale(0.95); opacity: 1; }
            92% { transform: translateY(-160px) scale(0.95); opacity: 1; }
            100% { transform: translateY(50px) scale(0.8); opacity: 0; }
        }
        @media (min-width: 768px) {
            @keyframes robot-peek-ultimate {
                0% { transform: translateX(80px) scale(0.8); opacity: 0; }
                8% { transform: translateX(-300px) scale(1.0); opacity: 1; }
                92% { transform: translateX(-300px) scale(1.0); opacity: 1; }
                100% { transform: translateX(80px) scale(0.8); opacity: 0; }
            }
        }
        @keyframes energy-glow {
            0%, 100% { filter: drop-shadow(0 0 10px #6366f1); opacity: 0.8; }
            50% { filter: drop-shadow(0 0 25px #38bdf8); opacity: 1; }
        }
        @keyframes robot-wave-natural {
            0%, 100% { transform: rotate(0deg); }
            50% { transform: rotate(-22deg) translateX(-3px); }
        }
        @keyframes robot-talk-pulse {
            0%, 100% { transform: scaleX(1); opacity: 0.6; }
            50% { transform: scaleX(2); opacity: 1; }
        }
        @keyframes bubble-pop-sequence {
            0% { transform: scale(0) translateY(20px); opacity: 0; }
            5% { transform: scale(1.1) translateY(0); opacity: 1; }
            10%, 98% { transform: scale(1) translateY(0); opacity: 1; }
            100% { transform: scale(0) translateY(20px); opacity: 0; }
        }
        @keyframes robot-blink {
            0%, 48%, 52%, 100% { opacity: 1; transform: scaleY(1); }
            50% { opacity: 0.3; transform: scaleY(0.1); }
        }

        .robot-root-masterpiece { animation: robot-hover 4s ease-in-out infinite; transform: scaleX(-1); }
        .robot-eye { animation: robot-blink 3s infinite; transform-origin: center; }
        .robot-pupil { animation: eye-search 5s ease-in-out infinite; }
        .robot-arm-active { transform-origin: 20% 20%; animation: robot-wave-natural 1.2s ease-in-out infinite; }
        .robot-hover-pod { animation: energy-glow 2s ease-in-out infinite; }
        .robot-mouth-active { animation: robot-talk-pulse 1s ease-in-out infinite; }
        .animate-robot-peek-ultimate { animation: robot-peek-ultimate 34s cubic-bezier(0.34, 1.56, 0.64, 1) forwards; }
        .speech-bubble-v3 { animation: bubble-pop-sequence 33.5s ease-out forwards; }

        @keyframes logo-entrance {
            0% { transform: translateY(-40px); opacity: 0; }
            100% { transform: translateY(0); opacity: 1; }
        }
        .animate-logo-entrance { animation: logo-entrance 2s cubic-bezier(0.22, 1, 0.36, 1) forwards; }
        .bg-immersive {
            background-image: url('{{ asset('img/bg.jpg') }}');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }
    </style>
</head>
<body class="bg-immersive text-slate-200 min-h-screen relative overflow-x-hidden flex flex-col justify-center items-center p-4">

    <!-- Overall Dark Overlay for Immersive Feel -->
    <div class="fixed inset-0 bg-gradient-to-br from-slate-950/40 via-[#020617]/50 to-indigo-950/40 -z-10"></div>
    <div class="fixed inset-0 -z-10"></div>

    <!-- Decorative Glows -->
    <div class="fixed top-[-10%] right-[-5%] w-[400px] h-[400px] bg-indigo-600/15 rounded-full blur-[120px] -z-10"></div>
    <div class="fixed bottom-[-10%] left-[-5%] w-[400px] h-[400px] bg-blue-700/15 rounded-full blur-[120px] -z-10"></div>

    <div class="w-full max-w-7xl flex flex-col md:flex-row items-center justify-between gap-8 md:gap-16 relative z-10 px-6 md:px-12">
        
        <!-- BRANDING SECTION (LEFT / TOP) -->
        <div class="w-full md:w-3/5 text-center md:text-left space-y-8 py-6 md:py-0">
            <div class="animate-logo-entrance inline-block">
                <img src="{{ asset('img/logo.png') }}" alt="MTsN 11 Majalengka" 
                    class="w-48 md:w-64 h-auto object-contain filter drop-shadow-[0_20px_40px_rgba(79,70,229,0.5)]">
            </div>

            <div x-data="{ 
                titles: ['Sistem Pembagian Tugas Terintegrasi.', 'Inovasi Digital Madrasah Berkemajuan.', 'Efektifitas Penjadwalan Tanpa Batas.', 'Powered by SIMPATISANS Smart Engine.'],
                current: 0,
                init() { setInterval(() => { this.current = (this.current + 1) % this.titles.length }, 4000) }
            }" class="space-y-3">
                <h1 class="text-3xl md:text-5xl font-extrabold text-white tracking-tight drop-shadow-2xl leading-tight">
                    SIMPATISANS <br class="hidden md:block"> <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-300 to-blue-300">MTsN 11 Majalengka</span>
                </h1>
                <div class="h-8">
                    <p class="text-lg md:text-xl text-white font-bold tracking-wide drop-shadow-md"
                        x-text="titles[current]" x-transition:enter="transition ease-out duration-700" x-transition:enter-start="opacity-0 translate-x-4" x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0 -translate-x-4">
                    </p>
                </div>
            </div>

            <!-- Functional Icons (Compact Row) -->
            <div class="flex flex-wrap justify-center md:justify-start gap-4 mt-6">
                <!-- Management -->
                <div class="w-10 h-10 rounded-xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center group hover:bg-indigo-500/20 transition-all cursor-help" title="Manajemen">
                    <svg class="w-5 h-5 text-indigo-400 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                </div>
                <!-- Task Distribution -->
                <div class="w-10 h-10 rounded-xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center group hover:bg-blue-500/20 transition-all cursor-help" title="Pembagian Tugas">
                    <svg class="w-5 h-5 text-blue-400 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>
                <!-- Teaching -->
                <div class="w-10 h-10 rounded-xl bg-purple-500/10 border border-purple-500/20 flex items-center justify-center group hover:bg-purple-500/20 transition-all cursor-help" title="Mengajar">
                    <svg class="w-5 h-5 text-purple-400 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.247 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                </div>
                <!-- Scheduling -->
                <div class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center group hover:bg-emerald-500/20 transition-all cursor-help" title="Penjadwalan">
                    <svg class="w-5 h-5 text-emerald-400 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                </div>
            </div>
        </div>

        <!-- LOGIN FORM (RIGHT / BOTTOM) -->
        <div class="w-full max-w-lg md:w-[420px] relative" x-data="{ 
            isPeeking: false,
            messageIndex: 0,
            displayText: '',
            typingInterval: null,
            isLoading: false,
            messages: [
                @if(session('success'))
                    '{{ session('success') }}',
                @endif
                @if($errors->any())
                    '{{ $errors->first() }}',
                @endif
                'Halo Bapak/Ibu, kenalin Aku Robot SIMPATISANS!',
                'Selamat datang di SIMPATISANS. Silakan masuk untuk manajemen pembagian tugas dan penjadwalan.',
                'SIMPATISANS membantu pembagian tugas dan penjadwalan dengan Efisien, Akurat, dan Terintegrasi!.',
                'Aku diciptakan oleh Mr. Aditya khusus untuk MTsN 11 Majalengka!'
            ],
            typeWriter(text) {
                if (!text) return;
                clearInterval(this.typingInterval);
                this.displayText = '';
                let i = 0;
                this.typingInterval = setInterval(() => {
                    if (i < text.length) {
                        this.displayText += text.charAt(i);
                        i++;
                    } else {
                        clearInterval(this.typingInterval);
                    }
                }, 30);
            },
            speak(text) {
                this.isPeeking = true;
                this.typeWriter(text);
                setTimeout(() => {
                    if (this.displayText === text) {
                        this.isPeeking = false;
                        this.displayText = '';
                    }
                }, 8000);
            },
            triggerPeek() {
                if (this.isPeeking || this.isLoading) return;
                this.messageIndex = 0;
                this.isPeeking = true;
                this.typeWriter(this.messages[0]);
                
                let interval = setInterval(() => {
                    if (this.messageIndex < this.messages.length - 1) {
                        this.messageIndex++;
                        this.typeWriter(this.messages[this.messageIndex]);
                    } else {
                        clearInterval(interval);
                    }
                }, 8000);

                setTimeout(() => { 
                    this.isPeeking = false;
                    clearInterval(interval);
                    this.displayText = '';
                }, 34000);
            },
            async submitLogin(e) {
                this.isLoading = true;
                const form = e.target;
                const formData = new FormData(form);

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        const errorMsg = data.errors ? data.errors[0] : 'Terjadi kesalahan sistem.';
                        this.speak(errorMsg);
                        this.isLoading = false;
                    } else {
                        window.location.href = data.redirect;
                    }
                } catch (error) {
                    this.speak('Waduh, koneksi bermasalah. Coba cek internet Anda.');
                    this.isLoading = false;
                }
            },
            init() {
                setTimeout(() => this.triggerPeek(), 1500);
                setInterval(() => {
                    if (!this.isPeeking && !this.isLoading && Math.random() > 0.7) this.triggerPeek();
                }, 10000);
            }
        }" :class="{ 'is-peeking': isPeeking }">
            
            <!-- Living Robot SIMPATISANS v3.2 (The Masterpiece SVG) -->
            <div class="hidden md:block md:absolute md:top-1/2 md:left-0 md:-translate-y-[110%] md:w-64 md:h-64 -z-10 md:pointer-events-none md:opacity-0" 
                 :class="{ 'animate-robot-peek-ultimate': isPeeking }">
                
                <div class="robot-root-masterpiece w-full h-full">
                    <svg viewBox="0 0 200 200" class="w-full h-full">
                        <defs>
                            <linearGradient id="gradMetallic" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" style="stop-color:#ffffff;stop-opacity:1" />
                                <stop offset="100%" style="stop-color:#94a3b8;stop-opacity:1" />
                            </linearGradient>
                            <radialGradient id="eyeGlow" cx="50%" cy="50%" r="50%">
                                <stop offset="0%" style="stop-color:#38bdf8;stop-opacity:1" />
                                <stop offset="100%" style="stop-color:#0ea5e9;stop-opacity:0" />
                            </radialGradient>
                            <filter id="softShadow" x="-20%" y="-20%" width="140%" height="140%">
                                <feGaussianBlur in="SourceAlpha" stdDeviation="3" />
                                <feOffset dx="2" dy="2" result="offsetblur" />
                                <feComponentTransfer><feFuncA type="linear" slope="0.3"/></feComponentTransfer>
                                <feMerge><feMergeNode/><feMergeNode in="SourceGraphic"/></feMerge>
                            </filter>
                        </defs>
                        <!-- Masterpiece Silhouette (Organic Single Path) -->
                        <path d="M60,65 Q60,30 100,30 Q140,30 140,65 Q140,90 125,100 Q150,110 150,140 Q150,170 100,170 Q50,170 50,140 Q50,110 75,100 Q60,90 60,65" 
                              fill="url(#gradMetallic)" stroke="#1e293b" stroke-width="1.5" filter="url(#softShadow)" />
                        
                        <!-- Detailed Face Area -->
                        <rect x="72" y="55" width="56" height="32" rx="14" fill="#0f172a" opacity="0.95"/>
                        <g class="robot-eye" style="transform-origin: 86px 71px;">
                            <circle cx="86" cy="71" r="7" fill="#38bdf8" />
                            <circle cx="86" cy="71" r="12" fill="url(#eyeGlow)" />
                        </g>
                        <g class="robot-eye" style="transform-origin: 114px 71px;">
                            <circle cx="114" cy="71" r="7" fill="#38bdf8" />
                            <circle cx="114" cy="71" r="12" fill="url(#eyeGlow)" />
                        </g>
                        <!-- Voice Waveform (Mouth) -->
                        <rect x="88" y="90" width="24" height="3" rx="1.5" fill="#6366f1" class="robot-mouth-active" style="transform-origin: center;"/>

                        <!-- Energy Core -->
                        <circle cx="100" cy="135" r="15" fill="#0f172a"/>
                        <circle cx="100" cy="135" r="8" fill="#6366f1">
                            <animate attributeName="opacity" values="1;0.4;1" dur="2s" repeatCount="indefinite" />
                            <animate attributeName="r" values="8;11;8" dur="2s" repeatCount="indefinite" />
                        </circle>

                        <!-- Hover-Pod Fusion -->
                        <path d="M70,170 Q100,195 130,170" fill="#64748b" class="robot-hover-pod" />
                        <rect x="85" y="188" width="30" height="5" rx="2.5" fill="#38bdf8" class="robot-hover-pod" opacity="0.7"/>

                        <!-- Masterpiece Arms (Simetris Zero-Gap Edition) -->
                        <!-- Shoulder Joints -->
                        <circle cx="60" cy="118" r="8" fill="url(#gradMetallic)" stroke="#1e293b" stroke-width="1.5" />
                        <circle cx="138" cy="118" r="8" fill="url(#gradMetallic)" stroke="#1e293b" stroke-width="1.5" />

                        <!-- Arm (Screen-Left) -->
                        <g id="arm-right" style="transform-origin: 138px 118px;">
                            <path d="M138,118 Q165,115 175,90" fill="none" stroke="#cbd5e1" stroke-width="12" stroke-linecap="round"/>
                            <circle cx="175" cy="90" r="8" fill="#ffffff" stroke="#1e293b" stroke-width="2"/>
                        </g>

                        <!-- Arm Waving (Screen-Right) -->
                        <g id="arm-left" class="robot-arm-active" style="transform-origin: 60px 118px;">
                            <path d="M60,118 Q35,115 25,90" fill="none" stroke="#cbd5e1" stroke-width="12" stroke-linecap="round"/>
                            <circle cx="25" cy="90" r="8" fill="#ffffff" stroke="#1e293b" stroke-width="2"/>
                        </g>
                    </svg>
                </div>

                <!-- Sequential Speech Bubble (Placed AFTER SVG to be on TOP) -->
                <div x-show="isPeeking" class="absolute -top-24 left-0 speech-bubble-v3">
                    <div :class="{ 
                            'bg-red-50 border-red-200 text-red-900': {{ $errors->any() ? 'true' : 'false' }}, 
                            'bg-emerald-50 border-emerald-200 text-emerald-900': {{ session('success') ? 'true' : 'false' }},
                            'bg-white border-indigo-100 text-slate-900': {{ (!$errors->any() && !session('success')) ? 'true' : 'false' }} 
                         }" 
                         class="text-[10px] md:text-[11px] font-bold px-7 py-5 rounded-[32px] shadow-2xl relative max-w-[320px] leading-relaxed border transition-colors duration-500">
                        <span x-text="displayText" class="block text-center min-h-[3em]"></span>
                        <!-- Bubble Pointer -->
                        <div :class="{ 
                            'border-t-red-50': {{ $errors->any() ? 'true' : 'false' }}, 
                            'border-t-emerald-50': {{ session('success') ? 'true' : 'false' }},
                            'border-t-white': {{ (!$errors->any() && !session('success')) ? 'true' : 'false' }} 
                        }" class="absolute bottom-[-14px] left-1/2 -translate-x-1/2 w-0 h-0 border-l-[14px] border-l-transparent border-r-[14px] border-r-transparent border-t-[14px] transition-colors duration-500"></div>
                    </div>
                </div>
            </div>

            <div class="glass-card p-6 md:p-8 rounded-[40px] shadow-2xl relative overflow-hidden group hover:border-indigo-500/30 transition-all duration-500">
                
                <!-- Inner Light Effect -->
                <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-transparent via-indigo-500/50 to-transparent"></div>

                <!-- Dual Logos (Kemenag & MTsN 11) - Centered -->
                <div class="flex justify-center items-center gap-4 mb-4">
                    <img src="{{ asset('img/logo-kemenag.png') }}" alt="Kemenag" class="w-10 h-10 object-contain filter drop-shadow-md">
                    <img src="{{ asset('img/logo-mtsn11.png') }}" alt="MTsN 11" class="w-10 h-10 object-contain filter drop-shadow-md">
                </div>

                <div class="mb-6 text-center">
                    <h2 class="text-2xl font-extrabold text-white mb-2">Selamat Datang!</h2>
                    <p class="text-slate-400 font-medium text-xs">Masuk menggunakan NIP/NIK dan Kata Sandi.</p>
                </div>

                <!-- Form -->
                <form action="{{ route('login.post') }}" method="POST" class="space-y-5" novalidate @submit.prevent="submitLogin">
                    @csrf
                    
                    @if(session('success'))
                        <div class="bg-emerald-500/10 border border-emerald-500/40 text-emerald-400 p-4 rounded-2xl text-[10px] font-semibold mb-2 flex items-start gap-3">
                            <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span>{{ session('success') }}</span>
                        </div>
                    @endif
                    


                    <div class="space-y-1.5">
                        <label class="text-[9px] font-black uppercase tracking-[0.2em] text-slate-500 ml-1">NIP / NIK</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-4 flex items-center text-slate-500 group-focus-within:text-indigo-400 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            </span>
                            <input type="text" name="username" placeholder="Masukkan NIP/NIK" required autofocus
                                class="w-full bg-slate-950/40 border border-white/5 rounded-[20px] py-3.5 pl-12 pr-4 text-white placeholder-slate-600 focus:outline-none focus:border-indigo-500 glow-input font-bold transition-all text-xs">
                        </div>
                    </div>

                    <div class="space-y-1.5" x-data="{ show: false }">
                        <div class="flex justify-between items-center px-1">
                            <label class="text-[9px] font-black uppercase tracking-[0.2em] text-slate-500">Kata Sandi</label>
                            <a href="{{ route('password.request') }}" class="text-[9px] font-bold text-indigo-400 hover:text-indigo-300 transition-colors">Lupa?</a>
                        </div>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-4 flex items-center text-slate-500 group-focus-within:text-indigo-400 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                            </span>
                            <input :type="show ? 'text' : 'password'" name="password" placeholder="••••••••" required
                                class="w-full bg-slate-950/40 border border-white/5 rounded-[20px] py-3.5 pl-12 pr-10 text-white placeholder-slate-600 focus:outline-none focus:border-indigo-500 glow-input font-bold transition-all text-xs">
                            <button type="button" @click="show = !show" class="absolute inset-y-0 right-4 flex items-center text-slate-500 hover:text-white transition-colors">
                                <svg x-show="!show" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                <svg x-show="show" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"></path></svg>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center px-1">
                        <label class="flex items-center cursor-pointer group">
                            <input type="checkbox" name="remember" class="w-4 h-4 rounded border-slate-700 bg-slate-900 text-indigo-600 focus:ring-indigo-500 focus:ring-offset-slate-900 transition-all">
                            <span class="ml-3 text-[11px] text-slate-500 font-bold uppercase tracking-wider group-hover:text-indigo-400 transition-colors">Ingat Saya</span>
                        </label>
                    </div>

                    <div class="pt-4">
                        <button type="submit" :disabled="isLoading"
                            class="w-full bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-500 hover:to-indigo-600 text-white font-extrabold py-3 rounded-[22px] shadow-2xl shadow-indigo-600/30 transform hover:-translate-y-1 transition-all active:scale-[0.98] tracking-widest text-sm text-[12px] flex justify-center items-center gap-2">
                            <template x-if="!isLoading">
                                <span>MASUK</span>
                            </template>
                            <template x-if="isLoading">
                                <span class="flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    Memverifikasi...
                                </span>
                            </template>
                        </button>
                    </div>
                </form>

                <!-- Footer Sign -->
                <div class="mt-12 text-center">
                    <p class="text-[9px] text-white/30 uppercase tracking-[0.4em] font-black border-t border-white/5 pt-6">MTsN 11 Majalengka © 2026 <br>Developed by <a href="https://www.instagram.com/atadityas_13" target="_blank" class="text-indigo-400 hover:text-indigo-300 transition-colors">A.T Aditya    </a></p>
                </div>
            </div>
        </div>

    </div>

</body>
</html>
