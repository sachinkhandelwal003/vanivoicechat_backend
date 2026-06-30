<?php

namespace App\Traits;

use App\Models\Room;
use App\Models\RoomSetting;
use App\Models\RoomUserRole;

trait RoomPermissionTrait
{
    // 🔹 user role get
    public function getRoomUserRole($roomId, $userId)
    {
        $room = Room::select('id', 'user_id')->find($roomId);

        if (!$room) return null;

        if ((int) $room->user_id === (int) $userId) {
            return 'owner';
        }

        return RoomUserRole::where('room_id', $roomId)
            ->where('user_id', $userId)
            ->value('role') ?? 'guest';
    }

    // 🔹 room setting get
    public function getRoomSetting($roomId)
    {
        return RoomSetting::firstOrCreate(
            ['room_id' => $roomId],
            [
                'mic_permission' => 0,
                'message_permission' => 0,
                'admin_can_play_music' => 0,
            ]
        );
    }

    // 🔹 mic permission check
    public function canUseMic($roomId, $userId)
    {
        $setting = $this->getRoomSetting($roomId);
        $role = $this->getRoomUserRole($roomId, $userId);

        if ((int) $setting->mic_permission === 0) {
            return true;
        }

        if ((int) $setting->mic_permission === 1) {
            return in_array($role, ['owner', 'admin']);
        }

        if ((int) $setting->mic_permission === 2) {
            return in_array($role, ['owner', 'admin', 'member']);
        }

        return false;
    }

    // 🔹 message permission
    public function canSendMessage($roomId, $userId)
    {
        $setting = $this->getRoomSetting($roomId);
        $role = $this->getRoomUserRole($roomId, $userId);

        if ((int) $setting->message_permission === 0) {
            return true;
        }

        if ((int) $setting->message_permission === 1) {
            return in_array($role, ['owner', 'admin', 'member', 'guest']);
        }

        return false;
    }

    // 🔹 admin music permission
    public function canPlayMusic($roomId, $userId)
    {
        $setting = $this->getRoomSetting($roomId);
        $role = $this->getRoomUserRole($roomId, $userId);

        if ($role === 'owner') {
            return true;
        }

        if ($role === 'admin' && (int) $setting->admin_can_play_music === 1) {
            return true;
        }

        return false;
    }
}