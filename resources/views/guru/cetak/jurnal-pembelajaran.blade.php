<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=0.2, maximum-scale=5.0, user-scalable=yes">
    <title>Jurnal Pembelajaran - {{ $guru->nama_lengkap }}</title>
    <style>
        @media screen {
            body {
                background: #f0f2f5;
                margin: 0;
                padding: 24px 0;
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 24px;
            }
            .paper, .cover {
                box-shadow: 0 4px 20px rgba(0,0,0,0.12);
            }
        }
        @media print {
            body { margin: 0; background: #fff; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .paper, .cover { box-shadow: none !important; }
            .cover { page-break-after: always; }
            .section-page { page-break-before: always; }
            .section-page:first-of-type { page-break-before: auto; }
            .sign-wrap { page-break-inside: avoid; }
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Times New Roman', Times, serif;
            color: #000;
        }
        .cover {
            width: 210mm;
            min-height: 297mm;
            background: #fff;
            padding: 22mm 18mm;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            position: relative;
        }
        .cover-title {
            margin-top: 18mm;
            line-height: 1.35;
        }
        .cover-title h1 {
            margin: 0;
            font-size: 22pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }
        .cover-title h2 {
            margin: 8px 0 0;
            font-size: 16pt;
            font-weight: 700;
            text-transform: uppercase;
        }
        .cover-logo {
            margin: 28px auto;
            width: 42mm;
            height: 42mm;
            object-fit: contain;
        }
        .cover-spacer {
            flex: 1;
            min-height: 16mm;
        }
        .cover-guru {
            margin-bottom: 10mm;
        }
        .cover-guru .nama {
            font-size: 14pt;
            font-weight: 700;
            margin-bottom: 4px;
        }
        .cover-guru .nip {
            font-size: 12pt;
        }
        .cover-school {
            padding-bottom: 8mm;
        }
        .cover-school .school {
            font-size: 15pt;
            font-weight: 700;
            text-transform: uppercase;
        }
        .cover-school .agency {
            font-size: 12pt;
            font-weight: 700;
            text-transform: uppercase;
            margin-top: 4px;
        }
        .paper {
            width: 297mm;
            min-height: 210mm;
            background: #fff;
            padding: 12mm 10mm;
        }
        .title {
            text-align: center;
            margin-bottom: 14px;
            line-height: 1.35;
        }
        .title h1 {
            margin: 0;
            font-size: 14pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        .title h2 {
            margin: 3px 0 0;
            font-size: 12pt;
            font-weight: 700;
            text-transform: uppercase;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            border: 1.5pt solid #000;
            font-size: 9.5pt;
        }
        th, td {
            border: 1pt solid #000;
            padding: 5px 4px;
            vertical-align: top;
        }
        th {
            background: #f3f4f6;
            text-align: center;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 8.5pt;
            line-height: 1.25;
        }
        td.center { text-align: center; }
        .sign-wrap {
            margin-top: 24px;
            display: flex;
            justify-content: space-between;
            gap: 40px;
        }
        .sign-box {
            width: 42%;
            text-align: left;
            font-size: 11pt;
            line-height: 1.4;
        }
        .sign-space { height: 70px; }
        .sign-name {
            font-weight: 700;
            text-decoration: underline;
            margin-top: 4px;
        }
        .sign-nip { margin-top: 2px; }
        .waktu-line {
            font-size: 8.5pt;
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
@endphp

{{-- Cover ala LKB --}}
<div class="cover">
    <div class="cover-title">
        <h1>Jurnal Pembelajaran</h1>
        <h2>Semester {{ $activeSemester->tipe }}</h2>
        <h2>Tahun Pelajaran {{ $activeSemester->nama_tahun }}</h2>
    </div>

    <img src="{{ asset('img/logo-kemenag.png') }}" alt="Logo Kemenag" class="cover-logo">

    <div class="cover-spacer"></div>

    <div class="cover-guru">
        <div class="nama">{{ $guru->nama_lengkap }}</div>
        <div class="nip">NIP. {{ $guru->username }}</div>
    </div>

    <div class="cover-spacer"></div>

    <div class="cover-school">
        <div class="school">MTsN 11 Majalengka</div>
        <div class="agency">Kementerian Agama Kabupaten Majalengka</div>
    </div>
</div>

@foreach($sections as $sectionIndex => $section)
    @php
        $kelas = $section['kelas'];
        $rows = $section['rows'] ?? [];
    @endphp
    <div class="paper section-page">
        <div class="title">
            <h1>Jurnal Pembelajaran</h1>
            <h2>Semester {{ $activeSemester->tipe }} / TP {{ $activeSemester->nama_tahun }} / Kelas {{ $kelas?->nama_kelas ?? '—' }}</h2>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width:4%">No</th>
                    <th style="width:16%">Hari, Tanggal<br>Waktu</th>
                    <th style="width:14%">Mata Pelajaran</th>
                    <th style="width:22%">Materi Pokok Bahasan</th>
                    <th style="width:12%">Ketercapaian</th>
                    <th style="width:16%">Penugasan Siswa</th>
                    <th style="width:16%">Catatan Guru</th>
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

        {{-- TTD per kelas, rata kiri --}}
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
    </div>
@endforeach
</body>
</html>
