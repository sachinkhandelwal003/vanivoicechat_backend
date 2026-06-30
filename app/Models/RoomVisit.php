<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomVisit extends Model
{
    use HasFactory;

    protected $table = 'room_visits';
    protected $fillable = ['user_id', 'room_id', 'last_visited_at'];


    public function user()
    {
        return $this->belongsTo(AppUser::class, 'user_id');
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }
}
