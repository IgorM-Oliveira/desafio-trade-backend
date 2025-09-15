<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tournament extends Model
{
    protected $fillable = ['title', 'status', 'started_at', 'finished_at'];
    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function matches(): HasMany
    {
        return $this->hasMany(MatchGame::class);
    }
    public function standings(): HasMany
    {
        return $this->hasMany(Standing::class);
    }
}
