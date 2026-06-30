<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomSeat extends Model
{
    use HasFactory;

    protected $table = 'room_seats';
    protected $fillable = ['user_id', 'room_id', 'seat_no', 'is_on_mic'];


    public function user()
    {
        return $this->belongsTo(AppUser::class, 'user_id');
    }
}
