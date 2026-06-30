<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RoomSeatMicUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $roomId;
    public $seatNo;
    public $isOnMic;
    public $seats;
    public $user;

    /**
     * Create a new event instance.
     */
    public function __construct($roomId, $seatNo, $isOnMic, $seats, $user)
    {
        $this->roomId = $roomId;
        $this->seatNo = (int) $seatNo;
        $this->isOnMic = (int) $isOnMic;
        $this->seats = $seats;
        $this->user = $user;
    }

    /**
     * Broadcast channel
     */
    public function broadcastOn()
    {
        // as you said: NORMAL channel (not private/presence)
        return new Channel('room.' . $this->roomId);
    }

    /**
     * Event name (important for frontend)
     */
    public function broadcastAs()
    {
        return 'room.seat.mic.updated';
    }

    /**
     * Data sent to frontend
     */
    public function broadcastWith()
    {
        return [
            'room_id' => $this->roomId,
            'seat_no' => $this->seatNo,
            'is_on_mic' => $this->isOnMic,

            // full updated seats list (IMPORTANT for sync)
            'seats' => $this->seats,

            // user info
            'user' => $this->user,

            // optional type for handling
            'type' => $this->isOnMic ? 'unmute' : 'mute',
        ];
    }
}