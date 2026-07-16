<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <title>Jurnal Pembelajaran - {{ $guru->nama_lengkap }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 8mm;
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Times New Roman', Times, serif;
            color: #000;
            margin: 0;
        }

        /* Layar: muat lebar viewport, bisa digeser/zoom */
        @media screen {
            html, body {
                width: 100%;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            body {
                background: #e8ecf1;
                padding: 12px;
                display: block;
            }
            .sheet {
                width: 100%;
                max-width: 100%;
                margin: 0 auto 16px;
                background: #fff;
                box-shadow: 0 4px 16px rgba(0,0,0,0.12);
            }
            .cover, .paper {
                width: 100% !important;
                max-width: 100%;
                min-height: 0 !important;
                height: auto !important;
                aspect-ratio: 297 / 210;
            }
        }

        @media print {
            body { background: #fff; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .sheet { box-shadow: none !important; margin: 0 !important; }
            .cover {
                page-break-after: always;
                page-break-inside: avoid;
            }
            .section-page {
                page-break-before: always;
                page-break-inside: auto;
            }
            .sign-wrap {
                page-break-before: avoid;
                page-break-inside: avoid;
            }
        }

        .cover, .paper {
            width: 297mm;
            background: #fff;
        }
        .cover {
            height: 210mm;
            overflow: hidden;
        }
        .paper {
            min-height: 210mm;
            height: auto;
            overflow: visible;
        }

        .cover {
            padding: 10mm 16mm 8mm;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        .cover-title {
            width: 100%;
            line-height: 1.25;
        }
        .cover-title h1 {
            margin: 0;
            font-size: 22pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }
        .cover-title h2 {
            margin: 4px 0 0;
            font-size: 13pt;
            font-weight: 700;
            text-transform: uppercase;
        }
        .cover-kelas {
            margin-top: 2.2em; /* ~2 enter */
            font-size: 13pt;
            font-weight: 700;
            text-transform: uppercase;
            line-height: 1.35;
            max-width: 90%;
        }
        .cover-logo-wrap {
            margin: 8mm auto 0;
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .cover-logo {
            width: 32mm;
            height: 32mm;
            object-fit: contain;
            display: block;
            margin: 0 auto;
        }
        .cover-spacer { flex: 1 1 auto; min-height: 4mm; }
        .cover-guru .nama {
            font-size: 13pt;
            font-weight: 700;
            margin-bottom: 2px;
        }
        .cover-guru .nip { font-size: 11pt; }
        .cover-school .school {
            font-size: 13pt;
            font-weight: 700;
            text-transform: uppercase;
        }
        .cover-school .agency {
            font-size: 11pt;
            font-weight: 700;
            text-transform: uppercase;
            margin-top: 2px;
        }

        .paper {
            padding: 8mm 10mm 7mm;
            display: flex;
            flex-direction: column;
        }
        .title {
            text-align: center;
            margin-bottom: 6px;
            line-height: 1.25;
            flex: 0 0 auto;
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
            font-size: 11pt;
            font-weight: 700;
            text-transform: uppercase;
        }
        .title .kelas-line {
            margin-top: 1.6em; /* jarak 2 enter dari baris semester */
        }
        .table-wrap {
            flex: 0 0 auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            border: 1.5pt solid #000;
            font-size: 9pt;
        }
        th, td {
            border: 1pt solid #000;
            padding: 4px 3px;
            vertical-align: top;
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

        /* KM 1 tab kiri, Guru 1 tab kanan */
        .sign-wrap {
            margin-top: 10mm;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding-left: 12.7mm;  /* ~1 tab */
            padding-right: 12.7mm;
            flex: 0 0 auto;
        }
        .sign-box {
            width: 72mm;
            text-align: left;
            font-size: 10pt;
            line-height: 1.3;
        }
        .sign-space { height: 48px; }
        .sign-name {
            font-weight: 700;
            text-decoration: underline;
            margin-top: 2px;
        }
        .sign-nip { margin-top: 1px; }
        .waktu-line {
            font-size: 8pt;
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

<div class="sheet cover">
    <div class="cover-title">
        <h1>Jurnal Pembelajaran</h1>
        <h2>Semester {{ $activeSemester->tipe }} Tahun Pelajaran {{ $activeSemester->nama_tahun }}</h2>
        <div class="cover-kelas">Kelas : {{ $daftarKelas !== '' ? $daftarKelas : '—' }}</div>
    </div>

    <div class="cover-logo-wrap">
        <img src="{{ asset('img/logo-kemenag.png') }}" alt="Logo Kemenag" class="cover-logo">
    </div>

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
    <div class="sheet paper section-page">
        <div class="title">
            <h1>Jurnal Pembelajaran</h1>
            <h2>Semester {{ $activeSemester->tipe }} Tahun Pelajaran {{ $activeSemester->nama_tahun }}</h2>
            <h2 class="kelas-line">Kelas {{ $namaKelas }}</h2>
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
