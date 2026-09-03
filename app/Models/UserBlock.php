<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserBlock extends Model
{
    use HasFactory;

    protected $table = 'user_blocks';

    protected $fillable = [
        'blocker_id',
        'blocked_user_id',
        'room_id',
        'expires_at'
    ];

    public function blockedUser()
    {
        return $this->belongsTo(AppUser::class, 'blocked_user_id');
    }

    public function blockerUser()
    {
        return $this->belongsTo(AppUser::class, 'blocker_id');
    }

    public function blockerAdmin()
    {
        return $this->belongsTo(User::class, 'blocker_id');
    }

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id');
    }
}
