<?php

namespace App\Services\EmisGtk;

use App\Models\Jadwal;
use App\Models\Kelas;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
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
    public function export(int $semesterId, int $versionId, array $kelasIds): array
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
            'skipped_tingkat_belum_di_template' => [],
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

                $tingkat = (string) $kelas->tingkat_emis;
                $rombel = $this->normalizeRombel((string) $kelas->rombel_emis);

                // Hanya isi blok yang sudah ada di template EMIS (jangan append tingkat baru).
                // Template resmi EMIS sudah memuat banyak tingkat dengan nama rombel sama (01, 02, …).
                if (! isset($index[$this->rowKey($tingkat, $rombel, 1)])) {
                    $report['skipped_tingkat_belum_di_template'][] = "{$kelas->nama_kelas} ({$tingkat}/{$rombel})";

                    continue;
                }

                $this->clearTeachableCells($sheet, $index, $tingkat, $rombel);

                $jadwals = Jadwal::where('semester_id', $semesterId)
                    ->where('version_id', $versionId)
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

                    $row = $index[$this->rowKey($tingkat, $rombel, $emisJam)] ?? null;
                    if (! $row) {
                        $report['skipped_no_row'][] = "{$kelas->nama_kelas} {$day} jam EMIS {$emisJam}";

                        continue;
                    }

                    $existingMapel = $this->cellString($sheet, 'E'.$row);
                    if (EmisGtkJamMapper::isTemplateMarker($existingMapel)) {
                        $report['skipped_template']++;

                        continue;
                    }

                    $guru = $beban->guru;
                    $mapel = $beban->mapel;
                    $idGtk = $guru?->id_gtk;
                    $idMapel = $mapel?->emisIdForTingkat($tingkat);

                    if (! $idGtk) {
                        $report['skipped_no_gtk'][] = ($guru?->nama_guru ?? 'Guru')." ({$kelas->nama_kelas}, {$day} jam {$jadwal->jam_ke})";

                        continue;
                    }

                    if (! $idMapel) {
                        $report['skipped_no_mapel'][] = ($mapel?->nama_mapel ?? 'Mapel')." tingkat {$tingkat} ({$kelas->nama_kelas})";

                        continue;
                    }

                    $sheet->setCellValueExplicit('D'.$row, (string) $idGtk, DataType::TYPE_STRING);
                    $sheet->setCellValueExplicit('E'.$row, (string) $idMapel, DataType::TYPE_STRING);
                    $report['filled']++;
                }
            }
        }

        $report['skipped_no_emis_kelas'] = array_values(array_unique($report['skipped_no_emis_kelas']));
        $report['skipped_tingkat_belum_di_template'] = array_values(array_unique($report['skipped_tingkat_belum_di_template']));

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
            $kelas = $this->cellString($sheet, 'A'.$row);
            $rombel = $this->normalizeRombel($this->cellString($sheet, 'B'.$row));
            $jam = (int) $this->cellString($sheet, 'C'.$row);

            if ($kelas === '' || $rombel === '' || $jam < 1) {
                continue;
            }

            $index[$this->rowKey($kelas, $rombel, $jam)] = $row;
        }

        return $index;
    }

    private function rowKey(string $tingkat, string $rombel, int $jam): string
    {
        return trim($tingkat).'|'.$this->normalizeRombel($rombel).'|'.$jam;
    }

    private function normalizeRombel(string $rombel): string
    {
        $rombel = trim($rombel);
        if ($rombel === '') {
            return '';
        }

        // Sudah format EMIS unik: 8-01, 9-06
        if (preg_match('/^\d+-\d+$/', $rombel)) {
            return $rombel;
        }

        if (ctype_digit($rombel)) {
            return str_pad((string) (int) $rombel, 2, '0', STR_PAD_LEFT);
        }

        return $rombel;
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

            $mapelVal = $this->cellString($sheet, 'E'.$row);
            if (EmisGtkJamMapper::isTemplateMarker($mapelVal)) {
                continue;
            }

            $sheet->setCellValueExplicit('D'.$row, '', DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('E'.$row, '', DataType::TYPE_STRING);
        }
    }

    private function cellString(Worksheet $sheet, string $coordinate): string
    {
        $value = $sheet->getCell($coordinate)->getValue();

        if ($value instanceof RichText) {
            return trim($value->getPlainText());
        }

        if ($value === null) {
            return '';
        }

        return trim((string) $value);
    }
}
