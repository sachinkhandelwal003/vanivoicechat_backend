<?php

namespace App\Events;

use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class RoomSeatUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $roomId;
    public $type;
    public $seats;
    public $seatNo;
    public $user;

    public function __construct($roomId, $type, $seats, $seatNo = null, $user = null)
    {
        $this->roomId = $roomId;
        $this->type = $type;
        $this->seats = $seats;
        $this->seatNo = $seatNo;
        $this->user = $user;
    }

    public function broadcastOn()
    {
        return new Channel('room.' . $this->roomId);
    }

    public function broadcastAs()
    {
        return 'room.seat.updated';
    }

    public function broadcastWith()
    {
        return [
            'room_id' => $this->roomId,
            'type' => $this->type,
            'seat_no' => $this->seatNo,
            'user' => $this->user,
            'seats' => $this->seats ?? null,
        ];
    }
}