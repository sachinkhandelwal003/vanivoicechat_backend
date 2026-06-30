<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TreasureLevel extends Model
{
    protected $table = 'treasure_levels';

    protected $fillable = [
        'level',
        'target_points',
        'chest_image',
        'status'
    ];

  
    public function rewards()
    {
        return $this->hasMany(TreasureLevelReward::class, 'treasure_level_id')
            ->where('status', 1);
    }
}
