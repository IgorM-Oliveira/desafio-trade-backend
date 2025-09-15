<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->enum('stage', ['QF', 'SF', 'THIRD', 'FINAL'])->index();
            $table->foreignId('home_team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('away_team_id')->constrained('teams')->cascadeOnDelete();
            $table->unsignedInteger('home_goals')->nullable();
            $table->unsignedInteger('away_goals')->nullable();
            $table->integer('home_points_delta')->nullable();
            $table->integer('away_points_delta')->nullable();
            $table->foreignId('winner_team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->timestamp('played_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};
