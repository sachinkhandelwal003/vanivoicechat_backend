<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use App\Models\RoomMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Helper\Helper;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;


class RoomMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $roomMessage;


    public function __construct(RoomMessage $roomMessage)
    {
        $this->roomMessage = $roomMessage;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('room.' . $this->roomMessage->room_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'room.message.sent';
    }

    public function broadcastWith(): array
    {
        $image = Helper::showImage($this->roomMessage->user?->image, true);
        return [
            'id' => $this->roomMessage->id,
            'room_id' => $this->roomMessage->room_id,
            'user_id' => $this->roomMessage->user_id,
            'message' => $this->roomMessage->message,
            'message_type' => $this->roomMessage->message_type,
            'chat_bubble' => $this->roomMessage->chat_bubble ?? null,
            'created_at' => $this->roomMessage->created_at?->toDateTimeString(),
            'user' => [
                'id' => $this->roomMessage->user?->id,
                'name' => $this->roomMessage->user?->name,
                'nickname_meta' => $this->roomMessage->user?->nickname_meta
                    ?? [
                        'animated' => false,
                        'color' => null,
                        'effect' => null,
                    ],
                'gender' => $this->roomMessage->user?->gender,
                'uid' => $this->roomMessage->user?->uid,
                'image' => $image,
                'wealth_icon' => $this->roomMessage->user?->wealth_icon ?? null,
                'charm_icon' => $this->roomMessage->user?->charm_icon ?? null,
                'medals' => $this->roomMessage->user?->medals ?? [],
                // 'role_badge' => $this->roomMessage->user?->role_badge ?? []
            ],
        ];
    }
}
