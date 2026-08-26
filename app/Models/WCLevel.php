<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WCLevel extends Model
{
    protected $table = 'wc_levels';

    protected $fillable = [
        'user_id',
        'type',
        'level',
        'exp'
    ];

    public function levelData()
    {
        return $this->hasMany(Level::class, 'level', 'level');
    }
}
