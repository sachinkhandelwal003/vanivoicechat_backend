<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TreasureLevelReward extends Model
{
    protected $table = 'treasure_level_rewards';

    protected $fillable = [
        'treasure_level_id',
        'reward_type',
        'reward_item_id',
        'coins',
        'valid_days',
        'reward_image',
        'status'
    ];

    public function level()
    {
        return $this->belongsTo(TreasureLevel::class, 'treasure_level_id');
    }
}
