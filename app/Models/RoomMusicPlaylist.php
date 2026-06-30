<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomMusicPlaylist extends Model
{
    use HasFactory;

    protected $table = 'room_music_playlist';

    protected $fillable = [
        'room_id',
        'user_id',
        'title',
        'artist',
        'audio_url',
        'duration_seconds',
        'position',
        'is_active',
    ];

    // Room relation
    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    // User who added song
    public function addedBy()
    {
        return $this->belongsTo(AppUser::class, 'user_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    // Active songs only
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    // Order by position
    public function scopeOrdered($query)
    {
        return $query->orderBy('position');
    }
}