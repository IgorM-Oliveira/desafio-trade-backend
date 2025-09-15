<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchGame extends Model
{
    protected $table = 'matches';
    protected $fillable = [
        'tournament_id',
        'stage',
        'home_team_id',
        'away_team_id',
        'home_goals',
        'away_goals',
        'home_points_delta',
        'away_points_delta',
        'winner_team_id',
        'played_at'
    ];
    protected $casts = ['played_at' => 'datetime'];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }
    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }
    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }
    public function winner(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'winner_team_id');
    }
}
