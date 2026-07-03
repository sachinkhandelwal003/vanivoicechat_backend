<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TreasureLevelClaims extends Model
{
    protected $table = 'treasure_level_claims';

    protected $fillable = [
        'room_id',
        'user_id',
        'treasure_level_id',
        'treasure_level_reward_id',
        'level',
        'reward_type',
        'reward_item_id',
        'coins',
        'valid_days',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function user()
    {
        return $this->belongsTo(AppUser::class, 'user_id');
    }

    public function reward()
    {
        return $this->belongsTo(TreasureLevelReward::class, 'treasure_level_reward_id');
    }

    public function levelInfo()
    {
        return $this->belongsTo(TreasureLevel::class, 'treasure_level_id');
    }
}
