<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Friendship extends Model
{
    use HasFactory;

    protected $table = 'friendships';
    protected $fillable = ['user_one', 'user_two', 'status', 'action_user_id'];

    public function senderUser()
    {
        return $this->belongsTo(AppUser::class, 'action_user_id');
    }

    public function userOne()
    {
        return $this->belongsTo(AppUser::class, 'user_one');
    }

    public function userTwo()
    {
        return $this->belongsTo(AppUser::class, 'user_two');
    }
}
