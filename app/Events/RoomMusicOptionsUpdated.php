<?php

namespace App\Events;

use App\Models\AppUser;
use App\Models\RoomMusicActivePlayer;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Carbon\Carbon;

class RoomMusicOptionsUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $roomId;
    public $musicState;
    public $user;

    public function __construct(int $roomId, RoomMusicActivePlayer $musicState, AppUser $user)
    {
        $this->roomId = $roomId;
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
        return 'room.music.options.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'room_id' => $this->roomId,
            'action' => 'music_options_updated',
            'music_state' => [
                'room_id' => $this->musicState->room_id,
                'current_playlist_id' => $this->musicState->playlist_id,
                'active_player_id' => $this->musicState->id,
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
            ],
            'triggered_by' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'image' => $this->user->image,
            ],
        ];
    }
}
