<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomSetting extends Model
{
    use HasFactory;

    protected $table = 'room_settings';

    protected $fillable = [
        'room_id',
        'mic_permission',
        'message_permission',
        'admin_can_play_music',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

}