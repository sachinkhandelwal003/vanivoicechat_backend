<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RoomLockUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $roomId;
    public $isLocked;
    public $isPasswordLocked;

    public function __construct($roomId, $isLocked, $isPasswordLocked = false)
    {
        $this->roomId = (int) $roomId;
        $this->isLocked = (bool) $isLocked;
        $this->isPasswordLocked = (bool) $isPasswordLocked;
    }

    public function broadcastOn()
    {
        return new Channel('room.' . $this->roomId);
    }

    public function broadcastAs()
    {
        return 'room.lock.updated';
    }

    public function broadcastWith()
    {
        return [
            'room_id' => $this->roomId,
            'is_locked' => $this->isLocked,
            'is_password_locked' => $this->isPasswordLocked,
        ];
    }
}
