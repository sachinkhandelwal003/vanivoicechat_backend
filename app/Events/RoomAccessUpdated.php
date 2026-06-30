<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RoomAccessUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $roomId;

    public $type;
    public $settings;

    public function __construct($roomId, $type, $settings)
    {
        $this->roomId = (int) $roomId;
        $this->type = $type;
        $this->settings = $settings;
    }

    public function broadcastOn()
    {
        return new Channel('room.' . $this->roomId);
    }

    public function broadcastAs()
    {
        return 'room.access.updated';
    }

    public function broadcastWith()
    {
        return [
            'room_id' => $this->roomId,
            'type' => $this->type,
            'mic_permission' => (int) $this->settings->mic_permission,
            'message_permission' => (int) $this->settings->message_permission,
            'admin_can_play_music' => (int) $this->settings->admin_can_play_music,
        ];
    }
}
