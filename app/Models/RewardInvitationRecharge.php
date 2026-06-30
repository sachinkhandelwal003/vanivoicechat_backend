<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RewardInvitationRecharge extends Model
{
    use SoftDeletes;

    protected $table = 'reward_invitation_recharge';

    protected $fillable = [
        'deposit_amount',
        'gain_benefits',
        'status',
    ];

    protected $dates = [
        'deleted_at',
    ];
}