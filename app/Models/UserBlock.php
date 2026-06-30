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
        'room_id'
    ];

    public function blockedUser()
    {
        return $this->belongsTo(AppUser::class, 'blocked_user_id');
    }
}
