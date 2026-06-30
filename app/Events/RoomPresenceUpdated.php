<?php

namespace App\Events;

use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;


class RoomPresenceUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $roomId;
    public $onlineCount;
    public $users;
    public $type;
    public $user;

    public function __construct($roomId, $onlineCount, $users = [], $type = null, $user = null)
    {
        $this->roomId = $roomId;
        $this->onlineCount = $onlineCount;
        $this->users = $users;
        $this->type = $type; // join / leave
        $this->user = $user; // current join/leave user
    }

    public function broadcastOn()
    {
        return new Channel('room.' . $this->roomId);
    }

    public function broadcastAs()
    {
        return 'room.presence.updated';
    }

    public function broadcastWith()
    {
        return [
            'room_id' => $this->roomId,
            'online_count' => $this->onlineCount,
            'users' => $this->users,
            'type' => $this->type,
            'user' => $this->user,
        ];
    }
}