<?php

namespace App\Services;

use App\Models\{Tournament, Team, MatchGame, Standing};
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class TournamentSimulator
{
    /** Sorteia confrontos das quartas e registra jogos vazios */
    public function seedQuarterFinals(Tournament $t, array $teamIds, ?int $seed = null): void
    {
        if ($seed !== null) {
            mt_srand($seed);
        }

        $ids = Team::whereIn('id', $teamIds)->pluck('id')->all(); // garante que existem
        shuffle($ids); // sorteio simples

        // cria 4 jogos (0x1, 2x3, 4x5, 6x7)
        for ($i = 0; $i < 8; $i += 2) {
            MatchGame::create([
                'tournament_id' => $t->id,
                'stage'         => 'QF',
                'home_team_id'  => $ids[$i],
                'away_team_id'  => $ids[$i + 1],
            ]);
        }
    }

    /** Executa toda a simulação (QF -> SF -> THIRD -> FINAL) */
    public function simulate(Tournament $t, ?int $seed = null): void
    {
        if ($seed !== null) {
            mt_srand($seed);
        }

        DB::transaction(function () use ($t) {
            $t->update(['status' => 'running', 'started_at' => now()]);

            // 1) Jogar QF
            $qfWinners = $this->playRound($t, 'QF');
            if (count($qfWinners) !== 4) {
                abort(422, 'Quartas de final incompletas (esperado 4 vencedores).');
            }

            // 2) Montar & jogar SF (em pares 0-1 e 2-3)
            $pairs = array_chunk(array_values($qfWinners), 2); // [[w1,w2],[w3,w4]]
            foreach ($pairs as $p) {
                if (count($p) !== 2) {
                    abort(422, 'Par de semifinal inválido.');
                }
                MatchGame::create([
                    'tournament_id' => $t->id,
                    'stage'         => 'SF',
                    'home_team_id'  => $p[0],
                    'away_team_id'  => $p[1],
                ]);
            }
            $sfWinners = $this->playRound($t, 'SF');
            if (count($sfWinners) !== 2) {
                abort(422, 'Semifinais incompletas (esperado 2 vencedores).');
            }

            // 3) Terceiro lugar = perdedores das SF
            $sfTeams = array_values(array_merge(...$pairs)); // [w1,w2,w3,w4]
            $losers  = array_values(array_diff($sfTeams, $sfWinners)); // deve ter 2
            if (count($losers) !== 2) {
                abort(422, 'Não foi possível determinar os times do 3º lugar.');
            }
            MatchGame::create([
                'tournament_id' => $t->id,
                'stage'         => 'THIRD',
                'home_team_id'  => $losers[0],
                'away_team_id'  => $losers[1],
            ]);
            $this->playRound($t, 'THIRD');

            // 4) Final com vencedores das SF
            MatchGame::create([
                'tournament_id' => $t->id,
                'stage'         => 'FINAL',
                'home_team_id'  => $sfWinners[0],
                'away_team_id'  => $sfWinners[1],
            ]);
            $this->playRound($t, 'FINAL');

            // 5) Standings
            $this->computeStandings($t);

            $t->update(['status' => 'finished', 'finished_at' => now()]);
        });
    }


    /** Cria partidas em pares [ids[0] vs ids[1]], [ids[2] vs ids[3]] */
    protected function createPairMatches(Tournament $t, string $stage, array $teamIds)
    {
        $teamIds = array_values($teamIds); // reindexa o array
        if (count($teamIds) < 2 || count($teamIds) % 2 !== 0) {
            throw new \RuntimeException("Número inválido de equipes para a fase {$stage}");
        }

        $pairs = collect($teamIds)->values()->chunk(2);

        return $pairs->map(function ($chunk) use ($t, $stage) {
            $chunk = $chunk->values(); // reindexa o chunk (0,1)
            if ($chunk->count() !== 2) {
                throw new \RuntimeException("Par incompleto detectado em {$stage}");
            }
            return MatchGame::create([
                'tournament_id' => $t->id,
                'stage'         => $stage,
                'home_team_id'  => $chunk->get(0),
                'away_team_id'  => $chunk->get(1),
            ]);
        });
    }

    /** Joga todas as partidas de uma fase e retorna winner_ids em ordem */
    protected function playRound(Tournament $t, string $stage): array
    {
        $winners = [];
        foreach ($t->matches()->where('stage', $stage)->orderBy('id')->get() as $m) {
            $hg = random_int(0, 5);
            $ag = random_int(0, 5);
            [$ht, $at] = $this->totalsBefore($t, $m->home_team_id, $m->away_team_id);
            $winner = $this->decideWinner($m->home_team_id, $m->away_team_id, $hg, $ag, $ht, $at);

            $m->update([
                'home_goals' => $hg,
                'away_goals' => $ag,
                'home_points_delta' => $hg - $ag,
                'away_points_delta' => $ag - $hg,
                'winner_team_id' => $winner,
                'played_at' => now(),
            ]);
            $winners[] = $winner;
        }
        return $winners;
    }

    /** Soma dos deltas já jogados no torneio por time */
    protected function totalsBefore(Tournament $t, int $homeId, int $awayId): array
    {
        $sum = fn($teamId) => MatchGame::where('tournament_id', $t->id)
            ->whereNotNull('played_at')
            ->where(fn($q) => $q->where('home_team_id', $teamId)->orWhere('away_team_id', $teamId))
            ->get()->sum(fn($m) => $m->home_team_id === $teamId ? ($m->home_points_delta ?? 0) : ($m->away_points_delta ?? 0));
        return [$sum($homeId), $sum($awayId)];
    }

    /** Regra de desempate: gols >; se empate, maior total; se empate, menor registered_order */
    public function decideWinner(int $homeId, int $awayId, int $hg, int $ag, int $homeTotal, int $awayTotal): int
    {
        if ($hg !== $ag) return $hg > $ag ? $homeId : $awayId;
        if ($homeTotal !== $awayTotal) return $homeTotal > $awayTotal ? $homeId : $awayId;
        $ho = Team::find($homeId)->registered_order;
        $ao = Team::find($awayId)->registered_order;
        return $ho < $ao ? $homeId : $awayId;
    }

    /** Calcula 1º a 4º usando FINAL e THIRD; grava points_total acumulado */
    protected function computeStandings(Tournament $t): void
    {
        $pts = [];
        foreach ($t->matches()->whereNotNull('played_at')->get() as $m) {
            $pts[$m->home_team_id] = ($pts[$m->home_team_id] ?? 0) + ($m->home_points_delta ?? 0);
            $pts[$m->away_team_id] = ($pts[$m->away_team_id] ?? 0) + ($m->away_points_delta ?? 0);
        }

        $final = $t->matches()->where('stage', 'FINAL')->first();
        $third = $t->matches()->where('stage', 'THIRD')->first();

        $first  = $final->winner_team_id;
        $second = $final->home_team_id === $first ? $final->away_team_id : $final->home_team_id;

        $thirdW = $third->winner_team_id;
        $fourth = $third->home_team_id === $thirdW ? $third->away_team_id : $third->home_team_id;

        foreach ([[1, $first], [2, $second], [3, $thirdW], [4, $fourth]] as [$pos, $teamId]) {
            Standing::updateOrCreate(
                ['tournament_id' => $t->id, 'team_id' => $teamId],
                ['position' => $pos, 'points_total' => $pts[$teamId] ?? 0]
            );
        }
    }
}
