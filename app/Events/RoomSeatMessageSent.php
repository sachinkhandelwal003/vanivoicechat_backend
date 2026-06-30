<?php

namespace App\Events;

use App\Models\RoomMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class RoomSeatMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public int $roomId;
    public array $messageData;

    public function __construct(int $roomId, array $messageData)
    {
        $this->roomId = $roomId;
        $this->messageData = $messageData;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('room.' . $this->roomId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'room.seat.message.sent';
    }

    public function broadcastWith(): array
    {
        return $this->messageData;
    }
}
