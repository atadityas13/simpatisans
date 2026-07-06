@extends('layouts.app')
@section('header', 'Pembagian Tugas Mengajar')
@section('content')
    @if(!$selectedSemester)
        <div
            class="bg-amber-50 border border-amber-200 text-amber-800 rounded-xl p-8 flex flex-col items-center justify-center text-center space-y-4 mb-8">
            <svg class="w-16 h-16 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div>
                <h3 class="text-xl font-black uppercase tracking-widest text-amber-900">Belum Ada Semester Aktif</h3>
                <p class="text-sm mt-2 font-medium">Silakan buat dan aktifkan semester pada menu pengaturan terlebih dahulu
                    untuk mengelola pembagian tugas.</p>
            </div>
            <a href="{{ route('semester.index') }}"
                class="mt-4 bg-amber-500 hover:bg-amber-600 text-black px-6 py-2.5 rounded-lg font-bold text-sm uppercase tracking-widest shadow-md transition transform hover:-translate-y-0.5">Atur
                Semester Sekarang</a>
        </div>
    @else
        <div
            class="mb-6 flex flex-col md:flex-row justify-between items-center bg-white p-4 rounded-xl border border-gray-100 shadow-sm gap-4">
            <div class="flex-1">
                <h2 class="text-xl font-bold text-gray-900">Distribusi Beban Mengajar</h2>
                <p class="text-gray-500 text-xs mt-0.5">Distribusi JTM, tugas tambahan dan monitoring beban kerja guru.</p>
            </div>

            <form action="{{ route('pembagian.index') }}" method="GET" class="flex items-center space-x-2">
                <select name="semester_id" onchange="this.form.submit()"
                    class="bg-gray-50 border border-gray-200 text-gray-900 text-xs rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 font-bold">
                    @foreach($allSemesters as $sem)
                        <option value="{{ $sem->id }}" {{ $selectedSemester->id == $sem->id ? 'selected' : '' }}>
                            {{ $sem->nama_tahun }} - {{ $sem->tipe }} {{ $sem->is_active ? '(Aktif)' : '' }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>

        @if(!$selectedSemester->is_active)
            <div class="mb-6 bg-amber-50 border border-amber-200 p-4 rounded-xl flex items-center gap-3">
                <div class="bg-amber-100 p-2 rounded-lg text-amber-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 15v2m0 0v3m0-3h3m-3 0H9m12-3a9 9 0 11-18 0 9 9 0 0118 0zM15 7h.01M9 7h.01M15 11h.01M9 11h.01" />
                    </svg>
                </div>
                <div>
                    <p class="text-amber-900 font-bold text-sm uppercase tracking-tight">ARSIP</p>
                    <p class="text-amber-700 text-xs mt-0.5">Anda memilih filter semester lain. Hanya dapat melihat data.</p>
                </div>
            </div>
        @endif

        <div x-data="{
            guruSearch: '',
            guruSearchBlobs: @json($guruSearchBlobs ?? []),
            rowMatches(blob) {
                const q = this.guruSearch.trim().toLowerCase();
                return !q || blob.includes(q);
            },
            guruMatchCount() {
                const q = this.guruSearch.trim().toLowerCase();
                if (!q) return this.guruSearchBlobs.length;
                return this.guruSearchBlobs.filter(b => b.includes(q)).length;
            },
            guruFilterEmpty() {
                const q = this.guruSearch.trim().toLowerCase();
                return q && this.guruMatchCount() === 0;
            }
        }" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            @include('partials.guru-search-bar')

            <table class="w-full whitespace-nowrap text-sm">
                <thead>
                    <tr
                        class="bg-gray-50 border-b border-gray-200 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        <th class="px-5 py-4">Nama Guru</th>
                        <th class="px-5 py-4 text-center">JTM KBM</th>
                        <th class="px-5 py-4 text-center">Tugas Tambahan</th>
                        <th class="px-5 py-4 text-center">Total Beban</th>
                        <th class="px-5 py-4 text-center">JTM Linear</th>
                        <th class="px-5 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($gurus as $i => $guru)
                        @php $m = $rows[$i]; @endphp
                        <tr class="hover:bg-gray-50 transition" data-search="{{ e($guruSearchBlobs[$i] ?? '') }}" x-show="rowMatches($el.dataset.search)">
                            <td class="px-5 py-4">
                                <p class="font-bold text-gray-900">{{ $guru->nama_lengkap }}</p>
                                <p class="text-[11px] text-indigo-600 font-black mt-0.5 uppercase tracking-wide">{{ $guru->kode_guru }}</p>
                                <p class="text-[11px] text-gray-500 mt-0.5">
                                    {{ $guru->status_sertifikasi ? 'Sertifikasi: ' . $guru->mapelSertifikasi?->nama_mapel : 'Belum Sertifikasi' }}
                                </p>
                                <div class="mt-1">
                                    @if($m['layak'] === null)
                                        <span
                                            class="text-[10px] font-medium text-gray-400 uppercase tracking-tight">Non-Sertifikasi</span>
                                    @elseif($m['layak'])
                                        <span class="px-2 py-0.5 bg-green-100 text-green-700 text-[10px] font-bold rounded uppercase">✓
                                            Layak TPG</span>
                                    @else
                                        <span class="px-2 py-0.5 bg-red-100 text-red-700 text-[10px] font-bold rounded uppercase">✗
                                            Belum Layak</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-5 py-4 text-center font-semibold text-gray-700">{{ $m['jtmKbm'] }} Jam</td>
                            <td class="px-5 py-4 text-center font-semibold text-gray-700">{{ $m['jtmTugas'] }} Jam</td>
                            <td class="px-5 py-4 text-center font-bold text-indigo-700">{{ $m['totalBeban'] }} Jam</td>
                            <td
                                class="px-5 py-4 text-center font-bold {{ $m['totalLinear'] >= $m['TARGET'] ? 'text-green-600' : 'text-orange-500' }}">
                                {{ $m['totalLinear'] }} Jam
                            </td>
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('pembagian.show', ['guru' => $guru->id, 'semester_id' => $selectedSemester->id]) }}"
                                    class="inline-flex items-center gap-1 p-2 bg-indigo-50 border border-indigo-200 text-indigo-700 hover:bg-indigo-600 hover:text-white rounded-xl transition shadow-sm"
                                    title="{{ $selectedSemester->is_active ? 'Atur Pembagian Tugas' : 'Lihat Detail Pembagian' }}">
                                    @if($selectedSemester->is_active)
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                        </svg>
                                        <svg class="w-3 h-3 -ml-2 mb-3 bg-white rounded-full p-0.5 shadow-sm ring-1 ring-indigo-200"
                                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        </svg>
                                    @else
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    @endif
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-10 text-center text-gray-400 italic">Belum ada data guru.</td>
                        </tr>
                    @endforelse
                    <tr x-show="guruFilterEmpty()" x-cloak>
                        <td colspan="6" class="px-5 py-10 text-center text-gray-500 italic">
                            Tidak ada guru yang cocok dengan pencarian &ldquo;<span x-text="guruSearch"></span>&rdquo;
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    @endif
@endsection