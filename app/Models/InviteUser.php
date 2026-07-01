<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InviteUser extends Model
{
    use HasFactory;

    protected $table = 'invite_users';

    protected $fillable = [
        'inviter_id',
        'invited_user_id',
        'invite_code',
        'is_completed',
        'completed_at',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function inviter()
    {
        return $this->belongsTo(AppUser::class, 'inviter_id');
    }

    public function invitedUser()
    {
        return $this->belongsTo(AppUser::class, 'invited_user_id');
    }
}
