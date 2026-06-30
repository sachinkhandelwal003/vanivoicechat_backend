<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomRewardClaim extends Model
{
    protected $table = 'room_reward_claims';

    protected $fillable = [
        'room_id',
        'owner_id',
        'reward_date',
        'room_contribution',
        'reward_coins',
        'is_claimed',
        'claimed_at',
        'slab_id',
        'slab_room_contribution',
        'slab_reward_coins',
        'system_commission',
        'owner_reward_coins'
    ];
}