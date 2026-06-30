<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RoomUserBanned implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $roomId;
    public $targetUserId;
    public $host;
    public $seats;
    public $onlineCount;
    public $onlineUsers;
    public $banDuration;
    public $expiresAt;

    public function __construct(
        $roomId,
        $targetUserId,
        $host,
        $seats,
        $onlineCount,
        $onlineUsers,
        $banDuration,
        $expiresAt
    ) {
        $this->roomId = $roomId;
        $this->targetUserId = (int) $targetUserId;
        $this->host = $host;
        $this->seats = $seats;
        $this->onlineCount = (int) $onlineCount;
        $this->onlineUsers = $onlineUsers;
        $this->banDuration = $banDuration;
        $this->expiresAt = $expiresAt;
    }

    public function broadcastOn()
    {
        return new Channel('room.' . $this->roomId);
    }

    public function broadcastAs()
    {
        return 'room.user.banned';
    }

    public function broadcastWith()
    {
        return [
            'room_id' => $this->roomId,
            'target_user_id' => $this->targetUserId,
            'host' => $this->host,
            'seats' => $this->seats,
            'online_count' => $this->onlineCount,
            'online_users' => $this->onlineUsers,
            'ban_duration' => $this->banDuration,
            'expires_at' => $this->expiresAt ? $this->expiresAt->toDateTimeString() : 'forever',
            'type' => 'ban',
        ];
    }
}
