<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Wali Kelas - {{ $activeSemester->nama_tahun }}</title>
    <style>
        * {
            box-sizing: border-box;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            @page {
                size: A4 portrait;
                margin: 2cm;
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

        .header h1,
        .header h2,
        .header h3 {
            margin: 5px 0;
            font-weight: bold;
            text-transform: uppercase;
        }

        .header h1 {
            font-size: 20px;
        }

        .header h2 {
            font-size: 18px;
        }

        .header h3 {
            font-size: 16px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 40px;
        }

        th {
            background-color: #dedede;
            border: 1px solid #000;
            padding: 10px 8px;
            font-size: 15px;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
        }

        td {
            border: 1px solid #000;
            padding: 10px 8px;
            font-size: 15px;
            vertical-align: middle;
        }

        .col-no {
            width: 60px;
            text-align: center;
        }

        .col-kelas {
            width: 120px;
            text-align: center;
        }

        .col-nama {
            text-align: left;
        }

        .signature-container {
            margin-top: 20px;
            display: flex;
            justify-content: flex-end;
        }

        .signature-box {
            width: 300px;
            text-align: left;
            font-size: 16px;
            position: relative;
        }

        .sign-area p {
            margin: 0;
            line-height: 1.4;
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
                width: 210mm;
                min-height: 297mm;
                background: white;
                box-shadow: 0 10px 40px rgba(0, 0, 0, 0.8);
                border: 1px solid #444;
                padding: 2cm;
                box-sizing: border-box;
            }

            .no-print {
                position: fixed;
                top: 15px;
                right: 15px;
                z-index: 9999;
            }

            .no-print-btn {
                display: inline-block;
                background: #4f46e5;
                color: #fff;
                padding: 10px 18px;
                border-radius: 10px;
                font-size: 13px;
                font-weight: 700;
                text-decoration: none;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            }

            .no-print-btn:hover {
                background: #4338ca;
            }
        }
    </style>
</head>

<body>
    <div class="no-print">
        <a href="javascript:window.print()" class="no-print-btn">Cetak Daftar Wali Kelas</a>
    </div>

    <div class="main-paper">
        <div class="header">
            <h1>Daftar Wali Kelas</h1>
            <h2>MTsN 11 Majalengka</h2>
            <h3>Tahun Pelajaran {{ $activeSemester->nama_tahun }}</h3>
        </div>

        <table>
            <thead>
                <tr>
                    <th class="col-no">No</th>
                    <th class="col-kelas">Kelas</th>
                    <th class="col-nama">Nama Wali</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td class="col-no">{{ $row['no'] }}</td>
                        <td class="col-kelas">{{ $row['kelas'] }}</td>
                        <td class="col-nama">{{ $row['nama_wali'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" style="text-align: center;">Belum ada data kelas.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="signature-container">
            <div class="signature-box">
                <div class="sign-area">
                    @php
                        $hasNewStempel = \Illuminate\Support\Facades\Storage::disk('public')->exists('presets/stempel.png');
                        $hasNewTTD = \Illuminate\Support\Facades\Storage::disk('public')->exists('presets/ttd_kepala.png');

                        $stempelURL = $hasNewStempel ? asset('storage/presets/stempel.png') : asset('img/stempel_ttd_kepala.png');
                        $ttdURL = $hasNewTTD ? asset('storage/presets/ttd_kepala.png') : null;
                    @endphp
                    <p>{{ $cetakTanggalLokasi ?? 'Cingambul, ' . date('j F Y') }}</p>
                    <p style="margin-top: -4px;">{{ strtoupper($cetakPejabatLabel ?? 'Kepala Madrasah') }},</p>

                    <div style="position: relative; height: 80px; margin-top: 10px;">
                        @if($hasNewTTD)
                            <div class="adjustable-wrapper" data-adjustable-id="wali_kelas_ttd" style="position: absolute; left: -40px; top: -20px; z-index: 1;">
                                <img src="{{ $ttdURL }}?v={{ time() }}"
                                    style="height: 110px; width: auto; display: block;">
                                <div class="resize-handle"></div>
                            </div>
                        @endif

                        @if($hasNewStempel)
                            <div class="adjustable-wrapper" data-adjustable-id="wali_kelas_stempel" style="position: absolute; left: -120px; top: -50px; z-index: 2;">
                                <img src="{{ $stempelURL }}?v={{ time() }}"
                                    style="width: 190px; height: auto; display: block; opacity: 0.85;">
                                <div class="resize-handle"></div>
                            </div>
                        @else
                            <div class="adjustable-wrapper" data-adjustable-id="wali_kelas_stempel_legacy" style="position: absolute; left: -80px; top: 10px; z-index: 2;">
                                <img src="{{ $stempelURL }}"
                                    style="width: 180px; height: auto; display: block;">
                                <div class="resize-handle"></div>
                            </div>
                        @endif
                    </div>

                    <div style="margin-top: 0;">
                        <p><strong><u>{{ $kepalaMadrasah->nama_lengkap ?? '..........................................' }}</u></strong></p>
                        <p style="margin-top: -4px;">NIP. {{ $kepalaMadrasah->username ?? '..........................................' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('admin.cetak._adjustable_assets', ['templateKey' => 'cetak_wali_kelas'])
</body>

</html>
