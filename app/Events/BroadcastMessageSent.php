<?php
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class BroadcastMessageSent implements ShouldBroadcastNow
{
    use SerializesModels;

    public $data;

    public function __construct($user, $message)
    {
        $this->data = [
            'id'      => $user->id,
            'uid'     => $user->uid,
            'name'    => $user->name,
            'message' => $message,
        ];
    }

    public function broadcastOn()
    {
        return new Channel('broadcast.' . strtoupper(auth()->user()->country));
    }

    public function broadcastAs()
    {
        return 'broadcast.message';
    }
}
