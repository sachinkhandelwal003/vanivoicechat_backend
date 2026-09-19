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
use App\Models\ManualCoinTransaction;
use App\Models\GiftTransaction;
use App\Models\RelationshipInvitation;
use App\Models\HostSalarySettlement;
use App\Models\AgencySalarySettlement;
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

                'balance' => $seller->user->sellers_coin_wallet ?? 0,

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

        $user = Helper::findUserByUid($request->user_uid);

        if (!$user || (strtolower($user->country) !== strtolower($country->name) && strtolower($user->country) !== strtolower($country->nicename))) {
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

        $user = Helper::findUserByUid($request->user_uid);

        if (!$user) {

            return response()->json([
                'status' => false,
                'message' => 'User not found'
            ], 404);
        }

        $sellerUser = $seller->user;

        if (($sellerUser->sellers_coin_wallet ?? 0) < $request->coin) {

            return response()->json([
                'status' => false,
                'message' => 'Insufficient balance'
            ], 422);
        }

        DB::beginTransaction();

        try {

            $sellerUser->decrement(
                'sellers_coin_wallet',
                $request->coin
            );

            // $user->increment('total_points', $request->coin);
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
                //     $sellerUser->fresh()->buy_coins_wallet
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

        $manualCoinHistory = ManualCoinTransaction::where(
            'user_id',
            $sellerUserId
        )
            ->where(function ($q) {
                $q->where('target_wallet', 'seller')
                    ->orWhereNull('target_wallet');
            })
            ->latest()
            ->get()
            ->map(function ($item) {

                return [

                    'id' => $item->id,

                    'history_type' => 'manual_coin_transaction',

                    'coin' => $item->coins,

                    'transaction_type' => $item->action,

                    'remark' => $item->reason ?? 'Admin Manual Coin Transfer',

                    'created_at' => $item->created_at,
                ];
            })
            ->toArray();

        $history = collect($adminHistory)
            ->merge($sellerHistory)
            ->merge($manualCoinHistory)
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

                'balance' => $merchant->user->sellers_coin_wallet ?? 0,

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

        $user = Helper::findUserByUid($request->uid);

        if (!$user || (strtolower($user->country) !== strtolower($country->name) && strtolower($user->country) !== strtolower($country->nicename))) {
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

        $user = Helper::findUserByUid($request->user_uid);

        if (!$user) {

            return response()->json([
                'status' => false,
                'message' => 'User not found'
            ], 404);
        }

        $merchantUser = $merchant->user;

        if (($merchantUser->sellers_coin_wallet ?? 0) < $request->coin) {

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
                'sellers_coin_wallet',
                $request->coin
            );

            /*
        |--------------------------------------------------------------------------
        | Add User Balance
        |--------------------------------------------------------------------------
        */

            // $user->increment('total_points', $request->coin);
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

                //     'merchant_balance' => $merchantUser->fresh()->buy_coins_wallet,

                //     'user_balance' => $user->fresh()->buy_coins_wallet
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

        $seller = Helper::findUserByUid($request->seller_uid);

        if (!$seller || (strtolower($seller->country) !== strtolower($country->name) && strtolower($seller->country) !== strtolower($country->nicename))) {
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

        $sellerUser = Helper::findUserByUid($request->seller_uid);

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

        if (($merchantUser->sellers_coin_wallet ?? 0) < $request->coin) {

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
                'sellers_coin_wallet',
                $request->coin
            );

            /*
        |--------------------------------------------------------------------------
        | Add Seller Balance
        |--------------------------------------------------------------------------
        */

            $sellerUser->increment(
                'sellers_coin_wallet',
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
                //     $merchantUser->fresh()->sellers_coin_wallet,

                //     'seller_balance' =>
                //     $sellerUser->fresh()->sellers_coin_wallet,
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

        $manualCoinHistory = ManualCoinTransaction::where(
            'user_id',
            $merchantUserId
        )
            ->where(function ($q) {
                $q->where('target_wallet', 'seller')
                    ->orWhereNull('target_wallet');
            })
            ->latest()
            ->get()
            ->map(function ($item) {

                return [

                    'id' => $item->id,

                    'history_type' => 'manual_coin_transaction',

                    'coin' => $item->coins,

                    'transaction_type' => $item->action,

                    'remark' => $item->reason ?? 'Admin Manual Coin Transfer',

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
            ->merge($manualCoinHistory)
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

        // 1. Admin Manual Money Transactions
        $manualTransactions = ManualMoneyTransaction::where('user_id', $user->id)
            ->get()
            ->map(function ($item) {
                $createdAt = $item->created_at ? Carbon::parse($item->created_at) : null;
                return [
                    'id'             => (int) $item->id,
                    'type'           => $item->type, // credit / deduct
                    'amount'         => (float) $item->amount,
                    'before_balance' => (float) $item->before_balance,
                    'after_balance'  => (float) $item->after_balance,
                    'reason'         => $item->reason ?? ($item->type === 'credit' ? 'Admin Money Added' : 'Admin Money Deducted'),
                    'created_at'     => $createdAt ? $createdAt->format('Y-m-d H:i:s') : '',
                    'raw_date'       => $createdAt ? $createdAt->timestamp : 0,
                ];
            });

        // 2. Host Salary Settlements
        $hostSalarySettlements = HostSalarySettlement::where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereHas('host', function ($hq) use ($user) {
                      $hq->where('user_id', $user->id);
                  });
            })
            ->where('status', 'settled')
            ->get()
            ->map(function ($item) use ($user) {
                $amount = (float) ($item->host_salary ?? $item->total_salary ?? 0);
                $settledDate = $item->settled_at ? Carbon::parse($item->settled_at) : ($item->created_at ? Carbon::parse($item->created_at) : null);
                $cycleStr = !empty($item->month) ? " ({$item->month} Cycle {$item->cycle})" : '';

                return [
                    'id'             => (int) $item->id,
                    'type'           => 'credit',
                    'amount'         => $amount,
                    'before_balance' => (float) max(0, ($user->balance ?? 0) - $amount),
                    'after_balance'  => (float) ($user->balance ?? 0),
                    'reason'         => 'Host Salary Settlement' . $cycleStr,
                    'created_at'     => $settledDate ? $settledDate->format('Y-m-d H:i:s') : '',
                    'raw_date'       => $settledDate ? $settledDate->timestamp : 0,
                ];
            });

        // 3. Agency Salary Settlements
        $agencySalarySettlements = AgencySalarySettlement::where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereHas('agency', function ($aq) use ($user) {
                      $aq->where('user_id', $user->id);
                  });
            })
            ->where('status', 'settled')
            ->get()
            ->map(function ($item) use ($user) {
                $amount = (float) ($item->agent_salary ?? $item->total_salary ?? 0);
                $settledDate = $item->settled_at ? Carbon::parse($item->settled_at) : ($item->created_at ? Carbon::parse($item->created_at) : null);
                $cycleStr = !empty($item->month) ? " ({$item->month} Cycle {$item->cycle})" : '';

                return [
                    'id'             => (int) $item->id,
                    'type'           => 'credit',
                    'amount'         => $amount,
                    'before_balance' => (float) max(0, ($user->balance ?? 0) - $amount),
                    'after_balance'  => (float) ($user->balance ?? 0),
                    'reason'         => 'Agency Salary Settlement' . $cycleStr,
                    'created_at'     => $settledDate ? $settledDate->format('Y-m-d H:i:s') : '',
                    'raw_date'       => $settledDate ? $settledDate->timestamp : 0,
                ];
            });

        $transactions = $manualTransactions
            ->concat($hostSalarySettlements)
            ->concat($agencySalarySettlements)
            ->filter(function ($item) {
                return !empty($item['created_at']);
            })
            ->sortByDesc('raw_date')
            ->values()
            ->map(function ($item) {
                unset($item['raw_date']);
                return $item;
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

        // 1. Recharge History (Coin Seller / Merchant)
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

                $createdAt = $item->created_at ? Carbon::parse($item->created_at) : null;

                return [
                    'id' => (int) $item->id,
                    'title' => $title,
                    'description' => $description,
                    'type' => 'credit',
                    'amount' => (int) $item->coin,
                    'balance' => (int) $user->buy_coins_wallet,
                    'from_name' => $item->seller->name ?? null,
                    'from_uid' => !empty($item->seller?->uid) ? (int) $item->seller->uid : null,
                    'role' => $item->role ?? 'recharge',
                    'icon_type' => 'wallet',
                    'created_at' => $createdAt,
                    'created_date' => $createdAt ? $createdAt->format('d M Y, h:i A') : '',
                ];
            });

        // 2. Admin History
        $adminTransactions = CoinSellerTransaction::where('receiver_id', $user->id)
            ->where('receiver_type', 'user')
            ->get()
            ->map(function ($item) use ($user) {

                $isCredit = $item->transaction_type === 'recharge';
                $createdAt = $item->created_at ? Carbon::parse($item->created_at) : null;

                return [
                    'id' => (int) $item->id,
                    'title' => $isCredit ? 'Admin Recharge' : 'Admin Deduct',
                    'description' => $item->remark ?? ($isCredit ? 'Coins added by admin' : 'Coins deducted by admin'),
                    'type' => $isCredit ? 'credit' : 'deduct',
                    'amount' => (int) $item->coins,
                    'balance' => (int) ($item->balance_after ?? $user->buy_coins_wallet),
                    'from_name' => 'Admin',
                    'from_uid' => null,
                    'role' => 'admin',
                    'icon_type' => 'wallet',
                    'created_at' => $createdAt,
                    'created_date' => $createdAt ? $createdAt->format('d M Y, h:i A') : '',
                ];
            });

        // 3. Manual Admin Coin Transactions (Send & Deduct from Admin Panel)
        $manualAdminTransactions = ManualCoinTransaction::with('admin:id,name')
            ->where('user_id', $user->id)
            ->where('target_wallet', 'user')
            ->get()
            ->map(function ($item) {

                $isSend = $item->action === 'send';
                $createdAt = $item->created_at ? Carbon::parse($item->created_at) : null;
                $coins = (int) $item->coins;

                if ($isSend) {
                    $title = 'System Recharge';
                    $description = number_format($coins) . ' Coins added by admin' . ($item->reason ? ' (' . $item->reason . ')' : '');
                    $type = 'credit';
                } else {
                    $title = 'System Deduct';
                    $description = number_format($coins) . ' Coins deducted by admin' . ($item->reason ? ' (' . $item->reason . ')' : '');
                    $type = 'deduct';
                }

                return [
                    'id' => (int) $item->id,
                    'title' => $title,
                    'description' => $description,
                    'type' => $type,
                    'amount' => $coins,
                    'balance' => (int) $item->after_coins,
                    'from_name' => $item->admin->name ?? 'Admin',
                    'from_uid' => null,
                    'role' => 'admin',
                    'icon_type' => 'wallet',
                    'created_at' => $createdAt,
                    'created_date' => $createdAt ? $createdAt->format('d M Y, h:i A') : '',
                ];
            });

        // 4. Online Package Coin Transactions
        $onlineTransactions = DB::table('coin_transactions')
            ->where('user_id', $user->id)
            ->where('payment_status', 'success')
            ->get()
            ->map(function ($item) use ($user) {

                $coins = (int) ($item->total_coins ?? ($item->coins + ($item->bonus_coins ?? 0)));
                $createdAt = $item->created_at ? Carbon::parse($item->created_at) : null;

                return [
                    'id' => (int) $item->id,
                    'title' => 'Online Recharge',
                    'description' => number_format($coins) . ' Coins purchased online',
                    'type' => 'credit',
                    'amount' => $coins,
                    'balance' => (int) $user->buy_coins_wallet,
                    'from_name' => 'Online Payment',
                    'from_uid' => null,
                    'role' => 'online_recharge',
                    'icon_type' => 'wallet',
                    'created_at' => $createdAt,
                    'created_date' => $createdAt ? $createdAt->format('d M Y, h:i A') : '',
                ];
            });

        // 5. Red Envelope Claims
        $redEnvelopes = DB::table('red_envelope_claims')
            ->where('user_id', $user->id)
            ->get()
            ->map(function ($item) use ($user) {

                $amount = (int) $item->amount;
                $dateRaw = $item->claimed_at ?? $item->created_at;
                $createdAt = $dateRaw ? Carbon::parse($dateRaw) : null;

                return [
                    'id' => (int) $item->id,
                    'title' => 'Red Envelope Claimed',
                    'description' => number_format($amount) . ' Coins claimed from red envelope',
                    'type' => 'credit',
                    'amount' => $amount,
                    'balance' => (int) $user->buy_coins_wallet,
                    'from_name' => 'Red Envelope',
                    'from_uid' => null,
                    'role' => 'red_envelope',
                    'icon_type' => 'wallet',
                    'created_at' => $createdAt,
                    'created_date' => $createdAt ? $createdAt->format('d M Y, h:i A') : '',
                ];
            });

        // 6. Room Reward Claims
        $roomRewards = DB::table('room_reward_claims')
            ->where('owner_id', $user->id)
            ->where('is_claimed', 1)
            ->get()
            ->map(function ($item) use ($user) {

                $amount = (int) ($item->owner_reward_coins ?? $item->reward_coins ?? $item->slab_reward_coins ?? 0);
                $dateRaw = $item->claimed_at ?? $item->created_at;
                $createdAt = $dateRaw ? Carbon::parse($dateRaw) : null;

                return [
                    'id' => (int) $item->id,
                    'title' => 'Room Reward Claimed',
                    'description' => number_format($amount) . ' Coins claimed as room reward',
                    'type' => 'credit',
                    'amount' => $amount,
                    'balance' => (int) $user->buy_coins_wallet,
                    'from_name' => 'Room Reward',
                    'from_uid' => null,
                    'role' => 'room_reward',
                    'icon_type' => 'wallet',
                    'created_at' => $createdAt,
                    'created_date' => $createdAt ? $createdAt->format('d M Y, h:i A') : '',
                ];
            });

        // 7. Treasure Level Claims
        $treasureClaims = DB::table('treasure_level_claims')
            ->where('user_id', $user->id)
            ->where('reward_type', 'coins')
            ->where('coins', '>', 0)
            ->get()
            ->map(function ($item) use ($user) {

                $coins = (int) $item->coins;
                $createdAt = $item->created_at ? Carbon::parse($item->created_at) : null;

                return [
                    'id' => (int) $item->id,
                    'title' => 'Treasure Box Reward',
                    'description' => number_format($coins) . ' Coins claimed from Level ' . $item->level . ' Treasure Box',
                    'type' => 'credit',
                    'amount' => $coins,
                    'balance' => (int) $user->buy_coins_wallet,
                    'from_name' => 'Treasure Box',
                    'from_uid' => null,
                    'role' => 'treasure',
                    'icon_type' => 'wallet',
                    'created_at' => $createdAt,
                    'created_date' => $createdAt ? $createdAt->format('d M Y, h:i A') : '',
                ];
            });

        // 8. Invite Reward Histories
        $inviteRewards = DB::table('invite_reward_histories')
            ->where('user_id', $user->id)
            ->get()
            ->map(function ($item) use ($user) {

                $coins = (int) $item->reward_coin;
                $createdAt = $item->created_at ? Carbon::parse($item->created_at) : null;

                return [
                    'id' => (int) $item->id,
                    'title' => 'Invite Friends Reward',
                    'description' => number_format($coins) . ' Coins rewarded for inviting ' . ($item->target_person ?? 0) . ' users',
                    'type' => 'credit',
                    'amount' => $coins,
                    'balance' => (int) $user->buy_coins_wallet,
                    'from_name' => 'Invite Reward',
                    'from_uid' => null,
                    'role' => 'invite_reward',
                    'icon_type' => 'wallet',
                    'created_at' => $createdAt,
                    'created_date' => $createdAt ? $createdAt->format('d M Y, h:i A') : '',
                ];
            });

        // 9. Red Envelope Creation
        $redEnvelopesCreated = DB::table('red_envelopes')
            ->where('sender_user_id', $user->id)
            ->get()
            ->map(function ($item) use ($user) {

                $amount = (int) $item->total_amount;
                $createdAt = $item->created_at ? Carbon::parse($item->created_at) : null;

                return [
                    'id' => (int) $item->id,
                    'title' => 'Red Envelope Created',
                    'description' => number_format($amount) . ' Coins spent creating red envelope',
                    'type' => 'deduct',
                    'amount' => $amount,
                    'balance' => (int) $user->buy_coins_wallet,
                    'from_name' => 'Red Envelope',
                    'from_uid' => null,
                    'role' => 'red_envelope_create',
                    'icon_type' => 'wallet',
                    'created_at' => $createdAt,
                    'created_date' => $createdAt ? $createdAt->format('d M Y, h:i A') : '',
                ];
            });

        // 10. Broadcast Creation
        $broadcastsCreated = DB::table('broadcasts')
            ->where('user_id', $user->id)
            ->get()
            ->map(function ($item) use ($user) {

                $cost = (int) ($item->cost ?? 0);
                $createdAt = $item->created_at ? Carbon::parse($item->created_at) : null;
                $msgSnippet = !empty($item->message) ? ': ' . Str::limit(e($item->message), 30) : '';

                return [
                    'id' => (int) $item->id,
                    'title' => 'Broadcast Created',
                    'description' => number_format($cost) . ' Coins spent creating broadcast' . $msgSnippet,
                    'type' => 'deduct',
                    'amount' => $cost,
                    'balance' => (int) $user->buy_coins_wallet,
                    'from_name' => 'System Broadcast',
                    'from_uid' => null,
                    'role' => 'broadcast_create',
                    'icon_type' => 'wallet',
                    'created_at' => $createdAt,
                    'created_date' => $createdAt ? $createdAt->format('d M Y, h:i A') : '',
                ];
            });

        // 11. Gift Sent Transactions
        $giftsSent = GiftTransaction::with(['receiver:id,name,uid', 'gift:id,name'])
            ->where('sender_id', $user->id)
            ->get()
            ->map(function ($item) use ($user) {

                $amount = (int) ($item->total_value ?? ($item->coin_value * ($item->multiplier ?? 1)));
                $createdAt = $item->created_at ? Carbon::parse($item->created_at) : null;
                $giftTitle = $item->gift->name ?? 'Gift';
                $receiverName = $item->receiver->name ?? 'User';

                return [
                    'id' => (int) $item->id,
                    'title' => 'Gift Sent',
                    'description' => number_format($amount) . ' Coins spent sending ' . $giftTitle . ' to ' . $receiverName,
                    'type' => 'deduct',
                    'amount' => $amount,
                    'balance' => (int) $user->buy_coins_wallet,
                    'from_name' => $receiverName,
                    'from_uid' => !empty($item->receiver?->uid) ? (int) $item->receiver->uid : null,
                    'role' => 'send_gift',
                    'icon_type' => 'wallet',
                    'created_at' => $createdAt,
                    'created_date' => $createdAt ? $createdAt->format('d M Y, h:i A') : '',
                ];
            });

        // 13. CP / Relationship Invitations Sent
        $cpInvitesSent = RelationshipInvitation::with(['receiver:id,name,uid', 'relationshipItem:id,name,required_coins'])
            ->where('sender_id', $user->id)
            ->get()
            ->map(function ($item) use ($user) {

                $amount = (int) ($item->relationshipItem->required_coins ?? 0);
                $createdAt = $item->created_at ? Carbon::parse($item->created_at) : null;
                $receiverName = $item->receiver->name ?? 'User';
                $relTitle = $item->relationshipItem->name ?? ucfirst($item->type ?? 'CP');

                return [
                    'id' => (int) $item->id,
                    'title' => 'CP Invite Sent',
                    'description' => 'Sent ' . $relTitle . ' invite to ' . $receiverName,
                    'type' => 'deduct',
                    'amount' => $amount,
                    'balance' => (int) $user->buy_coins_wallet,
                    'from_name' => $receiverName,
                    'from_uid' => !empty($item->receiver?->uid) ? (int) $item->receiver->uid : null,
                    'role' => 'cp_invite_send',
                    'icon_type' => 'wallet',
                    'created_at' => $createdAt,
                    'created_date' => $createdAt ? $createdAt->format('d M Y, h:i A') : '',
                ];
            });


        $history = $recharges
            ->concat($adminTransactions)
            ->concat($manualAdminTransactions)
            ->concat($onlineTransactions)
            ->concat($redEnvelopes)
            ->concat($redEnvelopesCreated)
            ->concat($broadcastsCreated)
            ->concat($giftsSent)
            ->concat($cpInvitesSent)
            ->concat($roomRewards)
            ->concat($treasureClaims)
            ->concat($inviteRewards)
            ->filter(function ($item) {
                return !empty($item['created_at']);
            })
            ->sortByDesc('created_at')
            ->values()
            ->map(function ($item) {

                return [
                    'id' => (int) $item['id'],
                    'title' => $item['title'],
                    'description' => $item['description'],
                    'type' => $item['type'],
                    'amount' => (int) $item['amount'],
                    'balance' => (int) $item['balance'],
                    'from_name' => $item['from_name'],
                    'from_uid' => !empty($item['from_uid']) ? (int) $item['from_uid'] : null,
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
