<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameSessionPlayer extends Model
{
    use HasFactory;

    protected $table = 'game_session_players';

    protected $fillable = [
        'game_session_id',
        'user_id',
        'uid',
        'is_ai',
        'ai_level',
        'rank',
        'is_escaped',
        'is_win',
        'score',
        'commission_score',
        'award',
        'role',
        'is_managed',
        'entry_coins',
        'win_coins',
        'loss_coins',
        'net_coins',
    ];

    protected $casts = [
        'game_session_id' => 'integer',
        'user_id' => 'integer',
        'is_ai' => 'boolean',
        'ai_level' => 'integer',
        'rank' => 'integer',
        'is_escaped' => 'boolean',
        'is_win' => 'integer',
        'score' => 'integer',
        'commission_score' => 'integer',
        'award' => 'integer',
        'role' => 'integer',
        'is_managed' => 'boolean',
        'entry_coins' => 'integer',
        'win_coins' => 'integer',
        'loss_coins' => 'integer',
        'net_coins' => 'integer',
    ];

    public function gameSession(): BelongsTo
    {
        return $this->belongsTo(GameSession::class, 'game_session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'user_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(GameTransaction::class, 'game_session_player_id');
    }
}
