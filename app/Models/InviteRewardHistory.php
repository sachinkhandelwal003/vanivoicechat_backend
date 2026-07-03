<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InviteRewardHistory extends Model
{
    use HasFactory;

    protected $table = 'invite_reward_histories';

    protected $fillable = [
        'user_id',
        'reward_inviting_id',
        'target_person',
        'reward_coin',
    ];

    protected $casts = [
        'reward_coin' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(AppUser::class, 'user_id');
    }

    public function reward()
    {
        return $this->belongsTo(RewardInviting::class, 'reward_inviting_id');
    }
}