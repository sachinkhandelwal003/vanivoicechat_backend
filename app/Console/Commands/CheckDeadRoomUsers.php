<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\RoomPresence;
use App\Models\RoomSeat;
use App\Models\User;
use App\Models\RoomMusicActivePlayer;
use App\Events\RoomPresenceUpdated;
use App\Events\RoomSeatUpdated;
use Illuminate\Support\Facades\DB;

class CheckDeadRoomUsers extends Command
{
    protected $signature = 'room:check-dead-users';

    protected $description = 'Remove inactive room users';

    public function handle()
    {
        $expiredUsers = RoomPresence::whereNotNull('last_ping_at')
            ->where('last_ping_at', '<', now()->subSeconds(30))
            ->get();

        foreach ($expiredUsers as $presence) {

            DB::beginTransaction();

            try {

                $roomId = $presence->room_id;
                $userId = $presence->user_id;

                $user = User::find($userId);

                $seat = RoomSeat::where('room_id', $roomId)
                    ->where('user_id', $userId)
                    ->first();

                $oldSeatNo = $seat->seat_no ?? null;
                $oldIsOnMic = $seat->is_on_mic ?? 0;

                \Log::info("Dead user detected", [
                    'room_id' => $roomId,
                    'user_id' => $userId,
                ]);

                /*
                |--------------------------------------------------------------------------
                | DELETE ROOM SEAT
                |--------------------------------------------------------------------------
                */

                RoomSeat::where('room_id', $roomId)
                    ->where('user_id', $userId)
                    ->delete();

                /*
                |--------------------------------------------------------------------------
                | DELETE ROOM PRESENCE
                |--------------------------------------------------------------------------
                */

                RoomPresence::where('room_id', $roomId)
                    ->where('user_id', $userId)
                    ->delete();

                /*
                |--------------------------------------------------------------------------
                | STOP MUSIC
                |--------------------------------------------------------------------------
                */

                RoomMusicActivePlayer::where('room_id', $roomId)
                    ->where('started_by', $userId)
                    ->update([
                        'status' => 'stopped',
                        'is_active' => false,
                        // 'started_at' => null,
                    ]);

                $onlineCount = RoomPresence::where('room_id', $roomId)->count();

                $currentUser = [
                    'id' => $user->id ?? null,
                    'name' => $user->name ?? null,
                    'image' => !empty($user?->image)
                        ? \App\Helper\Helper::showImage($user->image, true)
                        : null,
                    'seat_no' => $oldSeatNo,
                    'is_on_mic' => $oldIsOnMic,
                ];

                // Same logic as leave API
                $seats = app(\App\Http\Controllers\Api\RoomController::class)
                    ->getRoomSeats($roomId);

                DB::commit();

                /*
                |--------------------------------------------------------------------------
                | BROADCAST REALTIME EVENTS
                |--------------------------------------------------------------------------
                */

                broadcast(new RoomPresenceUpdated(
                    $roomId,
                    $onlineCount,
                    [],
                    'leave',
                    $currentUser
                ));

                broadcast(new RoomSeatUpdated(
                    $roomId,
                    'leave',
                    $seats,
                    $oldSeatNo,
                    $currentUser
                ));

                \Log::info("Dead user removed and broadcasted", [
                    'room_id' => $roomId,
                    'user_id' => $userId,
                ]);
            } catch (\Throwable $e) {

                DB::rollBack();

                \Log::error('Dead user cleanup failed', [
                    'room_id' => $presence->room_id ?? null,
                    'user_id' => $presence->user_id ?? null,
                    'error' => $e->getMessage(),
                    'line' => $e->getLine(),
                ]);
            }
        }

        return Command::SUCCESS;
    }
}
