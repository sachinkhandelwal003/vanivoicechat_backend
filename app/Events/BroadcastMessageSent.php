<?php
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class BroadcastMessageSent implements ShouldBroadcastNow
{
    use SerializesModels;

    public $data;
    protected $country;
    public function __construct($user, $message)
    {
        $imageUrl = asset('storage/broadcast.png');
        $this->country = strtoupper($user->country);
        $this->data = [
            'id'      => $user->id,
            'uid'     => $user->uid,
            'name'    => $user->name,
            'message' => $message,
            'image'   => $imageUrl,
        ];
    }

    public function broadcastOn()
    {
        return new Channel('broadcast.' . $this->country);
    }

    public function broadcastAs()
    {
        return 'broadcast.message';
    }
}
