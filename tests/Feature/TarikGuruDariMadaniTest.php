<?php

namespace Tests\Feature;

use App\Models\Guru;
use App\Models\Mapel;
use App\Models\Rumpun;
use App\Services\Madani\TarikGuruService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        $this->assertTrue($kolom->contains('kode_guru'));
        $this->assertFalse($kolom->contains('email'));

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
        $this->assertSame('ZZ', $lama->kode_guru);
        $this->assertTrue($lama->status_sertifikasi);
        $this->assertSame('Matematika', $lama->mapelIjazah->nama_mapel);
        $this->assertNull(Guru::query()->where('username', '197001012000011009')->first());
        $this->assertDatabaseMissing('users', ['username' => '197001012000011009', 'role' => 'guru']);
        $this->assertDatabaseHas('users', ['username' => 'admin.kurikulum', 'role' => 'admin_kurikulum']);
    }

    public function test_sync_memetakan_rumpun_dan_mengosongkan_mapel_ijazah(): void
    {
        $mapel = Mapel::query()->create(['nama_mapel' => 'Matematika']);
        $rumpun = Rumpun::query()->create(['nama_rumpun' => 'IPA']);
        $guru = Guru::query()->create([
            'username' => '198001012005011001',
            'kode_guru' => 'BD',
            'nama_guru' => 'Budi Santoso',
            'status_pegawai' => 'PNS',
            'mapel_ijazah_id' => $mapel->id,
        ]);

        $rows = [
            $this->baris([
                'mapel_ijazah' => null,
                'rumpun_ijazah' => 'IPA',
                'kode_internal' => 'BD',
            ]),
        ];

        app(TarikGuruService::class)->sinkron($rows);
        $guru->refresh();

        $this->assertNull($guru->mapel_ijazah_id);
        $this->assertSame($rumpun->id, $guru->rumpun_ijazah_id);
    }

    public function test_tanggal_lahir_rusak_tidak_menghentikan_perbandingan(): void
    {
        $guru = Guru::query()->create([
            'username' => '198001012005011001',
            'kode_guru' => 'BD',
            'nama_guru' => 'Budi Santoso',
            'status_pegawai' => 'PNS',
        ]);
        DB::table('gurus')->where('id', $guru->id)->update(['tanggal_lahir' => '0000-00-00']);

        $hasil = app(TarikGuruService::class)->bandingkan([
            $this->baris(['tanggal_lahir' => '1980-01-01']),
        ]);

        $kolom = collect($hasil['sinkron'][0]['perubahan'])->pluck('kolom');
        $this->assertTrue($kolom->contains('tanggal_lahir'));
    }

    public function test_referensi_mengirim_nama_mapel_dan_rumpun(): void
    {
        Mapel::query()->create(['nama_mapel' => 'Matematika']);
        Rumpun::query()->create(['nama_rumpun' => 'IPA']);

        $this->withoutMiddleware()
            ->getJson('/api/madani/referensi')
            ->assertOk()
            ->assertJsonPath('mapels.0', 'Matematika')
            ->assertJsonPath('rumpuns.0', 'IPA');
    }

    public function test_hapus_akun_guru_menyisakan_admin(): void
    {
        $this->withoutMiddleware()
            ->post(route('pengguna.hapus-akun-guru'))
            ->assertRedirect();

        $this->assertDatabaseMissing('users', ['role' => 'guru']);
        $this->assertDatabaseHas('users', ['role' => 'super_admin']);
        $this->assertDatabaseHas('users', ['role' => 'admin_kurikulum']);
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

    public function test_tarik_nip_hanya_menambah_guru_yang_diminta(): void
    {
        config([
            'services.madani.url' => 'https://madani.test/api',
            'services.madani.introspect_secret' => 'rahasia',
        ]);

        Http::fake([
            'https://madani.test/api/v1/simpatisans/gtks' => Http::response([
                'data' => [
                    $this->baris([
                        'nip' => '199003032015011003',
                        'nama' => 'Andi Nugroho',
                        'kode_internal' => 'ZZ',
                    ]),
                    $this->baris([
                        'nip' => '198811112015011005',
                        'nama' => 'Orang Lain',
                        'kode_internal' => 'AA',
                    ]),
                ],
            ]),
        ]);

        $this->withoutMiddleware()
            ->post(route('guru.tarik'), ['nip' => '1990 0303 201501 1 003'])
            ->assertRedirect(route('guru.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('gurus', [
            'username' => '199003032015011003',
            'nama_guru' => 'Andi Nugroho',
            'kode_guru' => 'ZZ',
        ]);
        $this->assertDatabaseMissing('gurus', ['username' => '198811112015011005']);
    }

    public function test_cari_nip_menampilkan_data_tanpa_menyimpan(): void
    {
        config([
            'services.madani.url' => 'https://madani.test/api',
            'services.madani.introspect_secret' => 'rahasia',
        ]);

        Http::fake([
            'https://madani.test/api/v1/simpatisans/gtks' => Http::response([
                'data' => [
                    $this->baris([
                        'nip' => '199003032015011003',
                        'nama' => 'Andi Nugroho',
                        'kode_internal' => 'ZZ',
                        'nomor_hp' => '081234567890',
                    ]),
                ],
            ]),
        ]);

        $this->withoutMiddleware()
            ->postJson(route('guru.tarik.cari'), ['nip' => '199003032015011003'])
            ->assertOk()
            ->assertJsonPath('status', 'siap')
            ->assertJsonPath('baris.0.label', 'Nama')
            ->assertJsonPath('baris.0.nilai', 'Andi Nugroho')
            ->assertJsonFragment(['label' => 'Nomor HP', 'nilai' => '081234567890']);

        $this->assertSame(0, Guru::query()->count());
    }

    public function test_cari_nip_yang_tidak_ada(): void
    {
        config([
            'services.madani.url' => 'https://madani.test/api',
            'services.madani.introspect_secret' => 'rahasia',
        ]);

        Http::fake([
            'https://madani.test/api/v1/simpatisans/gtks' => Http::response(['data' => []]),
        ]);

        $this->withoutMiddleware()
            ->postJson(route('guru.tarik.cari'), ['nip' => '199003032015011003'])
            ->assertOk()
            ->assertJsonPath('status', 'tidak_ada');
    }

    public function test_hapus_guru_tidak_lagi_tersedia(): void
    {
        $guru = Guru::query()->create([
            'username' => '198001012005011001',
            'kode_guru' => 'BD',
            'nama_guru' => 'Budi Santoso',
            'status_pegawai' => 'PNS',
        ]);

        $this->withoutMiddleware()
            ->delete('/master/guru/'.$guru->id)
            ->assertStatus(405);

        $this->assertDatabaseHas('gurus', ['id' => $guru->id]);
    }

    public function test_tarik_nip_yang_sudah_ada_ditolak(): void
    {
        config([
            'services.madani.url' => 'https://madani.test/api',
            'services.madani.introspect_secret' => 'rahasia',
        ]);
        Guru::query()->create([
            'username' => '199003032015011003',
            'kode_guru' => 'AA',
            'nama_guru' => 'Andi Nugroho',
            'status_pegawai' => 'PNS',
        ]);

        Http::fake([
            'https://madani.test/api/v1/simpatisans/gtks' => Http::response([
                'data' => [
                    $this->baris(['nip' => '199003032015011003', 'nama' => 'Andi Nugroho']),
                ],
            ]),
        ]);

        $this->withoutMiddleware()
            ->post(route('guru.tarik'), ['nip' => '199003032015011003'])
            ->assertRedirect(route('guru.index'))
            ->assertSessionHas('error');

        $this->assertSame(1, Guru::query()->count());
    }

    public function test_sync_satu_tidak_mengubah_guru_lain(): void
    {
        config([
            'services.madani.url' => 'https://madani.test/api',
            'services.madani.introspect_secret' => 'rahasia',
        ]);

        $target = Guru::query()->create([
            'username' => '198001012005011001',
            'kode_guru' => 'BD',
            'nama_guru' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'status_pegawai' => 'PNS',
        ]);
        $lain = Guru::query()->create([
            'username' => '197001012000011009',
            'kode_guru' => 'LL',
            'nama_guru' => 'Hanya di Simpatisans',
            'status_pegawai' => 'NON_ASN',
        ]);

        Http::fake([
            'https://madani.test/api/v1/simpatisans/gtks' => Http::response([
                'data' => [
                    $this->baris([
                        'nip' => '198001012005011001',
                        'nama' => 'Budi Diperbarui',
                        'email' => null,
                        'nomor_hp' => '081234567890',
                    ]),
                    $this->baris([
                        'nip' => '197001012000011009',
                        'nama' => 'Seharusnya Tidak Berubah',
                    ]),
                ],
            ]),
        ]);

        $this->withoutMiddleware()
            ->post(route('guru.sync', $target))
            ->assertRedirect(route('guru.index'))
            ->assertSessionHas('success');

        $target->refresh();
        $lain->refresh();
        $this->assertSame('Budi Diperbarui', $target->nama_guru);
        $this->assertSame('budi@example.com', $target->email);
        $this->assertSame('081234567890', $target->nomor_hp);
        $this->assertSame('Hanya di Simpatisans', $lain->nama_guru);
    }

    public function test_update_hanya_menyimpan_mapel_diampu(): void
    {
        $mapel = Mapel::query()->create(['nama_mapel' => 'Matematika']);
        $guru = Guru::query()->create([
            'username' => '198001012005011001',
            'kode_guru' => 'BD',
            'nama_guru' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'status_pegawai' => 'PNS',
        ]);

        $this->withoutMiddleware()
            ->get(route('guru.edit', $guru))
            ->assertRedirect(route('guru.index'));

        $this->withoutMiddleware()
            ->put(route('guru.update', $guru), [
                'mapel_diampu' => [$mapel->id],
                'nama_guru' => 'Nama Disusupkan',
                'email' => 'hack@example.com',
                'nomor_hp' => '0800000000',
            ])
            ->assertRedirect(route('guru.index'))
            ->assertSessionHas('success');

        $guru->refresh();
        $this->assertSame('Budi Santoso', $guru->nama_guru);
        $this->assertSame('budi@example.com', $guru->email);
        $this->assertNull($guru->nomor_hp);
        $this->assertSame([$mapel->id], $guru->mapelDiampu()->pluck('mapels.id')->all());
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
            'rumpun_ijazah' => null,
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

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('nama_lengkap')->nullable();
            $table->string('role')->nullable();
            $table->string('password')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::table('users')->insert([
            [
                'username' => '197001012000011009',
                'nama_lengkap' => 'Hanya di Simpatisans',
                'role' => 'guru',
                'password' => 'rahasia',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'username' => 'superadmin',
                'nama_lengkap' => 'Super Admin',
                'role' => 'super_admin',
                'password' => 'rahasia',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'username' => 'admin.kurikulum',
                'nama_lengkap' => 'Admin Kurikulum',
                'role' => 'admin_kurikulum',
                'password' => 'rahasia',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

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

        Schema::create('guru_mapels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('guru_id');
            $table->unsignedBigInteger('mapel_id');
            $table->timestamps();
        });
    }
}
