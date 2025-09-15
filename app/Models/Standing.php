<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Standing extends Model
{
    protected $fillable = ['tournament_id', 'team_id', 'position', 'points_total'];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
