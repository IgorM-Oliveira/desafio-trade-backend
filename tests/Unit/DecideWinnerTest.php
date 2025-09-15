<?php

namespace Tests\Unit;

use App\Models\Team;
use App\Services\TournamentSimulator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DecideWinnerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function desempata_por_total_acumulado_e_depois_por_registered_order()
    {
        $sim = new TournamentSimulator();

        $a = Team::create(['name' => 'A', 'registered_order' => 2]);
        $b = Team::create(['name' => 'B', 'registered_order' => 5]);

        $this->assertEquals($a->id, $sim->decideWinner($a->id, $b->id, 1, 1, 3, 1));
        $this->assertEquals($a->id, $sim->decideWinner($a->id, $b->id, 2, 2, 0, 0));
    }
}
