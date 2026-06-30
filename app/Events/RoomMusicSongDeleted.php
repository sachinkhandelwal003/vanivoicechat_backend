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
use Carbon\Carbon;

class RoomMusicSongDeleted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $roomId;
    public $deletedSong;
    public $nextSong;
    public $musicState;
    public $user;
    public $message;

    public function __construct(
        int $roomId,
        $deletedSong,
        ?RoomMusicPlaylist $nextSong,
        ?RoomMusicActivePlayer $musicState,
        AppUser $user,
        string $message
    ) {
        $this->roomId = $roomId;
        $this->deletedSong = $deletedSong;
        $this->nextSong = $nextSong;
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
        return 'room.music.song.deleted';
    }

    private function getValue($data, string $key)
    {
        if (is_array($data)) {
            return $data[$key] ?? null;
        }

        if (is_object($data)) {
            return $data->{$key} ?? null;
        }

        return null;
    }

    public function broadcastWith(): array
    {
        return [
            'status' => true,
            'message' => $this->message,
            'room_id' => $this->roomId,
            'action' => 'delete_song',

            'deleted_song' => [
                'id' => $this->getValue($this->deletedSong, 'id'),
                'room_id' => $this->getValue($this->deletedSong, 'room_id'),
                'title' => $this->getValue($this->deletedSong, 'title'),
                'artist' => $this->getValue($this->deletedSong, 'artist'),
                'audio_url' => $this->getValue($this->deletedSong, 'audio_url')
                    ? \Helper::showImage($this->getValue($this->deletedSong, 'audio_url'), true)
                    : null,
                'duration_seconds' => $this->getValue($this->deletedSong, 'duration_seconds'),
                'position' => $this->getValue($this->deletedSong, 'position'),
                'is_active' => $this->getValue($this->deletedSong, 'is_active'),
            ],

            'next_song' => $this->nextSong ? [
                'id' => $this->nextSong->id,
                'room_id' => $this->nextSong->room_id,
                'title' => $this->nextSong->title,
                'artist' => $this->nextSong->artist,
                'audio_url' => $this->nextSong->audio_url
                    ? \Helper::showImage($this->nextSong->audio_url, true)
                    : null,
                'duration_seconds' => $this->nextSong->duration_seconds,
                'position' => $this->nextSong->position,
                'is_active' => $this->nextSong->is_active,
            ] : null,

            'music_state' => $this->musicState ? [
                'active_player_id' => $this->musicState->id,
                'room_id' => $this->musicState->room_id,
                'current_playlist_id' => $this->musicState->playlist_id,
                'agora_player_id' => $this->musicState->agora_player_id,
                'player_name' => $this->musicState->player_name,
                'system_uid' => $this->musicState->system_uid,
                'status' => $this->musicState->status,
                'current_position_sec' => $this->musicState->current_position_sec,
                'started_at' => !empty($this->musicState->started_at)
                    ? Carbon::parse($this->musicState->started_at)->format('Y-m-d H:i:s')
                    : null,
                'volume' => $this->musicState->volume,
                'is_loop' => (bool) $this->musicState->is_loop,
                'is_shuffle' => false,
                'last_action_by' => $this->user->id,
                'is_music_active' => (bool) $this->musicState->is_active,
                'agora_sequence' => (int) $this->musicState->agora_sequence,
            ] : null,

            'triggered_by' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'image' => $this->user->image,
            ],
        ];
    }
}
