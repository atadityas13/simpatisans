@extends('layouts.app')

@section('header', 'Dashboard Guru')

@section('content')
@php
    $status = $tpgStatus['status'] ?? 'syncing';
    $isEligible = $status === 'eligible';
    $isIneligible = in_array($status, ['not_certified', 'deficit'], true);
    $statusCardClass = $isEligible
        ? 'border-emerald-100 bg-emerald-50'
        : ($isIneligible ? 'border-red-100 bg-red-50' : 'border-amber-100 bg-amber-50');
    $statusIconClass = $isEligible
        ? 'text-emerald-600'
        : ($isIneligible ? 'text-red-600' : 'text-amber-600');
    $statusTitleClass = $isEligible
        ? 'text-emerald-800'
        : ($isIneligible ? 'text-red-800' : 'text-amber-800');
    $statusSubtitleClass = $isEligible
        ? 'text-emerald-700'
        : ($isIneligible ? 'text-red-700' : 'text-amber-700');
@endphp

<div class="space-y-8">
    <div class="relative overflow-hidden rounded-[32px] bg-gradient-to-br from-indigo-700 via-indigo-600 to-emerald-600 p-8 text-white shadow-2xl shadow-indigo-200 sm:p-12">
        <div class="relative z-10 grid gap-8 lg:grid-cols-[1fr,340px] lg:items-center">
            <div>
                <p class="mb-3 inline-flex rounded-full bg-white/15 px-4 py-1.5 text-xs font-black uppercase tracking-[0.22em] text-indigo-100">
                    Akses Guru Web
                </p>
                <h2 class="text-3xl font-extrabold leading-tight sm:text-4xl">
                    Selamat datang, {{ auth()->user()->nama_lengkap ?? auth()->user()->username }}.
                </h2>
                <p class="mt-4 max-w-2xl text-lg font-medium text-indigo-50">
                    Fitur guru SimpatiSans sekarang digunakan melalui aplikasi Android Ta'lim. Halaman web ini hanya menampilkan ringkasan singkat dan tautan instalasi aplikasi.
                </p>
            </div>

            <div class="rounded-3xl border border-white/15 bg-white/10 p-5 backdrop-blur">
                <p class="text-sm font-bold text-indigo-100">Install Aplikasi Ta'lim</p>
                <p class="mt-2 text-sm text-white/80">Gunakan aplikasi Android untuk jadwal, kalender, pengingat, kinerja, dan fitur guru lainnya.</p>
                <a href="{{ $playStoreUrl }}" target="_blank" rel="noopener"
                    class="mt-5 inline-flex w-full items-center justify-center rounded-2xl bg-white px-5 py-3 text-sm font-extrabold text-indigo-700 shadow-lg transition hover:bg-indigo-50">
                    Buka di Google Play
                </a>
            </div>
        </div>
        <div class="absolute -bottom-24 -right-24 h-72 w-72 rounded-full bg-white/10 blur-3xl"></div>
    </div>

    <div class="grid grid-cols-1 gap-8 xl:grid-cols-[1fr,420px]">
        <div class="rounded-[28px] border border-slate-100 bg-white p-6 shadow-sm">
            <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-400">{{ $hariIni }}</p>
                    <h3 class="mt-1 text-2xl font-extrabold text-slate-900">Ringkasan Jadwal Hari Ini</h3>
                </div>
                @if($semester)
                    <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-bold text-indigo-700">
                        {{ $semester->nama_tahun }} - {{ $semester->tipe }}
                    </span>
                @endif
            </div>

            @if($jadwalHariIni->isEmpty())
                <div class="rounded-3xl border border-dashed border-slate-200 bg-slate-50/70 px-6 py-14 text-center">
                    <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-2xl bg-white text-indigo-500 shadow-sm">
                        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h4 class="text-lg font-bold text-slate-800">Tidak ada jadwal mengajar hari ini.</h4>
                    <p class="mt-2 text-sm text-slate-500">Detail jadwal lengkap tetap tersedia di aplikasi Android Ta'lim.</p>
                </div>
            @else
                <div class="space-y-3">
                    @foreach($jadwalHariIni as $jadwal)
                        <div class="flex flex-col gap-3 rounded-2xl border border-slate-100 bg-slate-50/60 p-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-xs font-black uppercase tracking-[0.18em] text-indigo-500">Jam ke {{ $jadwal['jam_ke'] }}</p>
                                <p class="mt-1 text-lg font-extrabold text-slate-900">{{ $jadwal['mapel'] ?? 'Pelajaran' }}</p>
                                <p class="text-sm font-medium text-slate-500">{{ $jadwal['kelas'] ?? 'Kelas belum tersedia' }}</p>
                            </div>
                            <span class="rounded-full bg-white px-3 py-1.5 text-sm font-bold text-slate-600 shadow-sm">
                                {{ $jadwal['waktu'] ?? 'Waktu belum tersedia' }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="space-y-6">
            <div class="rounded-[28px] border {{ $statusCardClass }} p-6 shadow-sm">
                <div class="mb-4 flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white {{ $statusIconClass }} shadow-sm">
                        @if($isEligible)
                            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        @elseif($isIneligible)
                            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        @else
                            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        @endif
                    </div>
                    <div>
                        <h3 class="text-lg font-extrabold {{ $statusTitleClass }}">Status Kelayakan TPG</h3>
                        <p class="text-sm font-semibold {{ $statusSubtitleClass }}">
                            Semester {{ $semester?->tipe ?? '-' }} TP {{ $semester?->nama_tahun ?? '-' }}
                        </p>
                    </div>
                </div>
                <p class="text-sm font-medium leading-6 text-slate-700">
                    {{ $tpgStatus['message'] ?? 'Status kelayakan TPG sedang disinkronkan dari SimpatiSans.' }}
                </p>
                @if($tpgStatus)
                    <div class="mt-5 grid grid-cols-2 gap-3">
                        <div class="rounded-2xl bg-white/75 p-4">
                            <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Linear</p>
                            <p class="mt-1 text-xl font-extrabold text-slate-900">{{ $tpgStatus['total_linear_jam'] }} jam</p>
                        </div>
                        <div class="rounded-2xl bg-white/75 p-4">
                            <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Target</p>
                            <p class="mt-1 text-xl font-extrabold text-slate-900">{{ $tpgStatus['target_jam'] }} jam</p>
                        </div>
                    </div>
                @endif
            </div>

            <div class="rounded-[28px] border border-slate-100 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-extrabold text-slate-900">Informasi Penggunaan</h3>
                <ul class="mt-4 space-y-3 text-sm font-medium text-slate-600">
                    <li class="flex gap-3">
                        <span class="mt-1 h-2 w-2 rounded-full bg-indigo-500"></span>
                        Menu guru di web dinonaktifkan agar semua fitur guru terpusat di aplikasi Android.
                    </li>
                    <li class="flex gap-3">
                        <span class="mt-1 h-2 w-2 rounded-full bg-indigo-500"></span>
                        Login aplikasi menggunakan username dan password SimpatiSans yang sama.
                    </li>
                    <li class="flex gap-3">
                        <span class="mt-1 h-2 w-2 rounded-full bg-indigo-500"></span>
                        Jika Play Store belum menampilkan aplikasi, tunggu sampai rilis testing/production aktif.
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
