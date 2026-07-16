<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=0.2, maximum-scale=5.0, user-scalable=yes">
    <title>Jurnal Pembelajaran - {{ $guru->nama_lengkap }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        @page {
            size: A4 landscape;
            margin: 0.8cm;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            color: #000;
            background: #fff;
        }

        @media screen {
            body.guru-app-view {
                display: block !important;
                background: #e8ecf0 !important;
                padding: 0 !important;
                margin: 0 !important;
                overflow-x: hidden;
                overflow-y: auto;
            }
            .guru-app-frame {
                width: 100%;
                max-width: 100vw;
                display: flex;
                justify-content: center;
                align-items: flex-start;
                padding: 8px 0 88px;
                box-sizing: border-box;
                overflow: hidden;
            }
            body.guru-app-view .jurnal-doc {
                transform-origin: top center;
                flex-shrink: 0;
                margin: 0;
            }
            .main-paper {
                box-shadow: 0 4px 18px rgba(0, 0, 0, 0.14);
                margin-bottom: 14px;
            }
            .main-paper:last-child {
                margin-bottom: 0;
            }
        }

        body.guru-printing .guru-app-frame,
        body.guru-printing .jurnal-doc {
            transform: none !important;
            height: auto !important;
            padding: 0 !important;
            overflow: visible !important;
        }

        @media print {
            body.guru-app-view {
                background: #fff !important;
            }
            .guru-app-frame {
                display: block !important;
                padding: 0 !important;
                margin: 0 !important;
                overflow: visible !important;
            }
            .jurnal-doc {
                transform: none !important;
                width: 100% !important;
            }
            .main-paper {
                width: 100% !important;
                margin: 0 !important;
                box-shadow: none !important;
            }
            .kelas-paper {
                min-height: 0 !important;
                padding: 0 !important;
                page-break-before: always !important;
                break-before: page !important;
            }
            .kelas-paper:last-child {
                page-break-after: auto !important;
                break-after: auto !important;
            }
            /* Cover: pertahankan tinggi penuh + flex agar logo tengah & nama di bawah (seperti LKB) */
            .main-paper.cover-paper {
                /* 210mm halaman - margin @page 8mm atas + 8mm bawah */
                height: 194mm !important;
                min-height: 194mm !important;
                padding: 16mm 18mm 14mm !important;
                display: flex !important;
                flex-direction: column !important;
                justify-content: space-between !important;
                page-break-after: always !important;
                break-after: page !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            .cover-paper .cover-title {
                padding-top: 4mm;
            }
            .cover-paper .cover-logo-area {
                flex: 1 1 auto !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                min-height: 45mm !important;
            }
            thead { display: table-header-group; }
            tr { page-break-inside: avoid; break-inside: avoid; }
            .sign-wrap {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
        }

        .jurnal-doc {
            width: 297mm;
        }

        .main-paper {
            width: 297mm;
            background: #fff;
            padding: 0.8cm;
            position: relative;
        }

        /* ===== COVER (satu halaman penuh, pola LKB) ===== */
        .cover-paper {
            height: 210mm;
            min-height: 210mm;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            text-align: center;
            padding: 14mm 18mm 12mm;
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
            margin-top: 2.4em;
            font-size: 13pt;
            font-weight: 700;
            text-transform: uppercase;
            line-height: 1.35;
            padding: 0 10mm;
        }
        .cover-logo-area {
            flex: 1 1 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 55mm;
        }
        .cover-logo {
            width: 40mm;
            height: 40mm;
            object-fit: contain;
        }
        .cover-bottom .nama {
            font-size: 14pt;
            font-weight: 700;
            margin-bottom: 2px;
        }
        .cover-bottom .nip {
            font-size: 11pt;
            margin-bottom: 8mm;
        }
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
        .section-title {
            text-align: center;
            margin-bottom: 5mm;
        }
        .section-title h1 {
            font-size: 14pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        .section-title h2 {
            margin-top: 3px;
            font-size: 11pt;
            font-weight: 700;
            text-transform: uppercase;
        }
        .section-title .kelas-line {
            margin-top: 1.8em;
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
            margin-top: 8mm;
            display: flex;
            justify-content: space-between;
            padding-left: 12.7mm;
            padding-right: 12.7mm;
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
<body class="guru-app-view">
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

<div class="guru-app-frame" id="guru-app-frame">
    <div class="jurnal-doc" id="jurnal-doc">
        {{-- COVER --}}
        <div class="main-paper cover-paper">
            <div class="cover-title">
                <h1>Jurnal Pembelajaran</h1>
                <h2>Semester {{ $activeSemester->tipe }} Tahun Pelajaran {{ $activeSemester->nama_tahun }}</h2>
                <div class="cover-kelas">Kelas : {{ $daftarKelas !== '' ? $daftarKelas : '—' }}</div>
            </div>

            <div class="cover-logo-area">
                <img src="{{ asset('img/logo-kemenag.png') }}" alt="Logo Kemenag" class="cover-logo">
            </div>

            <div class="cover-bottom">
                <div class="nama">{{ $guru->nama_lengkap }}</div>
                <div class="nip">NIP. {{ $guru->username }}</div>
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
            <div class="main-paper kelas-paper">
                <div class="section-title">
                    <h1>Jurnal Pembelajaran</h1>
                    <h2>Semester {{ $activeSemester->tipe }} Tahun Pelajaran {{ $activeSemester->nama_tahun }}</h2>
                    <h2 class="kelas-line">Kelas {{ $namaKelas }}</h2>
                </div>

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
    </div>
</div>

<script>
(function () {
    function fitGuruApp() {
        if (document.body.classList.contains('guru-printing')) return;
        var doc = document.getElementById('jurnal-doc');
        var frame = document.getElementById('guru-app-frame');
        if (!doc || !frame) return;

        doc.style.transform = '';
        var naturalW = doc.offsetWidth;
        if (!naturalW) return;

        var viewW = window.innerWidth || document.documentElement.clientWidth;
        var scale = Math.min(1, (viewW - 8) / naturalW);

        if (scale < 0.999) {
            doc.style.transform = 'scale(' + scale + ')';
            doc.style.transformOrigin = 'top center';
        }
        frame.style.height = Math.ceil(doc.getBoundingClientRect().height) + 'px';
    }

    window.fitGuruApp = fitGuruApp;
    window.fitToScreen = fitGuruApp;

    window.prepareGuruPrint = function (cb) {
        document.body.classList.add('guru-printing');
        var doc = document.getElementById('jurnal-doc');
        if (doc) doc.style.transform = 'none';
        var frame = document.getElementById('guru-app-frame');
        if (frame) frame.style.height = 'auto';
        setTimeout(function () {
            if (typeof cb === 'function') cb();
        }, 280);
    };

    window.finishGuruPrint = function () {
        document.body.classList.remove('guru-printing');
        fitGuruApp();
    };

    window.addEventListener('load', function () {
        fitGuruApp();
        setTimeout(fitGuruApp, 300);
        setTimeout(fitGuruApp, 800);
    });
    window.addEventListener('resize', fitGuruApp);
    window.addEventListener('orientationchange', function () {
        setTimeout(fitGuruApp, 200);
        setTimeout(fitGuruApp, 600);
    });
})();
</script>
</body>
</html>
