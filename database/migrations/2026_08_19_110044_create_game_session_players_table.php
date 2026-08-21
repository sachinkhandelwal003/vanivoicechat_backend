<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_session_players', function (Blueprint $table) {

            $table->id();

            $table->foreignId('game_session_id')
                ->constrained('game_sessions')
                ->cascadeOnDelete();

            // Vani user
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('app_users')
                ->nullOnDelete();

            // SUD UID
            $table->string('uid', 255);

            // Robot or real player
            $table->boolean('is_ai')->default(false);
            $table->unsignedInteger('ai_level')->default(0);

            // Game result
            $table->unsignedInteger('rank')->nullable();

            $table->boolean('is_escaped')->default(false);

            /*
            SUD:
            0 = no information
            1 = lose
            2 = win
            3 = draw
            */
            $table->unsignedTinyInteger('is_win')->nullable();

            $table->integer('score')->default(0);

            $table->integer('commission_score')->default(0);

            $table->integer('award')->default(0);

            $table->integer('role')->nullable();

            $table->boolean('is_managed')->default(false);

            /*
            Vani coin information
            */
            $table->unsignedBigInteger('entry_coins')->default(0);

            $table->unsignedBigInteger('win_coins')->default(0);

            $table->unsignedBigInteger('loss_coins')->default(0);

            $table->bigInteger('net_coins')->default(0);

            $table->timestamps();

            $table->index('uid');
            $table->index('user_id');
            $table->index('is_win');

            // Same user should not be inserted twice
            // in the same game session.
            $table->unique([
                'game_session_id',
                'uid'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_session_players');
    }
};
