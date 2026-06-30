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

            $currentLevel = $levels->where('required_exp', '<=', $currentExp)->last();

            $nextLevel = $levels->where('required_exp', '>', $currentExp)->first();

            $description = DB::table('wc_level_settings')
                ->where('type', $type)
                ->value('description');

            $levelList = $levels->map(function ($item) use ($currentExp) {
                return [
                    'id' => $item->id,
                    'level' => $item->level,
                    'required_exp' => (int)$item->required_exp,
                    'icon' => Helper::showImage($item->icon, true),
                    'entry_effect' => Helper::showImage($item->entry_effect, true),

                    'is_unlocked' => $currentExp >= $item->required_exp
                ];
            });

            return response()->json([
                'status' => true,
                'message' => 'Level data fetched successfully',

                'data' => [
                    'type' => $type,

                    'current_exp' => (int)$currentExp,

                    'current_level' => $currentLevel ? $currentLevel->level : 'LEVEL 1',

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
