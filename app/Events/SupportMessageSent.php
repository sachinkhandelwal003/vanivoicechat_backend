<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;


class SupportMessageSent implements ShouldBroadcastNow
{
    use SerializesModels;
    public $message;

    public function __construct($message)
    {
        
        $this->message = $message;
    }

    public function broadcastOn()
    {
        return [
            new Channel('support-channel.' . $this->message->conversation_id),
            // new Channel('support-global'),
        ];
    }

    public function broadcastAs()
    {
        return 'support.message';
    }
}
