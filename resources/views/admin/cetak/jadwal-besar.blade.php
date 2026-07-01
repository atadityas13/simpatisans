<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Besar - Poster Multipagi (Dynamic Spliced)</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 0;
        }

        /* SCREEN PREVIEW: Automatic Scaling to fit monitor */
        @media screen {
            body {
                background-color: #1a1a1b;
                margin: 0;
                padding: 20px;
                display: flex;
                flex-direction: column;
                align-items: center;
                min-height: 100vh;
            }

            .poster-canvas {
                display: grid;
                grid-template-columns: repeat(3, 210mm);
                gap: 15px;
                /* Automatically scales the entire poster to fit typical screen width */
                transform: scale(0.35); 
                transform-origin: top center;
                margin-bottom: -3000px; /* Large compensation for more rows */
            }

            .page-window {
                box-shadow: 0 10px 40px rgba(0,0,0,0.8);
                border: 1px solid #444;
            }

            .page-window::before {
                content: "Lembar " attr(data-page-num);
                position: absolute;
                top: -35px;
                left: 0;
                color: #fff;
                font-family: 'Segoe UI', Arial, sans-serif;
                font-size: 16pt;
                font-weight: bold;
                text-transform: uppercase;
                letter-spacing: 2px;
            }
        }

        /* PRINT SETTINGS */
    @media print {
        body {
            background-color: #fff;
            display: block;
        }

        .poster-canvas {
            display: block;
        }

        .page-window {
            margin: 0;
            box-shadow: none;
            border: none;
            page-break-after: always;
        }

        .no-print {
            display: none !important;
        }
    }

    /* Generic paper styles */
    .page-window {
        width: 210mm;
        height: 297mm;
        background: white;
        display: flex;
        align-items: center;
        justify-content: center;
        box-sizing: border-box;
        overflow: hidden;
        position: relative;
    }

    /* The printable "window" (5mm margin from all edges) */
    .content-clipper {
        width: 200mm;
        height: 287mm;
        overflow: hidden;
        position: relative;
        outline: 0.1pt solid #ccc; /* Alignment guide */
        background: white;
    }

        /* The Original Layout (Optimized for 12 Pages 3x4) */
        .original-layout {
            position: absolute;
            width: 260mm; /* Fixed width for consistent horizontal slicing */
            height: auto;
            transform-origin: top left;
            /* Recalculated for perfect 12-page fit */
            /* Scale X 2.3 -> 260 * 2.3 = 598mm (Fits in 3x200mm) */
            /* Corrective Scaling: Decreased Y to 2.85 to ensure all content fits in 12 pages */
            transform: scale(2.32, 2.88); 
            padding: 0.4cm 0.5cm 2.5cm 0.5cm; /* Added 2.5cm bottom padding as safety buffer */
            box-sizing: border-box;
            font-family: 'Arial Narrow', 'Arial', sans-serif;
            background: white;
            color: #000;
        }

        /* REPLICATING ESSENTIAL STYLES */
        .container { width: 100%; }
        .header { display: flex; align-items: center; justify-content: center; position: relative; padding: 2pt 0; min-height: 42pt; }
        .logo-left { position: absolute; left: 0; top: 50%; transform: translateY(-50%) scale(1, 0.821); height: 70pt; width: 70pt; object-fit: contain; }
        .logo-right { position: absolute; right: 0; top: 50%; transform: translateY(-50%) scale(1, 0.821); height: 65pt; width: 65pt; object-fit: contain; }
        .header-content { text-align: center; width: 100%; padding: 0 10pt; box-sizing: border-box; }
        .header-content h1 { font-size: 14pt; margin: 0; font-weight: bold; line-height: 1.1; letter-spacing: 1.7pt; }
        .header-content h2 { font-size: 18pt; margin: 1pt 0; font-weight: bold; line-height: 1.1; letter-spacing: 2.5pt; }
        .header-content p { font-size: 11pt; margin: 0; line-height: 1.2; letter-spacing: 1.3pt; }
        .header-line { border-bottom: 2pt solid #000; margin-top: 1pt; margin-bottom: 5pt; }
        .title-section { text-align: center; margin: 2pt 0 2pt 0; }
        .title-section h3 { font-size: 13pt; margin: 0; font-weight: bold; text-transform: uppercase; }
        .main-content { display: flex; align-items: flex-start; gap: 5pt; }
        table { border-collapse: collapse; width: 100%; border: 2pt solid #000; }
        
        /* NO-WRAP FOR CELLS (Bapak's request for AT-07 format) */
        th, td { 
            border: 0.5pt solid #000; 
            padding: 0.5pt; 
            text-align: center; 
            height: 8.8pt; 
            font-weight: normal; /* Removed bold as per user request */
            white-space: nowrap; /* Forces one line */
            letter-spacing: -0.1pt; /* Subtle compression if needed */
        }
        
        /* Ensuring only the leftmost overall border is thick */
        thead tr:first-child th:first-child, .col-hari { 
            border-left: 2pt solid #000 !important; 
        }
        
        th { background-color: #d9e6c3; font-weight: bold; font-size: 6.5pt; border: 1pt solid #000; height: 12pt; }
        thead tr:first-child th { height: 15pt; }
        .vertical-text { writing-mode: vertical-rl; transform: rotate(180deg); white-space: nowrap; display: inline-block; line-height: 1; padding: 2pt 0; height: 35pt; }
        .rotated-content { 
            writing-mode: vertical-rl; 
            transform: rotate(180deg); 
            white-space: nowrap; 
            font-weight: bold; 
            font-size: 7pt; 
            display: inline-block;
            margin: auto;
            line-height: 1.1;
        }
        .day-separator, .day-separator td, .day-separator th { border-bottom: 1.5pt solid #000 !important; }
        .grade-separator { border-right: 1.5pt solid #000 !important; }
        .header-bg { background-color: #f2f2f2; font-weight: bold; }
        .special-row { background-color: #4b54b5ff; font-weight: bold; letter-spacing: 3pt; color: white !important; white-space: normal; }
        .ist-row { background-color: #f15151ff; font-weight: bold; letter-spacing: 3pt; color: white !important; white-space: normal; }
        .makan-row { background-color: #9fc5e8; font-weight: bold; letter-spacing: 3pt; white-space: normal; }
        .sholat-row { background-color: #42b419ff; font-weight: bold; letter-spacing: 5pt; color: white !important; white-space: normal; }
        .violet-row { background-color: #42b419ff; font-weight: bold; letter-spacing: 5pt; color: white !important; white-space: normal; }
        .dark-green-bg { background-color: #354c29ff !important; color: #ffffff !important; }
        .legend-container { width: 130pt; flex-shrink: 0; margin-left: 5pt; }
        .legend-table { font-size: 5pt; width: 100%; }
        .legend-table td { text-align: left; padding: 0.5pt 1pt; border: 0.5pt solid #000; height: 8.0pt; white-space: nowrap; overflow: hidden; }
        .legend-table th { text-align: center; background-color: #f2f2f2; font-size: 5pt; padding: 1pt; font-weight: bold; }
        .kg-col { width: 18pt; text-align: center !important; font-weight: bold; }
        .no-col { width: 12pt; text-align: center !important; }
        .footer-container { margin-top: 5pt; }
        .signature-box { width: 100%; text-align: center; margin-top: 10pt; font-size: 10pt; }
        .signature-box p { margin: 0; line-height: 1.1; }
        .col-hari { 
            width: 10pt; 
            background-color: #f2f2f2 !important; 
            padding: 2pt 0 !important;
            vertical-align: middle !important;
            text-align: center !important;
        }
        .col-waktu { width: 38pt; background-color: #f2f2f2; }
        .col-jam { width: 14pt; background-color: #f2f2f2; }
        .col-kelas { width: 14pt; }
        .crop-marks { position: absolute; bottom: 1.5mm; right: 2mm; font-size: 5pt; color: #bbb; pointer-events: none; }
    </style>
</head>

<body>
    <div class="no-print controls-panel">
        <a href="javascript:window.print()" class="no-print-btn">Cetak Poster</a>
    </div>
    @php
        $hariList = [
            'Senin' => [
                ['time' => '07.00-07.35', 'jam' => 'UPC', 'type' => 'UPACARA'],
                ['time' => '07.35-08.10', 'jam' => 1], ['time' => '08.10-08.45', 'jam' => 2],
                ['time' => '08.45-09.20', 'jam' => 3], ['time' => '09.50-10.25', 'jam' => 4],
                ['time' => '09.20-09.50', 'jam' => 'IST', 'type' => 'ISTIRAHAT'],
                ['time' => '10.25-11.00', 'jam' => 5], ['time' => '11.00-11.35', 'jam' => 6],
                ['time' => '13.05-13.40', 'jam' => 7], ['time' => '11.35-12.25', 'jam' => 'MKN', 'type' => 'MAKAN'],
                ['time' => '12.25-13.05', 'jam' => 'SHL', 'type' => 'SHALAT'],
                ['time' => '13.40-14.15', 'jam' => 8], ['time' => '14.15-14.50', 'jam' => 9],
            ],
            'Selasa' => [
                ['time' => '07.00-07.35', 'jam' => 1], ['time' => '07.35-08.10', 'jam' => 2],
                ['time' => '08.10-08.45', 'jam' => 3], ['time' => '08.45-09.20', 'jam' => 4],
                ['time' => '09.20-09.50', 'jam' => 'IST', 'type' => 'ISTIRAHAT'],
                ['time' => '09.50-10.25', 'jam' => 5], ['time' => '10.25-11.00', 'jam' => 6],
                ['time' => '11.00-11.35', 'jam' => 7], ['time' => '11.35-12.25', 'jam' => 'MKN', 'type' => 'MAKAN'],
                ['time' => '12.25-13.05', 'jam' => 'SHL', 'type' => 'SHALAT'],
                ['time' => '13.05-13.40', 'jam' => 8], ['time' => '13.40-14.15', 'jam' => 9],
                ['time' => '14.15-14.50', 'jam' => 10],
            ],
            'Rabu' => [
                ['time' => '07.00-07.35', 'jam' => 1], ['time' => '07.35-08.10', 'jam' => 2],
                ['time' => '08.10-08.45', 'jam' => 3], ['time' => '08.45-09.20', 'jam' => 4],
                ['time' => '09.20-09.50', 'jam' => 'IST', 'type' => 'ISTIRAHAT'],
                ['time' => '09.50-10.25', 'jam' => 5], ['time' => '10.25-11.00', 'jam' => 6],
                ['time' => '11.00-11.35', 'jam' => 7], ['time' => '11.35-12.25', 'jam' => 'MKN', 'type' => 'MAKAN'],
                ['time' => '12.25-13.05', 'jam' => 'SHL', 'type' => 'SHALAT'],
                ['time' => '13.05-13.40', 'jam' => 8], ['time' => '13.40-14.15', 'jam' => 9],
                ['time' => '14.15-14.50', 'jam' => 10],
            ],
            'Kamis' => [
                ['time' => '07.00-07.35', 'jam' => 1], ['time' => '07.35-08.10', 'jam' => 2],
                ['time' => '08.10-08.45', 'jam' => 3], ['time' => '08.45-09.20', 'jam' => 4],
                ['time' => '09.20-09.50', 'jam' => 'IST', 'type' => 'ISTIRAHAT'],
                ['time' => '09.50-10.25', 'jam' => 5], ['time' => '10.25-11.00', 'jam' => 6],
                ['time' => '11.00-11.35', 'jam' => 7], ['time' => '11.35-12.25', 'jam' => 'MKN', 'type' => 'MAKAN'],
                ['time' => '12.25-13.05', 'jam' => 'SHL', 'type' => 'SHALAT'],
                ['time' => '13.05-13.40', 'jam' => 8], ['time' => '13.40-14.15', 'jam' => 9],
                ['time' => '14.15-14.50', 'jam' => 10],
            ],
            'Jumat' => [
                ['time' => '07.00-07.15', 'jam' => 'LKD', 'type' => 'LKD'],
                ['time' => '07.15-08.00', 'jam' => 'QIR', 'type' => 'QIROAH'],
                ['time' => '08.00-08.30', 'jam' => 1], ['time' => '08.30-09.00', 'jam' => 2],
                ['time' => '09.00-09.30', 'jam' => 3], ['time' => '09.30-09.50', 'jam' => 'IST', 'type' => 'ISTIRAHAT'],
                ['time' => '09.50-10.20', 'jam' => 4], ['time' => '10.20-10.50', 'jam' => 5],
                ['time' => '10.50-11.20', 'jam' => 'MKN', 'type' => 'MAKAN'],
                ['time' => '11.20-12.30', 'jam' => 'SHL_J', 'type' => 'SHALAT_J'],
                ['time' => '12.30-14.30', 'jam' => 'PRAM', 'type' => 'PRAMUKA'],
            ]
        ];
    @endphp

    <div class="poster-canvas">
        {{-- Reverted to 12 pages (3x4) as per user optimization request --}}
        @for ($r = 0; $r < 4; $r++)
            @for ($c = 0; $c < 3; $c++)
                <div class="page-window" data-page-num="{{ ($r * 3) + $c + 1 }}">
                    <div class="content-clipper">
                        <div class="original-layout" style="left: -{{ $c * 200 }}mm; top: -{{ $r * 287 }}mm;">
                            <div class="container">
                                <div class="header">
                                    <img src="{{ asset('img/logo-kemenag.png') }}" class="logo-left">
                                    <div class="header-content">
                                        <h1>KEMENTERIAN AGAMA KABUPATEN MAJALENGKA</h1>
                                        <h2>MTs NEGERI 11 MAJALENGKA</h2>
                                        <p>Kp. Sindanghurip Desa ManiIs Kec. Cingambul Kab. Majalengka, 45467</p>
                                        <p>Telp. (0233) 3600020 E-mail: mtsn11majalengka@gmail.com</p>
                                    </div>
                                    <img src="{{ asset('img/logo-mtsn11.png') }}" class="logo-right">
                                </div>
                                <div class="header-line"></div>
                                <div class="title-section">
                                    <h3>JADWAL PROSES BELAJAR MENGAJAR</h3>
                                    <h3>SEMESTER {{ $activeSemester->tipe == 'Ganjil' ? 'I' : 'II' }} ({{ strtoupper($activeSemester->tipe) }}) TAHUN PELAJARAN {{ $activeSemester->nama_tahun }}</h3>
                                </div>
                                <div class="main-content">
                                    <div style="flex-grow: 1;">
                                        <table>
                                            <thead>
                                                <tr>
                                                    <th rowspan="2" class="col-hari header-bg" style="height: 40pt;"><span class="vertical-text">HARI</span></th>
                                                    <th rowspan="2" class="col-waktu header-bg" style="height: 40pt;">WAKTU</th>
                                                    <th rowspan="2" class="col-jam header-bg" style="height: 40pt;"><span class="vertical-text">JAM KE</span></th>
                                                    @foreach($kelasList as $tingkat => $kelas)
                                                        <th colspan="{{ $kelas->count() }}" class="header-bg {{ !$loop->last ? 'grade-separator' : '' }}" style="height: 20pt;">{{ $tingkat }}</th>
                                                    @endforeach
                                                </tr>
                                                <tr>
                                                    @foreach($allKelas as $index => $k)
                                                        @php $isLastOfGrade = ($index < $allKelas->count() - 1) && ($k->tingkat !== $allKelas[$index + 1]->tingkat); @endphp
                                                        <th class="col-kelas header-bg {{ $isLastOfGrade ? 'grade-separator' : '' }}">{{ str_replace('Kelas ', '', $k->nama_kelas) }}</th>
                                                    @endforeach
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($hariList as $hari => $slots)
                                                    @foreach($slots as $idx => $slot)
                                                        @php $isLastSlot = ($idx === count($slots) - 1); @endphp
                                                        <tr class="{{ $isLastSlot ? 'day-separator' : '' }}">
                                                            @if($idx === 0)
                                                                <td rowspan="{{ count($slots) }}" class="col-hari day-separator">
                                                                    <div class="rotated-content">{{ strtoupper($hari) }}</div>
                                                                </td>
                                                            @endif
                                                            <td class="col-waktu">{{ $slot['time'] }}</td>
                                                            <td class="col-jam">{{ is_numeric($slot['jam']) ? $slot['jam'] : '' }}</td>
                                                            @if(isset($slot['type']))
                                                                @php 
                                                                    $class = 'special-row';
                                                                    $type = $slot['type'];
                                                                    if($type === 'ISTIRAHAT') $class = 'ist-row';
                                                                    elseif($type === 'MAKAN') $class = 'makan-row';
                                                                    elseif(in_array($type, ['SHALAT', 'LKD', 'QIROAH', 'SHALAT_J'])) $class = 'sholat-row';

                                                                    $text = match($type) {
                                                                        'UPACARA' => 'UPACARA BENDERA',
                                                                        'MAKAN' => 'PENDISTRIBUSIAN MAKAN BERGIZI GRATIS',
                                                                        'SHALAT_J' => "SHALAT JUM'AT BERJAMAAH",
                                                                        'PRAMUKA' => 'EKSTRAKURIKULER PRAMUKA',
                                                                        'SHALAT' => 'SHALAT DZUHUR',
                                                                        'LKD' => 'LKD / KULTUM',
                                                                        default => $type
                                                                    };
                                                                @endphp
                                                                <td colspan="{{ $allKelas->count() }}" class="{{ $class }}">{{ $text }}</td>
                                                            @else
                                                                @foreach($allKelas as $index => $k)
                                                                    @php 
                                                                        $kg = $grid[$hari][$slot['jam']][$k->id] ?? ''; 
                                                                        $isLastOfGrade = ($index < $allKelas->count() - 1) && ($k->tingkat !== $allKelas[$index + 1]->tingkat);
                                                                        $isFriday5 = (strtoupper($hari) === 'JUMAT' && $slot['jam'] == 5);
                                                                    @endphp
                                                                    <td class="{{ $isFriday5 ? 'dark-green-bg' : '' }} {{ $isLastOfGrade ? 'grade-separator' : '' }}" style="{{ in_array($kg, ['AM', 'ED', 'SR', 'RM', 'WA', 'ZN']) ? 'color: red;' : '' }}">{{ $kg }}</td>
                                                                @endforeach
                                                            @endif
                                                        </tr>
                                                    @endforeach
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="legend-container">
                                        <table class="legend-table">
                                            <thead><tr><th class="kg-col">KG</th><th>NAMA GURU</th></tr></thead>
                                            <tbody>
                                                @foreach($gurus as $guru)
                                                    <tr><td class="kg-col">{{ $guru->kode_guru }}</td><td>{{ $guru->nama_lengkap }}</td></tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                        <table class="legend-table" style="margin-top: 5pt;">
                                            <thead><tr><th class="no-col">No</th><th>Mapel</th><th class="no-col">No</th><th>Mapel</th></tr></thead>
                                            <tbody>
                                                @php $half = ceil($mapels->count() / 2); $mapelColumns = $mapels->chunk($half); $col1 = $mapelColumns->get(0) ?? collect(); $col2 = $mapelColumns->get(1) ?? collect(); @endphp
                                                @for ($i = 0; $i < $half; $i++)
                                                    @php $m1 = $col1->values()->get($i); $m2 = $col2->values()->get($i); $nama1 = $m1 ? $m1->nama_mapel : ''; $nama2 = $m2 ? $m2->nama_mapel : ''; $nama1 = str_replace('Pendidikan Jasmani, Olahraga dan Kesehatan', 'Penjaskes', $nama1); $nama2 = str_replace('Pendidikan Jasmani, Olahraga dan Kesehatan', 'Penjaskes', $nama2); @endphp
                                                    <tr><td class="no-col">{{ $m1 ? str_pad($i + 1, 2, '0', STR_PAD_LEFT) : '' }}</td><td>{{ $nama1 }}</td><td class="no-col">{{ $m2 ? str_pad($i + 1 + $half, 2, '0', STR_PAD_LEFT) : '' }}</td><td>{{ $nama2 }}</td></tr>
                                                @endfor
                                            </tbody>
                                        </table>
                                        <div class="footer-container">
                                            @php
                                                $hasNewStempel = \Illuminate\Support\Facades\Storage::disk('public')->exists('presets/stempel.png');
                                                $hasNewTTD_Kepala = \Illuminate\Support\Facades\Storage::disk('public')->exists('presets/ttd_kepala.png');
                                                $hasNewTTD_Waka = \Illuminate\Support\Facades\Storage::disk('public')->exists('presets/ttd_waka.png');
                                                
                                                $stempelURL = $hasNewStempel ? asset('storage/presets/stempel.png') : null;
                                                $ttdKepalaURL = $hasNewTTD_Kepala ? asset('storage/presets/ttd_kepala.png') : null;
                                                $ttdWakaURL = $hasNewTTD_Waka ? asset('storage/presets/ttd_waka.png') : null;
                                                $v = time();

                                                $yearParts = explode('/', $activeSemester->nama_tahun);
                                                $printYear = $activeSemester->tipe === 'Ganjil' ? ($yearParts[0] ?? date('Y')) : ($yearParts[1] ?? ($yearParts[0] + 1));
                                            @endphp

                                            <div class="signature-box" style="margin-top: 15pt; position: relative;">
                                                <p>Cingambul, {{ strtoupper($activeSemester->tipe) == 'GANJIL' ? 'Juli' : 'Januari' }} {{ $printYear }}</p>
                                                <p>Wakil Kepala Bid. Kurikulum</p>
                                                
                                                <div style="height: 35pt; position: relative;">
                                                    @if($hasNewTTD_Waka)
                                                        <div class="adjustable-wrapper" data-adjustable-id="besar_ttd_waka" style="position: absolute; left: 20pt; top: -5pt; z-index: 1; transform: scale(1, 0.821); transform-origin: top left;">
                                                            <img src="{{ $ttdWakaURL }}?v={{ $v }}" style="height: 40pt; width: auto; display: block;">
                                                            <div class="resize-handle"></div>
                                                        </div>
                                                    @endif
                                                </div>

                                                <p><strong>{{ $wakaKurikulum->nama_lengkap ?? '....................................' }}</strong></p>
                                                <p>NIP. {{ $wakaKurikulum->username ?? '....................................' }}</p>
                                            </div>

                                            <div class="signature-box" style="margin-top: 10pt; position: relative;">
                                                <p>Mengetahui,</p>
                                                <p>Plt. Kepala Madrasah</p>

                                                <div style="height: 35pt; position: relative;">
                                                    @if($hasNewTTD_Kepala)
                                                        <div class="adjustable-wrapper" data-adjustable-id="besar_ttd_kepala" style="position: absolute; left: 15pt; top: -5pt; z-index: 1; transform: scale(1, 0.821); transform-origin: top left;">
                                                            <img src="{{ $ttdKepalaURL }}?v={{ $v }}" style="height: 45pt; width: auto; display: block;">
                                                            <div class="resize-handle"></div>
                                                        </div>
                                                    @endif
                                                    
                                                    @if($hasNewStempel)
                                                        <div class="adjustable-wrapper" data-adjustable-id="besar_stempel" style="position: absolute; left: -15pt; top: -10pt; z-index: 2; transform: scale(1, 0.821); transform-origin: top left;">
                                                            <img src="{{ $stempelURL }}?v={{ $v }}" style="width: 70pt; height: auto; display: block; opacity: 0.75;">
                                                            <div class="resize-handle"></div>
                                                        </div>
                                                    @endif
                                                </div>

                                                <p><strong>{{ $kepalaMadrasah->nama_lengkap ?? '....................................' }}</strong></p>
                                                <p>NIP. {{ $kepalaMadrasah->username ?? '....................................' }}</p>
                                            </div>
                                            <div style="margin-top: 35pt; text-align: left;"><span class="dark-green-bg" style="padding: 1pt 5pt; font-weight: bold; font-style: italic;">Kokurikuler BTQ</span></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="crop-marks">HALAMAN {{ ($r * 3) + $c + 1 }}</div>
                </div>
            @endfor
        @endfor
    </div>
    @include('admin.cetak._adjustable_assets', ['templateKey' => 'cetak_besar'])
</body>
</html>
