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

class RoomMusicResumed implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $roomId;
    public $song;
    public $musicState;
    public $user;
    public $message;

    public function __construct(
        int $roomId,
        ?RoomMusicPlaylist $song,
        RoomMusicActivePlayer $musicState,
        AppUser $user,
        string $message = 'Music resumed successfully'
    ) {
        $this->roomId = $roomId;
        $this->song = $song;
        $this->musicState = $musicState;
        $this->user = $user;
        $this->message = $message;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('room.music.' . $this->roomId)
        ];
    }

    public function broadcastAs(): string
    {
        return 'room.music.resumed';
    }

    public function broadcastWith(): array
    {
        return [
            'status' => true,
            'message' => $this->message,
            'room_id' => $this->roomId,
            'action' => 'resume',
            'song' => $this->song ? [
                'id' => $this->song->id,
                'room_id' => $this->song->room_id,
                'title' => $this->song->title,
                'artist' => $this->song->artist,
                'duration_seconds' => $this->song->duration_seconds,
                'position' => $this->song->position,
                'is_active' => $this->song->is_active,
                'audio_url' => $this->song->audio_url
                    ? \Helper::showImage($this->song->audio_url, true)
                    : null,
            ] : null,
            'music_state' => [
                'room_id' => $this->musicState->room_id,
                'current_playlist_id' => $this->musicState->playlist_id,
                'active_player_id' => $this->musicState->id,
                'agora_player_id' => $this->musicState->agora_player_id,
                'player_name' => $this->musicState->player_name,
                'system_uid' => $this->musicState->system_uid,
                'status' => $this->musicState->status,
                'current_position_sec' => $this->musicState->current_position_sec,
                'started_at' => $this->musicState->started_at
                    ? $this->musicState->started_at->format('Y-m-d H:i:s')
                    : null,
                'volume' => $this->musicState->volume,
                'is_loop' => (bool) $this->musicState->is_loop,
                'is_shuffle' => false,
                'last_action_by' => $this->user->id,
                'is_music_active' => (bool) $this->musicState->is_active,
                'agora_sequence' => (int) $this->musicState->agora_sequence,
            ],
            'triggered_by' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'image' => $this->user->image,
            ],
        ];
    }
}
