<?php

namespace App\Http\Controllers\Api;

use App\Helper\Helper;
use App\Http\Controllers\Controller;
use App\Models\TreasureLevel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TreasureController extends Controller
{
    public function details(Request $request): JsonResponse
    {
        $request->validate([
            'room_id' => 'required|integer|exists:rooms,id',
        ]);

        $roomId = (int) $request->room_id;

        $currentPoints = (int) DB::table('gift_transactions')
            ->where('room_id', $roomId)
            ->sum('total_value');

        $levels = TreasureLevel::with(['rewards' => function ($q) {
            $q->where('status', 1);
        }])
            ->where('status', 1)
            ->orderBy('level', 'asc')
            ->get();

        if ($levels->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No treasure levels found',
                'data' => null,
            ]);
        }

        $currentLevel = $levels->first();

        foreach ($levels as $level) {
            if ($currentPoints >= $level->target_points) {
                $currentLevel = $level;
            } else {
                break;
            }
        }

        $nextLevel = $levels->firstWhere('level', '>', $currentLevel->level);

        $targetPoints = $nextLevel
            ? (int) $nextLevel->target_points
            : (int) $currentLevel->target_points;

        $mainChestLevel = $nextLevel ?: $currentLevel;

        return response()->json([
            'status' => true,
            'message' => 'Treasure details fetched successfully',
            'data' => [
                'room_id' => $roomId,
                'current_points' => $currentPoints,
                'current_points_text' => $this->formatNumber($currentPoints),

                'current_level' => (int) $currentLevel->level,
                'current_level_text' => 'Lv.' . $currentLevel->level,

                'target_points' => $targetPoints,
                'target_points_text' => $this->formatNumber($targetPoints),
                'progress_text' => $currentPoints . '/' . $targetPoints,

                'main_chest' => [
                    'level_id' => $mainChestLevel->id,
                    'level' => (int) $mainChestLevel->level,
                    'image' => $mainChestLevel->chest_image ? Helper::showImage($mainChestLevel->chest_image) : null,
                ],

                'levels' => $levels->map(function ($level) use ($currentPoints) {
                    return [
                        'id' => $level->id,
                        'level' => (int) $level->level,
                        'level_text' => 'Lv.' . $level->level,
                        'target_points' => (int) $level->target_points,
                        'target_points_text' => $this->formatNumber($level->target_points),
                        'is_unlocked' => $currentPoints >= $level->target_points,
                        'chest_image' => $level->chest_image ? Helper::showImage($level->chest_image) : null,

                        'rewards' => $level->rewards->map(function ($reward) {
                            return [
                                'id' => $reward->id,
                                'reward_type' => $reward->reward_type,
                                'reward_item_id' => $reward->reward_item_id,
                                'coins' => (int) $reward->coins,
                                'coins_text' => $reward->coins > 0 ? $this->formatNumber($reward->coins) : null,
                                'valid_days' => $reward->valid_days,
                                'valid_days_text' => $reward->valid_days ? $reward->valid_days . 'Days' : null,
                                'reward_image' => $reward->reward_image ? Helper::showImage($reward->reward_image) : null,
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
}
