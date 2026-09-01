<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CoinSeller;
use App\Helper\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use PhonePe\payments\v2\standardCheckout\StandardCheckoutClient;
use PhonePe\payments\v2\models\request\builders\StandardCheckoutPayRequestBuilder;
use PhonePe\Env;

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
                'image' => asset('storage/recharge_agency.png'),
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

        DB::beginTransaction();

        try {

            $client = StandardCheckoutClient::getInstance(
                config('services.phonepe.client_id'),
                (int) config('services.phonepe.client_version'),
                config('services.phonepe.client_secret'),
                // Env::PRODUCTION
                Env::UAT,
            );

            $merchantOrderId = 'VANI' . now()->format('YmdHis') . rand(1000, 9999);
            $callbackUrl = config('services.phonepe.callback')
                . '?merchantOrderId=' . $merchantOrderId;
            $payRequest = StandardCheckoutPayRequestBuilder::builder()
                ->merchantOrderId($merchantOrderId)
                ->amount((int) ($package->price * 100))
                ->redirectUrl($callbackUrl)
                ->message('Vani Coin Recharge')
                ->udf1((string) $user->id)
                ->udf2((string) $package->id)
                ->build();

            $payResponse = $client->pay($payRequest);

            DB::table('coin_transactions')->insert([
                'user_id'                 => $user->id,
                'package_id'              => $package->id,
                'merchant_transaction_id' => $merchantOrderId,
                'coins'                   => $package->coins,
                'bonus_coins'             => $package->bonus_coins,
                'total_coins'             => $package->total_coins,
                'amount'                  => $package->price,
                'payment_status'          => 'pending',
                'type'                    => 'pending',
                'created_at'              => now(),
                'updated_at'              => now(),
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Payment initiated successfully',
                'data' => [
                    'merchant_order_id' => $merchantOrderId,
                    'order_id'          => $payResponse->getOrderId(),
                    'redirect_url'      => $payResponse->getRedirectUrl(),
                    'expire_at'         => $payResponse->getExpireAt(),
                    'amount'            => (float) $package->price,
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

    public function phonePeCallback(Request $request)
    {
        $merchantOrderId = $request->merchantOrderId;

        if (!$merchantOrderId) {
            return response()->json([
                'success' => false,
                'message' => 'Merchant Order ID missing'
            ], 400);
        }

        return $this->verifyAndCreditCoins($merchantOrderId);
    }

    public function checkPhonePeStatus(Request $request)
    {
        $request->validate([
            'merchant_order_id' => 'required|string'
        ]);

        return $this->verifyAndCreditCoins($request->merchant_order_id);
    }

    private function verifyAndCreditCoins($merchantOrderId)
    {
        DB::beginTransaction();

        try {

            $client = StandardCheckoutClient::getInstance(
                config('services.phonepe.client_id'),
                (int) config('services.phonepe.client_version'),
                config('services.phonepe.client_secret'),
                Env::UAT // Production me Env::PRODUCTION
            );

            $status = $client->getOrderStatus($merchantOrderId, true);

            $transaction = DB::table('coin_transactions')
                ->where('merchant_transaction_id', $merchantOrderId)
                ->lockForUpdate()
                ->first();

            if (!$transaction) {
                DB::rollBack();

                return response()->json([
                    'status' => false,
                    'message' => 'Transaction not found'
                ], 404);
            }

            if (
                $status->getState() === 'COMPLETED' &&
                $transaction->payment_status !== 'success'
            ) {

                DB::table('app_users')
                    ->where('id', $transaction->user_id)
                    ->increment('total_points', $transaction->total_coins);

                DB::table('app_users')
                    ->where('id', $transaction->user_id)
                    ->increment('buy_coins_wallet', $transaction->total_coins);

                $paymentDetails = $status->getPaymentDetails();

                $phonePeTxnId = null;

                if (!empty($paymentDetails) && isset($paymentDetails[0]->transactionId)) {
                    $phonePeTxnId = $paymentDetails[0]->transactionId;
                }

                DB::table('coin_transactions')
                    ->where('id', $transaction->id)
                    ->update([
                        'transaction_id' => $phonePeTxnId,
                        'payment_status' => 'success',
                        'type' => 'credit',
                        'updated_at' => now(),
                    ]);
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'payment_status' => $status->getState(),
                'merchant_order_id' => $merchantOrderId,
                'amount' => $status->getAmount() / 100,
            ]);
        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }


    public function rechargeAgency()
    {
        $data = CoinSeller::with('user')
            ->where('status', 1)
            // ->where('invite_status', 'accept')
            ->get()
            ->map(function ($seller) {

                $history = DB::table('coin_recharge_histories')
                    ->where('seller_id', $seller->user_id)
                    ->selectRaw('
                    COALESCE(SUM(coin),0) as coins_sold,
                    COUNT(id) as orders,
                    COUNT(DISTINCT user_id) as users
                ')
                    ->first();

                return [
                    'seller_id'       => $seller->id,
                    'user_id'         => $seller->user->id,
                    'name'            => $seller->user->name,
                    'uid'             => $seller->user->uid,
                    'image'           => !empty($seller->user->image)
                        ? Helper::showImage($seller->user->image, true)
                        : null,
                    'country'         => $seller->user->country,
                    'role'            => $seller->is_merchant ? 'Merchant' : 'Coinseller',

                    // App Users table
                    'available_coins' => (int) $seller->user->total_points,

                    // Recharge History
                    'coins_sold'      => (int) $history->coins_sold,
                    'orders'          => (int) $history->orders,
                    'users'           => (int) $history->users,

                    'whatsapp'        => $seller->whatsapp_number,
                ];
            });

        return response()->json([
            'status' => true,
            'data'   => $data
        ]);
    }
}
