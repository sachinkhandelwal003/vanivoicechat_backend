<?php

namespace App\Events;

use App\Models\AppUser;
use App\Models\RoomMusicPlaylist;
use App\Models\RoomMusicActivePlayer;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class RoomMusicPlayed implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $roomId;
    public $song;
    public $musicState;
    public $user;

    public function __construct(
        int $roomId,
        RoomMusicPlaylist $song,
        RoomMusicActivePlayer $musicState,
        AppUser $user
    ) {
        $this->roomId = $roomId;
        $this->song = $song;
        $this->musicState = $musicState;
        $this->user = $user;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('room.music.' . $this->roomId)
        ];
    }

    public function broadcastAs(): string
    {
        return 'room.music.played';
    }

    public function broadcastWith(): array
    {
        return [
            'room_id' => $this->roomId,
            'action' => 'play',
            'song' => [
                'id' => $this->song->id,
                'room_id' => $this->song->room_id,
                'title' => $this->song->title,
                'artist' => $this->song->artist,
                'audio_url' => $this->song->audio_url,
                'duration_seconds' => $this->song->duration_seconds,
                'position' => $this->song->position,
                'is_active' => $this->song->is_active,
            ],
            'music_state' => [
                'room_id' => $this->musicState->room_id,
                'current_playlist_id' => $this->musicState->playlist_id,
                'agora_player_id' => $this->musicState->agora_player_id,
                'player_name' => $this->musicState->player_name,
                'system_uid' => $this->musicState->system_uid,
                'is_music_active' => $this->musicState->is_active,
                'status' => $this->musicState->status,
                'current_position_sec' => $this->musicState->current_position_sec,
                'started_at' => optional($this->musicState->started_at)?->format('Y-m-d H:i:s'),
                'volume' => $this->musicState->volume,
                'is_loop' => $this->musicState->is_loop,
                'is_shuffle' => false,
                'last_action_by' => $this->musicState->started_by,
            ],
            'triggered_by' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'image' => $this->user->image,
            ],
        ];
    }
}
