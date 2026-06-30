<?php

namespace App\Http\Controllers\Api;

use App\Helper\Helper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GiftTransaction;
use App\Models\Room;
use App\Models\RoomRewardSlab;
use App\Models\AppUser;
use App\Models\RoomRewardClaim;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RoomRewardController extends Controller
{
    public function roomRewardDetails($room_id): JsonResponse
    {
        $room = Room::with('user')->find($room_id);

        if (!$room) {
            return response()->json([
                'status'  => false,
                'message' => 'Room not found',
            ], 404);
        }

        $authUser = Auth::user();

        $isRoomOwner = $authUser && (int) $authUser->id === (int) $room->user_id;

        $timezone = 'Asia/Kolkata';
        $now = Carbon::now($timezone);

        $rewardDate = $now->toDateString();

        $todayStartUtc = $now->copy()->startOfDay()->timezone('UTC');
        $todayEndUtc   = $now->copy()->endOfDay()->timezone('UTC');

        $todayContribution = (int) GiftTransaction::where('room_id', $room->id)
            ->whereBetween('created_at', [$todayStartUtc, $todayEndUtc])
            ->sum('total_value');



        //  Today only highest/latest achieved slab

        $todaySlab = RoomRewardSlab::where('status', 1)
            ->where('room_contribution', '<=', $todayContribution)
            ->orderByDesc('room_contribution')
            ->first();

        $todayReward = $todaySlab ? (int) $todaySlab->reward_coins : 0;

        if ($isRoomOwner && $todaySlab) {


            //  Same day ke old lower slab claim ko remove/update karo

            RoomRewardClaim::where('room_id', $room->id)
                ->where('owner_id', $room->user_id)
                ->where('reward_date', $rewardDate)
                ->where('is_claimed', 0)
                ->where('slab_id', '!=', $todaySlab->id)
                ->delete();

            RoomRewardClaim::firstOrCreate(
                [
                    'room_id'     => $room->id,
                    'owner_id'    => $room->user_id,
                    'reward_date' => $rewardDate,
                    'slab_id'     => $todaySlab->id,
                ],
                [
                    'room_contribution'      => $todayContribution,
                    'slab_room_contribution' => $todaySlab->room_contribution,
                    'slab_reward_coins'      => $todaySlab->reward_coins,
                    'reward_coins'           => $todaySlab->reward_coins,
                    'is_claimed'             => 0,
                ]
            );
        }

        $claimableRewards = collect();
        $availableReward = 0;

        if ($isRoomOwner) {
            $claimableRewards = RoomRewardClaim::where('room_id', $room->id)
                ->where('owner_id', $room->user_id)
                ->where('is_claimed', 0)
                ->orderBy('reward_date', 'asc')
                ->orderByDesc('slab_room_contribution')
                ->get()
                ->map(function ($claim) {
                    return [
                        'claim_id' => $claim->id,
                        'reward_date' => $claim->reward_date,

                        'slab_id' => $claim->slab_id,

                        'room_contribution' => (int) $claim->room_contribution,
                        'room_contribution_text' => $this->formatCoins($claim->room_contribution),

                        'slab_room_contribution' => (int) $claim->slab_room_contribution,
                        'slab_room_contribution_text' => $this->formatCoins($claim->slab_room_contribution),

                        'reward_coins' => (int) $claim->slab_reward_coins,
                        'reward_coins_text' => $this->formatCoins($claim->slab_reward_coins),

                        'receive_button_text' => 'Receive',
                    ];
                })
                ->values();


            // Available reward = all unclaimed latest day rewards

            $availableReward = (int) RoomRewardClaim::where('room_id', $room->id)
                ->where('owner_id', $room->user_id)
                ->where('is_claimed', 0)
                ->sum('slab_reward_coins');
        }

        $slabs = RoomRewardSlab::where('status', 1)
            ->orderBy('sort_order', 'asc')
            ->orderBy('room_contribution', 'asc')
            ->get()
            ->map(function ($slab) use ($todayContribution) {
                return [
                    'id' => $slab->id,

                    'room_contribution' => (int) $slab->room_contribution,
                    'room_contribution_text' => $this->formatCoins($slab->room_contribution),

                    'reward_coins' => (int) $slab->reward_coins,
                    'reward_coins_text' => $this->formatCoins($slab->reward_coins),

                    'is_reached' => $todayContribution >= $slab->room_contribution,
                ];
            })
            ->values();

        $owner = $room->user;

        return response()->json([
            'status'  => true,
            'message' => 'Room reward details fetched successfully',
            'data' => [
                'available_reward' => $availableReward,
                'available_reward_text' => $this->formatCoins($availableReward),

                'can_receive' => $claimableRewards->count() > 0,

                'today_contribution' => $todayContribution,
                'today_contribution_text' => $this->formatCoins($todayContribution),

                'today_rewards' => $todayReward,
                'today_rewards_text' => $this->formatCoins($todayReward),

                'reward_date' => $rewardDate,

                'room_owner' => [
                    'id' => $owner?->id,
                    'uid' => $owner?->uid,
                    'name' => $owner?->name,
                    'image' => $owner?->image ? Helper::showImage($owner->image) : null,
                ],

                'claimable_rewards' => $claimableRewards,

                'slabs' => $slabs,
            ],
        ]);
    }
    private function formatCoins($amount): string
    {
        $amount = (float) $amount;

        if ($amount >= 1000000) {
            return rtrim(rtrim(number_format($amount / 1000000, 2), '0'), '.') . 'M';
        }

        if ($amount >= 1000) {
            return rtrim(rtrim(number_format($amount / 1000, 2), '0'), '.') . 'K';
        }

        return (string) (int) $amount;
    }

    public function claimRoomReward(Request $request): JsonResponse
    {
        $authUser = Auth::user();

        if (!$authUser) {
            return response()->json([
                'status'  => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        DB::beginTransaction();

        try {
            $claim = RoomRewardClaim::where('id', $request->claim_id)
                ->lockForUpdate()
                ->first();

            if (!$claim) {
                DB::rollBack();

                return response()->json([
                    'status'  => false,
                    'message' => 'Reward claim not found',
                ], 404);
            }

            if ((int) $claim->owner_id !== (int) $authUser->id) {
                DB::rollBack();

                return response()->json([
                    'status'  => false,
                    'message' => 'Only room owner can claim this reward',
                ], 403);
            }

            if ((int) $claim->is_claimed === 1) {
                DB::rollBack();

                return response()->json([
                    'status'  => false,
                    'message' => 'Reward already claimed',
                ], 422);
            }

            $rewardCoins = (int) $claim->slab_reward_coins;

            if ($rewardCoins <= 0) {
                DB::rollBack();

                return response()->json([
                    'status'  => false,
                    'message' => 'Invalid reward amount',
                ], 422);
            }

            // 15% system cut
            $systemCommission = (int) floor($rewardCoins * 15 / 100);

            // 85% owner receive
            $ownerReceiveCoins = $rewardCoins - $systemCommission;

            AppUser::where('id', $authUser->id)
                ->increment('total_points', $ownerReceiveCoins);

            $claim->update([
                'system_commission'  => $systemCommission,
                'owner_reward_coins' => $ownerReceiveCoins,
                'reward_coins'       => $ownerReceiveCoins,
                'is_claimed'         => 1,
                'claimed_at'         => now(),
            ]);

            $updatedUser = AppUser::find($authUser->id);

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => 'Reward claimed successfully',
                'data' => [
                    'claim_id' => $claim->id,

                    'reward_date' => $claim->reward_date,

                    'total_reward_coins' => $rewardCoins,
                    'total_reward_coins_text' => $this->formatCoins($rewardCoins),

                    'system_commission' => $systemCommission,
                    'system_commission_text' => $this->formatCoins($systemCommission),

                    'owner_received_coins' => $ownerReceiveCoins,
                    'owner_received_coins_text' => $this->formatCoins($ownerReceiveCoins),

                    'current_total_points' => (int) $updatedUser->total_points,
                    'current_total_points_text' => $this->formatCoins($updatedUser->total_points),
                ],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'status'  => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
