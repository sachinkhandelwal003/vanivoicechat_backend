<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Helper\Helper;

class WCLevelController extends Controller
{
    public function getLevels(Request $request)
    {
        try {

            $type = $request->type ?? 'wealth';

            $user = Auth::user();

            $levels = DB::table('levels')
                ->where('type', $type)
                ->orderBy('required_exp', 'asc')
                ->get();

            $userLevel = DB::table('wc_levels')
                ->where('user_id', $user->id)
                ->where('type', $type)
                ->first();

            $currentExp = $userLevel->exp ?? 0;

            $currentLevel = $userLevel->level ?? 1;

            $nextLevel = $levels->where('level', '>', $currentLevel)->first();

            $wealthLevel = DB::table('wc_levels')
                ->where('user_id', $user->id)
                ->where('type', 'wealth')
                ->first();

            $charmLevel = DB::table('wc_levels')->where('user_id', $user->id)->where('type', 'charm')->first();

            $description = DB::table('wc_level_settings')
                ->where('type', $type)
                ->value('description');

            $levelList = $levels->map(function ($item) use ($currentLevel) {
                return [
                    'id' => $item->id,
                    'level' => $item->level,
                    'required_exp' => (int)$item->required_exp,
                    'icon' => Helper::showImage($item->icon, true),
                    // 'entry_effect' => Helper::showImage($item->entry_effect, true),

                    'is_unlocked' => $currentLevel >= $item->level
                ];
            });

            return response()->json([
                'status' => true,
                'message' => 'Level data fetched successfully',
                'user' => [
                    'id' => $user->id,
                    'uid' => $user->uid,
                    'name' => $user->name,
                    'image' => Helper::showImage($user->image, true),
                ],

                'data' => [
                    'type' => $type,
                    'user_levels' => [
                        'wealth' => [
                            'level' => $wealthLevel->level ?? 1,
                            'exp' => (int) ($wealthLevel->exp ?? 0)
                        ],
                        'charm' => [
                            'level' => $charmLevel->level ?? 1,
                            'exp' => (int) ($charmLevel->exp ?? 0)
                        ]
                    ],
                    'current_exp' => (int)$currentExp,
                    'current_level' => (int) $currentLevel,
                    'next_level' => $nextLevel ? (int)$nextLevel->level : null,
                    'next_level_exp' => $nextLevel ? (int)$nextLevel->required_exp : null,
                    'description' => $description,
                    'levels' => $levelList
                ]
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
