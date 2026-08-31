<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class GlobalGiftBannerSent implements ShouldBroadcastNow
{
    use SerializesModels;

    public $data;
    protected $country;

    public function __construct($sender, $gift)
    {
        $this->country = strtoupper($sender->country);

        $this->data = [
            'id'    => $sender->id,
            'uid'   => $sender->uid,
            'name'  => $sender->name,
            'image' => !empty($sender->image)
                ? \App\Helper\Helper::showImage($sender->image, true)
                : null,

            'gift' => [
                'id'    => $gift->id,
                'name'  => $gift->name,
                'image' => !empty($gift->cover)
                    ? \App\Helper\Helper::showImage($gift->cover, true)
                    : null,

                // Banner SVGA
                'banner' => asset('storage/gift_banner.svga'),
            ],
            'user_name' => 'name',
            'user_img' => 'avator',
            'gift_txt' => 'text',
            'gift_img' => 'image',
        ];
    }

    public function broadcastOn()
    {
        return new Channel('gift-banner.' . $this->country);
    }

    public function broadcastAs()
    {
        return 'gift.banner';
    }
}
