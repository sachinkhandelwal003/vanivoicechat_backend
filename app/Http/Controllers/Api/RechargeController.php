<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Helper\Helper;
use App\Models\AppUser;
use App\Models\AdminAccount;
use App\Models\Agency;
use App\Models\BdUser;
use App\Models\Host;
use App\Models\Notification;
use App\Models\CoinRechargeHistory;
use App\Models\CoinSeller;
use App\Models\CoinConversionRate;
use App\Models\CoinSellerTransaction;
use App\Models\Country;
use App\Models\ManualMoneyTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class RechargeController extends Controller
{

    public function sellerDashboard()
    {
        $seller = CoinSeller::with('user')
            ->where('user_id', auth()->id())
            ->where('is_merchant', 0)
            ->where('status', 1)
            ->first();

        if (!$seller) {

            return response()->json([
                'status' => false,
                'message' => 'Seller account not found'
            ], 404);
        }

        $rate = CoinConversionRate::first();

        return response()->json([

            'status' => true,

            'data' => [

                'balance' => $seller->user->total_points ?? 0,

                'seller_to_user_rate' =>
                $rate->seller_to_user_rate ?? 10000
            ]
        ]);
    }

    public function searchRechargeUser(Request $request)
    {
        $validator = Validator::make($request->all(), [

            'user_uid' => 'required'
        ]);

        if ($validator->fails()) {

            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        $seller = CoinSeller::where(
            'user_id',
            auth()->id()
        )
            ->where('is_merchant', 0)
            ->where('status', 1)
            ->first();

        if (!$seller) {

            return response()->json([
                'status' => false,
                'message' => 'Seller account not found'
            ], 404);
        }

        $country = Country::find($seller->country_id);

        if (!$country) {

            return response()->json([
                'status' => false,
                'message' => 'Seller country not found'
            ], 404);
        }

        $user = AppUser::where(
            'uid',
            $request->user_uid
        )
            ->where(
                'country',
                $country->name
            )
            ->first();

        if (!$user) {

            return response()->json([
                'status' => false,
                'message' => 'User not found in your country'
            ], 404);
        }

        return response()->json([

            'status' => true,

            'data' => [

                'id' => $user->id,

                'uid' => $user->uid,

                'name' => $user->name,

                'country' => $user->country,

                'image' => !empty($user->image)
                    ? Helper::showImage($user->image, true)
                    : null,
            ]
        ]);
    }

    public function rechargeCoin(Request $request)
    {
        $validator = Validator::make($request->all(), [

            'user_uid' => 'required',

            'coin' => 'required|numeric|min:1'
        ]);

        if ($validator->fails()) {

            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        $seller = CoinSeller::with('user')
            ->where('user_id', auth()->id())
            ->where('is_merchant', 0)
            ->where('status', 1)
            ->first();

        if (!$seller) {

            return response()->json([
                'status' => false,
                'message' => 'Seller account not found'
            ], 404);
        }

        $user = AppUser::where(
            'uid',
            $request->user_uid
        )->first();

        if (!$user) {

            return response()->json([
                'status' => false,
                'message' => 'User not found'
            ], 404);
        }

        $sellerUser = $seller->user;

        if (($sellerUser->total_points ?? 0) < $request->coin) {

            return response()->json([
                'status' => false,
                'message' => 'Insufficient balance'
            ], 422);
        }

        DB::beginTransaction();

        try {

            $sellerUser->decrement(
                'total_points',
                $request->coin
            );

            $user->increment('total_points', $request->coin);
            $user->increment('buy_coins_wallet', $request->coin);

            CoinRechargeHistory::create([

                'seller_id' => auth()->id(),
                'role' => 'coinseller',

                'user_id' => $user->id,

                'user_uid' => $user->uid,

                'coin' => $request->coin,

                'transaction_type' => 'user_recharge',

                'remark' => 'Recharge by seller',
            ]);

            DB::commit();

            return response()->json([

                'status' => true,

                'message' => 'Coin recharged successfully',

                // 'data' => [

                //     'seller_balance' =>
                //     $sellerUser->fresh()->total_points
                // ]
            ]);
        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([

                'status' => false,

                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function sellerHistory()
    {
        $seller = CoinSeller::where(
            'user_id',
            auth()->id()
        )
            ->where('is_merchant', 0)
            ->where('status', 1)
            ->first();

        if (!$seller) {

            return response()->json([
                'status' => false,
                'message' => 'Seller not found'
            ], 404);
        }

        $sellerUserId = auth()->id();

        $adminHistory = CoinSellerTransaction::where(
            'receiver_id',
            $sellerUserId
        )
            ->where(
                'receiver_type',
                'user'
            )
            ->latest()
            ->get()
            ->map(function ($item) {

                return [

                    'id' => $item->id,

                    'history_type' => 'admin_transaction',

                    'coin' => $item->coins,

                    'transaction_type' => $item->transaction_type,

                    'remark' => $item->remark,

                    'created_at' => $item->created_at,
                ];
            })
            ->toArray();

        $sellerHistory = CoinRechargeHistory::where(
            'seller_id',
            $sellerUserId
        )
            ->where(
                'role',
                'coinseller'
            )
            ->latest()
            ->get()
            ->map(function ($item) {

                return [

                    'id' => $item->id,

                    'history_type' => 'seller_transaction',

                    'coin' => $item->coin,

                    'transaction_type' => $item->transaction_type,

                    'user_uid' => $item->user_uid,

                    'remark' => $item->remark,

                    'created_at' => $item->created_at,
                ];
            })
            ->toArray();

        $history = collect($adminHistory)
            ->merge($sellerHistory)
            ->sortByDesc(function ($item) {

                return strtotime($item['created_at']);
            })
            ->values();

        return response()->json([

            'status' => true,

            'message' => 'History fetched successfully',

            'total_records' => $history->count(),

            'data' => $history
        ]);
    }


    public function merchantDashboard()
    {
        $merchant = CoinSeller::with('user')
            ->where('user_id', auth()->id())
            ->where('is_merchant', 1)
            ->first();

        $rate = CoinConversionRate::first();

        return response()->json([

            'status' => true,

            'data' => [

                'balance' => $merchant->user->total_points ?? 0,

                'merchant_to_user_rate' =>
                $rate->merchant_to_user_rate,

                'merchant_to_seller_rate' =>
                $rate->merchant_to_seller_rate,
            ]
        ]);
    }

    public function searchUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'uid' => 'required'
        ]);

        if ($validator->fails()) {

            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        $merchant = CoinSeller::where(
            'user_id',
            auth()->id()
        )
            ->where('is_merchant', 1)
            ->where('status', 1)
            ->first();

        if (!$merchant) {

            return response()->json([
                'status' => false,
                'message' => 'Merchant account not found'
            ], 404);
        }

        $country = Country::find($merchant->country_id);


        if (!$country) {

            return response()->json([
                'status' => false,
                'message' => 'Merchant country not found'
            ], 404);
        }

        $user = AppUser::where(
            'uid',
            $request->uid
        )
            ->where(
                'country',
                $country->nicename
            )
            ->first();

        if (!$user) {

            return response()->json([
                'status' => false,
                'message' => 'User not found in your country'
            ], 404);
        }

        return response()->json([

            'status' => true,

            'data' => [

                'id' => $user->id,

                'uid' => $user->uid,

                'name' => $user->name,

                'country' => $user->country,

                'image' => !empty($user->image)
                    ? Helper::showImage($user->image, true)
                    : null,
            ]
        ]);
    }



    public function merchantRechargeUser(Request $request)
    {
        $validator = Validator::make($request->all(), [

            'user_uid' => 'required',

            'coin' => 'required|numeric|min:1'
        ]);

        if ($validator->fails()) {

            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        $merchant = CoinSeller::with('user')
            ->where('user_id', auth()->id())
            ->where('is_merchant', 1)
            ->where('status', 1)
            ->first();

        if (!$merchant) {

            return response()->json([
                'status' => false,
                'message' => 'Merchant account not found'
            ], 404);
        }

        $user = AppUser::where(
            'uid',
            $request->user_uid
        )->first();

        if (!$user) {

            return response()->json([
                'status' => false,
                'message' => 'User not found'
            ], 404);
        }

        $merchantUser = $merchant->user;

        if (($merchantUser->total_points ?? 0) < $request->coin) {

            return response()->json([
                'status' => false,
                'message' => 'Insufficient balance'
            ], 422);
        }

        DB::beginTransaction();

        try {

            /*
        |--------------------------------------------------------------------------
        | Deduct Merchant Balance
        |--------------------------------------------------------------------------
        */

            $merchantUser->decrement(
                'total_points',
                $request->coin
            );

            /*
        |--------------------------------------------------------------------------
        | Add User Balance
        |--------------------------------------------------------------------------
        */

            $user->increment(
                'total_points',
                $request->coin
            );
            $user->increment('buy_coins_wallet', $request->coin);


            /*
        |--------------------------------------------------------------------------
        | Save History
        |--------------------------------------------------------------------------
        */

            CoinRechargeHistory::create([

                'seller_id' => auth()->id(),

                'role' => 'merchant',

                'user_id' => $user->id,

                'user_uid' => $user->uid,

                'coin' => $request->coin,

                'transaction_type' => 'merchant_to_user',

                'remark' => 'Merchant recharge to user',
            ]);

            DB::commit();

            return response()->json([

                'status' => true,

                'message' => 'Coin transferred successfully',

                // 'data' => [

                //     'merchant_balance' => $merchantUser->fresh()->total_points,

                //     'user_balance' => $user->fresh()->total_points
                // ]
            ]);
        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([

                'status' => false,

                'message' => $e->getMessage()
            ], 500);
        }
    }


    public function searchSeller(Request $request)
    {
        $validator = Validator::make($request->all(), [

            'seller_uid' => 'required'
        ]);

        if ($validator->fails()) {

            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        $merchant = CoinSeller::where(
            'user_id',
            auth()->id()
        )
            ->where('is_merchant', 1)
            ->where('status', 1)
            ->first();

        if (!$merchant) {

            return response()->json([
                'status' => false,
                'message' => 'Merchant account not found'
            ], 404);
        }

        $country = Country::find($merchant->country_id);

        if (!$country) {

            return response()->json([
                'status' => false,
                'message' => 'Merchant country not found'
            ], 404);
        }

        $seller = AppUser::where(
            'uid',
            $request->seller_uid
        )
            ->where(
                'country',
                $country->nicename
            )
            ->first();

        if (!$seller) {

            return response()->json([
                'status' => false,
                'message' => 'Seller not found in your country'
            ], 404);
        }

        $isSeller = CoinSeller::where(
            'user_id',
            $seller->id
        )
            ->where('is_merchant', 0)
            ->where('status', 1)
            ->exists();

        if (!$isSeller) {

            return response()->json([
                'status' => false,
                'message' => 'User is not a Seller'
            ], 422);
        }

        return response()->json([

            'status' => true,

            'data' => [

                'id' => $seller->id,

                'uid' => $seller->uid,

                'name' => $seller->name,

                'country' => $seller->country,

                'image' => !empty($seller->image)
                    ? Helper::showImage($seller->image, true)
                    : null,
            ]
        ]);
    }

    public function merchantRechargeSeller(Request $request)
    {
        $validator = Validator::make($request->all(), [

            'seller_uid' => 'required',

            'coin' => 'required|numeric|min:1'
        ]);

        if ($validator->fails()) {

            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        $merchant = CoinSeller::with('user')
            ->where('user_id', auth()->id())
            ->where('is_merchant', 1)
            ->where('status', 1)
            ->first();

        if (!$merchant) {

            return response()->json([
                'status' => false,
                'message' => 'Merchant account not found'
            ], 404);
        }

        $sellerUser = AppUser::where(
            'uid',
            $request->seller_uid
        )->first();

        if (!$sellerUser) {

            return response()->json([
                'status' => false,
                'message' => 'Seller not found'
            ], 404);
        }

        $seller = CoinSeller::where(
            'user_id',
            $sellerUser->id
        )
            ->where('is_merchant', 0)
            ->where('status', 1)
            ->first();

        if (!$seller) {

            return response()->json([
                'status' => false,
                'message' => 'This user is not a seller'
            ], 422);
        }

        $merchantUser = $merchant->user;

        if (($merchantUser->total_points ?? 0) < $request->coin) {

            return response()->json([
                'status' => false,
                'message' => 'Insufficient balance'
            ], 422);
        }

        DB::beginTransaction();

        try {

            /*
        |--------------------------------------------------------------------------
        | Deduct Merchant Balance
        |--------------------------------------------------------------------------
        */

            $merchantUser->decrement(
                'total_points',
                $request->coin
            );

            /*
        |--------------------------------------------------------------------------
        | Add Seller Balance
        |--------------------------------------------------------------------------
        */

            $sellerUser->increment(
                'total_points',
                $request->coin
            );


            /*
        |--------------------------------------------------------------------------
        | History
        |--------------------------------------------------------------------------
        */

            CoinRechargeHistory::create([

                'seller_id' => auth()->id(),

                'role' => 'merchant',

                'user_id' => $sellerUser->id,

                'user_uid' => $sellerUser->uid,

                'coin' => $request->coin,

                'transaction_type' => 'merchant_to_seller',

                'remark' => 'Merchant recharge to seller',
            ]);

            DB::commit();

            return response()->json([

                'status' => true,

                'message' => 'Coin transferred to seller successfully',

                // 'data' => [

                //     'merchant_balance' =>
                //     $merchantUser->fresh()->total_points,

                //     'seller_balance' =>
                //     $sellerUser->fresh()->total_points,
                // ]
            ]);
        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([

                'status' => false,

                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function merchantHistory()
    {
        $merchant = CoinSeller::where('user_id', auth()->id())
            ->where('is_merchant', 1)
            ->where('status', 1)
            ->first();

        if (!$merchant) {

            return response()->json([
                'status' => false,
                'message' => 'Merchant not found'
            ], 404);
        }

        $merchantUserId = auth()->id();

        /*
    |--------------------------------------------------------------------------
    | Admin -> Merchant History
    |--------------------------------------------------------------------------
    */

        $adminHistory = CoinSellerTransaction::where(
            'receiver_id',
            $merchantUserId
        )
            ->where(
                'receiver_type',
                'user'
            )
            ->latest()
            ->get()
            ->map(function ($item) {

                return [

                    'id' => $item->id,

                    'history_type' => 'admin_transaction',

                    'coin' => $item->coins,

                    'transaction_type' => $item->transaction_type,

                    'remark' => $item->remark,

                    // 'user_uid' => null,

                    'created_at' => $item->created_at,
                ];
            })
            ->toArray();

        /*
    |--------------------------------------------------------------------------
    | Merchant Recharge History
    |--------------------------------------------------------------------------
    */

        $merchantHistory = CoinRechargeHistory::where(
            'seller_id',
            $merchantUserId
        )
            ->where(
                'role',
                'merchant'
            )
            ->latest()
            ->get()
            ->map(function ($item) {

                return [

                    'id' => $item->id,

                    'history_type' => 'merchant_transaction',

                    'coin' => $item->coin,

                    'transaction_type' => $item->transaction_type,

                    'remark' => $item->remark,

                    // 'user_uid' => $item->user_uid,

                    'created_at' => $item->created_at,
                ];
            })
            ->toArray();

        /*
    |--------------------------------------------------------------------------
    | Merge Both Histories
    |--------------------------------------------------------------------------
    */

        $history = collect($adminHistory)
            ->merge($merchantHistory)
            ->sortByDesc(function ($item) {

                return strtotime($item['created_at']);
            })
            ->values();

        return response()->json([

            'status' => true,

            'message' => 'Merchant history fetched successfully',

            'total_records' => $history->count(),

            'data' => $history,
        ]);
    }

    public function manualMoneyHistory(Request $request)
    {
        $user = Auth::user();

        $transactions = ManualMoneyTransaction::where('user_id', $user->id)
            ->orderByDesc('id')
            ->get()
            ->map(function ($item) {
                return [
                    'id'             => (int) $item->id,
                    'type'           => $item->type, // credit / deduct
                    'amount'         => (float) $item->amount,
                    'before_balance' => (float) $item->before_balance,
                    'after_balance'  => (float) $item->after_balance,
                    'reason'         => $item->reason,
                    'created_at'     => $item->created_at->format('Y-m-d H:i:s'),
                ];
            });

        return response()->json([
            'status'  => true,
            'message' => 'Manual transaction history fetched successfully',
            'data'    => $transactions,
        ]);
    }

    public function coinsHistory(Request $request)
    {
        $user = Auth::user();

        // Recharge History
        $recharges = CoinRechargeHistory::with('seller:id,name,uid')
            ->where('user_id', $user->id)
            ->get()
            ->map(function ($item) use ($user) {

                // Role wise title + description
                if ($item->role === 'merchant') {
                    $title = 'Merchant Recharge';
                    $description = number_format($item->coin) . ' Coins added by a merchant';
                } elseif ($item->role === 'coinseller') {
                    $title = 'Seller Recharge';
                    $description = number_format($item->coin) . ' Coins added by a coin seller';
                } else {
                    $title = 'Recharge';
                    $description = number_format($item->coin) . ' Coins added';
                }

                return [
                    'id' => (int) $item->id,
                    'title' => $title,
                    'description' => $description,
                    'type' => 'credit',
                    'amount' => (int) $item->coin,
                    'balance' => (int) $user->total_points, // Current Balance
                    'from_name' => $item->seller->name ?? null,
                    'from_uid' => $item->seller->uid ?? null,
                    'role' => $item->role,
                    'icon_type' => 'wallet',
                    'created_at' => $item->created_at,
                    'created_date' => $item->created_at->format('d M Y, h:i A'),
                ];
            });

        // Admin History
        $adminTransactions = CoinSellerTransaction::where('receiver_id', $user->id)
            ->where('receiver_type', 'user')
            ->get()
            ->map(function ($item) {

                $isCredit = $item->transaction_type === 'recharge';

                return [
                    'id' => (int) $item->id,
                    'title' => $isCredit ? 'Admin Recharge' : 'Admin Deduct',
                    'description' => $item->remark,
                    'type' => $isCredit ? 'credit' : 'deduct',
                    'amount' => (int) $item->coins,
                    'balance' => (int) $item->balance_after,
                    'from_name' => 'Admin',
                    'from_uid' => null,
                    'role' => 'admin',
                    'icon_type' => 'wallet',
                    'created_at' => $item->created_at,
                    'created_date' => $item->created_at->format('d M Y, h:i A'),
                ];
            });

        $history = $recharges
            ->concat($adminTransactions)
            ->sortByDesc('created_at')
            ->values()
            ->map(function ($item) {

                return [
                    'id' => $item['id'],
                    'title' => $item['title'],
                    'description' => $item['description'],
                    'type' => $item['type'],
                    'amount' => $item['amount'],
                    'balance' => $item['balance'],
                    'from_name' => $item['from_name'],
                    'from_uid' => $item['from_uid'],
                    'role' => $item['role'],
                    'icon_type' => $item['icon_type'],
                    'created_at' => $item['created_date'],
                ];
            });

        return response()->json([
            'status' => true,
            'message' => 'Coins history fetched successfully',
            'data' => $history,
        ]);
    }
}
