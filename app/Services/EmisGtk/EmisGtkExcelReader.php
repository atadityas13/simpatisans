<?php

namespace App\Services\EmisGtk;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EmisGtkExcelReader
{
    /**
     * @return array{headers: array<int, string>, rows: array<int, array<string, string>>}
     */
    public function readSheet(string $path, ?string $sheetName = null): array
    {
        $spreadsheet = IOFactory::load($path);
        $sheet = $sheetName
            ? $spreadsheet->getSheetByName($sheetName)
            : $spreadsheet->getActiveSheet();

        if (! $sheet) {
            throw new \InvalidArgumentException("Sheet tidak ditemukan: {$sheetName}");
        }

        return $this->sheetToAssoc($sheet);
    }

    /**
     * @return array{headers: array<int, string>, rows: array<int, array<string, string>>}
     */
    public function sheetToAssoc(Worksheet $sheet): array
    {
        $raw = $sheet->toArray(null, true, true, false);
        if ($raw === []) {
            return ['headers' => [], 'rows' => []];
        }

        $headers = array_map(fn ($h) => trim((string) $h), $raw[0]);
        $rows = [];

        for ($i = 1, $n = count($raw); $i < $n; $i++) {
            $line = $raw[$i];
            $assoc = [];
            $hasValue = false;

            foreach ($headers as $idx => $header) {
                if ($header === '') {
                    continue;
                }
                $val = trim((string) ($line[$idx] ?? ''));
                if ($val !== '') {
                    $hasValue = true;
                }
                $assoc[$header] = $val;
            }

            if ($hasValue) {
                $rows[] = $assoc;
            }
        }

        return ['headers' => $headers, 'rows' => $rows];
    }
}
