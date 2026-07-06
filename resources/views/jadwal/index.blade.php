@extends('layouts.app')

@section('header', 'MANAJEMEN PENJADWALAN')

@section('content')
    @if(!$selectedSemester)
        <div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-xl p-8 flex flex-col items-center justify-center text-center space-y-4 mb-8">
            <svg class="w-16 h-16 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <div>
                <h3 class="text-xl font-black uppercase tracking-widest text-amber-900">Belum Ada Semester Aktif</h3>
                <p class="text-sm mt-2 font-medium">Silakan buat dan aktifkan semester pada menu pengaturan terlebih dahulu untuk melihat dan mengelola jadwal kelas.</p>
            </div>
            <a href="{{ route('semester.index') }}" class="mt-4 bg-amber-500 hover:bg-amber-600 text-black px-6 py-2.5 rounded-lg font-bold text-sm uppercase tracking-widest shadow-md transition transform hover:-translate-y-0.5">Atur Semester Sekarang</a>
        </div>
    @else
    <div x-data="scheduler" class="relative">
        <div class="print:hidden mb-6 flex flex-col md:flex-row justify-between items-center bg-white p-4 rounded-xl border border-gray-100 shadow-sm gap-4">
            <div class="flex-1">
                <div class="flex items-center gap-2">
                    <h2 class="text-xl font-black text-indigo-900 tracking-tighter uppercase">MANAJEMEN JADWAL</h2>
                </div>
                <p class="text-[10px] text-gray-500 font-bold mt-0.5">
                    Input manual per kelas, guru, atau hari — ketik KG, pilih mapel, atau cari autocomplete.
                </p>
            </div>
            
            <div class="flex items-center gap-3">
                <form action="{{ route('jadwal.index') }}" method="GET" class="flex items-center">
                    <select name="semester_id" onchange="this.form.submit()" class="bg-gray-50 border border-gray-200 text-gray-900 text-xs rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 font-bold shadow-sm">
                        @foreach($allSemesters as $sem)
                            <option value="{{ $sem->id }}" {{ $selectedSemester->id == $sem->id ? 'selected' : '' }}>
                                {{ $sem->nama_tahun }} - {{ $sem->tipe }} {{ $sem->is_active ? '(Aktif)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </form>

                <div class="h-8 w-px bg-gray-200 mx-1"></div>

                <button @click="showAnalysis = true"
                    class="whitespace-nowrap flex items-center gap-2 px-4 py-2.5 rounded-lg font-bold text-xs transition-all shadow-md active:scale-95 {{ ($hasCriticalWarnings ?? false) ? 'bg-red-600 hover:bg-red-700 text-white animate-pulse' : (($hasWarnings ?? false) ? 'bg-amber-500 hover:bg-amber-600 text-white' : 'bg-emerald-600 hover:bg-emerald-700 text-white') }}">
                    @if($hasCriticalWarnings ?? false)
                        <span>⚠</span>
                        <span>PERLU PERHATIAN ({{ $criticalWarnings ?? 0 }})</span>
                    @elseif($hasWarnings ?? false)
                        <span>ℹ</span>
                        <span>PENANDA KUALITAS ({{ $analisa['summary']['info_warnings'] ?? 0 }})</span>
                    @else
                        <span>✓</span>
                        <span>ANALISA (OK)</span>
                    @endif
                </button>

                @if($selectedSemester->is_active)
                    <button @click="showConstraintModal = true"
                        class="whitespace-nowrap flex items-center gap-2 px-4 py-2.5 rounded-lg font-bold text-xs transition-all shadow-md active:scale-95 bg-orange-500 hover:bg-orange-600 text-white">
                        <span>PRESET JADWAL</span>
                    </button>

                    <form action="{{ route('jadwal.generate') }}" method="POST" @submit="startLoading()" class="flex">
                        @csrf
                        <input type="hidden" name="semester_id" value="{{ $selectedSemester->id }}">
                        <button type="submit"
                            class="whitespace-nowrap flex items-center gap-2 px-4 py-2.5 rounded-lg font-bold text-xs transition-all shadow-md active:scale-95 bg-indigo-600 hover:bg-indigo-700 text-white">
                            <span>GENERATE (AI)</span>
                        </button>
                    </form>
                @endif
            </div>
        </div>

        @if(!$selectedSemester->is_active)
            <div class="mb-6 bg-amber-50 border border-amber-200 p-4 rounded-xl flex items-center gap-3">
                <div class="bg-amber-100 p-2 rounded-lg text-amber-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m0 0v3m0-3h3m-3 0H9m12-3a9 9 0 11-18 0 9 9 0 0118 0zM15 7h.01M9 7h.01M15 11h.01M9 11h.01"/></svg>
                </div>
                <div>
                    <p class="text-amber-900 font-bold text-sm uppercase tracking-tight">ARSIP</p>
                    <p class="text-amber-700 text-xs mt-0.5">Anda memilih filter semester lain. Perubahan jadwal tidak diizinkan.</p>
                </div>
            </div>
        @endif

        <!-- MODAL LOADING GENERATE (V2.6) -->
        <div x-show="showLoading" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-900/60 backdrop-blur-md p-4"
            style="display: none;">

            <div
                class="bg-white rounded-3xl shadow-2xl max-w-sm w-full p-8 text-center border-4 border-amber-500 relative overflow-hidden">
                <!-- Background Pulse Decor -->
                <div class="absolute -top-10 -right-10 w-32 h-32 bg-amber-100 rounded-full animate-pulse opacity-50"></div>

                <!-- Spinner -->
                <div class="relative z-10 mb-6 flex justify-center">
                    <div class="w-20 h-20 border-8 border-slate-100 border-t-amber-500 rounded-full animate-spin"></div>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <span class="text-2xl">🤖</span>
                    </div>
                </div>

                <h3 class="text-xl font-black text-slate-800 mb-2 uppercase tracking-tight">AI Scheduler Sedang Bekerja</h3>
                <p class="text-slate-500 text-sm mb-6 leading-relaxed">
                    SIMPATISANS Smart Engine sedang mensimulasikan ribuan kemungkinan matriks jadwal guru...
                </p>

                <!-- Dynamic Counter -->
                <div
                    class="bg-slate-900 text-amber-400 py-3 px-6 rounded-2xl font-mono text-2xl shadow-inner border border-slate-700 inline-block mb-2">
                    <span x-text="loadingCounter.toLocaleString()">0</span>
                </div>
                <div class="text-[10px] text-slate-400 uppercase font-bold tracking-widest">Simulasi Kombinasi Matriks</div>

                <div class="mt-8 flex items-center justify-center gap-2 text-xs text-amber-600 font-bold animate-bounce">
                    <span>⚡</span>
                    <span>Mencari kombinasi matriks jadwal terbaik...</span>
                </div>
            </div>
        </div>

        <!-- Modal Ketentuan Guru (Simplified) -->
        <div x-show="showConstraintModal" x-cloak
            class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-black/60 backdrop-blur-md"
            @keydown.escape.window="showConstraintModal = false">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-5xl max-h-[95vh] flex flex-col overflow-hidden border-t-8 border-orange-500"
                @click.away="showConstraintModal = false">

                <div class="p-4 border-b bg-orange-50 flex justify-between items-center shrink-0">
                    <div>
                        <h3 class="text-xl font-black text-orange-900 tracking-tighter uppercase">PRESET KETENTUAN GURU
                        </h3>
                        <p class="text-[10px] text-orange-700 font-bold italic">Tentukan blackout dan preserve mengajar guru.</p>
                    </div>
                    <button @click="showConstraintModal = false"
                        class="text-orange-900 hover:bg-orange-200 p-2 rounded-full text-2xl transition">&times;</button>
                </div>

                <div class="flex-1 overflow-y-auto p-6 space-y-8">
                    <!-- Selector & Tutorial -->
                    <div
                        class="bg-white border-2 border-orange-100 rounded-xl p-4 flex flex-col md:flex-row items-center gap-6 shadow-sm">
                        <div class="shrink-0 flex items-center gap-3">
                            <div
                                class="w-10 h-10 rounded-full bg-orange-500 flex items-center justify-center text-white text-xl">
                                👤</div>
                            <div class="flex flex-col">
                                <label class="font-black text-orange-800 text-[9px] uppercase tracking-widest">Pilih Guru
                                    Target:</label>
                                <select x-model="selectedGuruId"
                                    class="rounded-lg border-orange-300 text-sm font-bold focus:ring-orange-500 min-w-[250px]">
                                    <option value="">-- Pilih Guru --</option>
                                    @foreach($gurus as $g)
                                        <option value="{{ $g->id }}">[{{ $g->kode_guru }}] {{ $g->nama_guru }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="flex-1 border-l pl-6 space-y-2">
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest leading-none">Petunjuk
                                Seleksi (Drag Tabel):</p>
                            <div class="flex flex-wrap gap-4">
                                <div class="flex items-center gap-2 text-xs font-black">
                                    <span
                                        class="w-4 h-4 bg-red-100 border border-red-500 rounded text-[10px] flex items-center justify-center">🚫</span>
                                    <span class="text-red-700">SHIFT + CLICK or DRAG = BLACKOUT</span>
                                </div>
                                <div class="flex items-center gap-2 text-xs font-black">
                                    <span
                                        class="w-4 h-4 bg-blue-100 border border-blue-500 rounded text-[10px] flex items-center justify-center">📌</span>
                                    <span class="text-blue-700">CTRL + CLICK or DRAG = PRESERVE</span>
                                </div>
                                <div class="flex items-center gap-2 text-xs font-black">
                                    <span
                                        class="w-4 h-4 bg-gray-100 border border-gray-400 rounded text-[10px] flex items-center justify-center">🧹</span>
                                    <span class="text-gray-500">ALT + CLICK or DRAG = RESET</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Simplified Matrix -->
                    <div class="overflow-x-auto rounded-xl border border-gray-300 shadow-lg">
                        <table class="w-full border-collapse bg-white table-fixed">
                            <thead>
                                <tr
                                    class="bg-gray-100 text-[10px] font-black uppercase tracking-widest text-gray-600 border-b-2 border-gray-400">
                                    <th class="border p-2 w-24">WAKTU</th>
                                    <th class="border p-2 w-12">JAM</th>
                                    <th class="border p-2">SENIN</th>
                                    <th class="border p-2 text-orange-600">SELASA</th>
                                    <th class="border p-2 text-orange-600">RABU</th>
                                    <th class="border p-2 text-orange-600">KAMIS</th>
                                    <th class="border p-2 text-green-700 italic">JUM'AT</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $rowStructure = [
                                        ['type' => 'top', 'label' => 'UPACARA/KULTUM'],
                                        1, 2, 3, 
                                        ['type' => 'blocked', 'label' => 'ISTIRAHAT'],
                                        4, 5, 6, 7, 
                                        ['type' => 'blocked', 'label' => 'MBG / SHALAT DZUHUR'],
                                        8, 9, 10
                                    ];
                                @endphp

                                @foreach($rowStructure as $item)
                                    @if(is_array($item))
                                        <tr class="bg-gray-200 text-[8px] font-black uppercase italic text-gray-600">
                                            <td class="border p-2 text-center bg-gray-100 text-gray-400" colspan="2">{{ $item['label'] }}</td>
                                            @if($item['type'] === 'top')
                                                <td class="border p-1 text-center bg-gray-200">UPACARA</td>
                                                <td class="border p-1 text-center bg-gray-200" colspan="3"></td>
                                                <td class="border p-1 text-center bg-gray-200">LKD/KULTUM</td>
                                            @else
                                                <td class="border p-1 text-center bg-gray-200 tracking-[0.5em]" colspan="5">{{ $item['label'] }}</td>
                                            @endif
                                        </tr>
                                    @else
                                        {{-- Row JTM --}}
                                        @php $jam = $item; @endphp
                                        <tr class="h-10 group/row">
                                            <td class="border p-1 px-2 text-[9px] font-bold text-gray-400 bg-gray-50/50">
                                               {{-- Labelling jam berbeda-beda, tampilkan range umum atau '-' --}}
                                               <div class="flex flex-col gap-0.5 leading-none overflow-hidden">
                                                   <span class="text-[7px]">Senin: {{ $jamLabels['Senin'][$jam] ?? '-' }}</span>
                                                   <span class="text-[7px]">Selasa-Kamis:<br> {{ $jamLabels['Selasa-Kamis'][$jam] ?? '-' }}</span>
                                                   <span class="text-[7px]">Jum'at: {{ $jamLabels['Jumat'][$jam] ?? '-' }}</span>
                                               </div>
                                            </td>
                                            <td class="border p-1 text-center text-xs font-black bg-gray-100 text-gray-600">
                                                {{ $jam }}</td>
                                            
                                            @foreach(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'] as $h)
                                                @php
                                                    $isValid = $jam <= $strukturHari[$h];
                                                    $bg = $isValid ? 'bg-white cursor-crosshair' : 'bg-gray-200/50 cursor-not-allowed';
                                                @endphp
                                                <td class="border p-0 transition-all relative select-none overflow-hidden {{ $bg }}"
                                                    @if($isValid)
                                                        @mousedown="startSelection('{{ $h }}', {{ $jam }}, $event)"
                                                        @mouseenter="handleMouseEnter('{{ $h }}', {{ $jam }}, $event)"
                                                        @mouseup="endSelection()"
                                                    @endif>
                                                    
                                                    @if($isValid)
                                                        <div class="absolute inset-0 flex items-center justify-center text-lg"
                                                            :class="hasConstraint('{{ $h }}', {{ $jam }}, 0) ? 'bg-red-500/20 text-red-600' : (hasConstraint('{{ $h }}', {{ $jam }}, 1) ? 'bg-blue-500/20 text-blue-600' : '')">
                                                            <span x-show="hasConstraint('{{ $h }}', {{ $jam }}, 0)" x-cloak class="font-black drop-shadow-sm">🚫</span>
                                                            <span x-show="hasConstraint('{{ $h }}', {{ $jam }}, 1)" x-cloak class="font-black drop-shadow-sm">📌</span>
                                                        </div>
                                                        {{-- Shadow hover --}}
                                                        <div class="absolute inset-0 opacity-0 group-hover/row:bg-indigo-50/30 transition-opacity pointer-events-none"></div>
                                                    @else
                                                        <div class="absolute inset-0 bg-[repeating-linear-gradient(45deg,transparent,transparent_5px,rgba(0,0,0,0.03)_5px,rgba(0,0,0,0.03)_10px)] opacity-50"></div>
                                                    @endif
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- List Ketentuan Aktif -->
                    <div class="space-y-4">
                        <h4
                            class="text-sm font-black text-gray-800 border-l-4 border-indigo-600 pl-3 uppercase tracking-tighter">
                            DAFTAR KETENTUAN AKTIF (SEMUA GURU)</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($constraints->sortBy(fn($c, $gid) => $c->first()->guru->nama_guru) as $guruId => $cons)
                                <div class="border rounded-lg p-3 bg-white shadow-sm hover:shadow-md transition">
                                    <div class="flex items-center justify-between mb-2">
                                        <span
                                            class="font-black text-xs text-indigo-700 uppercase">[{{ $cons->first()->guru->kode_guru }}]
                                            {{ $cons->first()->guru->nama_guru }}</span>
                                        <span
                                            class="bg-gray-100 text-[9px] px-2 py-0.5 rounded-full font-bold text-gray-500 uppercase">{{ $cons->count() }}
                                            Aturan</span>
                                    </div>
                                    <div class="text-[9px] text-gray-600 space-y-1">
                                        @foreach($cons->groupBy('hari') as $hari => $hCons)
                                            <div>
                                                <span class="font-bold uppercase text-gray-800">{{ $hari }}:</span>
                                                @foreach($hCons->sortBy('jam_ke') as $c)
                                                    <span
                                                        class="inline-flex items-center px-1 rounded {{ $c->type == 0 ? 'bg-red-50 text-red-600' : 'bg-blue-50 text-blue-600' }} border {{ $c->type == 0 ? 'border-red-200' : 'border-blue-200' }} ml-1">
                                                        Jam {{ $c->jam_ke }}{{ $c->type == 0 ? '🚫' : '📌' }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="p-6 border-t bg-gray-50 text-right flex justify-between items-center shrink-0">
                    <p class="text-[10px] text-gray-400 font-bold italic">*Preset hanya penanda kualitas di Laporan Analisa, tidak memblokir Generate</p>
                    <button @click="showConstraintModal = false"
                        class="bg-gray-900 hover:bg-black text-white px-8 py-3 rounded-xl font-black text-xs uppercase shadow-xl transition-all active:scale-95">Selesai
                        & Keluar</button>
                </div>
            </div>
        </div>

        <!-- Modal Analisa -->
        <div x-show="showAnalysis" x-cloak
            class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
            @keydown.escape.window="showAnalysis = false">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden"
                @click.away="showAnalysis = false">
                <div class="p-4 border-b bg-gray-50 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-gray-800">Laporan Analisa Jadwal</h3>
                    <button @click="showAnalysis = false"
                        class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
                </div>
                <div class="flex-1 overflow-y-auto p-4 space-y-6">
                    @php
                        $crit = $analisa['summary']['critical_warnings'] ?? 0;
                        $info = $analisa['summary']['info_warnings'] ?? 0;
                        $terisiAnalisa = ($jadwals ?? collect())->count();
                    @endphp
                    @if($crit === 0 && $info === 0 && count($analisa['over_blocked']) === 0)
                        <div class="flex flex-col items-center justify-center py-10 text-emerald-600">
                            <span class="text-6xl mb-4">✓</span>
                            <p class="font-bold">Jadwal aman dan sudah sesuai aturan!</p>
                        </div>
                    @else
                        <div class="rounded-lg border p-3 text-xs space-y-1 {{ $crit > 0 ? 'bg-orange-50 border-orange-300' : 'bg-blue-50 border-blue-300' }}">
                            <p class="font-black uppercase tracking-wide {{ $crit > 0 ? 'text-orange-800' : 'text-blue-800' }}">
                                Ringkasan Analisa
                            </p>
                            <p><b>Slot terisi:</b> {{ $terisiAnalisa }}/792</p>
                            <p><b>Perlu perhatian (kritis):</b> {{ $crit }} — mapel belum penuh, bentrok, BTQ salah</p>
                            <p><b>Penanda kualitas (info):</b> {{ $info }} — preset, struktur JTM, kelelahan guru</p>
                            @if($info > 0 && $crit === 0)
                                <p class="text-blue-700 mt-1">Semua mapel sudah teralokasi. Item di bawah hanya saran perapian manual.</p>
                            @endif
                        </div>
                    @endif
                    @if($crit > 0 || $info > 0 || count($analisa['over_blocked']) > 0)
                        {{-- Mapel belum terisi (KRITIS) --}}
                        @if(count($analisa['belum_terisi'] ?? []) > 0)
                            <div>
                                <h4 class="bg-orange-600 !text-white p-2 rounded-t font-black text-xs uppercase tracking-widest">📋
                                    MAPEL BELUM TERISI PENUH (Perlu Perhatian)</h4>
                                <div class="border border-orange-600 border-t-0 rounded-b overflow-hidden bg-orange-50">
                                    <ul class="text-[10px] space-y-1 p-3 text-orange-950">
                                        @foreach($analisa['belum_terisi'] as $b)
                                            <li class="flex items-start gap-2">
                                                <span class="bg-white text-orange-600 font-black px-1 border border-orange-200 mt-0.5">!</span>
                                                <span><b>{{ $b['mapel'] }}</b> ({{ $b['guru'] }}) di {{ $b['kelas'] }}: {{ $b['aktual'] }}/{{ $b['standar'] }} jam</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @endif

                        {{-- Bagian Pelanggaran Blokir --}}
                        @if(count($analisa['pelanggaran_ketentuan']) > 0)
                            <div>
                                <h4 class="bg-red-600 text-white p-2 rounded-t font-black text-xs uppercase tracking-widest">🚨
                                    PRESET BLOKIR (Penanda Kualitas)</h4>
                                <p class="text-[10px] px-3 pt-2 text-red-800 bg-red-50 border-x border-red-600">
                                    Bukan kegagalan generate — slot ini dipilih agar semua jam terisi. Pindahkan manual bila perlu.
                                </p>
                                <div class="border border-red-600 border-t-0 rounded-b overflow-hidden bg-red-50">
                                    <ul class="text-xs space-y-1 p-3">
                                        @foreach($analisa['pelanggaran_ketentuan'] as $p)
                                            <li class="flex items-center gap-2">
                                                <span class="bg-white text-red-600 font-black px-1 border border-red-200">!</span>
                                                Guru <span class="font-black">[{{ $p['guru'] }}]</span> di <span
                                                    class="font-bold underline text-red-700">{{ $p['hari'] }} jam
                                                    ke-{{ $p['jam'] }}</span> (Kelas {{ $p['kelas'] }}) — preset <b>DIBLOKIR</b> dilanggar otomatis.
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @endif

                        {{-- Bagian Pelanggaran Struktur JTM --}}
                        @if(count($analisa['struktur_jtm']) > 0)
                            <div>
                                <h4 class="bg-indigo-600 text-white p-2 rounded-t font-black text-xs uppercase tracking-widest">🧮
                                    STRUKTUR JTM (Penanda Kualitas)</h4>
                                <p class="text-[10px] px-3 pt-2 text-indigo-800 bg-indigo-50 border-x border-indigo-600">
                                    Pembagian blok jam (2+2, 3+2, dll.) belum rapi — sesuaikan manual jika diperlukan.
                                </p>
                                <div class="border border-indigo-600 border-t-0 rounded-b overflow-hidden bg-indigo-50">
                                    <ul class="text-[10px] space-y-1 p-3">
                                        @foreach($analisa['struktur_jtm'] as $msg)
                                            <li class="flex items-start gap-2">
                                                <span
                                                    class="bg-white text-indigo-600 font-black px-1 border border-indigo-200 mt-0.5">!</span>
                                                <span class="leading-relaxed">{!! $msg !!}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @endif

                        {{-- Aturan BTQ --}}
                        @if(count($analisa['aturan_btq'] ?? []) > 0)
                            <div>
                                <h4 class="bg-emerald-700 text-white p-2 rounded-t font-black text-xs uppercase tracking-widest">📖
                                    ATURAN BTQ (Jumat Jam Terakhir)</h4>
                                <div class="border border-emerald-700 border-t-0 rounded-b overflow-hidden bg-emerald-50">
                                    <ul class="text-[10px] space-y-1 p-3">
                                        @foreach($analisa['aturan_btq'] as $msg)
                                            <li class="flex items-start gap-2">
                                                <span class="bg-white text-emerald-700 font-black px-1 border border-emerald-200 mt-0.5">!</span>
                                                <span class="leading-relaxed">{!! $msg !!}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @endif

                        {{-- Bagian Bentrok --}}
                        @if(count($analisa['bentrok']) > 0)
                            <div>
                                <h4
                                    class="bg-red-50 text-red-700 p-2 rounded font-bold text-sm mb-2 uppercase italic tracking-widest">
                                    ⚠ GURU BENTROK (Satu Guru di >1 Kelas)</h4>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-xs border-collapse">
                                        <thead class="bg-gray-100 italic">
                                            <tr>
                                                <th class="border p-2 text-left">Kode Guru</th>
                                                <th class="border p-2 text-left">Waktu</th>
                                                <th class="border p-2 text-left">Kelas Yang Terlibat</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white">
                                            @foreach($analisa['bentrok'] as $b)
                                                <tr>
                                                    <td class="border p-2 font-bold">{{ $b['guru'] }}</td>
                                                    <td class="border p-2 uppercase">{{ $b['hari'] }}, Jam {{ $b['jam'] }}</td>
                                                    <td class="border p-2 text-red-600 font-bold uppercase">
                                                        {{ implode(', ', $b['kelas']) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif

                        {{-- Bagian JTM --}}
                        @if(count($analisa['kelebihan_jtm']) > 0)
                            <div>
                                <h4
                                    class="bg-amber-50 text-amber-700 p-2 rounded font-bold text-sm mb-2 uppercase italic tracking-widest">
                                    ⚠ KELEBIHAN JAM (Melebihi Standar JTM)</h4>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-xs border-collapse">
                                        <thead class="bg-gray-100 italic">
                                            <tr>
                                                <th class="border p-2 text-left">Guru</th>
                                                <th class="border p-2 text-left">Mapel</th>
                                                <th class="border p-2 text-left">Kelas</th>
                                                <th class="border p-2 text-center">Standar JTM</th>
                                                <th class="border p-2 text-center">JTM Aktual</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white">
                                            @foreach($analisa['kelebihan_jtm'] as $k)
                                                <tr>
                                                    <td class="border p-2 font-bold">{{ $k['guru'] }}</td>
                                                    <td class="border p-2">{{ $k['mapel'] }}</td>
                                                    <td class="border p-2">{{ $k['kelas'] }}</td>
                                                    <td class="border p-2 text-center">{{ $k['standar'] }}</td>
                                                    <td class="border p-2 text-center text-red-600 font-black">{{ $k['aktual'] }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif

                        {{-- Bagian Fatigue --}}
                        @if(count($analisa['fatigue']) > 0)
                            <div>
                                <h4
                                    class="bg-purple-50 text-purple-700 p-2 rounded font-bold text-sm mb-2 uppercase italic tracking-widest">
                                    ⚠ KELELAHAN GURU (Penanda Kualitas, >= 8 Jam/Hari)</h4>
                                <ul class="text-xs space-y-1 pl-4 list-disc bg-white p-3 border rounded">
                                    @foreach($analisa['fatigue'] as $f)
                                        <li>Guru <span class="font-bold">[{{ $f['guru'] }}]</span> mengajar sebanyak <span
                                                class="text-red-600 font-bold">{{ $f['jumlah'] }} jam</span> pada hari <span
                                                class="uppercase">{{ $f['hari'] }}</span>.</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        {{-- Bagian Over-Blocked --}}
                        @if(count($analisa['over_blocked']) > 0)
                            <div>
                                <h4
                                    class="bg-orange-50 text-orange-700 p-2 rounded font-bold text-sm mb-2 uppercase italic tracking-widest">
                                    ⚠ TERLALU BANYAK BLOKIR (Resiko Generate Gagal)</h4>
                                <ul class="text-xs space-y-1 pl-4 list-disc bg-white p-3 border rounded">
                                    @foreach($analisa['over_blocked'] as $ob)
                                        <li>Hari <span class="uppercase font-bold">{{ $ob['hari'] }}</span> jam ke-<span
                                                class="font-bold">{{ $ob['jam'] }}</span>: <span
                                                class="text-red-600 font-bold">{{ $ob['persen'] }}%</span> guru diblokir
                                            ({{ $ob['jumlah'] }} orang).</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        {{-- Bagian Invalid Slots (Data Hantu) --}}
                        @if(count($analisa['invalid_slots']) > 0)
                            <div class="animate-pulse">
                                <h4
                                    class="bg-black text-black p-2 rounded-t font-black text-xs uppercase tracking-widest">
                                    TERDETEKSI DATA JAM TIDAK VALID</h4>
                                <div class="border border-black border-t-0 rounded-b overflow-hidden bg-gray-50">
                                    <div class="p-3">
                                        <p class="text-[10px] text-gray-500 mb-2 italic">Ditemukan jadwal di luar jam operasional. Data ini tersembunyi dari tabel tapi mengganggu analisa. Silakan Generate Ulang untuk menghapusnya.</p>
                                        <ul class="text-[10px] space-y-1">
                                            @foreach($analisa['invalid_slots'] as $inv)
                                                <li class="flex items-center gap-2">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-red-600"></span>
                                                    Guru <span class="font-bold">[{{ $inv['guru'] }}]</span> di <span class="font-bold uppercase">{{ $inv['hari'] }}</span> jam ke-<span class="font-bold text-red-600">{{ $inv['jam'] }}</span> (Kelas {{ $inv['kelas'] }})
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endif
                </div>
                <div class="p-4 border-t bg-gray-50 text-right">
                    <button @click="showAnalysis = false"
                        class="bg-gray-800 text-white px-6 py-2 rounded-lg font-bold text-xs uppercase transition-colors hover:bg-gray-900 shadow-md">Tutup
                        Laporan</button>
                </div>
            </div>
        </div>

        @include('jadwal.partials.manual-editor')

        @if($selectedSemester->is_active)
            <div class="flex justify-start mb-1.5 px-0.5">
                <form action="{{ route('jadwal.clear') }}" method="POST" 
                    data-confirm="Hapus SEMUA jadwal yang sudah terinput ke matriks untuk semester ini?">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="semester_id" value="{{ $selectedSemester->id }}">
                    <button type="submit" class="flex items-center gap-1 text-red-500 hover:text-red-700 font-bold text-[9px] uppercase tracking-tighter transition-colors opacity-80 hover:opacity-100 italic">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-9 9-7-7 9-9zM7 13l4 4" />
                        </svg>
                        <span>Reset Matriks</span>
                    </button>
                </form>
            </div>
        @endif

        <div x-show="viewMode === 'matrix'" x-cloak>
        <div
            class="bg-white rounded shadow-xl border border-gray-800 overflow-hidden print:border-0 print:shadow-none mb-10 overflow-x-auto">
            <table class="w-full text-[9px] border-collapse table-fixed min-w-[900px] print:min-w-0 print:w-full">
                @php
                    $allKelas = $kelasList->flatten();
                    $totalCols = $allKelas->count();
                @endphp
                <thead class="bg-white border-b-2 border-gray-800 text-[8px]">
                    <tr>
                        <th class="w-6 border border-gray-800 p-0.5 font-black uppercase text-center" rowspan="2">HARI</th>
                        <th class="w-20 border border-gray-800 p-0.5 font-black uppercase text-center" rowspan="2">WAKTU
                        </th>
                        <th class="w-6 border border-gray-800 p-0.5 font-black uppercase text-center" rowspan="2">JAM<br>KE
                        </th>
                        @foreach($kelasList as $tingkat => $kelas)
                            <th class="border border-gray-800 p-0.5 font-black uppercase text-center bg-gray-100"
                                colspan="{{ $kelas->count() }}">
                                {{ $tingkat }}
                            </th>
                        @endforeach
                    </tr>
                    <tr>
                        @foreach($kelasList as $tingkat => $kelas)
                            @foreach($kelas as $index => $kItem)
                                <th class="border border-gray-800 p-0.5 font-black text-center w-5 bg-gray-50">
                                    {{ $index + 1 }}
                                </th>
                            @endforeach
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'] as $hari)
                        @php
                            $jmlJam = $strukturHari[$hari];
                            $dayTopBorder = $hari !== 'Senin' ? 'border-t-[4px] border-black' : '';
                            if ($hari === 'Senin') {
                                $currentLabels = $jamLabels['Senin'];
                            } elseif ($hari === 'Jumat') {
                                $currentLabels = $jamLabels['Jumat'];
                            } else {
                                $currentLabels = $jamLabels['Selasa-Kamis'];
                            }
                        @endphp

                        <!-- Row Hari Meta -->
                        @php
                            $totalRowsForDay = $jmlJam;
                            if ($hari === 'Senin')
                                $totalRowsForDay += 4; // Upacara, Istirahat, Makan, Shalat
                            elseif ($hari === 'Jumat')
                                $totalRowsForDay += 6; // LKD, Qiroatul, Istirahat, Makan, Shalat, Pramuka
                            else
                                $totalRowsForDay += 3; // Istirahat, Makan, Shalat
                        @endphp

                        <!-- Row 1 Hari + Row Spesial/Awal -->
                        @if($hari === 'Senin')
                            <tr>
                                <td class="border border-gray-800 font-bold p-0.5 text-center bg-gray-50 align-middle"
                                    rowspan="{{ $totalRowsForDay }}">
                                    <div class="writing-vertical mx-auto">{{ $hari }}</div>
                                </td>
                                <td
                                    class="border border-gray-800 p-0.5 font-bold text-center text-[8px] tracking-tighter row-upacara">
                                    07.00-07.35</td>
                                <td class="border border-gray-800 p-0.5 font-black text-center uppercase tracking-widest row-upacara"
                                    colspan="{{ $totalCols + 1 }}">
                                    UPACARA BENDERA
                                </td>
                            </tr>
                        @elseif($hari === 'Jumat')
                            <tr class="{{ $dayTopBorder }}">
                                <td class="border border-gray-800 font-bold p-0.5 text-center bg-gray-50 align-middle"
                                    rowspan="{{ $totalRowsForDay }}">
                                    <div class="writing-vertical mx-auto">{{ $hari }}</div>
                                </td>
                                <td
                                    class="border border-gray-800 p-0.5 font-bold text-center text-[8px] tracking-tighter row-lkd">
                                    07.00-07.15</td>
                                <td class="border border-gray-800 p-0.5 font-black text-center uppercase tracking-widest row-lkd"
                                    colspan="{{ $totalCols + 1 }}">
                                    LKD/KULTUM
                                </td>
                            </tr>
                            <tr>
                                <td
                                    class="border border-gray-800 p-0.5 font-bold text-center text-[8px] tracking-tighter row-lkd">
                                    07.15-08.00</td>
                                <td class="border border-gray-800 p-0.5 font-black text-center uppercase tracking-widest row-lkd"
                                    colspan="{{ $totalCols + 1 }}">
                                    QIROATUL QUR'AN
                                </td>
                            </tr>
                        @endif

                        <!-- Loop JTM -->
                        @for($jam = 1; $jam <= $jmlJam; $jam++)

                            {{-- SISIPAN ISTIRAHAT --}}
                            @if($hari !== 'Jumat' && $jam === 5)
                                <tr class="row-istirahat">
                                    <td class="border border-gray-800 p-0.5 font-bold text-center text-[7px] tracking-tighter">
                                        09.20-09.50</td>
                                    <td class="border border-gray-800 p-0 text-[10px] font-black text-center uppercase tracking-widest"
                                        colspan="{{ $totalCols + 1 }}">ISTIRAHAT</td>
                                </tr>
                            @elseif($hari === 'Jumat' && $jam === 4)
                                <tr class="row-istirahat">
                                    <td class="border border-gray-800 p-0.5 font-bold text-center text-[7px] tracking-tighter">
                                        09.30-09.50</td>
                                    <td class="border border-gray-800 p-0 text-[10px] font-black text-center uppercase tracking-widest"
                                        colspan="{{ $totalCols + 1 }}">ISTIRAHAT</td>
                                </tr>
                            @endif

                            {{-- SISIPAN MAKAN/SHALAT --}}
                            @if($hari !== 'Jumat' && $jam === 8)
                                <tr class="row-makan">
                                    <td class="border border-gray-800 p-0.5 font-bold text-center text-[7px] tracking-tighter">
                                        11.35-12.25</td>
                                    <td class="border border-gray-800 p-0 text-[9px] font-black text-center uppercase tracking-widest"
                                        colspan="{{ $totalCols + 1 }}">PENDISTRIBUSIAN MAKAN BERGIZI GRATIS</td>
                                </tr>
                                <tr class="row-sholat">
                                    <td class="border border-gray-800 p-0.5 font-bold text-center text-[7px] tracking-tighter">
                                        12.25-13.05</td>
                                    <td class="border border-gray-800 p-0 text-[10px] font-black text-center uppercase tracking-widest"
                                        colspan="{{ $totalCols + 1 }}">SHALAT DUHUR</td>
                                </tr>
                            @endif

                            <!-- Baris JTM -->
                            <tr class="{{ (!in_array($hari, ['Senin', 'Jumat']) && $jam === 1) ? $dayTopBorder : '' }}">
                                @if(!in_array($hari, ['Senin', 'Jumat']) && $jam === 1)
                                    <td class="border border-gray-800 font-bold p-0.5 text-center bg-gray-50 align-middle"
                                        rowspan="{{ $totalRowsForDay }}">
                                        <div class="writing-vertical mx-auto">{{ $hari }}</div>
                                    </td>
                                @endif
                                <td
                                    class="border border-gray-800 p-0.5 font-bold text-center bg-gray-50 text-[8px] tracking-tighter whitespace-nowrap">
                                    {{ $currentLabels[$jam] ?? '-' }}</td>
                                <td class="border border-gray-800 p-0.5 font-bold text-center bg-gray-100">{{ $jam }}</td>
                                @foreach($kelasList as $tingkat => $kelas)
                                    @foreach($kelas as $kItem)
                                        @php
                                            $slot = $grid[$hari][$jam][$kItem->id] ?? null;
                                            $kg = $slot ? $slot->bebanMengajar->guru->kode_guru : '';
                                            $tName = $slot ? $slot->bebanMengajar->guru->nama_guru : '';
                                            $mName = $slot ? $slot->bebanMengajar->mapel->nama_mapel : '';

                                            $guruId = $slot ? $slot->bebanMengajar->guru_id : null;

                                            // Deteksi Bentrok Visual
                                            $isBentrok = false;
                                            if ($kg) {
                                                foreach ($analisa['bentrok'] as $b) {
                                                    if ($b['hari'] == $hari && $b['jam'] == $jam && $b['guru'] == $kg) {
                                                        $isBentrok = true;
                                                        break;
                                                    }
                                                }
                                            }

                                            $colorIndex = $guruId ? ($guruId % 7) : -1;
                                            $bgColors = ['bg-red-50', 'bg-blue-50', 'bg-green-50', 'bg-yellow-50', 'bg-purple-50', 'bg-pink-50', 'bg-indigo-50'];
                                            $bg = $colorIndex >= 0 ? $bgColors[$colorIndex] : 'bg-white';
                                            if ($isBentrok)
                                                $bg = 'bg-red-600 text-white'; // HIGHLIGHT MERAH JIKA BENTROK
                                            
                                            // Force Dark Green for Friday 5th period
                                            if ($hari === 'Jumat' && $jam === 5 && !$isBentrok) {
                                                $bg = 'cell-jumat-5';
                                            }
                                        @endphp
                                        <td class="border border-gray-800 p-0 text-center font-bold {{ $bg }} {{ $isBentrok ? 'shadow-[inset_0_0_4px_rgba(0,0,0,0.5)]' : ($selectedSemester->is_active ? 'hover:bg-indigo-200 cursor-pointer' : 'cursor-default opacity-80') }} transition-colors leading-tight relative"
                                            @if($selectedSemester->is_active)
                                                @dblclick="editCell('{{ $hari }}', {{ $jam }}, {{ $kItem->id }}, '{{ $kg }}', $event)"
                                            @endif
                                            title="{{ $slot ? "[$kg] $tName - $mName" : 'Kosong' }}{{ $isBentrok ? ' (BENTROK!)' : '' }}">
                                            {{ $kg }}
                                        </td>
                                    @endforeach
                                @endforeach
                            </tr>
                        @endfor

                        {{-- Akhir Jumat --}}
                        @if($hari === 'Jumat')
                            <tr class="row-makan">
                                <td class="border border-gray-800 p-0.5 font-bold text-center text-[7px] tracking-tighter">
                                    10.50-11.20</td>
                                <td class="border border-gray-800 p-0 text-[10px] font-black text-center uppercase tracking-widest"
                                    colspan="{{ $totalCols + 1 }}">PENDISTRIBUSIAN MAKAN BERGIZI GRATIS</td>
                            </tr>
                            <tr class="row-sholat">
                                <td class="border border-gray-800 p-0.5 font-bold text-center text-[7px] tracking-tighter">
                                    11.20-12.30</td>
                                <td class="border border-gray-800 p-0 text-[10px] font-black text-center uppercase tracking-widest"
                                    colspan="{{ $totalCols + 1 }}">SHALAT JUM'AT BERJAMAAH</td>
                            </tr>
                            <tr class="row-pramuka">
                                <td class="border border-gray-800 p-0.5 font-bold text-center text-[7px] tracking-tighter">
                                    12.30-14.30</td>
                                <td class="border border-gray-800 p-0 text-[10px] font-black text-center uppercase tracking-widest"
                                    colspan="{{ $totalCols + 1 }}">EKSTRAKURIKULER PRAMUKA</td>
                            </tr>
                        @endif

                    @endforeach
                </tbody>
            </table>

        </div>
        </div>

            <script>
                document.addEventListener('alpine:init', () => {
                    Alpine.data('scheduler', () => ({
                        viewMode: 'matrix',
                        showEditorModal: false,
                        editor: null,
                        slotData: @json($slotData ?? []),
                        strukturHari: @json($strukturHari ?? []),
                        jamLabels: @json($jamLabels ?? []),
                        kelasFlat: @json($kelasFlat ?? []),
                        guruList: @json($guruList ?? []),
                        days: ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'],
                        selectedKelasId: @json(collect($kelasFlat ?? [])->pluck('id')->first()),
                        selectedGuruIdView: @json(collect($guruList ?? [])->pluck('id')->first()),
                        selectedHariView: 'Senin',
                        strukturHariTotal: {{ array_sum($strukturHari ?? []) }},
                        lastEditing: null,
                        showAnalysis: false,
                        showConstraintModal: false,
                        // MODAL LOADING STATE (V2.6)
                        showLoading: false,
                        loadingCounter: 0,
                        loadingInterval: null,
                        semester_id: '{{ $selectedSemester->id }}',
                        is_active: {{ $selectedSemester->is_active ? 'true' : 'false' }},

                        startLoading() {
                            this.showLoading = true;
                            this.loadingCounter = 0;
                            // Increment counter rapidly to emphasize AI processing
                            this.loadingInterval = setInterval(() => {
                                // Pertambahan angka yang lebih dinamis dan tidak terbatas (WOW Effect)
                                let jump = Math.floor(Math.random() * 350) + 150;
                                if (this.loadingCounter > 100000) jump += Math.floor(Math.random() * 1000);
                                if (this.loadingCounter > 1000000) jump += Math.floor(Math.random() * 5000);
                                
                                this.loadingCounter += jump;
                            }, 40);
                        },

                        constraintMode: true, // Internal flag for drag logic within modal
                        selectedGuruId: '',
                        isSelecting: false,
                        selectionType: null, // 0: block, 1: preserve, 2: reset
                        constraints: @json($constraints),
                        bebanData: @json($bebanPerKelas ?? []),

                        bebanForKelas(kelasId) {
                            return this.bebanData[kelasId] || [];
                        },

                        findBebanById(id) {
                            for (const kelasId in this.bebanData) {
                                const b = this.bebanData[kelasId].find(x => x.id == id);
                                if (b) return b;
                            }
                            return null;
                        },

                        mapelGroupsForKelas(kelasId) {
                            const map = {};
                            for (const b of this.bebanForKelas(kelasId)) {
                                if (!map[b.mapel]) {
                                    map[b.mapel] = { mapel: b.mapel, total: 0, placed: 0 };
                                }
                                map[b.mapel].total += b.jtm;
                                map[b.mapel].placed += b.placed;
                            }
                            return Object.values(map).map(mg => ({
                                ...mg,
                                isFull: mg.placed >= mg.total,
                            }));
                        },

                        guruOptionsForKelasMapel(kelasId, mapelName) {
                            if (!mapelName) return [];
                            return this.bebanForKelas(kelasId).filter(b => b.mapel === mapelName && b.placed < b.jtm);
                        },

                        mapelGroupsForGuru(guruId) {
                            const map = {};
                            for (const b of this.bebanListForGuruIncomplete(guruId)) {
                                if (!map[b.mapel]) {
                                    map[b.mapel] = { mapel: b.mapel, total: 0, placed: 0 };
                                }
                                map[b.mapel].total += b.jtm;
                                map[b.mapel].placed += b.placed;
                            }
                            return Object.values(map);
                        },

                        kelasOptionsForGuruMapel(guruId, mapelName) {
                            if (!mapelName) return [];
                            return this.bebanListForGuruIncomplete(guruId).filter(b => b.mapel === mapelName);
                        },

                        guruListForKelas(kelasId, extraGuruId = null) {
                            const seen = {};
                            const out = [];
                            for (const b of this.bebanForKelas(kelasId)) {
                                const eligible = b.placed < b.jtm || b.guru_id == extraGuruId;
                                if (eligible && !seen[b.guru_id]) {
                                    seen[b.guru_id] = true;
                                    out.push({ guru_id: b.guru_id, kg: b.kg, guru: b.guru });
                                }
                            }
                            return out.sort((a, b) => a.kg.localeCompare(b.kg));
                        },

                        mapelOptionsForKelasGuru(kelasId, guruId) {
                            if (!guruId) return [];
                            return this.bebanForKelas(kelasId).filter(b => b.guru_id == guruId && b.placed < b.jtm);
                        },

                        editorSubtitle() {
                            if (!this.editor) return '';
                            let s = this.editor.hari + ' · Jam ' + this.editor.jam;
                            if (this.editor.kelasId) s += ' · Kelas ' + this.kelasName(this.editor.kelasId);
                            return s;
                        },

                        onMapelSelectKelas() {
                            const opts = this.guruOptionsForKelasMapel(this.editor.kelasId, this.editor.selectedMapel);
                            if (opts.length === 1) {
                                this.editor.selectedBebanId = opts[0].id;
                            } else {
                                this.editor.selectedBebanId = null;
                            }
                        },

                        onMapelSelectGuru() {
                            const opts = this.kelasOptionsForGuruMapel(this.editor.guruId, this.editor.selectedMapel);
                            if (opts.length === 1) {
                                this.editor.selectedBebanId = opts[0].id;
                                this.editor.kelasId = opts[0].kelas_id;
                            } else {
                                this.editor.selectedBebanId = null;
                            }
                        },

                        onMatrixKgSelect() {
                            const opts = this.mapelOptionsForKelasGuru(this.editor.kelasId, this.editor.selectedGuruId);
                            const available = opts.filter(b => b.placed < b.jtm);
                            if (available.length === 1) {
                                this.editor.selectedBebanId = available[0].id;
                                this.editor.selectedMapel = available[0].mapel;
                            } else {
                                this.editor.selectedBebanId = null;
                                this.editor.selectedMapel = '';
                            }
                            this.editor.blockHours = 1;
                        },

                        onMatrixMapelSelect() {
                            const b = this.findBebanById(this.editor.selectedBebanId);
                            if (b) this.editor.selectedMapel = b.mapel;
                            this.editor.blockHours = 1;
                        },

                        maxBlockHours() {
                            if (!this.editor?.selectedBebanId) return 1;
                            const beban = this.findBebanById(this.editor.selectedBebanId);
                            const remaining = beban ? Math.max(0, beban.jtm - beban.placed) : 0;
                            const maxJam = this.strukturHari[this.editor.hari] || 0;
                            let consecutive = 0;
                            for (let j = this.editor.jam; j <= maxJam; j++) {
                                const slot = this.getSlot(this.editor.hari, j, this.editor.kelasId);
                                if (slot && j !== this.editor.jam) break;
                                if (slot && j === this.editor.jam && slot.beban_id != this.editor.selectedBebanId) break;
                                consecutive++;
                            }
                            return Math.min(consecutive, remaining, 3) || 1;
                        },

                        resolveEditorContext() {
                            if (this.viewMode === 'matrix') return 'matrix';
                            if (this.viewMode === 'guru') return 'guru';
                            if (this.viewMode === 'hari') return 'hari';
                            return 'kelas';
                        },

                        openEditor(hari, jam, kelasId, slot = null, context = null) {
                            if (!this.is_active) return;
                            const ctx = context || this.resolveEditorContext();
                            const s = slot || this.getSlot(hari, jam, kelasId);
                            this.editor = {
                                hari, jam, kelasId,
                                context: ctx,
                                selectedMapel: s?.mapel || '',
                                selectedBebanId: s?.beban_id || null,
                                selectedGuruId: s?.guru_id || null,
                                guruId: null,
                                blockHours: 1,
                            };
                            if (ctx === 'kelas' || ctx === 'hari') {
                                if (s?.mapel) this.onMapelSelectKelas();
                            } else if (ctx === 'matrix' && s) {
                                this.editor.selectedGuruId = s.guru_id;
                                this.onMatrixKgSelect();
                            }
                            this.showEditorModal = true;
                        },

                        openEditorFromGuru(hari, jam) {
                            if (!this.is_active) return;
                            const slot = this.findGuruSlot(this.selectedGuruIdView, hari, jam);
                            if (slot) {
                                this.editor = {
                                    hari, jam,
                                    kelasId: slot.kelas_id,
                                    context: 'guru',
                                    guruId: this.selectedGuruIdView,
                                    selectedMapel: slot.mapel,
                                    selectedBebanId: slot.beban_id,
                                    selectedGuruId: null,
                                    blockHours: 1,
                                };
                            } else {
                                this.editor = {
                                    hari, jam, kelasId: null,
                                    context: 'guru',
                                    guruId: this.selectedGuruIdView,
                                    selectedMapel: '',
                                    selectedBebanId: null,
                                    selectedGuruId: null,
                                    blockHours: 1,
                                };
                            }
                            this.showEditorModal = true;
                        },

                        closeEditor() {
                            this.showEditorModal = false;
                            this.editor = null;
                        },

                        clearSlot() {
                            if (!this.editor) return;
                            const { hari, jam, kelasId } = this.editor;
                            this.closeEditor();
                            this.postSlotUpdate(hari, jam, kelasId, null, false);
                        },

                        saveFromEditor(force = false) {
                            if (!this.editor && !force) return;

                            let bebanId = this.editor.selectedBebanId;
                            let kelasId = this.editor.kelasId;

                            if (!bebanId && this.editor.selectedMapel) {
                                if (this.editor.context === 'guru') {
                                    const opts = this.kelasOptionsForGuruMapel(this.editor.guruId, this.editor.selectedMapel);
                                    if (opts.length === 1) {
                                        bebanId = opts[0].id;
                                        kelasId = opts[0].kelas_id;
                                    }
                                } else if (this.editor.context === 'kelas' || this.editor.context === 'hari') {
                                    const opts = this.guruOptionsForKelasMapel(kelasId, this.editor.selectedMapel);
                                    if (opts.length === 1) bebanId = opts[0].id;
                                }
                            }

                            if (!bebanId && this.editor.context === 'matrix') {
                                Swal.fire({ icon: 'warning', title: 'Belum lengkap', text: 'Pilih KG dan mapel terlebih dahulu.', confirmButtonColor: '#4f46e5' });
                                return;
                            }

                            if (!bebanId && this.editor.context !== 'matrix') {
                                Swal.fire({ icon: 'warning', title: 'Belum lengkap', text: 'Pilih mapel (dan guru/kelas jika perlu).', confirmButtonColor: '#4f46e5' });
                                return;
                            }

                            const beban = this.findBebanById(bebanId);
                            if (beban && beban.placed >= beban.jtm) {
                                Swal.fire({ icon: 'warning', title: 'Mapel penuh', text: 'Jam mapel ini sudah terpenuhi.', confirmButtonColor: '#4f46e5' });
                                return;
                            }

                            if (beban) kelasId = beban.kelas_id;

                            this.lastEditing = { hari: this.editor.hari, jam: this.editor.jam, kelasId };

                            const blockHours = (this.editor.context === 'matrix') ? (this.editor.blockHours || 1) : 1;
                            this.closeEditor();

                            if (blockHours > 1) {
                                this.postSlotUpdateBlock(this.lastEditing.hari, this.lastEditing.jam, kelasId, bebanId, blockHours, force);
                            } else {
                                this.postSlotUpdate(this.lastEditing.hari, this.lastEditing.jam, kelasId, bebanId, force);
                            }
                        },

                        async postSlotUpdateBlock(hari, startJam, kelasId, bebanId, count, force = false) {
                            for (let i = 0; i < count; i++) {
                                const jam = startJam + i;
                                const existing = this.getSlot(hari, jam, kelasId);
                                if (existing && i > 0) {
                                    Swal.fire({ icon: 'error', title: 'Slot terisi', text: `Jam ke-${jam} sudah terisi.`, confirmButtonColor: '#4f46e5' });
                                    return;
                                }
                                try {
                                    const res = await fetch('{{ route('jadwal.update-slot') }}', {
                                        method: 'POST',
                                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                                        body: JSON.stringify({
                                            hari, jam_ke: jam, kelas_id: kelasId,
                                            beban_mengajar_id: bebanId,
                                            semester_id: this.semester_id,
                                            force: force ? 1 : 0,
                                            _token: '{{ csrf_token() }}'
                                        })
                                    });
                                    const data = await res.json();
                                    if (data.has_conflict && !force) {
                                        Swal.fire({
                                            icon: 'warning', title: 'Guru Bentrok!',
                                            html: `<div class="text-sm text-left">${data.message}</div>`,
                                            showCancelButton: true,
                                            confirmButtonText: 'Lanjutkan',
                                            cancelButtonText: 'Batalkan',
                                            confirmButtonColor: '#4f46e5',
                                        }).then(r => {
                                            if (r.isConfirmed) this.postSlotUpdateBlock(hari, startJam, kelasId, bebanId, count, true);
                                        });
                                        return;
                                    }
                                    if (!res.ok || !data.success) {
                                        Swal.fire({ icon: 'error', title: 'Gagal', text: data.message || 'Gagal menyimpan.', confirmButtonColor: '#4f46e5' });
                                        return;
                                    }
                                } catch {
                                    Swal.fire({ icon: 'error', title: 'Koneksi Terputus', confirmButtonColor: '#4f46e5' });
                                    return;
                                }
                            }
                            location.reload();
                        },

                        postSlotUpdate(hari, jam, kelasId, bebanId, force = false) {
                            const payload = {
                                hari, jam_ke: jam, kelas_id: kelasId,
                                beban_mengajar_id: bebanId,
                                semester_id: this.semester_id,
                                force: force ? 1 : 0,
                                _token: '{{ csrf_token() }}'
                            };

                            fetch('{{ route('jadwal.update-slot') }}', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                                body: JSON.stringify(payload)
                            })
                                .then(async res => {
                                    const data = await res.json();
                                    if (res.ok && data.success) {
                                        location.reload();
                                    } else if (data.has_conflict) {
                                        Swal.fire({
                                            icon: 'warning',
                                            title: 'Guru Bentrok!',
                                            html: `<div class="text-sm border-l-4 border-amber-500 pl-3 bg-amber-50 py-2 text-amber-800 text-left font-bold">${data.message}</div><p class="text-xs text-gray-500 mt-3 italic">*Apakah Anda ingin tetap menyimpan jadwal ini?</p>`,
                                            showCancelButton: true,
                                            confirmButtonColor: '#4f46e5',
                                            cancelButtonColor: '#6b7280',
                                            confirmButtonText: 'Lanjutkan Simpan',
                                            cancelButtonText: 'Batalkan',
                                        }).then((result) => {
                                            if (result.isConfirmed) {
                                                this.postSlotUpdate(hari, jam, kelasId, bebanId, true);
                                            }
                                        });
                                    } else {
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Gagal Simpan',
                                            text: data.message || 'Gagal menyimpan jadwal.',
                                            confirmButtonColor: '#4f46e5',
                                        });
                                    }
                                })
                                .catch(() => {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Koneksi Terputus',
                                        text: 'Gagal koneksi ke server.',
                                        confirmButtonColor: '#4f46e5',
                                    });
                                });
                        },

                        getSlot(hari, jam, kelasId) {
                            return this.slotData?.[hari]?.[jam]?.[kelasId] ?? null;
                        },

                        getJamLabel(hari, jam) {
                            if (hari === 'Senin') return this.jamLabels['Senin']?.[jam] ?? '';
                            if (hari === 'Jumat') return this.jamLabels['Jumat']?.[jam] ?? '';
                            return this.jamLabels['Selasa-Kamis']?.[jam] ?? '';
                        },

                        jamRange(hari) {
                            const n = this.strukturHari[hari] || 0;
                            return Array.from({ length: n }, (_, i) => i + 1);
                        },

                        kelasName(kelasId) {
                            const k = this.kelasFlat.find(c => c.id == kelasId);
                            return k ? k.nama : kelasId;
                        },

                        kelasFilledCount(kelasId) {
                            let c = 0;
                            for (const h of this.days) {
                                for (const j of this.jamRange(h)) {
                                    if (this.getSlot(h, j, kelasId)) c++;
                                }
                            }
                            return c;
                        },

                        hariFilledCount(hari) {
                            let c = 0;
                            for (const k of this.kelasFlat) {
                                for (const j of this.jamRange(hari)) {
                                    if (this.getSlot(hari, j, k.id)) c++;
                                }
                            }
                            return c;
                        },

                        incompleteBebanForKelas(kelasId) {
                            return (this.bebanData[kelasId] || []).filter(b => b.placed < b.jtm);
                        },

                        bebanListForGuruIncomplete(guruId) {
                            const out = [];
                            for (const kelasId in this.bebanData) {
                                for (const b of this.bebanData[kelasId]) {
                                    if (b.guru_id == guruId && b.placed < b.jtm) out.push(b);
                                }
                            }
                            return out;
                        },

                        guruPlacedCount(guruId) {
                            let c = 0;
                            for (const kelasId in this.bebanData) {
                                for (const b of this.bebanData[kelasId]) {
                                    if (b.guru_id == guruId) c += b.placed;
                                }
                            }
                            return c;
                        },

                        guruJtmTotal(guruId) {
                            let t = 0;
                            for (const kelasId in this.bebanData) {
                                for (const b of this.bebanData[kelasId]) {
                                    if (b.guru_id == guruId) t += b.jtm;
                                }
                            }
                            return t;
                        },

                        findGuruSlot(guruId, hari, jam) {
                            const row = this.slotData?.[hari]?.[jam] || {};
                            for (const kelasId in row) {
                                const s = row[kelasId];
                                if (s && s.guru_id == guruId) {
                                    return { ...s, kelas_id: parseInt(kelasId) };
                                }
                            }
                            return null;
                        },

                        hasConstraintForGuru(guruId, hari, jam, type) {
                            if (!guruId || !this.constraints[guruId]) return false;
                            return (this.constraints[guruId] || []).some(c => c.hari == hari && c.jam_ke == jam && c.type == type);
                        },

                        hasConstraint(hari, jam, type) {
                            if (!this.selectedGuruId || !this.constraints[this.selectedGuruId]) return false;
                            return (this.constraints[this.selectedGuruId] || []).some(c => c.hari == hari && c.jam_ke == jam && c.type == type);
                        },

                        startSelection(hari, jam, event) {
                            if (!this.selectedGuruId) {
                                Swal.fire({
                                    icon: 'warning',
                                    title: 'Pilih Guru',
                                    text: 'Silakan pilih guru terlebih dahulu dari daftar di atas matriks.',
                                    confirmButtonColor: '#4f46e5',
                                });
                                return;
                            }
                            this.isSelecting = true;

                            if (event.shiftKey) this.selectionType = 0;
                            else if (event.ctrlKey) this.selectionType = 1;
                            else if (event.altKey) this.selectionType = 2;
                            else this.selectionType = 0; // Default Shift

                            this.applyConstraint(hari, jam);
                        },

                        handleMouseEnter(hari, jam, event) {
                            if (this.isSelecting) {
                                this.applyConstraint(hari, jam);
                            }
                        },

                        endSelection() {
                            this.isSelecting = false;
                        },

                        async applyConstraint(hari, jam) {
                            const payload = {
                                guru_id: this.selectedGuruId,
                                hari: hari,
                                jam_ke: jam,
                                type: this.selectionType,
                                semester_id: this.semester_id,
                                _token: '{{ csrf_token() }}'
                            };

                            // Update local state instant
                            if (!this.constraints[this.selectedGuruId]) this.constraints[this.selectedGuruId] = [];
                            this.constraints[this.selectedGuruId] = this.constraints[this.selectedGuruId].filter(c => !(c.hari == hari && c.jam_ke == jam));
                            if (this.selectionType !== 2) {
                                this.constraints[this.selectedGuruId].push({ hari, jam_ke: jam, type: this.selectionType });
                            }

                            try {
                                await fetch('{{ route('jadwal.toggle-constraint') }}', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json' },
                                    body: JSON.stringify(payload)
                                });
                            } catch (e) { console.error('Gagal simpan ketentuan'); }
                        },

                        editCell(hari, jam, kelasId, currentKg, event) {
                            const slot = this.getSlot(hari, jam, kelasId);
                            this.openEditor(hari, jam, kelasId, slot, 'matrix');
                        },
                    }));
                });
            </script>

            <style>
                [x-cloak] {
                    display: none !important;
                }

                /* Pelangi Jadwal (Sync with Print) */
                .row-upacara { background-color: #4b54b5 !important; color: white !important; }
                .row-istirahat { background-color: #f15151 !important; color: white !important; }
                .row-makan { background-color: #9fc5e8 !important; color: #1e3a8a !important; }
                .row-sholat { background-color: #42b419 !important; color: white !important; }
                .row-lkd { background-color: #42b419 !important; color: white !important; }
                .row-pramuka { background-color: #4b54b5 !important; color: white !important; }
                .cell-jumat-5 { background-color: #354c29 !important; color: white !important; }

                .border-t-\[4px\] {
                    border-top-width: 4px !important;
                }

                .writing-vertical {
                    writing-mode: vertical-rl;
                    text-orientation: mixed;
                    transform: rotate(180deg);
                    font-weight: 800;
                    font-size: 10px;
                    letter-spacing: 0.2em;
                    text-transform: uppercase;
                }

                @media print {
                    @page {
                        size: A4 portrait;
                        margin: 5mm;
                    }

                    body {
                        background: white !important;
                        -webkit-print-color-adjust: exact !important;
                        print-color-adjust: exact !important;
                    }

                    .print\:hidden {
                        display: none !important;
                    }

                    header,
                    aside,
                    .sidebar {
                        display: none !important;
                    }

                    main {
                        padding: 0 !important;
                        margin: 0 !important;
                        width: 100% !important;
                    }

                    .overflow-x-auto {
                        overflow: visible !important;
                    }

                    table {
                        width: 100% !important;
                        font-size: 8px !important;
                        border-color: black !important;
                    }

                    .bg-indigo-900 {
                        background-color: transparent !important;
                        color: black !important;
                    }
                }
            </style>
    </div>
    @endif
@endsection