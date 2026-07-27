<?php

namespace App\Services\EmisGtk;

use App\Models\Jadwal;
use App\Models\Kelas;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EmisGtkExportService
{
    private const DAY_SHEETS = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];

    public function __construct(
        private readonly EmisGtkReferenceService $referenceService,
    ) {}

    /**
     * @param  list<int>  $kelasIds
     * @return array{path: string, filename: string, report: array}
     */
    public function export(int $semesterId, array $kelasIds): array
    {
        $templatePath = $this->referenceService->referencesPath('template_jadwal.xlsx');
        $spreadsheet = IOFactory::load($templatePath);

        $kelasList = Kelas::whereIn('id', $kelasIds)
            ->orderByRaw("FIELD(tingkat, 'VII', 'VIII', 'IX')")
            ->orderBy('nama_kelas')
            ->get();

        $report = [
            'filled' => 0,
            'skipped_no_gtk' => [],
            'skipped_no_mapel' => [],
            'skipped_no_emis_kelas' => [],
            'skipped_no_row' => [],
            'skipped_template' => 0,
        ];

        foreach (self::DAY_SHEETS as $day) {
            $sheet = $spreadsheet->getSheetByName($day);
            if (! $sheet) {
                continue;
            }

            $index = $this->buildRowIndex($sheet);

            foreach ($kelasList as $kelas) {
                $this->ensureKelasEmisCodes($kelas);

                if (! $kelas->tingkat_emis || ! $kelas->rombel_emis) {
                    $report['skipped_no_emis_kelas'][] = $kelas->nama_kelas;

                    continue;
                }

                $this->ensureRombelBlock($sheet, $index, $kelas->tingkat_emis, $kelas->rombel_emis);
                $index = $this->buildRowIndex($sheet);

                $this->clearTeachableCells($sheet, $index, $kelas->tingkat_emis, $kelas->rombel_emis);

                $jadwals = Jadwal::where('semester_id', $semesterId)
                    ->where('hari', $day)
                    ->whereHas('bebanMengajar', fn ($q) => $q->where('kelas_id', $kelas->id))
                    ->with(['bebanMengajar.guru', 'bebanMengajar.mapel'])
                    ->get();

                foreach ($jadwals as $jadwal) {
                    $beban = $jadwal->bebanMengajar;
                    if (! $beban) {
                        continue;
                    }

                    $emisJam = EmisGtkJamMapper::emisJamFor($day, (int) $jadwal->jam_ke);
                    if (! $emisJam) {
                        continue;
                    }

                    $key = $this->rowKey($kelas->tingkat_emis, $kelas->rombel_emis, $emisJam);
                    $row = $index[$key] ?? null;

                    if (! $row) {
                        $report['skipped_no_row'][] = "{$kelas->nama_kelas} {$day} jam EMIS {$emisJam}";

                        continue;
                    }

                    $mapelCell = 'E'.$row;
                    $existingMapel = (string) $sheet->getCell($mapelCell)->getValue();
                    if (EmisGtkJamMapper::isTemplateMarker($existingMapel)) {
                        $report['skipped_template']++;

                        continue;
                    }

                    $guru = $beban->guru;
                    $mapel = $beban->mapel;
                    $idGtk = $guru?->id_gtk;
                    $idMapel = $mapel?->emisIdForTingkat($kelas->tingkat_emis);

                    if (! $idGtk) {
                        $report['skipped_no_gtk'][] = ($guru?->nama_guru ?? 'Guru')." ({$kelas->nama_kelas}, {$day} jam {$jadwal->jam_ke})";

                        continue;
                    }

                    if (! $idMapel) {
                        $report['skipped_no_mapel'][] = ($mapel?->nama_mapel ?? 'Mapel')." tingkat {$kelas->tingkat_emis} ({$kelas->nama_kelas})";

                        continue;
                    }

                    $sheet->setCellValueExplicit('D'.$row, (string) $idGtk, DataType::TYPE_STRING);
                    $sheet->setCellValueExplicit($mapelCell, (string) $idMapel, DataType::TYPE_STRING);
                    $report['filled']++;
                }
            }
        }

        $filename = 'jadwal_emis_gtk_'.date('Y-m-d_His').'.xlsx';
        $path = storage_path('app/temp/'.$filename);

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($path);

        return [
            'path' => $path,
            'filename' => $filename,
            'report' => $report,
        ];
    }

    private function ensureKelasEmisCodes(Kelas $kelas): void
    {
        if ($kelas->tingkat_emis && $kelas->rombel_emis) {
            return;
        }

        $codes = $this->referenceService->deriveKelasEmisCodes($kelas->nama_kelas, $kelas->tingkat);
        if ($codes) {
            $kelas->update($codes);
            $kelas->refresh();
        }
    }

    /**
     * @return array<string, int>
     */
    private function buildRowIndex(Worksheet $sheet): array
    {
        $index = [];
        $maxRow = $sheet->getHighestRow();

        for ($row = 2; $row <= $maxRow; $row++) {
            $kelas = trim((string) $sheet->getCell('A'.$row)->getValue());
            $rombel = trim((string) $sheet->getCell('B'.$row)->getValue());
            $jam = trim((string) $sheet->getCell('C'.$row)->getValue());

            if ($kelas === '' || $rombel === '' || $jam === '') {
                continue;
            }

            $index[$this->rowKey($kelas, $rombel, (int) $jam)] = $row;
        }

        return $index;
    }

    private function rowKey(string $tingkat, string $rombel, int $jam): string
    {
        return $tingkat.'|'.$rombel.'|'.$jam;
    }

    /**
     * @param  array<string, int>  $index
     */
    private function ensureRombelBlock(
        Worksheet $sheet,
        array $index,
        string $tingkat,
        string $rombel,
        string $referenceTingkat = '8',
    ): void {
        if (isset($index[$this->rowKey($tingkat, $rombel, 1)])) {
            return;
        }

        $refRows = [];
        for ($jam = 1; $jam <= 13; $jam++) {
            $key = $this->rowKey($referenceTingkat, $rombel, $jam);
            if (isset($index[$key])) {
                $refRows[$jam] = $index[$key];
            }
        }

        if (count($refRows) < 13) {
            return;
        }

        foreach (range(1, 13) as $jam) {
            $refRow = $refRows[$jam];
            $newRow = $sheet->getHighestRow() + 1;

            $sheet->setCellValueExplicit('A'.$newRow, (string) $tingkat, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('B'.$newRow, (string) $rombel, DataType::TYPE_STRING);
            $sheet->setCellValue('C'.$newRow, $jam);

            $templateMapel = (string) $sheet->getCell('E'.$refRow)->getValue();
            if (EmisGtkJamMapper::isTemplateMarker($templateMapel)) {
                $sheet->setCellValue('E'.$newRow, $templateMapel);
            }
        }
    }

    /**
     * @param  array<string, int>  $index
     */
    private function clearTeachableCells(
        Worksheet $sheet,
        array $index,
        string $tingkat,
        string $rombel,
    ): void {
        for ($jam = 1; $jam <= 13; $jam++) {
            $row = $index[$this->rowKey($tingkat, $rombel, $jam)] ?? null;
            if (! $row) {
                continue;
            }

            $mapelVal = (string) $sheet->getCell('E'.$row)->getValue();
            if (EmisGtkJamMapper::isTemplateMarker($mapelVal)) {
                continue;
            }

            $sheet->setCellValue('D'.$row, '');
            $sheet->setCellValue('E'.$row, '');
        }
    }
}
