<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=0.2, maximum-scale=5.0, user-scalable=yes">
    <title>Jurnal Pembelajaran - {{ $kelas->nama_kelas }}</title>
    <style>
        @media screen {
            body {
                background: #f0f2f5;
                margin: 0;
                padding: 24px 0;
                display: flex;
                justify-content: center;
            }
            .paper {
                box-shadow: 0 4px 20px rgba(0,0,0,0.12);
            }
        }
        @media print {
            @page { size: A4 landscape; margin: 0.8cm; }
            body { margin: 0; background: #fff; -webkit-print-color-adjust: exact; }
            .paper { box-shadow: none !important; }
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Times New Roman', Times, serif;
            color: #000;
        }
        .paper {
            width: 297mm;
            min-height: 210mm;
            background: #fff;
            padding: 12mm 10mm;
        }
        .title {
            text-align: center;
            margin-bottom: 16px;
            line-height: 1.35;
        }
        .title h1 {
            margin: 0;
            font-size: 15pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        .title h2 {
            margin: 4px 0 0;
            font-size: 13pt;
            font-weight: 700;
            text-transform: uppercase;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            border: 1.5pt solid #000;
            font-size: 10pt;
        }
        th, td {
            border: 1pt solid #000;
            padding: 6px 5px;
            vertical-align: top;
        }
        th {
            background: #f3f4f6;
            text-align: center;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 9pt;
        }
        td.center { text-align: center; }
        .check {
            display: inline-block;
            width: 12px;
            height: 12px;
            border: 1px solid #000;
            text-align: center;
            line-height: 11px;
            font-size: 10px;
            margin-right: 3px;
        }
        .sign-wrap {
            margin-top: 28px;
            display: flex;
            justify-content: space-between;
            gap: 40px;
            page-break-inside: avoid;
        }
        .sign-box {
            width: 42%;
            text-align: center;
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
        .meta {
            margin-top: 8px;
            font-size: 10pt;
            color: #444;
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
@endphp
<div class="paper">
    <div class="title">
        <h1>Jurnal Pembelajaran</h1>
        <h2>Semester {{ $activeSemester->tipe }} Tahun Ajaran {{ $activeSemester->nama_tahun }}</h2>
        <h2>Kelas {{ $kelas->nama_kelas }}</h2>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:4%">No</th>
                <th style="width:14%">Hari, Tanggal</th>
                <th style="width:14%">Mata Pelajaran</th>
                <th style="width:22%">Materi Pokok Bahasan</th>
                <th style="width:14%">Ketercapaian</th>
                <th style="width:16%">Penugasan Siswa</th>
                <th style="width:16%">Catatan Guru</th>
            </tr>
        </thead>
        <tbody>
            @foreach($entries as $index => $entry)
                <tr>
                    <td class="center">{{ $index + 1 }}</td>
                    <td>
                        {{ $entry->hari }}, {{ $formatTanggal($entry->tanggal) }}
                        @if($entry->jam_ke)
                            <div style="font-size:8.5pt;margin-top:2px;">Jam ke {{ $entry->jam_ke }}</div>
                        @endif
                    </td>
                    <td>{{ $entry->mapel?->nama_mapel ?? '—' }}</td>
                    <td>{{ $entry->materi_pokok }}</td>
                    <td>
                        <div><span class="check">{{ $entry->ketercapaian === 'tercapai' ? '✓' : '' }}</span> Tercapai</div>
                        <div style="margin-top:4px;"><span class="check">{{ $entry->ketercapaian === 'belum' ? '✓' : '' }}</span> Belum</div>
                    </td>
                    <td>{{ $entry->penugasan_siswa ?: '—' }}</td>
                    <td>{{ $entry->catatan_guru ?: '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="sign-wrap">
        <div class="sign-box">
            <div>Mengetahui,</div>
            <div>Kepala Madrasah</div>
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
</body>
</html>
