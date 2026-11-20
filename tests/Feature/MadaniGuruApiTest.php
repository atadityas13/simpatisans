<?php

namespace Tests\Feature;

use App\Models\Guru;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MadaniGuruApiTest extends TestCase
{
    public function test_gurus_ditolak_tanpa_secret(): void
    {
        config(['services.madani.introspect_secret' => 'export-secret']);

        $this->getJson('/api/madani/gurus')
            ->assertUnauthorized();
    }

    public function test_gurus_ditolak_dengan_secret_salah(): void
    {
        config(['services.madani.introspect_secret' => 'export-secret']);

        $this->getJson('/api/madani/gurus', [
            'X-Madani-Introspect-Secret' => 'wrong',
        ])->assertUnauthorized();
    }

    public function test_gurus_mengembalikan_nip_duk_dan_nama_mapel(): void
    {
        config(['services.madani.introspect_secret' => 'export-secret']);
        $this->buatTabelMinimal();

        $mapelId = \Illuminate\Support\Facades\DB::table('mapels')->insertGetId([
            'nama_mapel' => 'Matematika',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Guru::query()->create([
            'username' => '198001012005011001',
            'kode_guru' => 'BD',
            'duk' => 3,
            'nama_guru' => 'Budi Santoso',
            'gelar_depan' => '-',
            'nuptk' => '1234567890123456',
            'mapel_ijazah_id' => $mapelId,
            'status_sertifikasi' => true,
        ]);

        $this->getJson('/api/madani/gurus', [
            'X-Madani-Introspect-Secret' => 'export-secret',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.nip', '198001012005011001')
            ->assertJsonPath('data.0.duk', 3)
            ->assertJsonPath('data.0.nama', 'Budi Santoso')
            ->assertJsonPath('data.0.gelar_depan', null)
            ->assertJsonPath('data.0.mapel_ijazah', 'Matematika')
            ->assertJsonPath('data.0.status_sertifikasi', true);
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
