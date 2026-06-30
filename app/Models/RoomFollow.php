<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomFollow extends Model
{
    use HasFactory;

    protected $table = 'room_follows';
    protected $fillable = ['user_id', 'room_id'];


    public function user()
    {
        return $this->belongsTo(AppUser::class, 'user_id');
    }

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id');
    }
}
