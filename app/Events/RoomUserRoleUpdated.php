<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RoomUserRoleUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $roomId;
    public $targetUserId;
    public $role;
    public $updatedBy;
    public $user;

    public function __construct($roomId, $targetUserId, $role, $updatedBy, $user)
    {
        $this->roomId = (int) $roomId;
        $this->targetUserId = (int) $targetUserId;
        $this->role = $role;
        $this->updatedBy = $updatedBy;
        $this->user = $user;
    }

    public function broadcastOn()
    {
        return new Channel('room.' . $this->roomId);
    }

    public function broadcastAs()
    {
        return 'room.user.role.updated';
    }

    public function broadcastWith()
    {
        return [
            'room_id' => $this->roomId,
            'target_user_id' => $this->targetUserId,
            'role' => $this->role,
            'updated_by' => $this->updatedBy,
            'user' => $this->user,
            'type' => 'role_update',
        ];
    }
}