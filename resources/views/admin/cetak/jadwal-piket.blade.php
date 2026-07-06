<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Jadwal Piket Guru - {{ $activeSemester->nama_tahun }}</title>
    <style>
        * {
            box-sizing: border-box;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            @page {
                size: A4 landscape;
                margin: 1.5cm;
            }

            body {
                -webkit-print-color-adjust: exact;
                background-color: #fff !important;
            }
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            line-height: 1.4;
            color: #000;
            margin: 0;
            padding: 0;
            background-color: #fff;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 20px;
            margin: 0;
            text-transform: uppercase;
            font-weight: bold;
        }

        .header h2 {
            font-size: 18px;
            margin: 5px 0;
            text-transform: uppercase;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            table-layout: fixed;
            /* Ensures equal column widths */
        }

        th {
            width: 20%;
            /* 100% / 5 columns */
            background-color: #dededeff;
            /* Light amber/yellow from image */
            border: 1px solid #000;
            padding: 12px 8px;
            font-size: 16px;
            text-transform: uppercase;
            font-weight: bold;
            text-align: center;
        }

        td {
            border: 1px solid #000;
            padding: 15px 10px;
            font-size: 16px;
            text-align: center;
            /* Centered as per new propotional requirement */
            vertical-align: middle;
            min-height: 45px;
            /* Increased height for proportionality */
        }

        .signature-container {
            margin-top: 40px;
            display: flex;
            justify-content: flex-end;
        }

        .signature-box {
            width: 300px;
            text-align: left;
            font-size: 18px;
            position: relative;
        }

        .stempel {
            position: absolute;
            width: 180px;
            height: auto;
            opacity: 0.7;
            left: -80px;
            top: 20px;
            pointer-events: none;
            z-index: 10;
        }

        .sign-area {
            position: relative;
            z-index: 20;
        }

        @media screen {
            body {
                background-color: #1a1a1b;
                display: flex;
                flex-direction: column;
                align-items: center;
                padding: 40px 0;
                min-height: 100vh;
            }

            .main-paper {
                width: 297mm; /* Landscape A4 */
                background: white;
                box-shadow: 0 10px 40px rgba(0,0,0,0.8);
                border: 1px solid #444;
                padding: 1.5cm;
                box-sizing: border-box;
                position: relative;
            }
        }
    </style>
</head>

<body>
    <!-- Standardized Controls Panel -->
    <div class="no-print controls-panel">
        <a href="javascript:window.print()" class="no-print-btn">Cetak Jadwal Piket</a>
    </div>

    <div class="main-paper">
        <div class="header">
            <h1>JADWAL PIKET GURU</h1>
            <h2>MTs NEGERI 11 MAJALENGKA</h2>
            <h2>SEMESTER {{ $activeSemester->tipe == 'Ganjil' ? 'I' : 'II' }} ({{ strtoupper($activeSemester->tipe) }})
                TAHUN PELAJARAN {{ $activeSemester->nama_tahun }}</h2>
        </div>

    <table>
        <thead>
            <tr>
                @foreach($days as $day)
                    <th>{{ $day === 'Jumat' ? "JUM'AT" : strtoupper($day) }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @php
                $maxRows = 0;
                foreach ($days as $day) {
                    $maxRows = max($maxRows, count($schedule[$day]));
                }
                // Proportional default for 2 teachers per day
                $maxRows = max($maxRows, 2);
            @endphp

            @for($i = 0; $i < $maxRows; $i++)
                <tr>
                    @foreach($days as $day)
                        <td>
                            {{ $schedule[$day][$i]->nama_lengkap ?? '' }}
                        </td>
                    @endforeach
                </tr>
            @endfor
        </tbody>
    </table>

    <div class="signature-container">
        <div class="signature-box">
            <div class="sign-area">
                @php
                    $yearParts = explode('/', $activeSemester->nama_tahun);
                    $printYear = $activeSemester->tipe === 'Ganjil' ? ($yearParts[0] ?? date('Y')) : ($yearParts[1] ?? ($yearParts[0] + 1));
                    $printMonth = $activeSemester->tipe === 'Ganjil' ? 'Juli' : 'Januari';

                    $hasNewStempel = \Illuminate\Support\Facades\Storage::disk('public')->exists('presets/stempel.png');
                    $hasNewTTD = \Illuminate\Support\Facades\Storage::disk('public')->exists('presets/ttd_kepala.png');

                    $stempelURL = $hasNewStempel ? asset('storage/presets/stempel.png') : asset('img/stempel_ttd_kepala.png');
                    $ttdURL = $hasNewTTD ? asset('storage/presets/ttd_kepala.png') : null;
                @endphp
                <p>{{ $cetakTanggalLokasi ?? 'Cingambul, ' . date('j F Y') }}</p>
                <p style="margin-top: -20px;">{{ $cetakPejabatLabel ?? 'Kepala Madrasah' }},</p>

                <div style="position: relative; height: 80px; margin-top: 10px;">
                    @if($hasNewTTD)
                        <div class="adjustable-wrapper" data-adjustable-id="piket_ttd" style="position: absolute; left: -40px; top: -20px; z-index: 1;">
                            <img src="{{ $ttdURL }}?v={{ time() }}"
                                style="height: 110px; width: auto; display: block;">
                            <div class="resize-handle"></div>
                        </div>
                    @endif

                    @if($hasNewStempel)
                        <div class="adjustable-wrapper" data-adjustable-id="piket_stempel" style="position: absolute; left: -120px; top: -50px; z-index: 2;">
                            <img src="{{ $stempelURL }}?v={{ time() }}" 
                                style="width: 190px; height: auto; display: block; opacity: 0.85;">
                            <div class="resize-handle"></div>
                        </div>
                    @else
                        {{-- Fallback to legacy combined image if no new assets --}}
                        <div class="adjustable-wrapper" data-adjustable-id="piket_stempel_legacy" style="position: absolute; left: -80px; top: 10px; z-index: 2;">
                            <img src="{{ $stempelURL }}" 
                                style="width: 180px; height: auto; display: block;">
                            <div class="resize-handle"></div>
                        </div>
                    @endif
                </div>

                <div style="margin-top: 0px;">
                    <p><strong><u>{{ $kepalaMadrasah->nama_lengkap ?? '..........................................' }}</u></strong>
                    </p>
                    <p style="margin-top: -20px;">NIP.
                        {{ $kepalaMadrasah->username ?? '..........................................' }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    </div>
    @include('admin.cetak._adjustable_assets', ['templateKey' => 'cetak_piket'])
</body>

</html>