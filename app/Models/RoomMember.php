<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomMember extends Model
{
    use HasFactory;

    protected $table = 'room_members';
    protected $fillable = ['user_id', 'room_id', 'joined_at', 'left_at'];


    public function user()
    {
        return $this->belongsTo(AppUser::class, 'user_id');
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function roleData()
    {
        return $this->hasOne(RoomUserRole::class, 'user_id', 'user_id');
    }
}
