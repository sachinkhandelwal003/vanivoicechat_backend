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
        $imageUrl = asset('storage/1776759640_7915.svga');
        $img_key = 'avator';
        $text_key = 'name';
        $this->country = strtoupper($user->country);
        $this->data = [
            'id'      => $user->id,
            'uid'     => $user->uid,
            'name'    => $user->name,
            'message' => $message,
            'image'   => $imageUrl,
            'img_key'   => $img_key,
            'text_key'   => $text_key,
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
