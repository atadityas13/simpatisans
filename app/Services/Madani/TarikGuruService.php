<?php

namespace App\Services\Madani;

use App\Models\Guru;
use App\Models\Mapel;
use App\Models\Rumpun;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class TarikGuruService
{
    public const SESSION_KEY = 'simpatisans_madani_guru_snapshot';

    /**
     * @return array<string, mixed>
     */
    public function fetchAndRemember(): array
    {
        $paket = $this->fetchRows();
        session([self::SESSION_KEY => $paket['rows']]);

        return $this->bandingkan($paket['rows'], $paket['peringatan']);
    }

    /**
     * @return array{ditarik: int}
     */
    public function tarikDariSesi(): array
    {
        $rows = session(self::SESSION_KEY);
        if (! is_array($rows)) {
            throw new RuntimeException('Bandingkan data Madani terlebih dahulu.');
        }

        return $this->tarik($rows);
    }

    /**
     * @return array{diperbarui: int, dihapus: int}
     */
    public function syncDariSesi(): array
    {
        $rows = session(self::SESSION_KEY);
        if (! is_array($rows)) {
            throw new RuntimeException('Bandingkan data Madani terlebih dahulu.');
        }

        return $this->sinkron($rows);
    }

    /**
     * @param  list<array<string, mixed>>  $remoteRows
     * @param  list<string>  $peringatan
     * @return array<string, mixed>
     */
    public function bandingkan(array $remoteRows, array $peringatan = []): array
    {
        $local = Guru::query()
            ->with(['mapelIjazah', 'rumpunIjazah', 'mapelSertifikasi'])
            ->orderBy('nama_guru')
            ->get();
        $mapelByNama = Mapel::query()->get()->keyBy(
            fn (Mapel $mapel): string => $this->kunciNama($mapel->nama_mapel)
        );
        $rumpunByNama = Rumpun::query()->get()->keyBy(
            fn (Rumpun $rumpun): string => $this->kunciNama($rumpun->nama_rumpun)
        );

        $remoteByNip = [];
        foreach ($remoteRows as $row) {
            $kunci = (string) ($row['nip_kunci'] ?? '');
            if ($kunci === '') {
                $peringatan[] = 'Guru '.(string) ($row['nama'] ?? '').' di Madani tidak punya NIP yang bisa dicocokkan, jadi dilewati.';

                continue;
            }

            $indeks = 'n:'.$kunci;
            if (isset($remoteByNip[$indeks])) {
                $peringatan[] = 'NIP '.(string) ($row['nip'] ?? $kunci).' muncul lebih dari sekali di Madani. Baris berikutnya diabaikan.';

                continue;
            }

            $remoteByNip[$indeks] = $row;
        }

        $sudahCocok = [];
        $tarik = [];
        $sinkron = [];
        $lewati = [];
        $sama = 0;
        $kodeTerpakai = [];
        $dukTerpakai = [];
        foreach ($local as $guru) {
            $kode = strtoupper(trim((string) $guru->kode_guru));
            if ($kode !== '') {
                $kodeTerpakai[$kode] = true;
            }
            if ($guru->duk !== null) {
                $dukTerpakai[(int) $guru->duk] = true;
            }
        }

        foreach ($remoteByNip as $row) {
            $kunci = (string) $row['nip_kunci'];
            $cocok = $local->filter(
                fn (Guru $guru): bool => $this->kunci((string) $guru->username) === $kunci
            )->values();

            if ($cocok->isEmpty()) {
                if (($row['status'] ?? 'aktif') !== 'aktif') {
                    $lewati[] = $row['nama'].' ('.$row['nip'].') nonaktif di Madani, tidak ditarik.';

                    continue;
                }

                $kode = $this->pilihKode((string) ($row['kode_internal'] ?? ''), $kodeTerpakai);
                if ($kode === null) {
                    $lewati[] = $row['nama'].' tidak ditarik karena kode guru 2 huruf sudah habis.';

                    continue;
                }

                $kodeTerpakai[$kode] = true;
                $status = $this->statusPegawai($row['status_pegawai'] ?? null) ?? 'NON_ASN';
                if ($this->statusPegawai($row['status_pegawai'] ?? null) === null && $this->berisi($row['status_pegawai'] ?? null)) {
                    $peringatan[] = 'Status pegawai '.$row['nama'].' tidak dikenali, dipakai NON_ASN.';
                }

                $duk = $this->dukAngka($row['duk'] ?? null);
                if ($duk !== null && isset($dukTerpakai[$duk])) {
                    $peringatan[] = 'DUK '.$duk.' untuk '.$row['nama'].' sudah dipakai, jadi tidak diisi.';
                    $duk = null;
                }
                if ($duk !== null) {
                    $dukTerpakai[$duk] = true;
                }

                $tarik[] = [
                    'nama' => $row['nama'],
                    'nip' => $row['nip'],
                    'kode' => $kode,
                    'duk' => $duk,
                    'status_pegawai' => $status,
                    'payload' => $this->payloadBaru($row, $kode, $status, $duk, $mapelByNama, $rumpunByNama, $peringatan),
                ];

                continue;
            }

            foreach ($cocok as $guru) {
                $sudahCocok[$guru->id] = true;
                if (($row['status'] ?? 'aktif') !== 'aktif') {
                    $peringatan[] = $guru->nama_lengkap.' nonaktif di Madani. Datanya tetap bisa disinkronkan, tidak dihapus dari Simpatisans.';
                }

                $perubahan = $this->perubahan($guru, $row, $mapelByNama, $rumpunByNama, $kodeTerpakai, $dukTerpakai, $peringatan);
                if ($perubahan === []) {
                    $sama++;

                    continue;
                }

                foreach ($perubahan as $ubah) {
                    if ($ubah['kolom'] === 'duk') {
                        $dukLama = $guru->duk === null ? null : (int) $guru->duk;
                        if ($dukLama !== null) {
                            unset($dukTerpakai[$dukLama]);
                        }
                        $dukTerpakai[(int) $ubah['nilai']] = true;
                    }
                }

                $sinkron[] = [
                    'guru' => $guru,
                    'perubahan' => $perubahan,
                ];
            }
        }

        $hanyaLokal = [];
        foreach ($local as $guru) {
            if (! isset($sudahCocok[$guru->id])) {
                $hanyaLokal[] = $guru;
            }
        }

        return [
            'tarik' => $tarik,
            'sinkron' => $sinkron,
            'hanya_lokal' => $hanyaLokal,
            'lewati' => $lewati,
            'peringatan' => $peringatan,
            'sama' => $sama,
            'total_madani' => count($remoteByNip),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $remoteRows
     * @return array{ditarik: int}
     */
    public function tarik(array $remoteRows): array
    {
        $hasil = $this->bandingkan($remoteRows);

        return DB::transaction(function () use ($hasil): array {
            $ditarik = 0;
            foreach ($hasil['tarik'] as $item) {
                Guru::query()->create($item['payload']);
                $ditarik++;
            }

            return ['ditarik' => $ditarik];
        });
    }

    /**
     * @param  list<array<string, mixed>>  $remoteRows
     * @return array{diperbarui: int, dihapus: int}
     */
    public function sinkron(array $remoteRows): array
    {
        $hasil = $this->bandingkan($remoteRows);

        return DB::transaction(function () use ($hasil): array {
            $diperbarui = 0;
            foreach ($hasil['sinkron'] as $item) {
                $payload = [];
                foreach ($item['perubahan'] as $ubah) {
                    $payload[$ubah['kolom']] = $ubah['nilai'];
                }

                if ($payload !== []) {
                    $item['guru']->update($payload);
                    $diperbarui++;
                }
            }

            $dihapus = 0;
            foreach ($hasil['hanya_lokal'] as $guru) {
                $this->hapusAkunGuru($guru);
                $guru->delete();
                $dihapus++;
            }

            return [
                'diperbarui' => $diperbarui,
                'dihapus' => $dihapus,
            ];
        });
    }

    private function hapusAkunGuru(Guru $guru): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        $user = User::query()
            ->where('username', $guru->username)
            ->where('role', 'guru')
            ->first();

        if ($user === null) {
            return;
        }

        if (Schema::hasTable('personal_access_tokens')) {
            DB::table('personal_access_tokens')
                ->where('tokenable_type', User::class)
                ->where('tokenable_id', $user->id)
                ->delete();
        }

        $user->delete();
    }

    /**
     * @return array{rows: list<array<string, mixed>>, peringatan: list<string>}
     */
    private function fetchRows(): array
    {
        $baseUrl = rtrim((string) config('services.madani.url'), '/');
        $secret = (string) config('services.madani.introspect_secret');

        if ($baseUrl === '' || $secret === '') {
            throw new RuntimeException('Konfigurasi Madani belum lengkap (MADANI_API_URL / MADANI_INTROSPECT_SECRET).');
        }

        try {
            $response = Http::connectTimeout(5)
                ->timeout(30)
                ->acceptJson()
                ->withHeaders([
                    'X-Madani-Introspect-Secret' => $secret,
                ])
                ->get($baseUrl.'/v1/simpatisans/gtks')
                ->throw();
        } catch (ConnectionException $e) {
            throw new RuntimeException('Tidak dapat terhubung ke Madani. Periksa jaringan dan URL API.', 0, $e);
        } catch (RequestException $e) {
            throw new RuntimeException('Madani menolak permintaan data guru. Periksa MADANI_INTROSPECT_SECRET.', 0, $e);
        }

        $data = $response->json('data');
        if (! is_array($data)) {
            throw new RuntimeException('Respons data guru Madani tidak valid.');
        }

        $rows = [];
        $peringatan = [];
        foreach ($data as $row) {
            if (! is_array($row)) {
                $peringatan[] = 'Ada baris guru Madani yang bukan data, jadi dilewati.';

                continue;
            }

            $normalized = $this->normalisasi($row);
            if ($normalized === null) {
                $peringatan[] = 'Ada guru Madani tanpa nama dan NIP, jadi dilewati.';

                continue;
            }

            $rows[] = $normalized;
        }

        return [
            'rows' => $rows,
            'peringatan' => $peringatan,
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>|null
     */
    private function normalisasi(array $row): ?array
    {
        $nipMentah = trim((string) ($row['nip'] ?? ''));
        $kunci = $this->kunci($nipMentah);
        $nip = $kunci !== '' && strlen($kunci) <= 30 ? $kunci : ($nipMentah !== '' && strlen($nipMentah) <= 30 ? $nipMentah : null);
        $nama = $this->teks($row['nama'] ?? null, 255);

        if ($nip === null && $nama === null) {
            return null;
        }

        return [
            'nip' => $nip ?? '',
            'nip_kunci' => $nip === null ? '' : $kunci,
            'nama' => $nama ?? ('Guru '.($nip ?? '')),
            'gelar_depan' => $this->teks($row['gelar_depan'] ?? null, 100),
            'gelar_belakang' => $this->teks($row['gelar_belakang'] ?? null, 100),
            'nuptk' => $this->teks($row['nuptk'] ?? null, 20),
            'jenis_kelamin' => $this->jenisKelamin(isset($row['jenis_kelamin']) ? (string) $row['jenis_kelamin'] : null),
            'tempat_lahir' => $this->teks($row['tempat_lahir'] ?? null, 100),
            'tanggal_lahir' => $this->tanggal($row['tanggal_lahir'] ?? null),
            'agama' => $this->teks($row['agama'] ?? null, 50),
            'nomor_hp' => $this->teks($row['nomor_hp'] ?? null, 20),
            'email' => $this->email($row['email'] ?? null),
            'alamat' => $this->teks($row['alamat'] ?? null, 500),
            'jabatan' => $this->teks($row['jabatan'] ?? null, 255),
            'golongan' => $this->teks($row['golongan'] ?? null, 20),
            'status_pegawai' => $row['status_pegawai'] ?? null,
            'kode_internal' => $row['kode_internal'] ?? null,
            'duk' => $row['duk'] ?? null,
            'mapel_ijazah' => $this->teks($row['mapel_ijazah'] ?? null, 255),
            'rumpun_ijazah' => $this->teks($row['rumpun_ijazah'] ?? null, 255),
            'mapel_sertifikasi' => $this->teks($row['mapel_sertifikasi'] ?? null, 255),
            'status_sertifikasi' => (bool) ($row['status_sertifikasi'] ?? false),
            'is_bk' => (bool) ($row['is_bk'] ?? false),
            'status' => (string) ($row['status'] ?? 'aktif'),
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  Collection<string, Mapel>  $mapelByNama
     * @param  Collection<string, Rumpun>  $rumpunByNama
     * @param  list<string>  $peringatan
     * @return array<string, mixed>
     */
    private function payloadBaru(array $row, string $kode, string $status, ?int $duk, $mapelByNama, $rumpunByNama, array &$peringatan): array
    {
        [$mapelIjazah, $rumpunIjazah] = $this->ijazah($row, $mapelByNama, $rumpunByNama, $row['nama'], $peringatan, 'tidak diisi');
        $mapelSertifikasi = $row['status_sertifikasi']
            ? $this->cariMapel($row['mapel_sertifikasi'] ?? null, $mapelByNama)
            : null;

        if ($row['status_sertifikasi'] && $this->berisi($row['mapel_sertifikasi'] ?? null) && $mapelSertifikasi === null) {
            $peringatan[] = 'Mapel sertifikasi '.$row['nama'].' ('.$row['mapel_sertifikasi'].') tidak ada di Simpatisans, jadi tidak diisi.';
        }

        $payload = [
            'username' => $row['nip'],
            'kode_guru' => $kode,
            'nama_guru' => $row['nama'],
            'status_pegawai' => $status,
            'status_sertifikasi' => (bool) $row['status_sertifikasi'],
            'is_bk' => (bool) $row['is_bk'],
            'mapel_ijazah_id' => $mapelIjazah?->id,
            'rumpun_ijazah_id' => $rumpunIjazah?->id,
            'mapel_sertifikasi_id' => $mapelSertifikasi?->id,
        ];

        if ($duk !== null) {
            $payload['duk'] = $duk;
        }

        foreach ([
            'gelar_depan', 'gelar_belakang', 'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir',
            'agama', 'nomor_hp', 'email', 'alamat', 'nuptk', 'jabatan', 'golongan',
        ] as $kolom) {
            if ($this->berisi($row[$kolom] ?? null)) {
                $payload[$kolom] = $row[$kolom];
            }
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  Collection<string, Mapel>  $mapelByNama
     * @param  Collection<string, Rumpun>  $rumpunByNama
     * @param  array<string, true>  $kodeTerpakai
     * @param  array<int, true>  $dukTerpakai
     * @param  list<string>  $peringatan
     * @return list<array{kolom: string, label: string, dari: ?string, ke: string, nilai: mixed}>
     */
    private function perubahan(Guru $guru, array $row, $mapelByNama, $rumpunByNama, array &$kodeTerpakai, array &$dukTerpakai, array &$peringatan): array
    {
        $hasil = [];

        $teks = [
            'nama_guru' => ['nama', 'Nama'],
            'gelar_depan' => ['gelar_depan', 'Gelar depan'],
            'gelar_belakang' => ['gelar_belakang', 'Gelar belakang'],
            'jenis_kelamin' => ['jenis_kelamin', 'Jenis kelamin'],
            'tempat_lahir' => ['tempat_lahir', 'Tempat lahir'],
            'agama' => ['agama', 'Agama'],
            'nomor_hp' => ['nomor_hp', 'Nomor HP'],
            'email' => ['email', 'Email'],
            'alamat' => ['alamat', 'Alamat'],
            'nuptk' => ['nuptk', 'NUPTK'],
            'jabatan' => ['jabatan', 'Jabatan'],
            'golongan' => ['golongan', 'Golongan'],
        ];

        foreach ($teks as $kolom => [$sumber, $label]) {
            $baru = $row[$sumber] ?? null;
            if (! $this->berisi($baru)) {
                continue;
            }

            $lama = trim((string) $guru->{$kolom});
            if ($lama === (string) $baru) {
                continue;
            }

            $hasil[] = [
                'kolom' => $kolom,
                'label' => $label,
                'dari' => $lama === '' ? null : $lama,
                'ke' => (string) $baru,
                'nilai' => $baru,
            ];
        }

        if ($this->berisi($row['tanggal_lahir'] ?? null)) {
            $lama = $this->tanggalTersimpan($guru);
            if ($lama !== $row['tanggal_lahir']) {
                $hasil[] = [
                    'kolom' => 'tanggal_lahir',
                    'label' => 'Tanggal lahir',
                    'dari' => $lama,
                    'ke' => $row['tanggal_lahir'],
                    'nilai' => $row['tanggal_lahir'],
                ];
            }
        }

        $status = $this->statusPegawai($row['status_pegawai'] ?? null);
        if ($status !== null && $status !== (string) $guru->status_pegawai) {
            $hasil[] = [
                'kolom' => 'status_pegawai',
                'label' => 'Status pegawai',
                'dari' => $guru->status_pegawai,
                'ke' => $status,
                'nilai' => $status,
            ];
        }

        $kode = strtoupper(trim((string) ($row['kode_internal'] ?? '')));
        $kodeLama = strtoupper(trim((string) $guru->kode_guru));
        if (preg_match('/^[A-Z0-9]{2}$/', $kode) === 1 && $kode !== $kodeLama) {
            if (isset($kodeTerpakai[$kode])) {
                $peringatan[] = 'KG '.$kode.' untuk '.$guru->nama_lengkap.' sudah dipakai guru lain, jadi tidak diubah.';
            } else {
                unset($kodeTerpakai[$kodeLama]);
                $kodeTerpakai[$kode] = true;
                $hasil[] = [
                    'kolom' => 'kode_guru',
                    'label' => 'Kode guru',
                    'dari' => $kodeLama !== '' ? $kodeLama : null,
                    'ke' => $kode,
                    'nilai' => $kode,
                ];
            }
        }

        $duk = $this->dukAngka($row['duk'] ?? null);
        if ($duk !== null && (int) $guru->duk !== $duk) {
            $bentrok = isset($dukTerpakai[$duk]);
            if ($bentrok) {
                $peringatan[] = 'DUK '.$duk.' untuk '.$guru->nama_lengkap.' sudah dipakai guru lain, jadi tidak diubah.';
            } else {
                $hasil[] = [
                    'kolom' => 'duk',
                    'label' => 'DUK',
                    'dari' => $guru->duk === null ? null : (string) $guru->duk,
                    'ke' => (string) $duk,
                    'nilai' => $duk,
                ];
            }
        }

        if ((bool) $guru->status_sertifikasi !== (bool) $row['status_sertifikasi']) {
            $hasil[] = [
                'kolom' => 'status_sertifikasi',
                'label' => 'Status sertifikasi',
                'dari' => $guru->status_sertifikasi ? 'sudah' : 'belum',
                'ke' => $row['status_sertifikasi'] ? 'sudah' : 'belum',
                'nilai' => (bool) $row['status_sertifikasi'],
            ];
        }

        if ((bool) $guru->is_bk !== (bool) $row['is_bk']) {
            $hasil[] = [
                'kolom' => 'is_bk',
                'label' => 'Guru BK',
                'dari' => $guru->is_bk ? 'ya' : 'tidak',
                'ke' => $row['is_bk'] ? 'ya' : 'tidak',
                'nilai' => (bool) $row['is_bk'],
            ];
        }

        [$mapelIjazah, $rumpunIjazah] = $this->ijazah($row, $mapelByNama, $rumpunByNama, $guru->nama_lengkap, $peringatan, 'tidak diubah');
        if ($mapelIjazah !== null) {
            if ((int) $guru->mapel_ijazah_id !== (int) $mapelIjazah->id) {
                $hasil[] = [
                    'kolom' => 'mapel_ijazah_id',
                    'label' => 'Mapel ijazah',
                    'dari' => $guru->mapelIjazah?->nama_mapel,
                    'ke' => $mapelIjazah->nama_mapel,
                    'nilai' => $mapelIjazah->id,
                ];
            }
            if ($guru->rumpun_ijazah_id) {
                $hasil[] = [
                    'kolom' => 'rumpun_ijazah_id',
                    'label' => 'Rumpun ijazah',
                    'dari' => $guru->rumpunIjazah?->nama_rumpun,
                    'ke' => 'kosong',
                    'nilai' => null,
                ];
            }
        } elseif ($rumpunIjazah !== null) {
            if ((int) $guru->rumpun_ijazah_id !== (int) $rumpunIjazah->id) {
                $hasil[] = [
                    'kolom' => 'rumpun_ijazah_id',
                    'label' => 'Rumpun ijazah',
                    'dari' => $guru->rumpunIjazah?->nama_rumpun,
                    'ke' => $rumpunIjazah->nama_rumpun,
                    'nilai' => $rumpunIjazah->id,
                ];
            }
            if ($guru->mapel_ijazah_id) {
                $hasil[] = [
                    'kolom' => 'mapel_ijazah_id',
                    'label' => 'Mapel ijazah',
                    'dari' => $guru->mapelIjazah?->nama_mapel,
                    'ke' => 'kosong',
                    'nilai' => null,
                ];
            }
        }

        if ($row['status_sertifikasi']) {
            $mapelSertifikasi = $this->cariMapel($row['mapel_sertifikasi'] ?? null, $mapelByNama);
            if ($this->berisi($row['mapel_sertifikasi'] ?? null) && $mapelSertifikasi === null) {
                $peringatan[] = 'Mapel sertifikasi '.$guru->nama_lengkap.' ('.$row['mapel_sertifikasi'].') tidak ada di Simpatisans, jadi tidak diubah.';
            }
            if ($mapelSertifikasi !== null && (int) $guru->mapel_sertifikasi_id !== (int) $mapelSertifikasi->id) {
                $hasil[] = [
                    'kolom' => 'mapel_sertifikasi_id',
                    'label' => 'Mapel sertifikasi',
                    'dari' => $guru->mapelSertifikasi?->nama_mapel,
                    'ke' => $mapelSertifikasi->nama_mapel,
                    'nilai' => $mapelSertifikasi->id,
                ];
            }
        } elseif ($guru->mapel_sertifikasi_id) {
            $hasil[] = [
                'kolom' => 'mapel_sertifikasi_id',
                'label' => 'Mapel sertifikasi',
                'dari' => $guru->mapelSertifikasi?->nama_mapel,
                'ke' => 'kosong',
                'nilai' => null,
            ];
        }

        return $hasil;
    }

    /**
     * @param  array<string, true>  $terpakai
     */
    private function pilihKode(string $kodeMadani, array &$terpakai): ?string
    {
        $kode = strtoupper(trim($kodeMadani));
        if (preg_match('/^[A-Z0-9]{2}$/', $kode) === 1 && ! isset($terpakai[$kode])) {
            return $kode;
        }

        foreach (range('A', 'Z') as $pertama) {
            foreach (range('A', 'Z') as $kedua) {
                $calon = $pertama.$kedua;
                if (! isset($terpakai[$calon])) {
                    return $calon;
                }
            }
        }

        return null;
    }

    private function dukAngka(mixed $nilai): ?int
    {
        $teks = trim((string) $nilai);
        if ($teks === '' || preg_match('/^\d+$/', $teks) !== 1) {
            return null;
        }

        $angka = (int) $teks;
        if ($angka < 1 || $angka > 65535) {
            return null;
        }

        return $angka;
    }

    private function statusPegawai(mixed $nilai): ?string
    {
        $teks = strtoupper(trim((string) $nilai));
        $teks = str_replace([' ', '-'], '_', $teks);
        $teks = str_replace('NONASN', 'NON_ASN', $teks);

        return in_array($teks, ['PNS', 'PPPK', 'NON_ASN'], true) ? $teks : null;
    }

    /**
     * @param  Collection<string, Mapel>  $mapelByNama
     */
    /**
     * @param  array<string, mixed>  $row
     * @param  Collection<string, Mapel>  $mapelByNama
     * @param  Collection<string, Rumpun>  $rumpunByNama
     * @param  list<string>  $peringatan
     * @return array{0: ?Mapel, 1: ?Rumpun}
     */
    private function ijazah(array $row, $mapelByNama, $rumpunByNama, string $nama, array &$peringatan, string $akibat): array
    {
        if ($this->berisi($row['mapel_ijazah'] ?? null)) {
            $mapel = $this->cariMapel($row['mapel_ijazah'], $mapelByNama);
            if ($mapel === null) {
                $peringatan[] = 'Mapel ijazah '.$nama.' ('.$row['mapel_ijazah'].') tidak ada di Simpatisans, jadi '.$akibat.'.';
            }

            return [$mapel, null];
        }

        if ($this->berisi($row['rumpun_ijazah'] ?? null)) {
            $kunci = $this->kunciNama($row['rumpun_ijazah']);
            $rumpun = $rumpunByNama->get($kunci);
            if ($rumpun === null) {
                $peringatan[] = 'Rumpun ijazah '.$nama.' ('.$row['rumpun_ijazah'].') tidak ada di Simpatisans, jadi '.$akibat.'.';
            }

            return [null, $rumpun];
        }

        return [null, null];
    }

    private function cariMapel(mixed $nama, $mapelByNama): ?Mapel
    {
        if (! $this->berisi($nama)) {
            return null;
        }

        return $mapelByNama->get($this->kunciNama($nama));
    }

    private function kunci(?string $nilai): string
    {
        $nilai = trim((string) $nilai);
        if ($nilai === '') {
            return '';
        }

        $angka = preg_replace('/\D+/', '', $nilai) ?? '';
        if (strlen($angka) >= 8) {
            return $angka;
        }

        $mentah = strtoupper((string) (preg_replace('/\s+/', '', $nilai) ?? ''));

        return strlen($mentah) >= 8 ? $mentah : '';
    }

    private function berisi(mixed $nilai): bool
    {
        if ($nilai === null) {
            return false;
        }

        $teks = trim((string) $nilai);

        return $teks !== '' && $teks !== '-' && $teks !== '—';
    }

    private function teks(mixed $nilai, int $maks): ?string
    {
        if (! $this->berisi($nilai)) {
            return null;
        }

        $teks = trim((string) $nilai);
        if (mb_strlen($teks) > $maks) {
            return null;
        }

        return $teks;
    }

    private function email(mixed $nilai): ?string
    {
        $teks = $this->teks($nilai, 255);
        if ($teks === null || filter_var($teks, FILTER_VALIDATE_EMAIL) === false) {
            return null;
        }

        return $teks;
    }

    private function jenisKelamin(?string $nilai): ?string
    {
        $nilai = strtoupper(trim((string) $nilai));

        return match (true) {
            in_array($nilai, ['L', 'LAKI-LAKI', 'LAKI LAKI'], true) => 'L',
            in_array($nilai, ['P', 'PEREMPUAN'], true) => 'P',
            default => null,
        };
    }

    private function tanggalTersimpan(Guru $guru): ?string
    {
        $mentah = $guru->getRawOriginal('tanggal_lahir');

        return $this->tanggal($mentah);
    }

    private function kunciNama(mixed $nama): string
    {
        $teks = trim((string) ($nama ?? ''));
        if ($teks === '' || ! mb_check_encoding($teks, 'UTF-8')) {
            return '';
        }

        return mb_strtolower($teks, 'UTF-8');
    }

    private function tanggal(mixed $nilai): ?string
    {
        if (! $this->berisi($nilai)) {
            return null;
        }

        $waktu = strtotime(trim((string) $nilai));
        if ($waktu === false) {
            return null;
        }

        return date('Y-m-d', $waktu);
    }
}
