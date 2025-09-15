<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    AuthController, TeamController, TournamentController
};

Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    // Teams
    Route::apiResource('teams', TeamController::class)->only(['index', 'store', 'destroy']);

    // Tournaments
    Route::post('tournaments', [TournamentController::class, 'store']);
    Route::get('tournaments', [TournamentController::class, 'index']);
    Route::get('tournaments/{tournament}', [TournamentController::class, 'show']);
    Route::post('tournaments/{tournament}/seed', [TournamentController::class, 'seed']);
    Route::post('tournaments/{tournament}/simulate', [TournamentController::class, 'simulate']);
});
