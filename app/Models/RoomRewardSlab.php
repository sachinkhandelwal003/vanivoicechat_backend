<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomRewardSlab extends Model
{

    protected $table = 'room_reward_slabs';

    protected $fillable = [
        'room_contribution',
        'reward_coins',
        'sort_order',
        'status',
    ];
}
