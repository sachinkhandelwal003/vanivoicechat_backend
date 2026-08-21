<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameSession extends Model
{
    use HasFactory;

    protected $table = 'game_sessions';

    protected $fillable = [
        'mg_id',
        'mg_id_str',
        'room_id',
        'game_mode',
        'game_mode_ex',
        'game_round_id',
        'report_game_info_key',
        'report_game_info_extras',
        'status',
        'battle_start_at',
        'battle_end_at',
        'battle_duration',
        'start_payload',
        'settle_payload',
    ];

    protected $casts = [
        'mg_id' => 'integer',
        'game_mode' => 'integer',
        'game_mode_ex' => 'integer',
        'battle_start_at' => 'integer',
        'battle_end_at' => 'integer',
        'battle_duration' => 'integer',
        'start_payload' => 'array',
        'settle_payload' => 'array',
    ];

    public function players(): HasMany
    {
        return $this->hasMany(GameSessionPlayer::class, 'game_session_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(GameTransaction::class, 'game_session_id');
    }
}
