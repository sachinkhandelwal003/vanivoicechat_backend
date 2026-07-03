<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserMedal extends Model
{
    use HasFactory;

    protected $table = 'user_medals';

    protected $fillable = [
        'user_id',
        'medal_id',
        'is_equipped',
        'achieved_at',
        'slot_no'
    ];

    public function medal()
    {
        return $this->belongsTo(Medal::class, 'medal_id');
    }

}