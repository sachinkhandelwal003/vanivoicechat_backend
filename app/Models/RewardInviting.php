<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RewardInviting extends Model
{
    use SoftDeletes;

    protected $table = 'reward_inviting';

    protected $fillable = [
        'target_person',
        'reward_coin',
        'status',
    ];

    protected $dates = [
        'deleted_at',
    ];
}