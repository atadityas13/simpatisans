<?php

namespace Tests\Feature;

use Tests\TestCase;

class MadaniRombelApiTest extends TestCase
{
    public function test_rombels_ditolak_tanpa_secret(): void
    {
        config(['services.madani.introspect_secret' => 'export-secret']);

        $this->getJson('/api/madani/rombels')
            ->assertUnauthorized();
    }

    public function test_rombels_ditolak_dengan_secret_salah(): void
    {
        config(['services.madani.introspect_secret' => 'export-secret']);

        $this->getJson('/api/madani/rombels', [
            'X-Madani-Introspect-Secret' => 'wrong',
        ])->assertUnauthorized();
    }
}
