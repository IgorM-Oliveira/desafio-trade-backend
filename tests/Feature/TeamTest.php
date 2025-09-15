<?php

namespace Tests\Feature;

use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function cria_time_com_nome_unico_e_ordem()
    {
        $this->actingAsUser()
            ->postJson('/api/teams', ['name' => 'Flamengo'])
            ->assertCreated();

        $this->actingAsUser()
            ->postJson('/api/teams', ['name' => 'Flamengo'])
            ->assertStatus(422);
    }
}
