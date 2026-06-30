<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class RoomSeatSettingUpdated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public int $roomId;
    public array $data;

    public function __construct(int $roomId, array $data)
    {
        $this->roomId = $roomId;
        $this->data = $data;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('room.' . $this->roomId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'room.seat.setting.updated';
    }

    public function broadcastWith(): array
    {
        return $this->data;
    }
}