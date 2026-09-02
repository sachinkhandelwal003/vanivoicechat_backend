<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomSeatSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id',
        'seat_no',
        'is_locked',
        'is_muted_by_host',
        'is_self_muted',
        'invited_user_id'
    ];

      public function room()
    {
        return $this->belongsTo(Room::class, 'room_id');
    }
}
