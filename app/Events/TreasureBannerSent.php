<?php

namespace App\Events;

use App\Helper\Helper;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class TreasureBannerSent implements ShouldBroadcastNow
{
    use SerializesModels;

    public $data;
    protected $country;

    public function __construct($room, $user, $level)
    {
        $this->country = strtoupper($room->country);

        $this->data = [
            'room_id' => $room->id,
            'level'   => $level,

            'banner' => Helper::treasureBanner($level),

            'user' => [
                'id'    => $user->id,
                'uid'   => $user->uid,
                'name'  => $user->name,
                'image' => Helper::showImage($user->image, true),
            ]
        ];
    }

    public function broadcastOn()
    {
        return new Channel('treasure.' . $this->country);
    }

    public function broadcastAs()
    {
        return 'treasure.banner';
    }
}
