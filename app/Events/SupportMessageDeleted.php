<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class SupportMessageDeleted implements ShouldBroadcastNow
{
    use SerializesModels;

    public $conversation_id;
    public $message_id;

    public function __construct($conversation_id, $message_id)
    {
        $this->conversation_id = $conversation_id;
        $this->message_id = $message_id;
    }

    public function broadcastOn()
    {
        return [
            new Channel('support-channel.' . $this->conversation_id),
        ];
    }

    public function broadcastAs()
    {
        return 'support.message.deleted';
    }
}
