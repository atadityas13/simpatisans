<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    @if(!empty($preselectedGuru))
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=0.2, maximum-scale=5.0, user-scalable=yes">
    @else
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @endif
    <title>Jadwal Pelajaran - {{ $activeSemester->getFullLabelAttribute() }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 0.5cm 0.7cm;
            /* 5mm top/bottom, 7mm left/right */
        }

        body {
            font-family: 'Arial Narrow', 'Arial', sans-serif;
            font-size: 6.5pt;
            margin: 0;
            padding: 0;
            color: #000;
        }

        .container {
            width: 100%;
        }

        /* Header Styling */
        .header {
            text-align: center;
            margin-bottom: 5px;
            padding-bottom: 2px;
            position: relative;
        }

        .header h1 {
            font-size: 9pt;
            margin: 0;
            font-weight: bold;
        }

        .header h2 {
            font-size: 10pt;
            margin: 0;
            font-weight: bold;
        }

        .header p {
            font-size: 7pt;
            margin: 0;
        }

        .header-line {
            border-bottom: 2pt solid #000;
            margin-bottom: 1pt;
        }

        .header-line-thin {
            border-bottom: 0.5pt solid #000;
        }

        .logo-left {
            position: absolute;
            left: 2pt;
            top: 5pt;
            height: 30pt;
        }

        .logo-right {
            position: absolute;
            right: 2pt;
            top: 5pt;
            height: 30pt;
        }

        .title-section {
            text-align: center;
            margin: 2pt 0 2pt 0;
        }

        .title-section h3 {
            font-size: 8.5pt;
            margin: 0;
            font-weight: bold;
            text-transform: uppercase;
        }

        /* Main Table Layout */
        .main-content {
            display: flex;
            align-items: flex-start;
            gap: 5pt;
            width: 100%;
            max-width: 100%;
            position: relative;
        }

        .schedule-table-wrap {
            flex: 1 1 0;
            min-width: 0;
            overflow: hidden;
            position: relative;
            z-index: 1;
        }

        .schedule-table-wrap table {
            table-layout: fixed;
            width: 100%;
        }

        .schedule-cell {
            white-space: nowrap !important;
            word-break: keep-all !important;
            overflow: hidden;
            line-height: 1 !important;
            font-size: 6pt;
            padding: 0 !important;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            border: 2pt solid #000;
        }

        th,
        td {
            border: 0.5pt solid #000;
            padding: 0.5pt;
            text-align: center;
            height: 8.8pt;
            font-weight: bold;
            white-space: nowrap;
            overflow: hidden;
            line-height: 1;
        }

        /* Ensuring only the leftmost overall border is thick */
        .col-hari,
        thead tr:first-child th:first-child {
            border-left: 2pt solid #000 !important;
        }

        th {
            background-color: #d9e6c3;
            font-weight: bold;
            font-size: 6.5pt;
            border: 1pt solid #000;
            height: 12pt;
            /* Increased from 9pt */
        }

        thead tr:first-child th {
            height: 15pt;
        }

        .vertical-text {
            writing-mode: vertical-rl;
            transform: rotate(180deg);
            white-space: nowrap;
            display: inline-block;
            line-height: 1;
            padding: 2pt 0;
            height: 35pt;
            /* Explicit height for vertical text container */
        }

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

        .day-separator,
        .day-separator td,
        .day-separator th {
            border-bottom: 1.5pt solid #000 !important;
        }

        .grade-separator {
            border-right: 1.5pt solid #000 !important;
        }

        /* Colors based on image */
        .header-bg {
            background-color: #f2f2f2;
            /* Light Gray */
            font-weight: bold;
        }

        .tingkat-bg {
            background-color: #f2f2f2;
        }

        .special-row {
            background-color: #4b54b5ff;
            /* Blue */
            font-weight: bold;
            letter-spacing: 3pt;
        }

        /* Yellow for UPACARA/PRAMUKA/UPACARA */
        .ist-row {
            background-color: #f15151ff;
            /* Red */
            font-weight: bold;
            letter-spacing: 3pt;
        }

        /* Light Green for ISTIRAHAT */
        .makan-row {
            background-color: #9fc5e8;
            /* Sky Blue */
            font-weight: bold;
            letter-spacing: 3pt;
        }

        /* Muted Cyan for MBG */
        .sholat-row {
            background-color: #42b419ff;
            /* Green */
            font-weight: bold;
            letter-spacing: 5pt;
        }

        /* Muted Blue for SHOLAT */
        .violet-row {
            background-color: #42b419ff;
            /* Green */
            font-weight: bold;
            letter-spacing: 5pt;
        }

        /* Light Violet for LKD/QIROAH */
        .jumat-activity {
            background-color: #42b419ff;
        }

        /* Legend Styling */
        .legend-container {
            width: 130pt;
            flex-shrink: 0;
            margin-left: 5pt;
            max-width: 130pt;
            overflow: visible;
            box-sizing: border-box;
            position: relative;
            z-index: 2;
        }

        .legend-table {
            font-size: 5pt;
            width: 100%;
            table-layout: fixed;
            max-width: 100%;
        }

        .legend-table td {
            text-align: left;
            padding: 0.5pt 1pt;
            border: 0.5pt solid #000;
            height: 8.0pt;
            /* Reduced slightly from 8.2pt */
            white-space: nowrap;
            overflow: hidden;
        }

        .legend-table th {
            text-align: center;
            background-color: #f2f2f2;
            /* Light Gray */
            font-size: 5pt;
            padding: 1pt;
            font-weight: bold;
        }

        /* Web: slightly smaller mapel names so long names fit the column */
        .mapel-legend-table td.mapel-legend-cell:not(.no-col) {
            font-size: 4.2pt;
            letter-spacing: -0.1pt;
            padding: 0.5pt 0.5pt;
        }

        .mapel-legend-table .no-col {
            width: 8pt;
        }

        /* Android app only: compact mapel legend so footer fits on 1 page */
        body.guru-app-view .mapel-legend-table th,
        body.guru-app-view .mapel-legend-table td {
            font-size: 2.5pt;
            height: 5.2pt;
            padding: 0;
            letter-spacing: -0.25pt;
        }

        body.guru-app-view .mapel-legend-table td.mapel-legend-cell:not(.no-col) {
            font-size: 2.3pt;
            font-weight: normal;
            letter-spacing: -0.3pt;
        }

        body.guru-app-view .mapel-legend-table .no-col {
            width: 6pt;
        }

        .legend-title {
            font-weight: bold;
            text-align: center;
            font-size: 6pt;
            margin-top: 5pt;
            margin-bottom: 2pt;
            border: 0.5pt solid #000;
            background: #f2f2f2;
            /* Light Gray */
        }

        .kg-col {
            width: 18pt;
            text-align: center !important;
            font-weight: bold;
        }

        .no-col {
            width: 12pt;
            text-align: center !important;
        }

        /* Header Layout */
        .header {
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            padding: 2pt 0;
            /* Reduced from 5pt */
            min-height: 42pt;
            /* Reduced from 50pt */
        }

        .logo-left {
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            height: 50pt;
            width: 50pt;
            object-fit: contain;
        }

        .logo-right {
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
            height: 45pt;
            width: 45pt;
            object-fit: contain;
        }

        .header-content {
            text-align: center;
            width: 100%;
            padding: 0 10pt;
            /* Reduced significantly from 45pt */
            box-sizing: border-box;
        }

        .header-content h1 {
            font-size: 11pt;
            margin: 0;
            font-weight: bold;
            line-height: 1.1;
            letter-spacing: 1.7pt;
        }

        .header-content h2 {
            font-size: 15pt;
            margin: 1pt 0;
            font-weight: bold;
            line-height: 1.1;
            letter-spacing: 2.5pt;
        }

        .header-content p {
            font-size: 8.5pt;
            margin: 0;
            line-height: 1.2;
            letter-spacing: 1.3pt;
        }

        .header-line {
            border-bottom: 2pt solid #000;
            margin-top: 1pt;
            /* Closer to text/logos */
            margin-bottom: 5pt;
            /* Closer to schedule table */
        }

        /* Footer / Signatures */
        .footer-container p {
            margin: 0;
            line-height: 1.1;
        }

        .footer-container,
        .signature-slot {
            overflow: visible;
        }

        .signature-box {
            width: 100%;
            text-align: center;
            margin-top: 10pt;
        }

        .signature-box p {
            margin: 0;
            line-height: 1.1;
            font-size: 8pt;
        }

        .signature-space {
            height: 30pt;
            position: relative;
        }

        .stamp-img {
            position: absolute;
            z-index: -1;
            opacity: 0.6;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            width: 60pt;
        }

        @media print {
            .no-print {
                display: none;
            }

            body {
                -webkit-print-color-adjust: exact;
            }

            .no-print {
                display: none !important;
            }
        }

        @media screen {
            body {
                background-color: #1a1a1a;
                display: flex;
                justify-content: center;
                padding: 40px 0;
            }

            .paper-preview {
                width: 210mm;
                min-height: 297mm;
                background: white;
                box-shadow: 0 0 20px rgba(0, 0, 0, 0.4);
                padding: 0.5cm 0.7cm;
                box-sizing: border-box;
                position: relative;
            }

            .guru-select {
                padding: 8px 12px;
                border-radius: 8px;
                border: 1px solid rgba(255,255,255,0.2);
                background: rgba(255,255,255,0.1);
                color: white;
                font-size: 13px;
                width: 100%;
                cursor: pointer;
                outline: none;
            }
            .guru-select option {
                background: #1e293b;
                color: white;
            }
        }

        .highlight-yellow {
            background-color: yellow !important;
            color: black !important;
            font-weight: bold !important;
        }

        .guru-specific-footer {
            display: none;
            text-align: right;
            font-size: 6pt;
            font-weight: bold;
            line-height: 1.2;
            border-top: 0.5pt dashed #999;
            padding-top: 3pt;
            margin-top: 8pt;
            white-space: nowrap;
        }

        .guru-specific-footer.guru-footer-active {
            display: block;
        }

        .kokurikuler-note {
            margin-top: 35pt;
            text-align: left;
            padding-left: 5pt;
        }

        @media print {
            .guru-specific-footer {
                display: none !important;
            }

            .guru-specific-footer.guru-footer-active {
                display: block !important;
                position: relative;
                text-align: right;
                margin-top: 8pt;
                padding-top: 3pt;
                border-top: 0.5pt dashed #999;
                font-size: 6pt;
                font-weight: bold;
                background: #fff;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .paper-preview {
                box-shadow: none !important;
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
            }

            .legend-container,
            .footer-container,
            .signature-slot {
                overflow: visible !important;
            }
        }

        /* Column Widths (Tightened) */
        .col-hari {
            width: 10pt;
            padding: 2pt 0 !important;
            background-color: #f2f2f2 !important;
            vertical-align: middle !important;
            text-align: center !important;
        }

        .col-waktu {
            width: 38pt;
            padding: 0.5pt;
            background-color: #f2f2f2;
        }

        .col-jam {
            width: 14pt;
            padding: 0;
            background-color: #f2f2f2;
        }

        .yellow-bg {
            background-color: #ffff01 !important;
        }

        .dark-green-bg {
            background-color: #354c29ff !important;
            /* Dark Green */
            color: #ffffff !important;
            /* White text for contrast */
        }

        /* Guru filter highlight must override Jumat jam 5 dark-green styling */
        .schedule-cell.highlight-yellow,
        .schedule-cell.dark-green-bg.highlight-yellow {
            background-color: yellow !important;
            color: black !important;
            font-weight: bold !important;
        }

        .col-kelas {
            width: 14pt;
        }

        @if(!empty($preselectedGuru))
        @media screen {
            body.guru-app-view {
                display: block !important;
                background: #e8ecf0 !important;
                padding: 0 !important;
                margin: 0 !important;
                overflow-x: hidden;
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
            body.guru-app-view .paper-preview {
                transform-origin: top center;
                flex-shrink: 0;
                margin: 0;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            }
        }
        body.guru-printing .guru-app-frame,
        body.guru-printing .paper-preview {
            transform: none !important;
            height: auto !important;
            padding: 0 !important;
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
            body.guru-app-view .paper-preview {
                transform: none !important;
                box-shadow: none !important;
            }
        }
        @endif
    </style>
</head>

<body class="{{ !empty($preselectedGuru) ? 'guru-app-view' : '' }}">
    @if(empty($preselectedGuru))
    <div class="no-print controls-panel">
        <a href="javascript:window.print()" class="no-print-btn">Cetak Jadwal</a>
        
        <div class="controls-group">
            <div style="font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: rgba(255,255,255,0.4); margin-bottom: 4px;">Filter Guru</div>
            <select id="guru-selector" class="guru-select">
                <option value="">-- Semua Guru --</option>
                @foreach($gurus as $guru)
                    <option value="{{ $guru->kode_guru }}" data-name="{{ $guru->nama_lengkap }}">{{ $guru->kode_guru }} - {{ $guru->nama_lengkap }}</option>
                @endforeach
            </select>
        </div>
    </div>
    @endif

    @if(!empty($preselectedGuru))
    <div class="guru-app-frame" id="guru-app-frame">
    @endif
    <div class="paper-preview" id="paper-preview">
        <div class="container">
        <!-- Header -->
        <div class="header">
            <img src="{{ asset('img/logo-kemenag.png') }}" alt="" class="logo-left">
            <div class="header-content">
                <h1>KEMENTERIAN AGAMA KABUPATEN MAJALENGKA</h1>
                <h2>MTs NEGERI 11 MAJALENGKA</h2>
                <p>Kp. Sindanghurip Desa ManiIs Kec. Cingambul Kab. Majalengka, 45467</p>
                <p>Telp. (0233) 3600020 E-mail: mtsn11majalengka@gmail.com</p>
            </div>
            <img src="{{ asset('img/logo-mtsn11.png') }}" alt="" class="logo-right">
        </div>
        <div class="header-line"></div>

        <div class="title-section">
            <h3>JADWAL PROSES BELAJAR MENGAJAR</h3>
            <h3>SEMESTER {{ $activeSemester->tipe == 'Ganjil' ? 'I' : 'II' }} ({{ strtoupper($activeSemester->tipe) }}) TAHUN PELAJARAN
                {{ $activeSemester->nama_tahun }}
            </h3>
        </div>

        <div class="main-content">
            <!-- Schedule Table -->
            <div class="schedule-table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th rowspan="2" class="col-hari header-bg" style="height: 40pt;"><span
                                    class="vertical-text">HARI</span></th>
                            <th rowspan="2" class="col-waktu header-bg" style="height: 40pt;">WAKTU</th>
                            <th rowspan="2" class="col-jam header-bg" style="height: 40pt;"><span
                                    class="vertical-text">JAM KE</span></th>
                            @foreach($kelasList as $tingkat => $kelas)
                                <th colspan="{{ $kelas->count() }}" class="header-bg {{ !$loop->last ? 'grade-separator' : '' }}" style="height: 20pt;">{{ $tingkat }}
                                </th>
                            @endforeach
                        </tr>
                        <tr>
                            @foreach($allKelas as $index => $k)
                                @php
                                    $isLastOfGrade = ($index < $allKelas->count() - 1) && ($k->tingkat !== $allKelas[$index + 1]->tingkat);
                                @endphp
                                <th class="col-kelas header-bg {{ $isLastOfGrade ? 'grade-separator' : '' }}">{{ str_replace('Kelas ', '', $k->nama_kelas) }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $hariList = [
                                'Senin' => [
                                    ['time' => '07.00-07.45', 'jam' => 'UPC', 'type' => 'UPACARA'],
                                    ['time' => '07.45-08.20', 'jam' => 1],
                                    ['time' => '08.20-08.55', 'jam' => 2],
                                    ['time' => '08.55-09.30', 'jam' => 3],
                                    ['time' => '09.30-10.05', 'jam' => 4],
                                    ['time' => '10.05-10.35', 'jam' => 'IST', 'type' => 'ISTIRAHAT'],
                                    ['time' => '10.35-11.10', 'jam' => 5],
                                    ['time' => '11.10-11.45', 'jam' => 6],
                                    ['time' => '11.45-12.15', 'jam' => 'MKN', 'type' => 'MAKAN'],
                                    ['time' => '12.15-12.45', 'jam' => 'SHL', 'type' => 'SHALAT'],
                                    ['time' => '12.45-13.20', 'jam' => 7],
                                    ['time' => '13.20-13.55', 'jam' => 8],
                                    ['time' => '13.55-14.30', 'jam' => 9],
                                ],
                                'Selasa' => [
                                    ['time' => '07.00-07.35', 'jam' => 1],
                                    ['time' => '07.35-08.10', 'jam' => 2],
                                    ['time' => '08.10-08.45', 'jam' => 3],
                                    ['time' => '08.45-09.20', 'jam' => 4],
                                    ['time' => '09.20-09.50', 'jam' => 'IST', 'type' => 'ISTIRAHAT'],
                                    ['time' => '09.50-10.25', 'jam' => 5],
                                    ['time' => '10.25-11.00', 'jam' => 6],
                                    ['time' => '11.00-11.35', 'jam' => 7],
                                    ['time' => '11.35-12.10', 'jam' => 'MKN', 'type' => 'MAKAN'],
                                    ['time' => '12.10-12.45', 'jam' => 'SHL', 'type' => 'SHALAT'],
                                    ['time' => '12.45-13.20', 'jam' => 8],
                                    ['time' => '13.20-13.55', 'jam' => 9],
                                    ['time' => '13.55-14.30', 'jam' => 10],
                                ],
                                'Rabu' => [
                                    ['time' => '07.00-07.35', 'jam' => 1],
                                    ['time' => '07.35-08.10', 'jam' => 2],
                                    ['time' => '08.10-08.45', 'jam' => 3],
                                    ['time' => '08.45-09.20', 'jam' => 4],
                                    ['time' => '09.20-09.50', 'jam' => 'IST', 'type' => 'ISTIRAHAT'],
                                    ['time' => '09.50-10.25', 'jam' => 5],
                                    ['time' => '10.25-11.00', 'jam' => 6],
                                    ['time' => '11.00-11.35', 'jam' => 7],
                                    ['time' => '11.35-12.10', 'jam' => 'MKN', 'type' => 'MAKAN'],
                                    ['time' => '12.10-12.45', 'jam' => 'SHL', 'type' => 'SHALAT'],
                                    ['time' => '12.45-13.20', 'jam' => 8],
                                    ['time' => '13.20-13.55', 'jam' => 9],
                                    ['time' => '13.55-14.30', 'jam' => 10],
                                ],
                                'Kamis' => [
                                    ['time' => '07.00-07.35', 'jam' => 1],
                                    ['time' => '07.35-08.10', 'jam' => 2],
                                    ['time' => '08.10-08.45', 'jam' => 3],
                                    ['time' => '08.45-09.20', 'jam' => 4],
                                    ['time' => '09.20-09.50', 'jam' => 'IST', 'type' => 'ISTIRAHAT'],
                                    ['time' => '09.50-10.25', 'jam' => 5],
                                    ['time' => '10.25-11.00', 'jam' => 6],
                                    ['time' => '11.00-11.35', 'jam' => 7],
                                    ['time' => '11.35-12.10', 'jam' => 'MKN', 'type' => 'MAKAN'],
                                    ['time' => '12.10-12.45', 'jam' => 'SHL', 'type' => 'SHALAT'],
                                    ['time' => '12.45-13.20', 'jam' => 8],
                                    ['time' => '13.20-13.55', 'jam' => 9],
                                    ['time' => '13.55-14.30', 'jam' => 10],
                                ],
                                'Jumat' => [
                                    ['time' => '07.00-07.15', 'jam' => 'LKD', 'type' => 'LKD'],
                                    ['time' => '07.15-08.00', 'jam' => 'QIR', 'type' => 'QIROAH'],
                                    ['time' => '08.00-08.30', 'jam' => 1],
                                    ['time' => '08.30-09.00', 'jam' => 2],
                                    ['time' => '09.00-09.30', 'jam' => 3],
                                    ['time' => '09.30-09.50', 'jam' => 'IST', 'type' => 'ISTIRAHAT'],
                                    ['time' => '09.50-10.20', 'jam' => 4],
                                    ['time' => '10.20-10.50', 'jam' => 5],
                                    ['time' => '10.50-11.20', 'jam' => 'MKN', 'type' => 'MAKAN'],
                                    ['time' => '11.20-12.30', 'jam' => 'SHL_J', 'type' => 'SHALAT_J'],
                                    ['time' => '12.30-14.30', 'jam' => 'PRAM', 'type' => 'PRAMUKA'],
                                ]
                            ];
                        @endphp

                        @foreach($hariList as $hari => $slots)
                            @foreach($slots as $idx => $slot)
                                @php $isLastSlot = ($idx === count($slots) - 1); @endphp
                                <tr class="{{ $isLastSlot ? 'day-separator' : '' }}">
                                    @if($idx === 0)
                                        <td rowspan="{{ count($slots) }}" class="col-hari">
                                            <div class="rotated-content">{{ strtoupper($hari) }}</div>
                                        </td>
                                    @endif
                                    <td class="col-waktu">{{ $slot['time'] }}</td>
                                    <td class="col-jam">{{ is_numeric($slot['jam']) ? $slot['jam'] : '' }}</td>

                                    @if(isset($slot['type']))
                                        @if($slot['type'] === 'UPACARA')
                                            <td colspan="{{ $allKelas->count() }}" class="special-row">UPACARA BENDERA</td>
                                        @elseif($slot['type'] === 'ISTIRAHAT')
                                            <td colspan="{{ $allKelas->count() }}" class="ist-row">ISTIRAHAT</td>
                                        @elseif($slot['type'] === 'MAKAN')
                                            <td colspan="{{ $allKelas->count() }}" class="makan-row">PENDISTRIBUSIAN MAKAN BERGIZI
                                                GRATIS</td>
                                        @elseif($slot['type'] === 'SHALAT')
                                            <td colspan="{{ $allKelas->count() }}" class="sholat-row">SHALAT DZUHUR</td>
                                        @elseif($slot['type'] === 'LKD')
                                            <td colspan="{{ $allKelas->count() }}" class="violet-row">LKD / KULTUM</td>
                                        @elseif($slot['type'] === 'QIROAH')
                                            <td colspan="{{ $allKelas->count() }}" class="violet-row">QIROATUL QUR'AN</td>
                                        @elseif($slot['type'] === 'SHALAT_J')
                                            <td colspan="{{ $allKelas->count() }}" class="sholat-row">SHALAT JUM'AT BERJAMAAH</td>
                                        @elseif($slot['type'] === 'PRAMUKA')
                                            <td colspan="{{ $allKelas->count() }}" class="special-row">EKSTRAKURIKULER PRAMUKA</td>
                                        @endif
                                    @else
                                        @foreach($allKelas as $index => $k)
                                            @php 
                                                $kg = $grid[$hari][$slot['jam']][$k->id] ?? ''; 
                                                $isLastOfGrade = ($index < $allKelas->count() - 1) && ($k->tingkat !== $allKelas[$index + 1]->tingkat);
                                            @endphp
                                            {{-- Highlight specific codes from image example TS, JM, IK... --}}
                                            <td class="schedule-cell {{ (strtoupper($hari) === 'JUMAT' && $slot['jam'] == 5) ? 'dark-green-bg' : '' }} {{ $isLastOfGrade ? 'grade-separator' : '' }}"
                                                data-kg-full="{{ $kg }}"
                                                style="{{ in_array($kg, ['AM', 'ED', 'SR', 'RM', 'WA', 'ZN']) ? 'color: red;' : '' }}">
                                                {{ $kg }}
                                            </td>
                                        @endforeach
                                    @endif
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Legend Table (Gurus) & Signatures -->
            <div class="legend-container">
                <table class="legend-table">
                    <thead>
                        <tr>
                            <th class="kg-col">KG</th>
                            <th>NAMA GURU</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($gurus as $guru)
                            <tr class="guru-legend-row" data-kg="{{ $guru->kode_guru }}">
                                <td class="kg-col">{{ $guru->kode_guru }}</td>
                                <td>{{ $guru->nama_lengkap }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <table class="legend-table mapel-legend-table" style="margin-top: 5pt;">
                    <thead>
                        <tr>
                            <th class="no-col">No</th>
                            <th>Mapel</th>
                            <th class="no-col">No</th>
                            <th>Mapel</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $half = ceil($mapels->count() / 2);
                            $mapelColumns = $mapels->chunk($half);
                            $col1 = $mapelColumns->get(0) ?? collect();
                            $col2 = $mapelColumns->get(1) ?? collect();
                        @endphp
                        @for ($i = 0; $i < $half; $i++)
                            @php
                                $m1 = $col1->values()->get($i);
                                $m2 = $col2->values()->get($i);
                                $no1 = $m1 ? str_pad($i + 1, 2, '0', STR_PAD_LEFT) : '';
                                $no2 = $m2 ? str_pad($i + 1 + $half, 2, '0', STR_PAD_LEFT) : '';

                                $nama1 = $m1 ? $m1->nama_mapel : '';
                                $nama2 = $m2 ? $m2->nama_mapel : '';

                                $nama1 = str_replace('Pendidikan Jasmani, Olahraga dan Kesehatan', 'Penjaskes', $nama1);
                                $nama2 = str_replace('Pendidikan Jasmani, Olahraga dan Kesehatan', 'Penjaskes', $nama2);
                            @endphp
                            <tr>
                                <td class="no-col mapel-legend-cell" data-mapel-no="{{ $no1 }}">{{ $no1 }}</td>
                                <td class="mapel-legend-cell" data-mapel-no="{{ $no1 }}">{{ $nama1 }}</td>
                                <td class="no-col mapel-legend-cell" data-mapel-no="{{ $no2 }}">{{ $no2 }}</td>
                                <td class="mapel-legend-cell" data-mapel-no="{{ $no2 }}">{{ $nama2 }}</td>
                            </tr>
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
                        <p>{{ $cetakTanggalLokasi ?? 'Cingambul, ' . date('j F Y') }}</p>
                        <p>Wakil Kepala Bid. Kurikulum</p>
                        
                        <div class="signature-slot" style="height: 35pt; position: relative;">
                            @if($hasNewTTD_Waka)
                                <div class="adjustable-wrapper" data-adjustable-id="pelajaran_ttd_waka" style="position: absolute; left: 20pt; top: -5pt; z-index: 1;">
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
                        <p>{{ $cetakPejabatLabel ?? 'Kepala Madrasah' }}</p>

                        <div class="signature-slot" style="height: 35pt; position: relative;">
                            @if($hasNewTTD_Kepala)
                                <div class="adjustable-wrapper" data-adjustable-id="pelajaran_ttd_kepala" style="position: absolute; left: 15pt; top: -5pt; z-index: 1;">
                                    <img src="{{ $ttdKepalaURL }}?v={{ $v }}" style="height: 45pt; width: auto; display: block;">
                                    <div class="resize-handle"></div>
                                </div>
                            @endif
                            
                            @if($hasNewStempel)
                                <div class="adjustable-wrapper" data-adjustable-id="pelajaran_stempel" style="position: absolute; left: -15pt; top: -10pt; z-index: 2;">
                                    <img src="{{ $stempelURL }}?v={{ $v }}" style="width: 85pt; height: auto; display: block; opacity: 0.75;">
                                    <div class="resize-handle"></div>
                                </div>
                            @endif
                        </div>

                        <p><strong>{{ $kepalaMadrasah->nama_lengkap ?? '....................................' }}</strong></p>
                        <p>NIP. {{ $kepalaMadrasah->username ?? '....................................' }}</p>
                    </div>

                    <div class="kokurikuler-note">
                        <span class="dark-green-bg"
                            style="padding: 1pt 5pt; font-weight: bold; font-style: italic;">Kokurikuler
                            BTQ</span>
                    </div>

                    <div id="specific-guru-footer" class="guru-specific-footer">
                        Dicetak untuk guru : <span id="display-guru-name"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @if(!empty($preselectedGuru))
    </div>
    @endif

    <script>
        function applyGuruFilter(selectedKg, selectedName) {
            document.querySelectorAll('.highlight-yellow').forEach(el => el.classList.remove('highlight-yellow'));

            const footer = document.getElementById('specific-guru-footer');
            const footerName = document.getElementById('display-guru-name');

            if (selectedKg) {
                document.querySelectorAll('.schedule-cell').forEach(cell => {
                    const kgFull = cell.getAttribute('data-kg-full');
                    if (kgFull) {
                        if (kgFull === selectedKg || kgFull.startsWith(selectedKg + '-')) {
                            cell.classList.add('highlight-yellow');

                            if (kgFull.includes('-')) {
                                const mapelNo = kgFull.split('-')[1];
                                document.querySelectorAll(`.mapel-legend-cell[data-mapel-no="${mapelNo}"]`).forEach(mCell => {
                                    mCell.classList.add('highlight-yellow');
                                });
                            }
                        }
                    }
                });

                document.querySelectorAll(`.guru-legend-row[data-kg="${selectedKg}"]`).forEach(row => {
                    row.classList.add('highlight-yellow');
                    row.querySelectorAll('td').forEach(td => td.classList.add('highlight-yellow'));
                });

                if (footer && footerName) {
                    footerName.textContent = selectedName;
                    footer.classList.add('guru-footer-active');
                }
            } else {
                if (footer) {
                    footer.classList.remove('guru-footer-active');
                }
            }
        }

        const guruSelector = document.getElementById('guru-selector');
        if (guruSelector) {
            guruSelector.addEventListener('change', function() {
                applyGuruFilter(this.value, this.options[this.selectedIndex].getAttribute('data-name'));
            });
        }

        @if(!empty($preselectedGuru))
        document.addEventListener('DOMContentLoaded', function() {
            applyGuruFilter(@json($preselectedGuru->kode_guru), @json($preselectedGuru->nama_lengkap));
        });
        @endif
    </script>
    @if(!empty($preselectedGuru))
    <script>
        (function () {
            function fitGuruApp() {
                if (document.body.classList.contains('guru-printing')) return;
                var paper = document.getElementById('paper-preview');
                var frame = document.getElementById('guru-app-frame');
                if (!paper || !frame) return;

                paper.style.transform = '';
                var naturalW = paper.offsetWidth;
                if (!naturalW) return;

                var viewW = window.innerWidth || document.documentElement.clientWidth;
                var scale = Math.min(1, (viewW - 8) / naturalW);

                if (scale < 0.999) {
                    paper.style.transform = 'scale(' + scale + ')';
                    paper.style.transformOrigin = 'top center';
                }
                frame.style.height = Math.ceil(paper.getBoundingClientRect().height) + 'px';
            }

            window.prepareGuruPrint = function (cb) {
                document.body.classList.add('guru-printing');
                var paper = document.getElementById('paper-preview');
                if (paper) paper.style.transform = 'none';
                var frame = document.getElementById('guru-app-frame');
                if (frame) frame.style.height = 'auto';
                setTimeout(function () { if (typeof cb === 'function') cb(); }, 250);
            };

            window.finishGuruPrint = function () {
                document.body.classList.remove('guru-printing');
                fitGuruApp();
            };

            window.addEventListener('load', function () {
                fitGuruApp();
                setTimeout(fitGuruApp, 300);
            });
            window.addEventListener('resize', fitGuruApp);
            window.addEventListener('orientationchange', function () {
                setTimeout(fitGuruApp, 300);
            });
        })();
    </script>
    @endif
    @if(empty($preselectedGuru))
    @include('admin.cetak._adjustable_assets', ['templateKey' => 'cetak_pelajaran'])
    @endif
</body>

</html>