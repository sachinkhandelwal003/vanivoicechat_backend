<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Helper\Helper;
use App\Models\VipTransaction;
use App\Models\Vip;
use App\Models\AppUser;
use App\Models\SvipTransaction;
use App\Models\Svip;
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
                    'entrance_image' => Helper::showImage($svip->entrance_image, true),
                    'voice_image' => Helper::showImage($svip->voice_image, true),
                    'profile_card' => Helper::showImage($svip->profile_card, true),

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


    public function buySvip(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'svip_id' => 'required|exists:svips,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ]);
        }

        DB::beginTransaction();

        try {

            $user = AppUser::where('id', $user->id)
                ->lockForUpdate()
                ->first();
            $svip = Svip::findOrFail($request->svip_id);

            if ($user->buy_coins_wallet < $svip->need_coins) {

                DB::rollBack();

                return response()->json([
                    'status' => false,
                    'message' => 'You do not have enough SVIP Points.'
                ]);
            }

            // Remove previous active SVIP
            SvipTransaction::where('user_id', $user->id)->delete();

            // Deduct coins
            $user->decrement('buy_coins_wallet', $svip->need_coins);

            $transaction = SvipTransaction::create([
                'user_id'    => $user->id,
                'svip_id'    => $svip->id,
                'coins_used' => $svip->need_coins,
                'start_at'   => now(),
                'end_at'     => now()->addDays($svip->days),
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'SVIP purchased successfully.',
                'data' => $transaction
            ]);
        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function getVipList()
    {
        try {

            $authUser = Auth::user();
            $authUserCoins = (int) ($authUser->total_points ?? 0);

            $vips = DB::table('vips')
                ->orderBy('needcoins')
                ->get();

            $data = $vips->map(function ($vip,$authUserCoins) {

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
                    'auth_user_coins' => (int)$authUserCoins,
                    'coins' => (int)$vip->needcoins,
                    'days' => (int)$vip->days,
                    'bg_color' => $vip->color,
                    'user' => [
                        'id' => 1,
                        'name' => 'Username',
                        'image' => asset('storage/defaul-user.png')
                    ],

                    'username_color' => $vip->username,
                    'badge' => Helper::showImage($vip->badge, true),
                    'entry' => Helper::showImage($vip->entry_tag, true),
                    'chat_card' => Helper::showImage($vip->chat_card, true),
                    'image_frame' => Helper::showImage($vip->image_frame, true),
                    'profile_frame' => Helper::showImage($vip->profile_frame, true),
                    'voice_frame' => Helper::showImage($vip->voice_frame, true),

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

    public function buyVip(Request $request)
    {
        $user = Auth::user();
        // dd($user);
        $validator = Validator::make($request->all(), [
            'vip_id' => 'required|exists:vips,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ]);
        }

        DB::beginTransaction();

        try {

            $vip = Vip::findOrFail($request->vip_id);

            // Check if user already has this VIP
            $alreadyHasVip = VipTransaction::where('user_id', $user->id)
                ->where('vip_id', $vip->id)
                ->where('end_at', '>', now())
                ->exists();

            if ($alreadyHasVip) {

                DB::rollBack();

                return response()->json([
                    'status' => false,
                    'message' => 'You already have this VIP.'
                ]);
            }

            // Check balance
            if ($user->total_points < $vip->needcoins) {

                DB::rollBack();

                return response()->json([
                    'status' => false,
                    'message' => 'Insufficient coins.'
                ]);
            }

            // Deduct coins
            $user->decrement('total_points', $vip->needcoins);

            $transaction = VipTransaction::create([
                'user_id'    => $user->id,
                'vip_id'     => $vip->id,
                'source'     => 'self',
                'sender_id'  => null,
                'coins_used' => $vip->needcoins,
                'start_at'   => now(),
                'end_at'     => now()->addDays($vip->days),
            ]);

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => 'VIP purchased successfully.',
                'data'    => $transaction
            ]);
        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'status'  => false,
                'message' => $e->getMessage()
            ]);
        }
    }


    public function giftVip(Request $request)
    {
        $sender = auth()->user();

        $validator = Validator::make($request->all(), [
            'receiver_id' => 'required|exists:app_users,id',
            'vip_id'      => 'required|exists:vips,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ]);
        }

        DB::beginTransaction();

        try {

            $receiver = AppUser::findOrFail($request->receiver_id);
            $vip = Vip::findOrFail($request->vip_id);

            // Prevent self gift
            if ($receiver->id == $sender->id) {

                DB::rollBack();

                return response()->json([
                    'status' => false,
                    'message' => 'You cannot gift VIP to yourself.'
                ]);
            }

            // Check if receiver already has this VIP
            $alreadyHasVip = VipTransaction::where('user_id', $receiver->id)
                ->where('vip_id', $vip->id)
                ->where('end_at', '>', now())
                ->exists();

            if ($alreadyHasVip) {

                DB::rollBack();

                return response()->json([
                    'status' => false,
                    'message' => 'User already has this VIP.'
                ]);
            }

            // Check sender balance
            if ($sender->total_points < $vip->needcoins) {

                DB::rollBack();

                return response()->json([
                    'status' => false,
                    'message' => 'Insufficient coins.'
                ]);
            }

            // Deduct sender coins
            $sender->decrement('total_points', $vip->needcoins);

            $transaction = VipTransaction::create([
                'user_id'    => $receiver->id,
                'vip_id'     => $vip->id,
                'source'     => 'gift',
                'sender_id'  => $sender->id,
                'coins_used' => $vip->needcoins,
                'start_at'   => now(),
                'end_at'     => now()->addDays($vip->days),
            ]);

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => 'VIP gifted successfully.',
                'data'    => $transaction
            ]);
        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'status'  => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function svipExp()
    {
        try {

            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthenticated',
                ], 401);
            }

            $svipPoints = (int) ($user->buy_coins_wallet ?? 0);

            $activeSvipTransaction = SvipTransaction::with('svip')
                ->where('user_id', $user->id)
                ->where('start_at', '<=', now())
                ->where('end_at', '>=', now())
                ->latest('end_at')
                ->first();

            $activeSvip = null;

            if ($activeSvipTransaction && $activeSvipTransaction->svip) {

                $activeSvip = [
                    'id' => $activeSvipTransaction->svip->id,
                    'name' => $activeSvipTransaction->svip->name,
                    'start_at' => $activeSvipTransaction->start_at,
                    'end_at' => $activeSvipTransaction->end_at,
                    'days' => $activeSvipTransaction->svip->days,
                ];
            }

            return response()->json([
                'status' => true,
                'message' => 'SVIP information fetched successfully',

                'data' => [
                    'svip_points' => $svipPoints,
                    'active_svip' => $activeSvip,
                ]
            ], 200);
        } catch (\Throwable $e) {

            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch SVIP information',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
