<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RoomGiftSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $roomId;
    public array $payload;

    public function __construct(int $roomId, array $payload)
    {
        $this->roomId = $roomId;
        $this->payload = $payload;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('room.' . $this->roomId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'room.gift.sent';
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
