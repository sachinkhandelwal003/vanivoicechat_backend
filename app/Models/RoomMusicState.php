<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomMusicState extends Model
{
    use HasFactory;

    protected $table = 'room_music_states';

    protected $fillable = [
        'room_id',
        'current_playlist_id',
        'agora_player_id',
        'system_uid',
        'is_music_active',
        'play_started_at',
        'agora_sequence',
        'status',
        'current_position_sec',
        'started_at',
        'volume',
        'is_loop',
        'is_shuffle',
        'last_action_by',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'play_started_at' => 'datetime',
    ];


    // Room relation
    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    // Current playing song
    public function currentSong()
    {
        return $this->belongsTo(RoomMusicPlaylist::class, 'current_playlist_id');
    }

    // Last action user
    public function lastActionUser()
    {
        return $this->belongsTo(AppUser::class, 'last_action_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    // Get actual playing position (sync logic)
    public function getActualPositionAttribute()
    {
        if ($this->status !== 'playing' || !$this->started_at) {
            return $this->current_position_sec;
        }

        $diff = now()->diffInSeconds($this->started_at);

        return $this->current_position_sec + $diff;
    }
}
