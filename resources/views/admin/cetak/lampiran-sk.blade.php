<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    @if(!empty($guruMobileView))
    <meta name="viewport" content="width=device-width, initial-scale=0.42, minimum-scale=0.15, maximum-scale=5.0, user-scalable=yes">
    @else
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @endif
    <title>Lampiran SK Pembagian Tugas - {{ $activeSemester->nama_tahun }}</title>
    <style>
        @media screen {
            body {
                background-color: #f0f2f5;
                padding: 40px 0;
                margin: 0;
                display: flex;
                flex-direction: column;
                align-items: center;
            }
            .main-paper {
                margin: 0 auto 30px auto;
                box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            }
        }

        @media print {
            .no-print { display: none !important; }
            @page { size: A4 landscape; margin: 0.8cm; }
            body { -webkit-print-color-adjust: exact; background-color: #fff !important; margin: 0; padding: 0; }
            .main-paper { 
                box-shadow: none !important; 
                border: none !important; 
                padding: 0 !important; 
                width: 100% !important;
                margin: 0 !important;
                page-break-after: always;
            }
            .main-paper:last-child {
                page-break-after: auto;
            }
        }

        * { box-sizing: border-box; }
        body {
            font-family: 'Arial Narrow', Arial, sans-serif;
            color: #000;
        }

        .main-paper {
            width: 297mm;
            background: white;
            padding: 0.8cm;
            position: relative;
            min-height: 210mm;
            display: flex;
            flex-direction: column;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            position: relative;
        }

        .header h1 { font-size: 13pt; margin: 0; text-transform: uppercase; font-weight: bold; }
        .header p { font-size: 12pt; margin: 0; font-weight: bold; text-transform: uppercase; }

        .logo-kemenag {
            position: absolute;
            left: 0;
            top: 0;
            height: 90px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            border: 2pt solid #000;
        }

        th, td {
            border: 1pt solid #000;
            padding: 3px 4px;
            vertical-align: middle;
        }

        .report-table tbody {
            border-bottom: 1.5pt solid #000;
        }

        th {
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
            font-size: 7.5pt;
        }
        
        thead.bg-gray th {
            background-color: #f2f2f2;
        }

        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .bg-gray { background-color: #f2f2f2; }

        .guru-name { font-size: 9pt; font-weight: bold; }
        .guru-info { font-size: 7.5pt; color: #000; line-height: 1.2; }

        .class-col { font-size: 7.5pt; }
        .jtm-col { font-weight: bold; }
        .total-col { font-weight: bold; background: #fdfdfd; }

        .paraf-container {
            margin-top: auto;
            display: flex;
            justify-content: flex-end;
            padding-top: 10px;
        }

        .paraf-box {
            width: 80px;
            height: 50px;
            border: 1pt solid #000;
            text-align: center;
            font-size: 7pt;
            padding-top: 2px;
        }

        /* Signature area */
        .signature-section {
            margin-top: 30px;
            display: flex;
            justify-content: flex-end;
        }
        .signature-box {
            width: 300px;
            text-align: left;
            position: relative;
            font-size: 10pt;
        }

        .bg-arsir {
            background: repeating-linear-gradient(
                45deg,
                #f2f2f2,
                #f2f2f2 5px,
                #e8e8e8 5px,
                #e8e8e8 10px
            ) !important;
        }

        @if(!empty($guruMobileView))
        .guru-mobile-view {
            background: #e8ecf0;
            overflow: auto;
            -webkit-overflow-scrolling: touch;
        }
        .guru-mobile-view .mobile-doc-scroll {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
            padding: 8px 8px 72px;
        }
        .guru-mobile-view .main-paper {
            width: 297mm;
            min-height: auto;
            margin: 0 auto;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12);
        }
        .guru-mobile-view .controls-panel {
            display: none !important;
        }
        .guru-mobile-view .print-fab-mobile {
            position: fixed;
            bottom: 20px;
            right: 16px;
            z-index: 10000;
            background: #047857;
            color: #fff;
            border: none;
            border-radius: 999px;
            padding: 12px 20px;
            font-size: 13px;
            font-weight: 700;
            box-shadow: 0 4px 14px rgba(4, 120, 87, 0.45);
        }
        .guru-mobile-view .adjustable-wrapper {
            pointer-events: none !important;
            border: none !important;
        }
        .guru-mobile-view .resize-handle {
            display: none !important;
        }
        @endif
    </style>
</head>
<body class="{{ !empty($guruMobileView) ? 'guru-mobile-view' : '' }}">
    <div class="no-print controls-panel">
        <a href="javascript:window.print()" class="no-print-btn">CETAK LAMPIRAN SK</a>
    </div>

    @if(!empty($guruMobileView))
    <button type="button" class="print-fab-mobile no-print" onclick="window.print()">Cetak Pembagian Tugas</button>
    <div class="mobile-doc-scroll">
    @endif

    @if(empty($guruMobileView))
    @include('admin.cetak._adjustable_assets', ['templateKey' => 'lampiran_sk'])
    @endif

    @php $mainIdx = 0; @endphp
    @foreach($gurus->chunk(6) as $pageIndex => $guruChunk)
    <div class="main-paper">
        @if($pageIndex === 0)
        <div class="header">
            <img src="{{ asset('img/logo-kemenag.png') }}" class="logo-kemenag">
            <h1>LAMPIRAN KEPUTUSAN KEPALA MADRASAH TSANAWIYAH NEGERI 11 MAJALENGKA</h1>
            <h1>KEMENTERIAN AGAMA KABUPATEN MAJALENGKA</h1>
            <p style="margin: 5px 0;">NOMOR : &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; TAHUN {{ date('Y') }}</p>
            <p>TENTANG</p>
            <p>PEMBAGIAN TUGAS GURU SEMESTER {{ $activeSemester->tipe == 'Ganjil' ? 'I (GANJIL)' : 'II (GENAP)' }} TAHUN PELAJARAN {{ $activeSemester->nama_tahun }}</p>
        </div>
        @else
        <div style="height: 10px;"></div>
        @endif

        <table class="report-table">
            <colgroup>
                <col style="width: 30px;"> {{-- No --}}
                <col style="width: 170px;"> {{-- Pegawai --}}
                <col style="width: 90px;"> {{-- Status --}}
                <col style="width: 110px;"> {{-- Mengajar --}}
                @foreach($allKelas as $k)
                    <col style="width: 18px;"> {{-- Kelas Grid --}}
                @endforeach
                <col style="width: 35px;"> {{-- JTM --}}
                <col style="width: 110px;"> {{-- Tugas Tambahan --}}
                <col style="width: 35px;"> {{-- Ekuivalen --}}
                <col style="width: 40px;"> {{-- Total Beban --}}
            </colgroup>
            <thead class="bg-gray">
                <tr>
                    <th rowspan="3">No</th>
                    <th rowspan="3">Pegawai</th>
                    <th rowspan="3">Status</th>
                    <th rowspan="3">Mengajar</th>
                    <th colspan="{{ $allKelas->count() }}">Kelas</th>
                    <th rowspan="3">JTM</th>
                    <th rowspan="3">Tugas Tambahan</th>
                    <th rowspan="3">Ekuivalen</th>
                    <th rowspan="3">Total Beban</th>
                </tr>
                <tr>
                    @foreach($kelasList as $tingkat => $kelas)
                        <th colspan="{{ $kelas->count() }}">{{ $tingkat }}</th>
                    @endforeach
                </tr>
                <tr>
                    @foreach($allKelas as $k)
                        <th class="class-col">{{ str_replace(['Kelas ', 'VII.', 'VIII.', 'IX.'], '', $k->nama_kelas) }}</th>
                    @endforeach
                </tr>
            </thead>
            @foreach($guruChunk as $index => $guru)
                @php
                    $displayIndex = ++$mainIdx;
                    $groupedBeban = $guru->bebanMengajars->groupBy('mapel_id')
                        ->sortByDesc(fn($group) => $guru->isLinear($group->first()->mapel));
                    $tugasTambahans = $guru->tugasTambahans;
                    $teachingRows = $groupedBeban->count() + ($guru->is_bk ? 1 : 0);
                    $taskRows = $tugasTambahans->count();
                    $maxRows = max($teachingRows, $taskRows, 1);
                    $hasTotalRow = $teachingRows > 1 || $taskRows > 1;
                    $displayRows = $maxRows + ($hasTotalRow ? 1 : 0);
                    $totalJtm = $guru->bebanMengajars->sum('jtm') + ($guru->is_bk ? 24 : 0);
                    $totalEkuivalen = $tugasTambahans->where('pivot.is_ekuivalen', true)->sum('jtm_ekuivalen');
                    $totalBeban = $totalJtm + $totalEkuivalen;
                @endphp
                <tbody style="page-break-inside: avoid;">
                    @for($i = 0; $i < $maxRows; $i++)
                        <tr>
                            @if($i === 0)
                                <td rowspan="{{ $displayRows }}" class="text-center">{{ $displayIndex }}</td>
                                <td rowspan="{{ $displayRows }}">
                                    <div class="guru-name text-uppercase">{{ $guru->nama_lengkap }}</div>
                                    <div class="guru-info">NIP. {{ $guru->username }}</div>
                                    <div class="guru-info">{{ $guru->jabatan }}</div>
                                </td>
                                <td rowspan="{{ $displayRows }}" class="text-left guru-info">
                                    {{ $guru->status_pegawai }} - {{ $guru->golongan }}<br>
                                    {{ $guru->status_sertifikasi ? 'Tersertifikasi' : 'Belum Sertifikasi' }}<br>
                                    Guru {{ $guru->mapelSertifikasi->nama_mapel ?? '-' }}
                                </td>
                            @endif

                            @php 
                                $bkActive = $guru->is_bk && $i === 0;
                                $bebanIdx = $guru->is_bk ? $i - 1 : $i;
                                $currentBebanMapel = $groupedBeban->values()->get($bebanIdx); 
                                
                                $mapelName = $bkActive ? 'Bimbingan dan Konseling' : ($currentBebanMapel ? $currentBebanMapel->first()->mapel->nama_mapel : '');
                                $mapelJtmTotal = $bkActive ? 24 : ($currentBebanMapel ? $currentBebanMapel->sum('jtm') : 0);
                                $isFirstEmptyMapel = !$bkActive && !$currentBebanMapel && $i === $teachingRows;
                                $mergeVerticalCount = $displayRows - $i;
                            @endphp

                            @if($bkActive)
                                <td colspan="{{ 1 + $allKelas->count() }}" class="text-center font-bold guru-info">
                                    Bimbingan dan Konseling
                                </td>
                            @elseif($currentBebanMapel)
                                <td class="guru-info" style="{{ str_contains($mapelName, 'Kokurikuler') ? 'background:#f5f5f5;' : '' }}">
                                    {{ $mapelName }}
                                </td>
                                @foreach($allKelas as $k)
                                    <td class="text-center font-bold guru-info">
                                        @php $bebanInClass = $currentBebanMapel->where('kelas_id', $k->id)->first(); @endphp
                                        @if($bebanInClass)
                                            {{ (!$guru->isLinear($bebanInClass->mapel) ? '*' : '') . $bebanInClass->jtm }}
                                        @endif
                                    </td>
                                @endforeach
                            @elseif($isFirstEmptyMapel)
                                <td rowspan="{{ $mergeVerticalCount }}" colspan="{{ 1 + $allKelas->count() }}" class="bg-gray"></td>
                            @endif

                            @if($bkActive || $currentBebanMapel)
                                <td class="text-center font-bold guru-info" style="font-size: 10pt;">
                                    @if($mapelJtmTotal > 0)
                                        @php 
                                            $isLinear = true;
                                            if ($currentBebanMapel) {
                                                $isLinear = $guru->isLinear($currentBebanMapel->first()->mapel);
                                            }
                                        @endphp
                                        {{ (!$isLinear ? '*' : '') . $mapelJtmTotal }}
                                    @endif
                                </td>
                            @elseif($isFirstEmptyMapel)
                                <td rowspan="{{ $mergeVerticalCount }}" class="text-center font-bold bg-gray" style="vertical-align: middle; font-size: 10pt;">
                                    {{ $totalJtm }}
                                </td>
                            @endif

                            @php $currentTugas = $tugasTambahans->get($i); @endphp
                            <td class="guru-info">
                                @if($currentTugas)
                                    @if(str_contains($currentTugas->nama_tugas, 'Wali Kelas'))
                                        Wali Kelas - {{ str_replace('Kelas ', '', $currentTugas->pivot->detail) }}
                                    @else
                                        {{ $currentTugas->nama_tugas }} {{ $currentTugas->pivot->detail }}
                                    @endif
                                @endif
                            </td>
                            <td class="text-center font-bold {{ $currentTugas ? '' : 'bg-gray' }}" style="font-size: 10pt;">
                                @if($currentTugas)
                                    @if($currentTugas->pivot->is_ekuivalen)
                                        {{ $currentTugas->jtm_ekuivalen }}
                                    @else
                                        <span style="font-size: 7pt; font-weight: normal;">0 (NE)</span>
                                    @endif
                                @endif
                            </td>

                            @if($i === 0)
                                <td rowspan="{{ $displayRows }}" class="text-center font-bold total-col" style="font-size: 10pt;">
                                    {{ $totalBeban }}
                                </td>
                            @endif
                        </tr>
                    @endfor
                    
                    @if($hasTotalRow)
                    <tr class="bg-gray font-bold" style="font-size:10pt;">
                        @if($teachingRows === $maxRows)
                            <td colspan="{{ 1 + $allKelas->count() }}"></td>
                            <td class="text-center">{{ $totalJtm }}</td>
                        @endif
                        <td class="bg-gray"></td>
                        <td class="text-center border-total-ek">{{ $totalEkuivalen }}</td>
                    </tr>
                    @endif
                </tbody>
            @endforeach

            @if($loop->last)
            <tbody class="bg-gray">
                <tr class="bg-gray font-bold" style="font-size:8pt;">
                    <td colspan="4" class="text-right" style="padding: 8px;">TOTAL JAM TATAP MUKA</td>
                    @foreach($allKelas as $k)
                        <td class="text-center" style="font-size: 7.5pt;">
                            {{ $gurus->sum(fn($g) => $g->bebanMengajars->where('kelas_id', $k->id)->sum('jtm')) ?: '' }}
                        </td>
                    @endforeach
                    <td class="text-center" style="font-size: 10pt;">{{ $gurus->sum(fn($g) => $g->bebanMengajars->sum('jtm') + ($g->is_bk ? 24 : 0)) }}</td>
                    <td colspan="1" class="text-right" style="padding: 8px;">TOTAL EKUIVALEN</td>
                    <td class="text-center" style="font-size: 10pt;">{{ $gurus->sum(fn($g) => $g->tugasTambahans->where('pivot.is_ekuivalen', true)->sum('jtm_ekuivalen')) }}</td>
                    <td class="text-center" style="background: #e2e8f0; font-size: 10pt;">{{ $gurus->sum(fn($g) => $g->bebanMengajars->sum('jtm') + ($g->is_bk ? 24 : 0) + $g->tugasTambahans->where('pivot.is_ekuivalen', true)->sum('jtm_ekuivalen')) }}</td>
                </tr>
            </tbody>
            @endif
        </table>

        <div style="font-size: 7.5pt; margin-top: 5px;">
            * JTM non-linier
        </div>

        @if(!$loop->last)
        <div class="paraf-container">
            <div class="paraf-box">
                Paraf<br>
                <div style="height: 30px;"></div>
            </div>
        </div>
        @else
        <div class="signature-section">
            <div class="signature-box">
                @php
                    $tanggalCetak = $cetakTanggal ?? date('j F Y');
                @endphp
                
                <table style="border: none !important; margin-bottom: 5px; width: auto !important;">
                    <tr style="border: none !important;">
                        <td style="border: none !important; padding: 0; width: 100px;">Ditetapkan di</td>
                        <td style="border: none !important; padding: 0 5px;">:</td>
                        <td style="border: none !important; padding: 0;">Cingambul</td>
                    </tr>
                    <tr style="border: none !important;">
                        <td style="border: none !important; padding: 0;">Pada Tanggal</td>
                        <td style="border: none !important; padding: 0 5px;">:</td>
                        <td style="border: none !important; padding: 0;">{{ $tanggalCetak }}</td>
                    </tr>
                </table>

                <p style="margin-top: 5px;">{{ $cetakPejabatLabelSingkat ?? 'Kepala' }},</p>
                
                <div style="position: relative; height: 70px; margin-top: 5px;">
                    <div class="adjustable-wrapper" data-adjustable-id="sk_ttd_kepala" style="position: absolute; left: 0; top: 0; z-index: 1;">
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->exists('presets/ttd_kepala.png') ? asset('storage/presets/ttd_kepala.png') : '' }}" style="height: 60px; width: auto;">
                        <div class="resize-handle"></div>
                    </div>
                    <div class="adjustable-wrapper" data-adjustable-id="sk_stempel" style="position: absolute; left: -30px; top: -10px; z-index: 2;">
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->exists('presets/stempel.png') ? asset('storage/presets/stempel.png') : '' }}" style="height: 80px; width: auto; opacity: 0.85;">
                        <div class="resize-handle"></div>
                    </div>
                </div>

                <p class="font-bold" style="text-decoration: underline; margin-bottom: 0;">{{ $kepalaMadrasah->nama_guru ?? '.......................................' }}</p>
                <p style="margin-top: 0;">NIP. {{ $kepalaMadrasah->username ?? '-' }}</p>
            </div>
        </div>
        @endif
    </div>
    @endforeach

    @if(!empty($guruMobileView))
    </div>
    @endif
</body>
</html>
