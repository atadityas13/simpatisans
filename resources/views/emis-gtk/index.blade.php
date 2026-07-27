@extends('layouts.app')

@section('header', 'EXPORT EMIS-GTK')

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-3xl p-8 border border-gray-100 shadow-sm relative overflow-hidden">
        <div class="relative z-10">
            <h2 class="text-2xl font-black text-gray-900 mb-2">Export Jadwal ke EMIS-GTK</h2>
            <p class="text-gray-500 font-medium max-w-3xl">
                Generate file Excel sesuai template EMIS-GTK dari jadwal SimpatiSans.
                Struktur mengikuti template resmi (kolom Kelas = tingkat, Rombel = nama unik seperti <strong>8-01</strong>, <strong>9-02</strong>).
                Pastikan nama rombel di EMIS-GTK sudah memakai format ini agar import tidak error.
            </p>
        </div>
        <div class="absolute top-0 right-0 -translate-y-12 translate-x-12 w-64 h-64 bg-teal-50 rounded-full blur-3xl opacity-50"></div>
    </div>

    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl p-4 text-sm font-medium">
            {{ session('success') }}
        </div>
    @endif

    @if(session('emis_export_report'))
        @php $emisReport = session('emis_export_report'); @endphp
        <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 text-sm space-y-2">
            <p class="font-black text-slate-800 uppercase text-xs tracking-widest">Laporan Export Terakhir</p>
            <p class="text-emerald-700 font-bold">Slot terisi: {{ $emisReport['filled'] ?? 0 }}</p>
            @if(!empty($emisReport['skipped_tingkat_belum_di_template']))
                <p class="text-amber-700"><strong>Belum ada di template EMIS:</strong> {{ implode(', ', $emisReport['skipped_tingkat_belum_di_template']) }}</p>
            @endif
            @if(($emisReport['skipped_template'] ?? 0) > 0)
                <p class="text-slate-600">Slot template dilewati: {{ $emisReport['skipped_template'] }}</p>
            @endif
            @if(!empty($emisReport['skipped_no_gtk']))
                <p class="text-amber-700"><strong>Tanpa ID GTK:</strong> {{ count($emisReport['skipped_no_gtk']) }} entri</p>
            @endif
            @if(!empty($emisReport['skipped_no_mapel']))
                <p class="text-amber-700"><strong>Tanpa ID Mapel EMIS:</strong> {{ count($emisReport['skipped_no_mapel']) }} entri</p>
            @endif
            @if(!empty($emisReport['skipped_no_emis_kelas']))
                <p class="text-red-700"><strong>Kelas tanpa kode EMIS:</strong> {{ implode(', ', $emisReport['skipped_no_emis_kelas']) }}</p>
            @endif
        </div>
    @endif

    @if(session('emis_import_summary'))
        @php $emisImport = session('emis_import_summary'); @endphp
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-sm space-y-1">
            <p class="font-black text-blue-900 uppercase text-xs tracking-widest">Hasil Import Referensi</p>
            <p>Mapel: {{ $emisImport['mapels']['updated'] ?? 0 }} diperbarui</p>
            <p>Guru ID GTK: {{ $emisImport['gurus']['matched'] ?? 0 }} cocok</p>
            <p>Kelas kode EMIS: {{ $emisImport['kelas']['updated'] ?? 0 }} diperbarui</p>
        </div>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">ID GTK Guru</p>
            <p class="text-2xl font-black text-teal-700 mt-1">{{ $stats['guru_with_gtk'] }} <span class="text-sm text-slate-400 font-bold">/ {{ $stats['guru_total'] }}</span></p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Mapel EMIS</p>
            <p class="text-2xl font-black text-teal-700 mt-1">{{ $stats['mapel_ready'] }} <span class="text-sm text-slate-400 font-bold">/ {{ $stats['mapel_total'] }}</span></p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Kode Kelas EMIS</p>
            <p class="text-2xl font-black text-teal-700 mt-1">{{ $stats['kelas_ready'] }} <span class="text-sm text-slate-400 font-bold">/ {{ $stats['kelas_total'] }}</span></p>
        </div>
    </div>

    @if(!$selectedSemester)
        <div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-xl p-8 text-center">
            <h3 class="text-lg font-black uppercase tracking-widest">Belum Ada Semester</h3>
            <p class="text-sm mt-2">Aktifkan semester terlebih dahulu sebelum export.</p>
            <a href="{{ route('semester.index') }}" class="inline-block mt-4 bg-amber-500 hover:bg-amber-600 text-black px-6 py-2.5 rounded-lg font-bold text-sm uppercase tracking-widest">Atur Semester</a>
        </div>
    @else
        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-6 space-y-6">
            <form action="{{ route('emis-gtk.index') }}" method="GET" class="flex flex-col sm:flex-row sm:items-center gap-3">
                <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Semester sumber jadwal</label>
                <select name="semester_id" onchange="this.form.submit()"
                    class="bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-lg focus:ring-teal-500 focus:border-teal-500 p-2.5 font-bold">
                    @foreach($allSemesters as $sem)
                        <option value="{{ $sem->id }}" {{ $selectedSemester->id == $sem->id ? 'selected' : '' }}>
                            {{ $sem->nama_tahun }} - {{ $sem->tipe }} {{ $sem->is_active ? '(Aktif)' : '' }}
                        </option>
                    @endforeach
                </select>
            </form>

            <form method="POST" action="{{ route('emis-gtk.export') }}" class="space-y-6">
                @csrf
                <input type="hidden" name="semester_id" value="{{ $selectedSemester->id }}">

                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-black uppercase tracking-widest text-slate-700">Pilih Kelas</h3>
                    <button type="button" onclick="document.querySelectorAll('.emis-kelas').forEach(c => c.checked = true)"
                        class="text-[10px] font-bold text-teal-600 hover:text-teal-800 uppercase tracking-widest">Pilih semua</button>
                </div>

                @foreach($kelasList as $tingkat => $kelases)
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="text-xs font-black uppercase tracking-widest text-slate-400">Tingkat {{ $tingkat }}</h4>
                            <button type="button"
                                class="text-[10px] font-bold text-teal-600 hover:text-teal-800"
                                onclick="document.querySelectorAll('.emis-kelas-{{ $tingkat }}').forEach(c => c.checked = true)">Pilih {{ $tingkat }}</button>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
                            @foreach($kelases as $kelas)
                                <label class="flex items-center gap-2 p-3 rounded-xl border border-slate-200 hover:bg-teal-50 cursor-pointer text-xs font-bold text-slate-700">
                                    <input type="checkbox" name="kelas_ids[]" value="{{ $kelas->id }}"
                                        class="emis-kelas emis-kelas-{{ $tingkat }} rounded border-teal-300 text-teal-600 focus:ring-teal-500"
                                        {{ in_array($kelas->tingkat_emis, ['8', '9'], true) ? 'checked' : '' }}>
                                    <span>{{ str_replace('Kelas ', '', $kelas->nama_kelas) }}</span>
                                    @if($kelas->tingkat_emis && $kelas->rombel_emis)
                                        <span class="text-[9px] text-slate-400 font-mono">{{ $kelas->tingkat_emis }}/{{ $kelas->rombel_emis }}</span>
                                    @endif
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                <button type="submit"
                    class="w-full sm:w-auto bg-teal-600 hover:bg-teal-700 text-white px-8 py-3 rounded-xl font-black text-xs uppercase tracking-widest shadow-md">
                    Download Excel EMIS-GTK
                </button>
            </form>
        </div>

        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-6 space-y-4">
            <h3 class="text-sm font-black uppercase tracking-widest text-slate-700">Update Referensi EMIS</h3>
            <p class="text-xs text-slate-500">Upload ulang file referensi dari EMIS-GTK bila ada perubahan, lalu sinkronkan ke database.</p>
            <form method="POST" action="{{ route('emis-gtk.import-references') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <input type="hidden" name="semester_id" value="{{ $selectedSemester->id }}">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <label class="block text-xs font-bold text-slate-600">Referensi PTK
                        <input type="file" name="referensi_ptk" accept=".xlsx" class="mt-1 block w-full text-xs"></label>
                    <label class="block text-xs font-bold text-slate-600">Referensi Mapel
                        <input type="file" name="referensi_pelajaran" accept=".xlsx" class="mt-1 block w-full text-xs"></label>
                    <label class="block text-xs font-bold text-slate-600">Template Jadwal
                        <input type="file" name="template_jadwal" accept=".xlsx" class="mt-1 block w-full text-xs"></label>
                    <label class="block text-xs font-bold text-slate-600">Referensi Rombel (opsional)
                        <input type="file" name="referensi_rombel" accept=".xlsx" class="mt-1 block w-full text-xs"></label>
                </div>
                <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white px-6 py-2.5 rounded-xl font-bold text-xs uppercase tracking-widest">
                    Upload & Sinkronkan
                </button>
            </form>
        </div>
    @endif
</div>
@endsection
