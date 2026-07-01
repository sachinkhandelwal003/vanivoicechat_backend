<?php

namespace App\Http\Controllers\Api;

use App\Helper\Helper;
use App\Http\Controllers\Controller;
use App\Models\TreasureLevel;
use App\Models\VipTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class TreasureController extends Controller
{
    public function details(Request $request): JsonResponse
    {
        $request->validate([
            'room_id' => 'required|integer|exists:rooms,id',
        ]);

        $roomId = (int) $request->room_id;

        $totalPoints = (int) DB::table('gift_transactions')
            ->where('room_id', $roomId)
            ->sum('total_value');

        $levels = TreasureLevel::with(['rewards' => function ($q) {
            $q->where('status', 1);
        }])
            ->where('status', 1)
            ->orderBy('level', 'asc')
            ->get()
            ->values();

        if ($levels->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No treasure levels found',
                'data' => null,
            ]);
        }

        $curtainImages = [
            1 => asset('storage/curtain/green_bg.png'),
            2 => asset('storage/curtain/blue_bg.png'),
            3 => asset('storage/curtain/purple_bg.png'),
            4 => asset('storage/curtain/red_bg.png'),
            5 => asset('storage/curtain/black_bg.png'),
        ];

        $openChestImages = [
            1 => asset('storage/open_chest/open_green_chest.webp'),
            2 => asset('storage/open_chest/open_blue_chest.webp'),
            3 => asset('storage/open_chest/open_purple_chest.webp'),
            4 => asset('storage/open_chest/open_red_chest.webp'),
            5 => asset('storage/open_chest/open_black_chest.webp'),
        ];

        $topContributor = [
            1 => asset('storage/treasure_frame/gold_frame.webp'),
            2 => asset('storage/treasure_frame/silver_frame.webp'),
            3 => asset('storage/treasure_frame/bronze_frame.webp'),
        ];

        $prizeFrame = [
            1 => asset('storage/treasure_frame/frame_1.webp'),
            2 => asset('storage/treasure_frame/frame_2.webp'),
        ];


        // Level Wise Top 3 Contributors

        $transactions = DB::table('gift_transactions')
            ->where('room_id', $roomId)
            ->where('total_value', '>', 0)
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get(['sender_id', 'total_value']);

        $levelProgress = [];
        $levelContributors = [];

        foreach ($levels as $level) {
            $levelProgress[$level->id] = 0;
            $levelContributors[$level->id] = [];
        }

        $currentLevelIndex = 0;

        foreach ($transactions as $transaction) {
            $remainingGiftValue = (int) $transaction->total_value;
            $senderId = (int) $transaction->sender_id;

            while ($remainingGiftValue > 0 && isset($levels[$currentLevelIndex])) {
                $currentLevel = $levels[$currentLevelIndex];

                $levelId = $currentLevel->id;
                $levelTarget = (int) $currentLevel->target_points;

                $alreadyFilled = (int) $levelProgress[$levelId];
                $levelRemaining = $levelTarget - $alreadyFilled;

                if ($levelRemaining <= 0) {
                    $currentLevelIndex++;
                    continue;
                }

                $allocatedPoints = min($remainingGiftValue, $levelRemaining);

                if (!isset($levelContributors[$levelId][$senderId])) {
                    $levelContributors[$levelId][$senderId] = 0;
                }

                $levelContributors[$levelId][$senderId] += $allocatedPoints;
                $levelProgress[$levelId] += $allocatedPoints;

                $remainingGiftValue -= $allocatedPoints;

                if ($levelProgress[$levelId] >= $levelTarget) {
                    $currentLevelIndex++;
                }
            }
        }

        $allContributorIds = collect($levelContributors)
            ->flatMap(function ($contributors) {
                return array_keys($contributors);
            })
            ->unique()
            ->values();

        $users = DB::table('app_users')
            ->whereIn('id', $allContributorIds)
            ->get(['id', 'name', 'image'])
            ->keyBy('id');

        $topContributorsByLevel = [];

        foreach ($levelContributors as $levelId => $contributors) {
            arsort($contributors);

            $topContributorsByLevel[$levelId] = collect($contributors)
                ->take(3)
                ->values()
                ->map(function ($points, $index) use ($contributors, $users, $topContributor) {
                    $userIds = array_keys($contributors);
                    $rank = $index + 1;
                    $userId = (int) $userIds[$index];

                    $user = $users->get($userId);

                    return [
                        'rank' => $rank,
                        'id' => $userId,
                        'name' => $user->name ?? null,
                        'image' => !empty($user->image)
                            ? Helper::showImage($user->image)
                            : null,
                        'contributor_frame' => $topContributor[$rank] ?? null,
                        'contribution_points' => (int) $points,
                        'contribution_points_text' => $this->formatNumber((int) $points),
                    ];
                });
        }


        // Active Level Calculate

        $activeLevel = $levels->last();
        $activeLevelCurrentPoints = 0;
        $activeLevelTargetPoints = (int) $activeLevel->target_points;

        $completedBeforePoints = 0;

        foreach ($levels as $level) {
            $levelTargetPoints = (int) $level->target_points;
            $levelCompleteAt = $completedBeforePoints + $levelTargetPoints;

            if ($totalPoints < $levelCompleteAt) {
                $activeLevel = $level;
                $activeLevelTargetPoints = $levelTargetPoints;
                $activeLevelCurrentPoints = max(0, $totalPoints - $completedBeforePoints);

                if ($activeLevelCurrentPoints > $activeLevelTargetPoints) {
                    $activeLevelCurrentPoints = $activeLevelTargetPoints;
                }

                break;
            }

            $completedBeforePoints += $levelTargetPoints;
        }

        if ($totalPoints >= $levels->sum('target_points')) {
            $activeLevel = $levels->last();
            $activeLevelTargetPoints = (int) $activeLevel->target_points;
            $activeLevelCurrentPoints = $activeLevelTargetPoints;
        }

        $completedBeforePointsForLevels = 0;

        return response()->json([
            'status' => true,
            'message' => 'Treasure details fetched successfully',

            'data' => [
                'room_id' => $roomId,

                'total_points' => $totalPoints,
                'total_points_text' => $this->formatNumber($totalPoints),

                'current_level' => (int) $activeLevel->level,
                'current_level_text' => 'Lv.' . $activeLevel->level,

                'current_points' => $activeLevelCurrentPoints,
                'current_points_text' => $this->formatNumber($activeLevelCurrentPoints),

                'target_points' => $activeLevelTargetPoints,
                'target_points_text' => $this->formatNumber($activeLevelTargetPoints),

                'progress_text' => $activeLevelCurrentPoints . '/' . $activeLevelTargetPoints,

                'levels' => $levels->map(function ($level) use (
                    $roomId,
                    $totalPoints,
                    $curtainImages,
                    &$completedBeforePointsForLevels,
                    $topContributorsByLevel,
                    $openChestImages,
                    $prizeFrame
                ) {
                    $levelTargetPoints = (int) $level->target_points;
                    $levelCompleteAt = $completedBeforePointsForLevels + $levelTargetPoints;

                    if ($totalPoints >= $levelCompleteAt) {
                        $levelCurrentPoints = $levelTargetPoints;
                    } elseif ($totalPoints > $completedBeforePointsForLevels) {
                        $levelCurrentPoints = $totalPoints - $completedBeforePointsForLevels;
                    } else {
                        $levelCurrentPoints = 0;
                    }

                    if ($levelCurrentPoints > $levelTargetPoints) {
                        $levelCurrentPoints = $levelTargetPoints;
                    }

                    $isUnlocked = $levelCurrentPoints >= $levelTargetPoints;

                    $itemRewardIds = $level->rewards
                        ->where('reward_type', '!=', 'coins')
                        ->pluck('id')
                        ->toArray();

                    $claimedItemRewardIds = DB::table('treasure_level_claims')
                        ->where('room_id', $roomId)
                        ->where('treasure_level_id', $level->id)
                        ->where('reward_type', '!=', 'coins')
                        ->pluck('treasure_level_reward_id')
                        ->toArray();

                    $allItemsClaimed = empty(array_diff($itemRewardIds, $claimedItemRewardIds));

                    $coinReward = $level->rewards->where('reward_type', 'coins')->first();

                    $coinPoolCompleted = true;

                    if ($coinReward) {
                        $distributedCoins = (int) DB::table('treasure_level_claims')
                            ->where('room_id', $roomId)
                            ->where('treasure_level_id', $level->id)
                            ->where('reward_type', 'coins')
                            ->sum('coins');

                        $coinPoolCompleted = $distributedCoins >= (int) $coinReward->coins;
                    }

                    $isChestOpened = $allItemsClaimed && $coinPoolCompleted;

                    $completedBeforePointsForLevels += $levelTargetPoints;

                    return [
                        'id' => $level->id,
                        'level' => (int) $level->level,
                        'level_text' => 'Lv.' . $level->level,

                        'curtain_img' => $curtainImages[$level->level] ?? null,

                        'total_target_points' => $levelTargetPoints,

                        'current_points' => $levelCurrentPoints,
                        'current_points_text' => $this->formatNumber($levelCurrentPoints),

                        'target_points' => $levelTargetPoints,
                        'target_points_text' => $this->formatNumber($levelTargetPoints),

                        'progress_text' => $levelCurrentPoints . '/' . $levelTargetPoints,

                        'progress_percentage' => $levelTargetPoints > 0
                            ? round(($levelCurrentPoints / $levelTargetPoints) * 100, 2)
                            : 0,

                        'is_unlocked' => $isUnlocked,
                        'is_chest_opened' => $isChestOpened,
                        'chest_status' => $isChestOpened ? 'opened' : 'closed',

                        'chest_image' => $isChestOpened
                            ? ($openChestImages[$level->level] ?? null)
                            : ($level->chest_image ? Helper::showImage($level->chest_image) : null),

                        'top_contributors' => $topContributorsByLevel[$level->id] ?? [],

                        // 'chest_image' => $level->chest_image
                        //     ? Helper::showImage($level->chest_image)
                        //     : null,

                        'rewards' => $level->rewards->map(function ($reward, $index) use ($prizeFrame) {
                            $prizeFrameImage = $index === 0
                                ? ($prizeFrame[1] ?? null)
                                : ($prizeFrame[2] ?? null);
                            return [
                                'id' => $reward->id,
                                'reward_type' => $reward->reward_type,
                                'reward_item_id' => $reward->reward_item_id,

                                'prize_frame' => $prizeFrameImage,
                                'coins' => (int) $reward->coins,
                                'coins_text' => $reward->coins > 0
                                    ? $this->formatNumber($reward->coins)
                                    : null,

                                'valid_days' => $reward->valid_days,
                                'valid_days_text' => $reward->valid_days
                                    ? $reward->valid_days . 'Days'
                                    : null,

                                'reward_image' => $reward->reward_image
                                    ? Helper::showImage($reward->reward_image)
                                    : null,
                            ];
                        })->values(),
                    ];
                })->values(),
            ],
        ]);
    }

    private function formatNumber($number): string
    {
        $number = (int) $number;

        if ($number >= 10000000) {
            return round($number / 10000000, 1) . 'Cr';
        }

        if ($number >= 100000) {
            return round($number / 100000, 1) . 'L';
        }

        if ($number >= 1000) {
            return round($number / 1000, 1) . 'K';
        }

        return (string) $number;
    }


    public function claim(Request $request): JsonResponse
    {
        $request->validate([
            'room_id'  => 'required|integer|exists:rooms,id',
            'level_id' => 'required|integer|exists:treasure_levels,id',
        ]);

        $user = Auth::user();
        $roomId = (int) $request->room_id;
        $levelId = (int) $request->level_id;

        DB::beginTransaction();

        try {
            $level = TreasureLevel::with(['rewards' => function ($q) {
                $q->where('status', 1);
            }])
                ->where('id', $levelId)
                ->where('status', 1)
                ->lockForUpdate()
                ->first();

            if (!$level) {
                DB::rollBack();

                return response()->json([
                    'status' => false,
                    'message' => 'Treasure level not found',
                ]);
            }

            $requiredPoints = (int) TreasureLevel::where('status', 1)
                ->where('level', '<=', $level->level)
                ->sum('target_points');

            $totalPoints = (int) DB::table('gift_transactions')
                ->where('room_id', $roomId)
                ->sum('total_value');

            if ($totalPoints < $requiredPoints) {
                DB::rollBack();

                return response()->json([
                    'status' => false,
                    'message' => 'This treasure level is not completed yet',
                ]);
            }


            //  Same user same room same level only one claim

            $alreadyClaimed = DB::table('treasure_level_claims')
                ->where('room_id', $roomId)
                ->where('user_id', $user->id)
                ->where('treasure_level_id', $level->id)
                ->lockForUpdate()
                ->exists();

            if ($alreadyClaimed) {
                DB::rollBack();

                return response()->json([
                    'status' => false,
                    'message' => 'You have already claimed this treasure level reward',
                ]);
            }

            $rewards = $level->rewards->values();

            if ($rewards->isEmpty()) {
                DB::rollBack();

                return response()->json([
                    'status' => false,
                    'message' => 'No rewards found for this level',
                ]);
            }


            //  Coin pool remaining check

            $coinReward = $rewards->where('reward_type', 'coins')->first();
            $coinRemaining = 0;

            if ($coinReward) {
                $totalCoinPool = (int) $coinReward->coins;

                $alreadyDistributedCoins = (int) DB::table('treasure_level_claims')
                    ->where('room_id', $roomId)
                    ->where('treasure_level_id', $level->id)
                    ->where('reward_type', 'coins')
                    ->lockForUpdate()
                    ->sum('coins');

                $coinRemaining = max(0, $totalCoinPool - $alreadyDistributedCoins);
            }


            //   Coin pool khatam ho gaya to coins reward random list se remove

            $alreadyClaimedRewardIds = DB::table('treasure_level_claims')
                ->where('room_id', $roomId)
                ->where('treasure_level_id', $level->id)
                ->where('reward_type', '!=', 'coins')
                ->pluck('treasure_level_reward_id')
                ->toArray();

            $availableRewards = $rewards->filter(function ($reward) use ($coinRemaining, $alreadyClaimedRewardIds) {

                // Coins reward tab tak available rahega jab tak coin pool remaining hai
                if ($reward->reward_type === 'coins') {
                    return $coinRemaining > 0;
                }

                // Non-coin item ek baar claim ho gaya to dubara nahi aayega
                if (in_array($reward->id, $alreadyClaimedRewardIds)) {
                    return false;
                }

                return true;
            })->values();

            if ($availableRewards->isEmpty()) {
                DB::rollBack();

                return response()->json([
                    'status' => false,
                    'message' => 'All rewards are already distributed for this level',
                ]);
            }


            //   Random reward select

            $selectedReward = $availableRewards->random();

            $claimCoins = 0;

            $allowedItemTypes = [
                'frame',
                'theme',
                'voice',
                'chat_bubble',
                'entry',
                'entry_tag',
                'vip',
                'id',
                'profile_card',
            ];


            //   Coins reward

            if ($selectedReward->reward_type === 'coins') {
                $minCoin = min(20, $coinRemaining);
                $maxCoin = min(500, $coinRemaining);

                if ($coinRemaining <= 100) {
                    $claimCoins = $coinRemaining;
                } else {
                    $claimCoins = rand($minCoin, $maxCoin);
                }

                DB::table('app_users')
                    ->where('id', $user->id)
                    ->increment('total_points', $claimCoins);
            }


            //   Item reward delivery

            if (
                $selectedReward->reward_type !== 'coins'
                && in_array($selectedReward->reward_type, $allowedItemTypes)
            ) {
                DB::table('item_deliveries')->insert([
                    'recipient'  => $user->id,
                    'type'       => $selectedReward->reward_type,
                    'item_id'    => $selectedReward->reward_item_id,
                    'valid_days' => $selectedReward->valid_days,
                    'start_at'   => now(),
                    'end_at'     => $selectedReward->valid_days
                        ? now()->addDays((int) $selectedReward->valid_days)
                        : null,
                    'coins_used' => 0,
                    'source'     => 'treasure',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if (
                $selectedReward->reward_type === 'vip'
                && $selectedReward->reward_item_id
            ) {

                VipTransaction::create([
                    'user_id'    => $user->id,
                    'vip_id'     => $selectedReward->reward_item_id,
                    'source'     => 'treasure',
                    'sender_id'  => null,
                    'coins_used' => 0,
                    'start_at'   => now(),
                    'end_at'     => $selectedReward->valid_days
                        ? now()->addDays((int)$selectedReward->valid_days)
                        : null,
                ]);
            }


            //   Save claim history

            DB::table('treasure_level_claims')->insert([
                'room_id'                  => $roomId,
                'user_id'                  => $user->id,
                'treasure_level_id'        => $level->id,
                'treasure_level_reward_id' => $selectedReward->id,

                'level'          => (int) $level->level,
                'reward_type'    => $selectedReward->reward_type,
                'reward_item_id' => $selectedReward->reward_item_id,
                'coins'          => $claimCoins,
                'valid_days'     => $selectedReward->valid_days,

                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Treasure reward claimed successfully',
                'data' => [
                    'room_id' => $roomId,

                    'level_id' => $level->id,
                    'level' => (int) $level->level,
                    'level_text' => 'Lv.' . $level->level,

                    'reward' => [
                        'id' => $selectedReward->id,
                        'reward_type' => $selectedReward->reward_type,
                        'reward_item_id' => $selectedReward->reward_item_id,

                        'coins' => $claimCoins,
                        'coins_text' => $claimCoins > 0
                            ? $this->formatNumber($claimCoins)
                            : null,

                        'valid_days' => $selectedReward->valid_days,
                        'valid_days_text' => $selectedReward->valid_days
                            ? $selectedReward->valid_days . 'Days'
                            : null,

                        'reward_image' => $selectedReward->reward_image
                            ? Helper::showImage($selectedReward->reward_image)
                            : null,
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
