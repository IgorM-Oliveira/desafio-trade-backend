<?php

namespace Tests\Feature;

use App\Models\{Team, Tournament};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TournamentFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function makeTeams(int $n = 8, int $startIndex = 1): array
    {
        $ids = [];
        for ($i = $startIndex; $i < $startIndex + $n; $i++) {
            $ids[] = Team::create([
                'name' => "T{$i}",
                'registered_order' => $i,
            ])->id;
        }
        return $ids;
    }

    /** @test */
    public function seed_rejeita_menos_ou_mais_de_8_times()
    {
        $t = Tournament::create();

        $ids10 = $this->makeTeams(10, 1);      // cria T1..T10 uma única vez
        $ids6  = array_slice($ids10, 0, 6);    // subset com 6 ids (inválido)

        $this->actingAsUser()
            ->postJson("/api/tournaments/{$t->id}/seed", ['team_ids' => $ids6])
            ->assertStatus(422);

        $this->actingAsUser()
            ->postJson("/api/tournaments/{$t->id}/seed", ['team_ids' => $ids10])
            ->assertStatus(422);
    }


    /** @test */
    public function fluxo_completo_simula_e_cria_8_partidas_e_top4()
    {
        $t = Tournament::create();
        $ids = $this->makeTeams(8, 1);

        // seed → 201 + corpo igual ao show (precisa ter "id")
        $this->actingAsUser()
            ->postJson("/api/tournaments/{$t->id}/seed", ['team_ids' => $ids, 'seed' => 42])
            ->assertStatus(201)
            ->assertJsonPath('id', $t->id);

        // simulate → 200 + finished
        $this->actingAsUser()
            ->postJson("/api/tournaments/{$t->id}/simulate", ['seed' => 42])
            ->assertOk()
            ->assertJsonPath('status', 'finished');

        // conferências finais
        $this->actingAsUser()
            ->getJson("/api/tournaments/{$t->id}")
            ->assertOk()
            ->assertJsonCount(8, 'matches')   // 4 QF + 2 SF + 1 THIRD + 1 FINAL
            ->assertJsonCount(4, 'standings'); // 1º..4º
    }
}
