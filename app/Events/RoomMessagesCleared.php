<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RoomMessagesCleared implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $roomId;
    public $clearedBy;
    public $clearType;
    public $message;

    public function __construct($roomId, $clearedBy, $clearType = 'all', $message = 'Room messages cleared')
    {
        $this->roomId = $roomId;
        $this->clearedBy = $clearedBy;
        $this->clearType = $clearType;
        $this->message = $message;
    }

    public function broadcastOn()
    {
        return new Channel('room.' . $this->roomId);
    }

    public function broadcastAs()
    {
        return 'room.messages.cleared';
    }

    public function broadcastWith()
    {
        return [
            'room_id' => $this->roomId,
            'cleared_by' => $this->clearedBy,
            'clear_type' => $this->clearType,
            'message' => $this->message,
        ];
    }
}