<?php

namespace Tests\Feature;

use App\Models\Guru;
use App\Models\Mapel;
use App\Services\Madani\TarikGuruService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TarikGuruDariMadaniTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->buatTabelMinimal();
    }

    public function test_form_tambah_guru_ditolak(): void
    {
        $this->withoutMiddleware()
            ->post(route('guru.store'))
            ->assertRedirect(route('guru.index'))
            ->assertSessionHas('error', 'Guru baru hanya bisa ditarik dari Madani.');
    }

    public function test_tarik_menambah_guru_baru_dan_sync_hanya_mengubah_data_madani(): void
    {
        Mapel::query()->create(['nama_mapel' => 'Matematika']);

        $lama = Guru::query()->create([
            'username' => '198001012005011001',
            'kode_guru' => 'BD',
            'duk' => 9,
            'nama_guru' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'status_pegawai' => 'PNS',
            'status_sertifikasi' => false,
            'is_bk' => false,
        ]);
        Guru::query()->create([
            'username' => '197001012000011009',
            'kode_guru' => 'LL',
            'nama_guru' => 'Hanya di Simpatisans',
            'status_pegawai' => 'NON_ASN',
        ]);

        $rows = [
            $this->baris([
                'nip' => '1980 0101 200501 1 001',
                'nama' => 'Budi Diperbarui',
                'duk' => '2',
                'email' => null,
                'kode_internal' => 'ZZ',
                'mapel_ijazah' => 'Matematika',
                'status_sertifikasi' => true,
            ]),
            $this->baris([
                'nip' => '199003032015011003',
                'nama' => 'Andi Nugroho',
                'kode_internal' => 'kode-panjang',
                'duk' => '4',
                'status_pegawai' => 'PPPK',
            ]),
            $this->baris([
                'nip' => '198811112015011005',
                'nama' => 'Nonaktif',
                'status' => 'nonaktif',
            ]),
        ];

        $service = app(TarikGuruService::class);
        $hasil = $service->bandingkan($rows);

        $this->assertCount(1, $hasil['tarik']);
        $this->assertSame('Andi Nugroho', $hasil['tarik'][0]['nama']);
        $this->assertSame('AA', $hasil['tarik'][0]['kode']);
        $this->assertSame(4, $hasil['tarik'][0]['duk']);
        $this->assertCount(1, $hasil['sinkron']);
        $this->assertSame($lama->id, $hasil['sinkron'][0]['guru']->id);
        $this->assertCount(1, $hasil['hanya_lokal']);
        $this->assertTrue(collect($hasil['lewati'])->contains(fn (string $pesan): bool => str_contains($pesan, 'Nonaktif')));

        $kolom = collect($hasil['sinkron'][0]['perubahan'])->pluck('kolom');
        $this->assertTrue($kolom->contains('nama_guru'));
        $this->assertTrue($kolom->contains('duk'));
        $this->assertTrue($kolom->contains('mapel_ijazah_id'));
        $this->assertFalse($kolom->contains('email'));
        $this->assertFalse($kolom->contains('kode_guru'));

        $service->tarik($rows);
        $baru = Guru::query()->where('username', '199003032015011003')->first();
        $this->assertNotNull($baru);
        $this->assertSame('AA', $baru->kode_guru);
        $this->assertSame('PPPK', $baru->status_pegawai);
        $this->assertSame(4, (int) $baru->duk);

        $service->sinkron($rows);
        $lama->refresh();
        $this->assertSame('Budi Diperbarui', $lama->nama_guru);
        $this->assertSame(2, (int) $lama->duk);
        $this->assertSame('budi@example.com', $lama->email);
        $this->assertSame('BD', $lama->kode_guru);
        $this->assertTrue($lama->status_sertifikasi);
        $this->assertSame('Matematika', $lama->mapelIjazah->nama_mapel);
        $this->assertNotNull(Guru::query()->where('username', '197001012000011009')->first());
    }

    public function test_halaman_membandingkan_lewat_api_madani(): void
    {
        config([
            'services.madani.url' => 'https://madani.test/api',
            'services.madani.introspect_secret' => 'rahasia',
        ]);

        Http::fake([
            'https://madani.test/api/v1/simpatisans/gtks' => Http::response([
                'data' => [
                    $this->baris(['nip' => '199003032015011003', 'nama' => 'Andi Nugroho']),
                ],
            ]),
        ]);

        $hasil = app(TarikGuruService::class)->fetchAndRemember();
        $this->assertSame('Andi Nugroho', $hasil['tarik'][0]['nama']);
        $this->assertCount(1, $hasil['tarik']);

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://madani.test/api/v1/simpatisans/gtks'
                && $request->hasHeader('X-Madani-Introspect-Secret', 'rahasia');
        });
    }

    /**
     * @param  array<string, mixed>  $override
     * @return array<string, mixed>
     */
    private function baris(array $override): array
    {
        $service = app(TarikGuruService::class);
        $method = new \ReflectionMethod($service, 'normalisasi');
        $method->setAccessible(true);

        return $method->invoke($service, array_merge([
            'nip' => '198001012005011001',
            'nama' => 'Budi Santoso',
            'status' => 'aktif',
            'status_pegawai' => 'PNS',
            'status_sertifikasi' => false,
            'is_bk' => false,
            'duk' => null,
            'email' => null,
            'kode_internal' => null,
            'mapel_ijazah' => null,
        ], $override));
    }

    private function buatTabelMinimal(): void
    {
        Schema::dropAllTables();

        Schema::create('mapels', function (Blueprint $table) {
            $table->id();
            $table->string('nama_mapel');
            $table->timestamps();
        });

        Schema::create('rumpuns', function (Blueprint $table) {
            $table->id();
            $table->string('nama_rumpun');
            $table->timestamps();
        });

        Schema::create('gurus', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('kode_guru', 2)->unique();
            $table->unsignedSmallInteger('duk')->nullable();
            $table->string('nama_guru');
            $table->string('gelar_depan')->nullable();
            $table->string('gelar_belakang')->nullable();
            $table->string('jenis_kelamin')->nullable();
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('agama')->nullable();
            $table->string('nomor_hp')->nullable();
            $table->string('email')->nullable();
            $table->text('alamat')->nullable();
            $table->string('nuptk')->nullable();
            $table->string('jabatan')->nullable();
            $table->string('golongan')->nullable();
            $table->string('status_pegawai')->nullable();
            $table->boolean('status_sertifikasi')->default(false);
            $table->boolean('is_bk')->default(false);
            $table->unsignedBigInteger('mapel_ijazah_id')->nullable();
            $table->unsignedBigInteger('rumpun_ijazah_id')->nullable();
            $table->unsignedBigInteger('mapel_sertifikasi_id')->nullable();
            $table->timestamps();
        });
    }
}
