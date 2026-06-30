<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomMessageClear extends Model
{

    protected $table = 'room_message_clears';
    protected $fillable = [
        'room_id',
        'user_id',
        'cleared_at',
    ];
}
