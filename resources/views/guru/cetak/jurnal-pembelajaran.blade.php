<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    {{-- Lebar ≈ A4 landscape 297mm @96dpi agar WebView bisa fit-to-width --}}
    <meta name="viewport" content="width=1123, initial-scale=1, maximum-scale=4, user-scalable=yes">
    <title>Jurnal Pembelajaran - {{ $guru->nama_lengkap }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 8mm;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body {
            font-family: 'Times New Roman', Times, serif;
            color: #000;
            background: #fff;
        }

        @media screen {
            body {
                background: #dfe3e8;
                padding: 10px 0 24px;
            }
            .sheet {
                box-shadow: 0 4px 18px rgba(0,0,0,0.15);
                margin: 0 auto 14px;
            }
        }

        @media print {
            body { background: #fff; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .sheet { box-shadow: none; margin: 0; }
            .cover { page-break-after: always; page-break-inside: avoid; }
            .section-page { page-break-before: always; }
            .sign-wrap { page-break-before: avoid; page-break-inside: avoid; }
        }

        /* A4 landscape tetap: 297 × 210 mm */
        .sheet {
            width: 297mm;
            height: 210mm;
            background: #fff;
            position: relative;
            overflow: hidden;
        }

        /* ===== COVER ===== */
        .cover {
            padding: 12mm 18mm 10mm;
            text-align: center;
        }
        .cover-title h1 {
            font-size: 24pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            line-height: 1.2;
        }
        .cover-title h2 {
            margin-top: 6px;
            font-size: 14pt;
            font-weight: 700;
            text-transform: uppercase;
            line-height: 1.25;
        }
        .cover-kelas {
            margin-top: 2.4em; /* jarak 2 enter */
            font-size: 13pt;
            font-weight: 700;
            text-transform: uppercase;
            line-height: 1.35;
            padding: 0 10mm;
        }
        .cover-logo {
            position: absolute;
            left: 50%;
            top: 52%;
            transform: translate(-50%, -50%);
            width: 36mm;
            height: 36mm;
            object-fit: contain;
        }
        .cover-bottom {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 12mm;
            text-align: center;
        }
        .cover-bottom .nama {
            font-size: 14pt;
            font-weight: 700;
            margin-bottom: 2px;
        }
        .cover-bottom .nip { font-size: 11pt; margin-bottom: 10mm; }
        .cover-bottom .school {
            font-size: 13pt;
            font-weight: 700;
            text-transform: uppercase;
        }
        .cover-bottom .agency {
            margin-top: 3px;
            font-size: 11pt;
            font-weight: 700;
            text-transform: uppercase;
        }

        /* ===== ISI PER KELAS ===== */
        .paper {
            padding: 8mm 10mm 7mm;
            display: flex;
            flex-direction: column;
        }
        .title {
            text-align: center;
            flex: 0 0 auto;
            margin-bottom: 5mm;
        }
        .title h1 {
            font-size: 14pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        .title h2 {
            margin-top: 3px;
            font-size: 11pt;
            font-weight: 700;
            text-transform: uppercase;
        }
        .title .kelas-line {
            margin-top: 1.8em;
        }
        .table-wrap {
            flex: 1 1 auto;
            min-height: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            border: 1.5pt solid #000;
            font-size: 9pt;
            table-layout: fixed;
        }
        th, td {
            border: 1pt solid #000;
            padding: 4px 3px;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        th {
            background: #f3f4f6;
            text-align: center;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 8pt;
            line-height: 1.2;
        }
        td.center { text-align: center; vertical-align: middle; }
        .waktu-line { font-size: 8pt; margin-top: 2px; }

        .sign-wrap {
            flex: 0 0 auto;
            margin-top: 8mm;
            display: flex;
            justify-content: space-between;
            padding-left: 12.7mm;  /* 1 tab kiri */
            padding-right: 12.7mm; /* 1 tab kanan */
        }
        .sign-box {
            width: 70mm;
            text-align: left;
            font-size: 10pt;
            line-height: 1.3;
        }
        .sign-space { height: 46px; }
        .sign-name {
            font-weight: 700;
            text-decoration: underline;
            margin-top: 2px;
        }
    </style>
</head>
<body>
@php
    $bulan = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
    $formatTanggal = function ($date) use ($bulan) {
        if (!$date) return '—';
        return $date->format('d') . ' ' . ($bulan[(int)$date->format('n')] ?? '') . ' ' . $date->format('Y');
    };
    $labelKetercapaian = function ($value) {
        return $value === 'tercapai' ? 'Tercapai' : 'Belum Tercapai';
    };
    $kelasShort = function ($nama) {
        $nama = trim((string) $nama);
        if ($nama === '') return '—';
        return preg_replace('/^kelas\s+/iu', '', $nama) ?: $nama;
    };
    $daftarKelas = collect($sections ?? [])
        ->map(fn ($s) => $kelasShort($s['kelas']->nama_kelas ?? null))
        ->filter(fn ($n) => $n !== '—' && $n !== '')
        ->unique()
        ->values()
        ->implode(', ');
@endphp

{{-- COVER --}}
<section class="sheet cover">
    <div class="cover-title">
        <h1>Jurnal Pembelajaran</h1>
        <h2>Semester {{ $activeSemester->tipe }} Tahun Pelajaran {{ $activeSemester->nama_tahun }}</h2>
        <div class="cover-kelas">Kelas : {{ $daftarKelas !== '' ? $daftarKelas : '—' }}</div>
    </div>

    <img src="{{ asset('img/logo-kemenag.png') }}" alt="Logo Kemenag" class="cover-logo">

    <div class="cover-bottom">
        <div class="nama">{{ $guru->nama_lengkap }}</div>
        <div class="nip">NIP. {{ $guru->username }}</div>
        <div class="school">MTsN 11 Majalengka</div>
        <div class="agency">Kementerian Agama Kabupaten Majalengka</div>
    </div>
</section>

@foreach($sections as $section)
    @php
        $kelas = $section['kelas'];
        $rows = $section['rows'] ?? [];
        $namaKelas = $kelasShort($kelas?->nama_kelas);
    @endphp
    <section class="sheet paper section-page">
        <div class="title">
            <h1>Jurnal Pembelajaran</h1>
            <h2>Semester {{ $activeSemester->tipe }} Tahun Pelajaran {{ $activeSemester->nama_tahun }}</h2>
            <h2 class="kelas-line">Kelas {{ $namaKelas }}</h2>
        </div>

        <div class="table-wrap">
            <table>
                <colgroup>
                    <col style="width:4%">
                    <col style="width:15%">
                    <col style="width:14%">
                    <col style="width:23%">
                    <col style="width:12%">
                    <col style="width:16%">
                    <col style="width:16%">
                </colgroup>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Hari, Tanggal<br>Waktu</th>
                        <th>Mata Pelajaran</th>
                        <th>Materi Pokok Bahasan</th>
                        <th>Ketercapaian</th>
                        <th>Penugasan Siswa</th>
                        <th>Catatan Guru</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $index => $row)
                        <tr>
                            <td class="center">{{ $index + 1 }}</td>
                            <td>
                                {{ $row['hari'] }}, {{ $formatTanggal($row['tanggal']) }}
                                @if(!empty($row['waktu']))
                                    <div class="waktu-line">{{ $row['waktu'] }}</div>
                                @endif
                            </td>
                            <td>{{ $row['mapel'] ?? '—' }}</td>
                            <td>{{ $row['materi_pokok'] }}</td>
                            <td class="center">{{ $labelKetercapaian($row['ketercapaian'] ?? '') }}</td>
                            <td>{{ $row['penugasan_siswa'] ?: '—' }}</td>
                            <td>{{ $row['catatan_guru'] ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="center">Belum ada entri jurnal.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="sign-wrap">
            <div class="sign-box">
                <div>Mengetahui,</div>
                <div>{{ $cetakPejabatLabel ?? 'Kepala Madrasah' }}</div>
                <div class="sign-space"></div>
                <div class="sign-name">{{ $kepalaMadrasah?->nama_lengkap ?? '................................' }}</div>
                <div class="sign-nip">NIP. {{ $kepalaMadrasah?->username ?? '................' }}</div>
            </div>
            <div class="sign-box">
                <div>{{ $tempatCetak }}, {{ $formatTanggal($tanggalCetak) }}</div>
                <div>Guru Pengampu</div>
                <div class="sign-space"></div>
                <div class="sign-name">{{ $guru->nama_lengkap }}</div>
                <div class="sign-nip">NIP. {{ $guru->username }}</div>
            </div>
        </div>
    </section>
@endforeach

<script>
(function () {
    function fitToScreen() {
        if (window.matchMedia && window.matchMedia('print').matches) {
            document.body.style.zoom = '';
            return;
        }
        var sheet = document.querySelector('.sheet');
        if (!sheet) return;
        var pageW = sheet.offsetWidth || 1123;
        var vw = window.innerWidth || pageW;
        var scale = (vw - 12) / pageW;
        if (scale > 0 && scale < 1.01) {
            document.body.style.zoom = String(Math.max(0.25, Math.min(scale, 1)));
        }
    }
    window.fitToScreen = fitToScreen;
    window.addEventListener('load', fitToScreen);
    window.addEventListener('resize', fitToScreen);
    window.prepareGuruPrint = function (done) {
        document.body.style.zoom = '';
        if (typeof done === 'function') setTimeout(done, 50);
    };
    window.finishGuruPrint = function () { fitToScreen(); };
    setTimeout(fitToScreen, 80);
    setTimeout(fitToScreen, 300);
})();
</script>
</body>
</html>
