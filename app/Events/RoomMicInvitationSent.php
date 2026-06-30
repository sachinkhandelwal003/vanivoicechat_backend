<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RoomMicInvitationSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $roomId;
    public $targetUserId;
    public $seatNo;
    public $host;
    public $data;

    public function __construct($roomId, $targetUserId, $seatNo, $host, $data = [])
    {
        $this->roomId = (int) $roomId;
        $this->targetUserId = (int) $targetUserId;
        $this->seatNo = (int) $seatNo;
        $this->host = $host;
        $this->data = $data;
    }

    public function broadcastOn()
    {
        return new Channel('room.' . $this->roomId);
    }

    public function broadcastAs()
    {
        return 'room.mic.invitation.sent';
    }

    public function broadcastWith()
    {
        return [
            'room_id' => $this->roomId,
            'target_user_id' => $this->targetUserId,
            'seat_no' => $this->seatNo,
            'host' => $this->host,
            'data' => $this->data,
            'type' => 'room_mic_invite',
        ];
    }
}
