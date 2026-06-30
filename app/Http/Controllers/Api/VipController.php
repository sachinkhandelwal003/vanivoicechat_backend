<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Helper\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class VipController extends Controller
{
    // Get SVIP List with Privileges
    public function getSvipList()
    {
        try {
            $user = Auth::user();

            $svips = DB::table('svips')
                ->where('status', 1)
                ->orderBy('need_coins')
                ->get();

            $privileges = DB::table('svip_privileges')
                ->where('status', 1)
                ->orderBy('sort_order')
                ->get();

            $data = $svips->map(function ($svip) use ($privileges) {

                $activePrivileges = DB::table('svip_level_privileges')
                    ->where('svip_id', $svip->id)
                    ->where('is_active', 1)
                    ->pluck('privilege_id')
                    ->toArray();

                return [
                    'id' => $svip->id,
                    'name' => $svip->name,
                    'coins' => (int)$svip->need_coins,
                    'days' => (int)$svip->days,
                    'bg_color' => $svip->color,

                    'medal' => Helper::showImage($svip->medal, true),
                    'medal_gif' => Helper::showImage($svip->medal_gif, true),
                    'title' => Helper::showImage($svip->title, true),
                    'bubble' => Helper::showImage($svip->bubble, true),
                    'headwear' => Helper::showImage($svip->headwear, true),
                    'entry' => Helper::showImage($svip->entry, true),

                    'privileges' => $privileges->map(function ($p) use ($activePrivileges) {
                        return [
                            'id' => $p->id,
                            'name' => $p->name,
                            'icon' => Helper::showImage($p->icon, true),
                            'is_active' => in_array($p->id, $activePrivileges)
                        ];
                    })
                ];
            });

            return response()->json([
                'status' => true,
                'message' => 'SVIP list fetched successfully',
                'total_points' => (int) ($user->total_points ?? 0),
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getVipList()
    {
        try {

            $vips = DB::table('vips')
                ->orderBy('needcoins')
                ->get();

            $data = $vips->map(function ($vip) {

                $privileges = DB::table('vip_privileges')
                    ->where('vip_id', $vip->id)
                    ->where('status', 1)
                    ->select('id', 'name', 'icon')
                    ->get()
                    ->map(function ($p) {
                        return [
                            'id' => $p->id,
                            'name' => $p->name,
                            'icon' => Helper::showImage($p->icon, true),
                        ];
                    });

                return [
                    'id' => $vip->id,
                    'name' => $vip->name,
                    'coins' => (int)$vip->needcoins,
                    'days' => (int)$vip->days,
                    'bg_color' => $vip->color,
                    
                    'username_color' => $vip->username,
                    'badge' => Helper::showImage($vip->badge, true),
                    'entry' => Helper::showImage($vip->entry_tag, true),
                    'chat_card' => Helper::showImage($vip->chat_card, true),
                    'image_frame' => Helper::showImage($vip->image_frame, true),
                    'profile_frame' => Helper::showImage($vip->profile_frame, true),

                    'privileges' => $privileges
                ];
            });

            return response()->json([
                'status' => true,
                'message' => 'VIP list fetched successfully',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
