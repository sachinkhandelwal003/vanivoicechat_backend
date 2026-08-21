<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameTransaction extends Model
{
    use HasFactory;

    protected $table = 'game_transactions';

    protected $fillable = [
        'game_session_id',
        'game_session_player_id',
        'user_id',
        'uid',
        'sud_order_id',
        'sud_out_order_id',
        'sud_notify_id',
        'transaction_type',
        'amount',
        'before_balance',
        'after_balance',
        'description',
        'status',
        'payload',
    ];

    protected $casts = [
        'game_session_id' => 'integer',
        'game_session_player_id' => 'integer',
        'user_id' => 'integer',
        'amount' => 'integer',
        'before_balance' => 'integer',
        'after_balance' => 'integer',
        'payload' => 'array',
    ];

    public function gameSession(): BelongsTo
    {
        return $this->belongsTo(GameSession::class, 'game_session_id');
    }

    public function gameSessionPlayer(): BelongsTo
    {
        return $this->belongsTo(GameSessionPlayer::class, 'game_session_player_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'user_id');
    }
}
