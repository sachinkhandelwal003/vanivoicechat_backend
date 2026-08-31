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
        $this->country = strtoupper($room->country ?? 'IN');

        $this->data = [
            'room_id' => $room->id,
            'level'   => $level,
            'message'   => 'Treasure Chest Unlocked!',
            'banner'  => Helper::treasureBanner($level),
            'treasure_txt' => 'text',
            't_name' => 'name',
            't_img' => 'avator',
            't_lvl' => 'level',
        ];
    }

    public function broadcastOn(): Channel
    {
        return new Channel('treasure.' . $this->country);
    }

    public function broadcastAs(): string
    {
        return 'treasure.banner';
    }
}
