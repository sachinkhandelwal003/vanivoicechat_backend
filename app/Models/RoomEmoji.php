<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomEmoji extends Model
{
    use HasFactory;

    protected $table = 'room_emojis';

    protected $fillable = [
        'title',
        'type',
        'file',
        'status'
    ];

}
