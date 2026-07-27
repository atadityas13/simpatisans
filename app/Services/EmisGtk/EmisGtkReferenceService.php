<?php

namespace App\Services\EmisGtk;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Mapel;

class EmisGtkReferenceService
{
    public function __construct(
        private readonly EmisGtkExcelReader $reader,
    ) {}

    public function referencesPath(string $filename): string
    {
        return database_path('emis_references/'.$filename);
    }

    /**
     * @return array{mapels: array, gurus: array, kelas: array, unmatched_ptk: array<string>}
     */
    public function importAll(?string $basePath = null): array
    {
        $basePath = $basePath ?? database_path('emis_references');

        return [
            'mapels' => $this->importMapelReferences($basePath.'/referensi_pelajaran.xlsx'),
            'gurus' => $this->importPtkReferences($basePath.'/referensi_ptk.xlsx'),
            'kelas' => $this->importKelasEmisCodes(),
        ];
    }

    /**
     * @return array{updated: int, missing: array<string>}
     */
    public function importMapelReferences(string $path): array
    {
        $data = $this->reader->readSheet($path);
        $lookup = [];

        foreach ($data['rows'] as $row) {
            $tingkat = trim((string) ($row['Tingkat'] ?? ''));
            $nama = trim((string) ($row['Nama Mata Pelajaran'] ?? ''));
            $id = trim((string) ($row['ID Mapel'] ?? ''));
            $kode = trim((string) ($row['Kode Kompetensi'] ?? ''));

            if ($tingkat === '' || $nama === '' || $id === '') {
                continue;
            }

            if ($kode !== '' && $kode !== '1011') {
                continue;
            }

            $lookup[EmisGtkMapelAliases::normalize($nama).'|'.$tingkat] = $id;
        }

        $updated = 0;
        $missing = [];

        foreach (Mapel::all() as $mapel) {
            $emisName = EmisGtkMapelAliases::localToEmis()[$mapel->nama_mapel] ?? $mapel->nama_mapel;
            $norm = EmisGtkMapelAliases::normalize($emisName);

            $id7 = $lookup[$norm.'|7'] ?? null;
            $id8 = $lookup[$norm.'|8'] ?? null;
            $id9 = $lookup[$norm.'|9'] ?? null;

            if (! $id7 || ! $id8 || ! $id9) {
                $missing[] = $mapel->nama_mapel;

                continue;
            }

            $mapel->update([
                'id_mapel_emis_7' => $id7,
                'id_mapel_emis_8' => $id8,
                'id_mapel_emis_9' => $id9,
            ]);
            $updated++;
        }

        return ['updated' => $updated, 'missing' => $missing];
    }

    /**
     * @return array{matched: int, unmatched: array<string>, ambiguous: array<string>}
     */
    public function importPtkReferences(string $path): array
    {
        $data = $this->reader->readSheet($path);
        $matched = 0;
        $unmatched = [];
        $ambiguous = [];

        foreach ($data['rows'] as $row) {
            $idPtk = trim((string) ($row['ID PTK'] ?? ''));
            $nama = trim((string) ($row['Nama'] ?? ''));
            $nuptk = trim((string) ($row['NUPTK'] ?? ''));
            $nik = trim((string) ($row['NIK'] ?? ''));

            if ($idPtk === '' || $nama === '') {
                continue;
            }

            $guru = $this->findGuruForPtk($nuptk, $nik, $nama);

            if ($guru === null) {
                $unmatched[] = $nama;

                continue;
            }

            if ($guru === false) {
                $ambiguous[] = $nama;

                continue;
            }

            $guru->update(['id_gtk' => $idPtk]);
            $matched++;
        }

        return ['matched' => $matched, 'unmatched' => $unmatched, 'ambiguous' => $ambiguous];
    }

    /**
     * @return array{updated: int}
     */
    public function importKelasEmisCodes(): array
    {
        $updated = 0;

        foreach (Kelas::all() as $kelas) {
            $codes = $this->deriveKelasEmisCodes($kelas->nama_kelas, $kelas->tingkat);

            if (! $codes) {
                continue;
            }

            $kelas->update($codes);
            $updated++;
        }

        return ['updated' => $updated];
    }

    /**
     * @return Guru|false|null Guru = match, false = ambiguous, null = no match
     */
    private function findGuruForPtk(string $nuptk, string $nik, string $emisName): Guru|false|null
    {
        if ($nuptk !== '' && $nuptk !== '-') {
            $byNuptk = Guru::where('nuptk', $nuptk)->get();
            if ($byNuptk->count() === 1) {
                return $byNuptk->first();
            }
            if ($byNuptk->count() > 1) {
                return false;
            }
        }

        if ($nik !== '' && $nik !== '-') {
            $byNik = Guru::where('username', $nik)->get();
            if ($byNik->count() === 1) {
                return $byNik->first();
            }
            if ($byNik->count() > 1) {
                return false;
            }
        }

        return $this->matchGuruByName($emisName);
    }

    /**
     * @return Guru|false|null
     */
    private function matchGuruByName(string $emisName): Guru|false|null
    {
        $target = $this->normalizePersonName($emisName);
        $best = null;
        $bestScore = 0;
        $ties = 0;

        foreach (Guru::all() as $guru) {
            $score = $this->nameSimilarity($target, $this->normalizePersonName($guru->nama_guru));

            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $guru;
                $ties = 1;
            } elseif ($score === $bestScore && $score >= 85) {
                $ties++;
            }
        }

        if ($bestScore < 85 || $ties > 1) {
            return $bestScore >= 85 && $ties > 1 ? false : null;
        }

        return $best;
    }

    private function normalizePersonName(string $name): string
    {
        $name = mb_strtolower(trim($name));
        $name = preg_replace('/\b(s\.pd|s\.ag|m\.pd|s\.pi|s\.kom)\b\.?/i', '', $name) ?? $name;
        $name = preg_replace('/[^a-z\s]/', ' ', $name) ?? $name;

        return trim(preg_replace('/\s+/', ' ', $name) ?? $name);
    }

    private function nameSimilarity(string $a, string $b): int
    {
        if ($a === '' || $b === '') {
            return 0;
        }

        if ($a === $b) {
            return 100;
        }

        $percent = 0;
        similar_text($a, $b, $percent);

        return (int) round($percent);
    }

    /**
     * @return array{tingkat_emis: string, rombel_emis: string}|null
     */
    public function deriveKelasEmisCodes(string $namaKelas, string $tingkat): ?array
    {
        $tingkatEmis = match (strtoupper(trim($tingkat))) {
            'VII', '7' => '7',
            'VIII', '8' => '8',
            'IX', '9' => '9',
            default => null,
        };

        if (! $tingkatEmis) {
            return null;
        }

        if (preg_match('/\.(\d+)/', $namaKelas, $m)) {
            $rombelNum = str_pad((string) (int) $m[1], 2, '0', STR_PAD_LEFT);

            // EMIS-GTK lookup rombel by nama unik lintas tingkat → format 8-01, 9-02, …
            return [
                'tingkat_emis' => $tingkatEmis,
                'rombel_emis' => $tingkatEmis.'-'.$rombelNum,
            ];
        }

        return null;
    }
}
