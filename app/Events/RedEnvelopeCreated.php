<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RedEnvelopeCreated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function broadcastOn()
    {
        
        return new Channel('red-envelope.' . strtolower($this->data['country']));
    }

    public function broadcastAs()
    {
        return 'red.envelope.created';
    }
}
