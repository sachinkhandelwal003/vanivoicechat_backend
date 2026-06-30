<?php

namespace App\Events;

use App\Models\AppUser;
use App\Models\Gift;
use App\Models\GiftTransaction;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RoomGiftSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $roomId;
    public $gift;
    public $sender;
    public $receivers;
    public $transactions;
    public $multiplier;
    public $receiverCount;
    public $totalCost;
    public $message;
    public $type;

    public function __construct(
        int $roomId,
        Gift $gift,
        AppUser $sender,
        $receivers,
        $transactions,
        int $multiplier,
        int $receiverCount,
        $totalCost
    ) {
        $this->roomId = $roomId;
        $isLucky = $gift->cover_type === 'lucky';
        $this->gift = [
            'id' => $gift->id,
            'title' => $gift->title ?? $gift->name ?? null,
            'name' => $gift->name ?? $gift->title ?? null,
            'image' => $gift->gif ? \Helper::showImage($gift->gif, true) : ($gift->cover ? \Helper::showImage($gift->cover, true) : null),
            'price' => $gift->price,
            'is_lucky' => $isLucky,
            'lucky_sound' => $isLucky
                ? 'https://vanivoicechat.kotiboxglobaltech.online/public/storage/lucky_gift_sound/lucky-gift-sound.mp3'
                : null,
        ];

        $this->sender = [
            'id' => $sender->id,
            'name' => $sender->name,
            'uid' => $sender->uid ?? null,
            'image' => $sender->image ? \Helper::showImage($sender->image, true) : null,
            'total_points' => $sender->fresh()->total_points,
        ];

        $this->receivers = collect($receivers)->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'uid' => $user->uid ?? null,
                'image' => $user->image ? \Helper::showImage($user->image, true) : null,
                'total_points' => $user->total_points,
                'total_value' => $user->total_value,
                'user_level' => $user->user_level,
            ];
        })->values();

        $this->transactions = collect($transactions)->map(function ($txn) {
            return [
                'id' => $txn->id,
                'room_id' => $txn->room_id,
                'sender_id' => $txn->sender_id,
                'receiver_id' => $txn->receiver_id,
                'gift_id' => $txn->gift_id,
                'coin_value' => $txn->coin_value,
                'multiplier' => $txn->multiplier,
                'total_value' => $txn->total_value,
                'created_at' => $txn->created_at?->toDateTimeString(),
            ];
        })->values();

        $this->multiplier = $multiplier;
        $this->receiverCount = $receiverCount;
        $this->totalCost = $totalCost;
        $this->type = 'gift';

        $this->message = $receiverCount > 1
            ? "{$sender->name} sent {$gift->name} x{$multiplier} to {$receiverCount} users"
            : "{$sender->name} sent {$gift->name} x{$multiplier}";
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('room.' . $this->roomId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'room.gift.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'type' => $this->type,
            'room_id' => $this->roomId,
            'message' => $this->message,
            'gift' => $this->gift,
            'sender' => $this->sender,
            'receivers' => $this->receivers,
            'transactions' => $this->transactions,
            'multiplier' => $this->multiplier,
            'receiver_count' => $this->receiverCount,
            'total_cost' => $this->totalCost,
        ];
    }
}
