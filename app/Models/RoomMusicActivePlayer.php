<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomMusicActivePlayer extends Model
{
    protected $table = 'room_music_active_players';

    protected $fillable = [
        'room_id',
        'playlist_id',
        'started_by',
        'agora_player_id',
        'player_name',
        'system_uid',
        'agora_sequence',
        'current_position_sec',
        'volume',
        'is_loop',
        'is_active',
        'status',
        'started_at',
    ];

    protected $casts = [
    'started_at' => 'datetime',
];


    // 🔗 Relationships (optional but useful)

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function playlist()
    {
        return $this->belongsTo(RoomMusicPlaylist::class, 'playlist_id');
    }

    public function user()
    {
        return $this->belongsTo(AppUser::class, 'started_by');
    }
}
