<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Helper\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function getCoinPackages()
    {
        try {
            $user = Auth::user();
            $packages = DB::table('coin_packages')
                ->where('status', 1)
                ->orderBy('id', 'asc')
                ->get();

            $data = $packages->map(function ($item) {
                return [
                    'id'    => $item->id,
                    'coins' => (int) $item->coins,
                    'price' => (int) $item->price,
                    'bonus_percent' => $item->bonus_percent,
                    'bonus_coins' => $item->bonus_coins,
                    'icon'  => Helper::showImage($item->icon, true),
                ];
            });

            return response()->json([
                'status'  => true,
                'message' => 'Coin packages fetched successfully',
                'total_points' => (int) ($user->total_points ?? 0),
                'data'    => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function buyCoinPackage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'package_id' => 'required|exists:coin_packages,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();

        DB::beginTransaction();
        try {

            // package get
            $package = DB::table('coin_packages')
                ->where('id', $request->package_id)
                ->where('status', 1)
                ->first();

            if (!$package) {
                return response()->json([
                    'status' => false,
                    'message' => 'Package not found'
                ], 404);
            }

            $coinsToAdd = (int) ($package->total_coins ?? 0);

            if ($coinsToAdd <= 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid package coins'
                ], 400);
            }

            // add coins to user
            DB::table('app_users')
                ->where('id', $user->id)
                ->increment('total_points', $coinsToAdd);

            DB::table('coin_transactions')->insert([
                'user_id'    => $user->id,
                'package_id' => $package->id,
                'coins'      => $package->coins,
                'bonus_coins'  => (int) ($package->bonus_coins ?? 0),
                'total_coins'  => $coinsToAdd,
                'amount'     => $package->price,
                'type'       => 'credit',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $updatedTotalCoins = (int) DB::table('app_users')
                ->where('id', $user->id)
                ->value('total_points');

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Coins added successfully',
                'data' => [
                    'coins_added' => (int)$package->coins,
                    'bonus_coins'  => (int) ($package->bonus_coins ?? 0),
                    'total_added'  => $coinsToAdd,
                    'total_coins' => $updatedTotalCoins
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
