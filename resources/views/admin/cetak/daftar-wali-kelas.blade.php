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

        .header h1 {
            font-size: 20px;
            margin: 0;
            text-transform: uppercase;
            font-weight: bold;
        }

        .header h2 {
            font-size: 18px;
            margin: 5px 0;
            font-weight: bold;
        }

        .header h3 {
            font-size: 16px;
            margin: 5px 0;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th {
            background-color: #dedede;
            border: 1px solid #000;
            padding: 10px 8px;
            font-size: 15px;
            font-weight: bold;
            text-align: center;
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
    </div>
</body>

</html>
