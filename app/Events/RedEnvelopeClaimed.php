<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RedEnvelopeClaimed implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('room.' . $this->data['room_id']),
        ];
    }

    public function broadcastAs(): string
    {
        return 'red.envelope.claimed';
    }

    public function broadcastWith(): array
    {
        return $this->data;
    }
}
