<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageDeleted implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public $messageId;
    public $senderId;
    public $receiverId;

    public function __construct($messageId, $senderId, $receiverId)
    {
        $this->messageId = $messageId;
        $this->senderId = $senderId;
        $this->receiverId = $receiverId;
    }

    public function broadcastOn()
    {
        return [
            new Channel('chat.' . $this->senderId),
            new Channel('chat.' . $this->receiverId),
        ];
    }

    public function broadcastAs()
    {
        return 'message.deleted';
    }
}
