<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=0.2, maximum-scale=5.0, user-scalable=yes">
    <title>Jurnal Pembelajaran - {{ $guru->nama_lengkap }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 10mm;
        }
        @media screen {
            body {
                background: #e8ecf1;
                margin: 0;
                padding: 20px 0 40px;
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 20px;
            }
            .paper, .cover {
                box-shadow: 0 6px 24px rgba(0,0,0,0.14);
            }
        }
        @media print {
            body { margin: 0; background: #fff; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .paper, .cover { box-shadow: none !important; margin: 0 !important; }
            .cover { page-break-after: always; }
            .section-page { page-break-before: always; }
            .sign-wrap { page-break-inside: avoid; }
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Times New Roman', Times, serif;
            color: #000;
        }
        .cover, .paper {
            width: 297mm;
            min-height: 210mm;
            background: #fff;
        }
        .cover {
            padding: 14mm 18mm;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        .cover-title {
            margin-top: 6mm;
            line-height: 1.3;
        }
        .cover-title h1 {
            margin: 0;
            font-size: 26pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .cover-title h2 {
            margin: 6px 0 0;
            font-size: 16pt;
            font-weight: 700;
            text-transform: uppercase;
        }
        .cover-logo {
            margin: 10mm auto 6mm;
            width: 38mm;
            height: 38mm;
            object-fit: contain;
        }
        .cover-spacer { flex: 1; min-height: 8mm; }
        .cover-guru .nama {
            font-size: 15pt;
            font-weight: 700;
            margin-bottom: 3px;
        }
        .cover-guru .nip { font-size: 12pt; }
        .cover-school .school {
            font-size: 15pt;
            font-weight: 700;
            text-transform: uppercase;
        }
        .cover-school .agency {
            font-size: 12pt;
            font-weight: 700;
            text-transform: uppercase;
            margin-top: 3px;
        }
        .paper {
            padding: 10mm 12mm 8mm;
            display: flex;
            flex-direction: column;
        }
        .title {
            text-align: center;
            margin-bottom: 8px;
            line-height: 1.3;
        }
        .title h1 {
            margin: 0;
            font-size: 15pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .title h2 {
            margin: 4px 0 0;
            font-size: 12pt;
            font-weight: 700;
            text-transform: uppercase;
        }
        .table-wrap { flex: 1; }
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
        td.center { text-align: center; vertical-align: middle; }
        .sign-wrap {
            margin-top: 16px;
            display: flex;
            justify-content: flex-start;
            gap: 70px;
            padding-left: 4mm;
        }
        .sign-box {
            width: 70mm;
            text-align: left;
            font-size: 10.5pt;
            line-height: 1.35;
        }
        .sign-space { height: 58px; }
        .sign-name {
            font-weight: 700;
            text-decoration: underline;
            margin-top: 2px;
        }
        .sign-nip { margin-top: 1px; }
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
    $kelasShort = function ($nama) {
        $nama = trim((string) $nama);
        if ($nama === '') return '—';
        return preg_replace('/^kelas\s+/iu', '', $nama) ?: $nama;
    };
@endphp

{{-- Cover landscape --}}
<div class="cover">
    <div class="cover-title">
        <h1>Jurnal Pembelajaran</h1>
        <h2>Semester {{ $activeSemester->tipe }} Tahun Pelajaran {{ $activeSemester->nama_tahun }}</h2>
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

@foreach($sections as $section)
    @php
        $kelas = $section['kelas'];
        $rows = $section['rows'] ?? [];
        $namaKelas = $kelasShort($kelas?->nama_kelas);
    @endphp
    <div class="paper section-page">
        <div class="title">
            <h1>Jurnal Pembelajaran</h1>
            <h2>Semester {{ $activeSemester->tipe }} Tahun Pelajaran {{ $activeSemester->nama_tahun }}</h2>
            <h2>Kelas {{ $namaKelas }}</h2>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th style="width:4%">No</th>
                        <th style="width:15%">Hari, Tanggal<br>Waktu</th>
                        <th style="width:14%">Mata Pelajaran</th>
                        <th style="width:23%">Materi Pokok Bahasan</th>
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
    </div>
@endforeach
</body>
</html>
