<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    protected $fillable = ['name', 'registered_order'];

    public function standings(): HasMany
    {
        return $this->hasMany(Standing::class);
    }
    public function homeMatches(): HasMany
    {
        return $this->hasMany(MatchGame::class, 'home_team_id');
    }
    public function awayMatches(): HasMany
    {
        return $this->hasMany(MatchGame::class, 'away_team_id');
    }
}
