<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests\{SeedTournamentRequest, SimulateTournamentRequest};
use App\Models\{Tournament, MatchGame};
use App\Services\TournamentSimulator;

class TournamentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Tournament::query()
            ->with(['standings.team'])
            ->latest('id')->paginate();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        return Tournament::create();
    }

    /**
     * Display the specified resource.
     */
    public function show(Tournament $tournament)
    {
        return $tournament->load(['matches.homeTeam', 'matches.awayTeam', 'standings.team']);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function seed(SeedTournamentRequest $req, Tournament $tournament, TournamentSimulator $sim)
    {
        abort_if($tournament->matches()->exists(), 422, 'Torneio já seedado.');

        $sim->seedQuarterFinals($tournament, $req->team_ids, $req->seed);

        // 201 + mesmo payload do show (assim o teste encontra "id")
        return response()->json(
            $tournament->load(['matches.homeTeam', 'matches.awayTeam', 'standings.team']),
            201
        );
    }

    public function simulate(SimulateTournamentRequest $req, Tournament $tournament, TournamentSimulator $sim)
    {
        abort_if(!$tournament->matches()->where('stage', 'QF')->exists(), 422, 'Seed das quartas é obrigatório.');
        abort_if($tournament->status === 'finished', 422, 'Torneio já finalizado.');

        $sim->simulate($tournament, $req->seed);

        // 200 + mesmo payload do show
        return response()->json(
            $tournament->load(['matches.homeTeam', 'matches.awayTeam', 'standings.team'])
        );
    }
}
