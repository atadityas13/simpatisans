@extends('layouts.app')
@section('header', 'Dashboard Analitik')
@section('content')
    <style>
        /* Robot SIMPATISANS v3.2 - The Masterpiece Edition */
        @keyframes robot-hover {
            0%, 100% { transform: translateY(0) rotate(-1deg); }
            50% { transform: translateY(-15px) rotate(1deg); }
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
        @keyframes robot-blink {
            0%, 48%, 52%, 100% { opacity: 1; transform: scaleY(1); }
            50% { opacity: 0.3; transform: scaleY(0.1); }
        }

        .robot-root-masterpiece { animation: robot-hover 4s ease-in-out infinite; transform: scaleX(-1); }
        .robot-eye { animation: robot-blink 3s infinite; transform-origin: center; }
        .robot-arm-active { transform-origin: 20% 20%; animation: robot-wave-natural 1.2s ease-in-out infinite; }
        .robot-hover-pod { animation: energy-glow 2s ease-in-out infinite; }
        .robot-mouth-active { animation: robot-talk-pulse 1s ease-in-out infinite; }
    </style>

    {{-- Dashboard Stats & Integrity Monitor --}}
    @if(!$activeSemester)
        <div
            class="bg-amber-50 border border-amber-200 text-amber-800 rounded-xl p-8 flex flex-col items-center justify-center text-center space-y-4 mb-8">
            <svg class="w-16 h-16 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div>
                <h3 class="text-xl font-black uppercase tracking-widest text-amber-900">Belum Ada Semester Aktif</h3>
                <p class="text-sm mt-2 font-medium">Silakan buat dan aktifkan semester pada menu pengaturan terlebih dahulu
                    untuk melihat dashboard analitik.</p>
            </div>
            <a href="{{ route('semester.index') }}"
                class="mt-4 bg-amber-500 hover:bg-amber-600 text-black px-6 py-2.5 rounded-lg font-bold text-sm uppercase tracking-widest shadow-md transition transform hover:-translate-y-0.5">Atur
                Semester Sekarang</a>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            {{-- Health Score Card --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden transition hover:shadow-md">
                <div class="p-6">
                    <div class="flex justify-between items-start mb-4">
                        <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Integritas Jadwal</h3>
                        <span
                            class="px-2 py-0.5 rounded-full text-[9px] font-bold uppercase {{ $stats['health_score'] > 90 ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">
                            {{ $stats['health_score'] > 90 ? 'Sempurna' : 'Butuh Perbaikan' }}
                        </span>
                    </div>
                    <div class="flex items-end gap-2">
                        <p
                            class="text-4xl font-black {{ $stats['health_score'] == 100 ? 'text-green-600' : ($stats['health_score'] > 80 ? 'text-indigo-900' : 'text-red-600') }}">
                            {{ $stats['health_score'] }}%
                        </p>
                        <p class="text-[10px] text-gray-400 font-bold mb-1 uppercase italic">Skor Akurasi</p>
                    </div>
                    <div class="mt-4 flex gap-2">
                        <div class="flex-1 bg-gray-100 h-1 rounded-full overflow-hidden">
                            <div class="h-full {{ $stats['health_score'] > 80 ? 'bg-green-500' : 'bg-red-500' }}"
                                style="width: {{ $stats['health_score'] }}%"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Conflict & Warning Summary --}}
            <div
                class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 transition hover:shadow-md flex flex-col justify-between">
                <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-4">Hasil Audit Sistem</h3>
                <div class="grid grid-cols-3 gap-2">
                    <div class="text-center">
                        <p class="text-xl font-black {{ count($analisa['bentrok']) > 0 ? 'text-red-600' : 'text-gray-300' }}">
                            {{ count($analisa['bentrok']) }}</p>
                        <p class="text-[8px] font-bold text-gray-400 uppercase">Bentrok</p>
                    </div>
                    <div class="text-center border-x border-gray-100">
                        <p class="text-xl font-black {{ count($analisa['fatigue']) > 0 ? 'text-amber-600' : 'text-gray-300' }}">
                            {{ count($analisa['fatigue']) }}</p>
                        <p class="text-[8px] font-bold text-gray-400 uppercase">Kelebihan Jam</p>
                    </div>
                    <div class="text-center">
                        <p
                            class="text-xl font-black {{ count($rekomendasi['wali_kelas_kosong']) > 0 ? 'text-indigo-600' : 'text-gray-300' }}">
                            {{ count($rekomendasi['wali_kelas_kosong']) }}</p>
                        <p class="text-[8px] font-bold text-gray-400 uppercase">Belum atur Wali kelas</p>
                    </div>
                </div>
            </div>

            {{-- Progres JTM --}}
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 transition hover:shadow-md">
                <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-4">Beban Kurikulum</h3>
                <div class="flex items-end justify-between mb-2">
                    <p class="text-3xl font-black text-indigo-900">{{ $stats['progres_jtm'] }}%</p>
                    <p class="text-[9px] text-gray-400 font-bold uppercase tracking-tighter">{{ $stats['jtm_terisi'] }} /
                        {{ $stats['jtm_total'] }} JP</p>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-1.5">
                    <div class="bg-indigo-600 h-1.5 rounded-full transition-all duration-500"
                        style="width: {{ $stats['progres_jtm'] }}%"></div>
                </div>
            </div>

            {{-- Matrix Engine Card - Simple & High Contrast --}}
            <div style="background-color: #0ea5e9; background-image: linear-gradient(135deg, #0ea5e9 0%, #0369a1 100%);"
                class="rounded-2xl shadow-xl p-6 text-white transition-all hover:scale-[1.02] active:scale-95 flex flex-col justify-between relative overflow-hidden group border-2 border-blue-400">

                {{-- Masterpiece Robot SVG - From Login Page --}}
                <div class="absolute -right-2 -bottom-10 w-40 h-40 pointer-events-none group-hover:rotate-6 transition-transform duration-700">
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
                            <path d="M60,65 Q60,30 100,30 Q140,30 140,65 Q140,90 125,100 Q150,110 150,140 Q150,170 100,170 Q50,170 50,140 Q50,110 75,100 Q60,90 60,65" 
                                  fill="url(#gradMetallic)" stroke="#1e293b" stroke-width="1.5" filter="url(#softShadow)" />
                            <rect x="72" y="55" width="56" height="32" rx="14" fill="#0f172a" opacity="0.95"/>
                            <g class="robot-eye" style="transform-origin: 86px 71px;">
                                <circle cx="86" cy="71" r="7" fill="#38bdf8" />
                                <circle cx="86" cy="71" r="12" fill="url(#eyeGlow)" />
                            </g>
                            <g class="robot-eye" style="transform-origin: 114px 71px;">
                                <circle cx="114" cy="71" r="7" fill="#38bdf8" />
                                <circle cx="114" cy="71" r="12" fill="url(#eyeGlow)" />
                            </g>
                            <rect x="88" y="90" width="24" height="3" rx="1.5" fill="#6366f1" class="robot-mouth-active" style="transform-origin: center;"/>
                            <circle cx="100" cy="135" r="15" fill="#0f172a"/>
                            <circle cx="100" cy="135" r="8" fill="#6366f1">
                                <animate attributeName="opacity" values="1;0.4;1" dur="2s" repeatCount="indefinite" />
                                <animate attributeName="r" values="8;11;8" dur="2s" repeatCount="indefinite" />
                            </circle>
                            <path d="M70,170 Q100,195 130,170" fill="#64748b" class="robot-hover-pod" />
                            <rect x="85" y="188" width="30" height="5" rx="2.5" fill="#38bdf8" class="robot-hover-pod" opacity="0.7"/>
                            <circle cx="60" cy="118" r="8" fill="url(#gradMetallic)" stroke="#1e293b" stroke-width="1.5" />
                            <circle cx="138" cy="118" r="8" fill="url(#gradMetallic)" stroke="#1e293b" stroke-width="1.5" />
                            <g id="arm-right" style="transform-origin: 138px 118px;">
                                <path d="M138,118 Q165,115 175,90" fill="none" stroke="#cbd5e1" stroke-width="12" stroke-linecap="round"/>
                                <circle cx="175" cy="90" r="8" fill="#ffffff" stroke="#1e293b" stroke-width="2"/>
                            </g>
                            <g id="arm-left" class="robot-arm-active" style="transform-origin: 60px 118px;">
                                <path d="M60,118 Q35,115 25,90" fill="none" stroke="#cbd5e1" stroke-width="12" stroke-linecap="round"/>
                                <circle cx="25" cy="90" r="8" fill="#ffffff" stroke="#1e293b" stroke-width="2"/>
                            </g>
                        </svg>
                    </div>
                </div>

                <div class="relative z-10">
                    <h3 class="text-[10px] font-black uppercase tracking-[0.4em] mb-2 text-white/90">Intelligent System</h3>
                    <p class="text-3xl font-black tracking-tight leading-none mb-2 text-white drop-shadow-md">AI Scheduler</p>
                    <p class="text-[11px] font-bold uppercase tracking-widest text-white/80">SIMPATISANS Smart Engine</p>
                </div>

                <a href="{{ route('jadwal.index') }}"
                    class="relative z-10 mt-8 inline-flex items-center justify-between bg-white text-blue-600 text-[11px] font-black py-4 px-6 rounded-2xl uppercase tracking-widest transition hover:bg-blue-50 shadow-2xl">
                    <span>Matrix</span>
                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
            {{-- Fatigue & Workload Alert --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-5 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
                    <h3 class="font-bold text-gray-800 flex items-center text-sm">
                        <svg class="w-4 h-4 mr-2 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        Kelebihan jam harian
                    </h3>
                    <span
                        class="bg-rose-100 text-rose-700 text-[9px] font-black px-2 py-0.5 rounded-full uppercase tracking-tighter">{{ count($analisa['fatigue']) }}
                        Peringatan</span>
                </div>
                <div class="p-0 max-h-[250px] overflow-y-auto">
                    <table class="w-full text-[11px]">
                        <tbody class="divide-y divide-gray-50">
                            @forelse($analisa['fatigue'] as $f)
                                <tr class="hover:bg-rose-50/30 transition">
                                    <td class="px-5 py-3">
                                        <span class="font-black text-gray-700 uppercase tracking-tighter">{{ $f['guru'] }}</span>
                                        <span class="text-gray-400 mx-1">•</span>
                                        <span class="font-bold text-gray-500 uppercase">{{ $f['hari'] }}</span>
                                    </td>
                                    <td class="px-5 py-3 text-right">
                                        <span
                                            class="bg-rose-50 text-rose-600 font-bold px-1.5 py-0.5 rounded border border-rose-100">{{ $f['jumlah'] }}
                                            Jam</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-5 py-10 text-center text-gray-400 italic">Tidak ada kelebihan jam harian
                                        terdeteksi. ✓</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-3 bg-gray-50 border-t border-gray-100 text-center">
                    <a href="{{ route('jadwal.index') }}"
                        class="text-[9px] font-black text-indigo-600 uppercase tracking-widest hover:underline">Sesuaikan
                        Matriks →</a>
                </div>
            </div>

            {{-- Integrity Issues --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden lg:col-span-2">
                <div class="p-5 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
                    <h3 class="font-bold text-gray-800 flex items-center text-sm">
                        <svg class="w-4 h-4 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        Masalah Integritas Jadwal
                    </h3>
                    <span
                        class="bg-indigo-100 text-indigo-700 text-[9px] font-black px-2 py-0.5 rounded-full uppercase tracking-tighter">{{ count($analisa['bentrok']) + count($analisa['pelanggaran_ketentuan']) + count($analisa['struktur_jtm']) }}
                        Total</span>
                </div>
                <div class="p-0 max-h-[250px] overflow-y-auto">
                    <div class="divide-y divide-gray-50">
                        {{-- Bentrok --}}
                        @foreach($analisa['bentrok'] as $b)
                            <div class="px-5 py-3 flex justify-between items-center hover:bg-red-50 transition">
                                <div class="text-[11px]">
                                    <span
                                        class="px-1.5 py-0.5 bg-red-600 text-white font-black rounded text-[9px] mr-2">BENTROK</span>
                                    <span class="font-black text-gray-700 uppercase">{{ $b['guru'] }}</span>
                                    <span class="text-gray-400">@ {{ $b['hari'] }} jam {{ $b['jam'] }}</span>
                                </div>
                                <div class="text-[9px] font-bold text-red-500 uppercase tracking-tighter">
                                    {{ implode(', ', $b['kelas']) }}</div>
                            </div>
                        @endforeach

                        {{-- Pelanggaran Ketentuan --}}
                        @foreach($analisa['pelanggaran_ketentuan'] as $pk)
                            <div
                                class="px-5 py-3 flex justify-between items-center hover:bg-amber-50 transition border-l-4 border-amber-400">
                                <div class="text-[11px]">
                                    <span
                                        class="px-1.5 py-0.5 bg-amber-500 text-white font-black rounded text-[9px] mr-2">BLOKIR</span>
                                    <span class="font-black text-gray-700 uppercase">{{ $pk['guru'] }}</span> mengajar di jam
                                    terlarang manual.
                                </div>
                                <div class="text-[9px] font-bold text-amber-600 uppercase tracking-tighter">{{ $pk['hari'] }}
                                    J{{ $pk['jam'] }}</div>
                            </div>
                        @endforeach

                        {{-- Struktur JTM --}}
                        @foreach($analisa['struktur_jtm'] as $sj)
                            <div class="px-5 py-3 text-[10px] text-gray-500 border-l-4 border-indigo-200">
                                <span class="font-black text-indigo-400 text-[8px] mr-1">STRUKTUR:</span> {!! $sj !!}
                            </div>
                        @endforeach

                        @if(count($analisa['bentrok']) == 0 && count($analisa['pelanggaran_ketentuan']) == 0 && count($analisa['struktur_jtm']) == 0)
                            <div class="px-5 py-12 text-center text-gray-400 italic text-[11px]">Lolos audit integritas jadwal.
                                Tidak ada masalah terdeteksi. ✓</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            {{-- Panel Rekomendasi: Wali Kelas --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-5 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
                    <h3 class="font-bold text-gray-800 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        Rombel Tanpa Wali Kelas
                    </h3>
                    <span
                        class="bg-amber-100 text-amber-700 text-[10px] font-bold px-2.5 py-1 rounded-full uppercase tracking-tighter">{{ count($rekomendasi['wali_kelas_kosong']) }}
                        Kelas</span>
                </div>
                <div class="p-0">
                    <div class="max-h-[300px] overflow-y-auto">
                        <table class="w-full text-sm">
                            <tbody class="divide-y divide-gray-100">
                                @forelse($rekomendasi['wali_kelas_kosong'] as $kelas)
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="px-6 py-4 font-bold text-gray-700">Kelas {{ $kelas->nama_kelas }}</td>
                                        <td class="px-6 py-4 text-right">
                                            <a href="{{ route('pembagian.index') }}"
                                                class="text-indigo-600 hover:text-indigo-800 font-black text-[10px] uppercase tracking-widest">Tentukan
                                                Wali →</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="px-6 py-12 text-center text-gray-400 italic font-medium">Semua rombel sudah
                                            memiliki Wali Kelas. ✓</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Panel Rekomendasi: TPG --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-5 border-b border-gray-100 bg-gray-100/50 flex justify-between items-center">
                    <h3 class="font-bold text-gray-800 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Defisit JTM Sertifikasi
                    </h3>
                    <span
                        class="bg-red-100 text-red-700 text-[10px] font-black px-2.5 py-1 rounded-full uppercase tracking-tighter">{{ count($rekomendasi['defisit_tpg']) }}
                        Guru</span>
                </div>
                <div class="p-0">
                    <div class="max-h-[300px] overflow-y-auto">
                        <table class="w-full text-sm">
                            <tbody class="divide-y divide-gray-100">
                                @forelse($rekomendasi['defisit_tpg'] as $item)
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="px-6 py-4">
                                            <p class="font-bold text-gray-700 uppercase tracking-tighter text-xs">
                                                {{ $item['nama'] }}
                                            </p>
                                            <p class="text-[10px] text-gray-400 font-bold uppercase">Terpenuhi:
                                                {{ $item['total'] }} / 24 JAM
                                            </p>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <span
                                                class="bg-red-50 text-red-600 font-black text-[10px] px-2 py-1 rounded">-{{ $item['kurang'] }}
                                                JAM</span>
                                            <a href="{{ route('pembagian.show', $item['id']) }}"
                                                class="ml-3 text-indigo-400 hover:text-indigo-600 transition">
                                                <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                                </svg>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="px-6 py-12 text-center text-gray-400 italic font-medium">Semua guru sertifikasi
                                            sudah memenuhi syarat 24 jam. ✓</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection