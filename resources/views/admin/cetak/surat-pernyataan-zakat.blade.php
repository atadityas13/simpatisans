<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Pernyataan Zakat Profesi</title>
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
                margin: 2.5cm 2.5cm 2cm 2.5cm;
            }

            body {
                -webkit-print-color-adjust: exact;
                background-color: #fff !important;
            }

            .surat-page {
                page-break-after: always;
                box-shadow: none !important;
                border: none !important;
                margin: 0 !important;
                padding: 0 !important;
                width: auto !important;
                min-height: auto !important;
            }

            .surat-page:last-child {
                page-break-after: auto;
            }
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            line-height: 1.5;
            color: #000;
            margin: 0;
            padding: 0;
            background-color: #fff;
            font-size: 12pt;
        }

        .surat-page {
            position: relative;
        }

        .title {
            text-align: center;
            font-size: 16pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 0 0 18px;
            text-decoration: underline;
        }

        .bismillah {
            font-style: italic;
            margin: 0 0 14px;
        }

        .intro {
            margin: 0 0 12px;
        }

        .identitas {
            width: 100%;
            border-collapse: collapse;
            margin: 0 0 18px;
        }

        .identitas td {
            vertical-align: top;
            padding: 3px 0;
            font-size: 12pt;
        }

        .identitas .label {
            width: 130px;
            white-space: nowrap;
        }

        .identitas .colon {
            width: 16px;
        }

        .identitas .value {
            border-bottom: 1px dotted #000;
            min-height: 1.2em;
            padding-left: 4px;
        }

        .body-text {
            text-align: justify;
            margin: 0 0 14px;
            text-indent: 0;
        }

        .signature-wrap {
            margin-top: 36px;
            display: flex;
            justify-content: flex-end;
        }

        .signature-box {
            width: 260px;
            text-align: center;
        }

        .signature-box p {
            margin: 0;
            line-height: 1.45;
        }

        .meterai {
            margin: 10px 0 4px;
            font-size: 10pt;
            font-style: italic;
        }

        .sign-space {
            height: 72px;
        }

        .sign-name {
            font-weight: bold;
            text-decoration: underline;
            margin-top: 4px;
        }

        @media screen {
            body {
                background-color: #1a1a1b;
                display: flex;
                flex-direction: column;
                align-items: center;
                padding: 40px 0 60px;
                min-height: 100vh;
            }

            .surat-page {
                width: 210mm;
                min-height: 297mm;
                background: white;
                box-shadow: 0 10px 40px rgba(0, 0, 0, 0.8);
                border: 1px solid #444;
                padding: 2.5cm;
                box-sizing: border-box;
                margin-bottom: 28px;
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
        <a href="javascript:window.print()" class="no-print-btn">Cetak Surat Pernyataan</a>
    </div>

    @forelse($gurus as $guru)
        @php
            $nama = $guru->nama_lengkap ?: '…………………………………………………………';
            $nip = filled($guru->username) ? $guru->username : '…………………………………………………………';
            $golongan = filled($guru->golongan) ? $guru->golongan : '…………………………………………………………';
            $jabatan = filled($guru->jabatan) ? $guru->jabatan : '…………………………………………………………';
            $unitKerja = 'MTsN 11 Majalengka';
        @endphp

        <div class="surat-page">
            <h1 class="title">Surat Pernyataan</h1>

            <p class="bismillah">Bismillaahirrahmaanirrahiimm</p>

            <p class="intro">Saya yang bertandatangan di bawah ini:</p>

            <table class="identitas">
                <tr>
                    <td class="label">Nama</td>
                    <td class="colon">:</td>
                    <td class="value">{{ $nama }}</td>
                </tr>
                <tr>
                    <td class="label">NIP</td>
                    <td class="colon">:</td>
                    <td class="value">{{ $nip }}</td>
                </tr>
                <tr>
                    <td class="label">Pangkat/ Gol</td>
                    <td class="colon">:</td>
                    <td class="value">{{ $golongan }}</td>
                </tr>
                <tr>
                    <td class="label">Jabatan</td>
                    <td class="colon">:</td>
                    <td class="value">{{ $jabatan }}</td>
                </tr>
                <tr>
                    <td class="label">Unit Kerja</td>
                    <td class="colon">:</td>
                    <td class="value">{{ $unitKerja }}</td>
                </tr>
            </table>

            <p class="body-text">
                Dengan ini saya bersedia untuk membayar zakat profesi sebesar 2,5% dari penghasilan bruto yang
                dipotong langsung dari gaji bulanan serta memberikan kuasa kepada bendahara untuk pemotongan gaji
                tersebut yang selanjutnya disetorkan kepada UPZ Kanwil Kemenag Provinsi Jawa Barat.
            </p>

            <p class="body-text">
                Demikian pernyataan ini saya buat dengan tanpa ada paksaan dari pihak manapun, semoga Allah SWT
                mencatat niat ini sebagai pahala di <em>Yaumul Hisab</em>, aamiin
            </p>

            <div class="signature-wrap">
                <div class="signature-box">
                    <p>Majalengka, {{ $tanggalSurat }}</p>
                    <p>Yang membuat pernyataan,</p>
                    <p class="meterai">Meterai Rp. 10.000</p>
                    <div class="sign-space"></div>
                    <p class="sign-name">{{ $nama }}</p>
                </div>
            </div>
        </div>
    @empty
        <div class="surat-page">
            <p style="text-align: center; margin-top: 4cm;">Belum ada data guru di sistem.</p>
        </div>
    @endforelse
</body>

</html>
