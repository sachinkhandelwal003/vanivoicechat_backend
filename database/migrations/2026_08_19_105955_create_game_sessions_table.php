<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_sessions', function (Blueprint $table) {

            $table->id();

            // SUD game information
            $table->unsignedBigInteger('mg_id')->nullable();
            $table->string('mg_id_str', 100)->nullable();

            $table->string('room_id', 255)->nullable();

            $table->unsignedInteger('game_mode')->nullable();
            $table->unsignedInteger('game_mode_ex')->nullable();

            // Unique SUD game round ID
            $table->string('game_round_id', 255)->unique();

            // Optional custom parameters
            $table->string('report_game_info_key', 255)->nullable();
            $table->text('report_game_info_extras')->nullable();

            // Game status
            $table->enum('status', [
                'started',
                'playing',
                'completed',
                'cancelled'
            ])->default('started');

            // Game timing
            $table->unsignedBigInteger('battle_start_at')->nullable();
            $table->unsignedBigInteger('battle_end_at')->nullable();
            $table->unsignedInteger('battle_duration')->nullable();

            // Complete SUD payload for debugging/audit
            $table->json('start_payload')->nullable();
            $table->json('settle_payload')->nullable();

            $table->timestamps();

            $table->index('mg_id');
            $table->index('room_id');
            $table->index('status');
            $table->index('battle_start_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_sessions');
    }
};
