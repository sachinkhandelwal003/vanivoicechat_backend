<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Helper\Helper;
use App\Models\AppUser;
use Illuminate\Http\Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class GameController extends Controller
{

    public function getCode(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        if (!$user->uid) {
            return response()->json([
                'status' => false,
                'message' => 'User UID not found',
            ], 400);
        }

        $now = time();

        // SUD short-term code validity: 2 hours
        $expireTime = $now + (2 * 60 * 60);

        $payload = [
            'uid' => (string) $user->uid,
            'exp' => $expireTime,
            'app_id' => (string) config('services.sud.app_id'),
        ];

        $code = JWT::encode(
            $payload,
            config('services.sud.app_secret'),
            'HS256'
        );

        return response()->json([
            'status' => true,
            'message' => 'SUD code generated successfully',
            'code' => $code,
            'expire_date' => $expireTime * 1000,
        ]);
    }

    public function getSsToken(Request $request)
    {
        $code = $request->input('code');

        // 1. Code required hai
        if (!$code) {
            return response()->json([
                'ret_code' => 1,
                'ret_msg' => 'Code is required',
                'sdk_error_code' => 1004,
                'data' => [],
            ]);
        }

        try {

            // 2. SUD code ko verify/decode karo
            $decoded = JWT::decode(
                $code,
                new Key(config('services.sud.app_secret'), 'HS256')
            );

            // 3. App ID verify karo
            if (
                !isset($decoded->app_id) ||
                (string) $decoded->app_id !== (string) config('services.sud.app_id')
            ) {
                return response()->json([
                    'ret_code' => 1,
                    'ret_msg' => 'Invalid app id',
                    'sdk_error_code' => 1004,
                    'data' => [],
                ]);
            }

            // 4. UID token se nikalo
            if (!isset($decoded->uid)) {
                return response()->json([
                    'ret_code' => 1,
                    'ret_msg' => 'UID not found in code',
                    'sdk_error_code' => 1004,
                    'data' => [],
                ]);
            }

            $uid = (string) $decoded->uid;

            // 5. Vani user find karo
            $user = AppUser::where('uid', $uid)->first();

            if (!$user) {
                return response()->json([
                    'ret_code' => 1,
                    'ret_msg' => 'User not found',
                    'sdk_error_code' => 1004,
                    'data' => [],
                ]);
            }

            // 6. SSToken ki expiry
            // 2 hours
            $now = time();
            $expireTime = $now + (2 * 60 * 60);

            // 7. SSToken payload
            $payload = [
                'uid' => (string) $user->uid,
                'exp' => $expireTime,
                'app_id' => (string) config('services.sud.app_id'),
            ];

            // 8. SSToken generate
            $ssToken = JWT::encode(
                $payload,
                config('services.sud.app_secret'),
                'HS256'
            );

            // 9. User avatar
            $avatarUrl = $user->image
                ? Helper::showImage($user->image, true)
                : '';

            // 10. Gender SUD ke allowed values ke according
            $gender = '';

            if ($user->gender === 'male') {
                $gender = 'male';
            } elseif ($user->gender === 'female') {
                $gender = 'female';
            }

            // 11. SUD response
            return response()->json([
                'ret_code' => 0,
                'ret_msg' => '',
                'sdk_error_code' => 0,
                'data' => [
                    'ss_token' => $ssToken,
                    'expire_date' => $expireTime * 1000,
                    'user_info' => [
                        'uid' => (string) $user->uid,
                        'nick_name' => $user->name ?? '',
                        'avatar_url' => $avatarUrl,
                        'gender' => $gender,
                        'is_ai' => 0,
                        'ai_level' => 0,
                    ],
                ],
            ]);
        } catch (\Firebase\JWT\ExpiredException $e) {

            return response()->json([
                'ret_code' => 1,
                'ret_msg' => 'Code expired',
                'sdk_error_code' => 1005,
                'data' => [],
            ]);
        } catch (\Exception $e) {

            Log::error('SUD getSsToken Error', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'ret_code' => 1,
                'ret_msg' => 'Invalid code',
                'sdk_error_code' => 1004,
                'data' => [],
            ]);
        }
    }

    public function updateSsToken(Request $request)
    {
        $ssToken = $request->input('ss_token');

        // 1. Existing SSToken required
        if (!$ssToken) {
            return response()->json([
                'ret_code' => 1,
                'ret_msg' => 'SS token is required',
                'sdk_error_code' => 1004,
                'data' => [],
            ]);
        }

        try {

            // 2. Existing SSToken verify/decode
            $decoded = JWT::decode(
                $ssToken,
                new Key(
                    config('services.sud.app_secret'),
                    'HS256'
                )
            );

            // 3. App ID verify
            if (
                !isset($decoded->app_id) ||
                (string) $decoded->app_id !==
                (string) config('services.sud.app_id')
            ) {
                return response()->json([
                    'ret_code' => 1,
                    'ret_msg' => 'Invalid app id',
                    'sdk_error_code' => 1004,
                    'data' => [],
                ]);
            }

            // 4. UID extract
            if (!isset($decoded->uid)) {
                return response()->json([
                    'ret_code' => 1,
                    'ret_msg' => 'UID not found in SS token',
                    'sdk_error_code' => 1004,
                    'data' => [],
                ]);
            }

            $uid = (string) $decoded->uid;

            // 5. Vani user find
            $user = AppUser::where('uid', $uid)->first();

            if (!$user) {
                return response()->json([
                    'ret_code' => 1,
                    'ret_msg' => 'User not found',
                    'sdk_error_code' => 1004,
                    'data' => [],
                ]);
            }

            // 6. Generate new expiry
            $now = time();

            // New token valid for 2 hours
            $expireTime = $now + (2 * 60 * 60);

            // 7. New SSToken payload
            $payload = [
                'uid' => (string) $user->uid,
                'exp' => $expireTime,
                'app_id' => (string) config('services.sud.app_id'),
            ];

            // 8. Generate new SSToken
            $newSsToken = JWT::encode(
                $payload,
                config('services.sud.app_secret'),
                'HS256'
            );

            // 9. Return SUD response
            return response()->json([
                'ret_code' => 0,
                'ret_msg' => '',
                'sdk_error_code' => 0,
                'data' => [
                    'ss_token' => $newSsToken,
                    'expire_date' => $expireTime * 1000,
                ],
            ]);
        } catch (\Firebase\JWT\ExpiredException $e) {

            // Token expired
            return response()->json([
                'ret_code' => 1,
                'ret_msg' => 'SS token expired',
                'sdk_error_code' => 1005,
                'data' => [],
            ]);
        } catch (\Exception $e) {

            Log::error('SUD updateSsToken Error', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'ret_code' => 1,
                'ret_msg' => 'Invalid SS token',
                'sdk_error_code' => 1004,
                'data' => [],
            ]);
        }
    }

    public function getUserInfo(Request $request)
    {
        $uid = $request->input('uid');

        // UID required
        if (!$uid) {
            return response()->json([
                'ret_code' => 1,
                'ret_msg' => 'UID is required',
                'sdk_error_code' => 1004,
                'data' => [],
            ]);
        }

        try {

            // Find Vani user
            $user = AppUser::where('uid', (string) $uid)->first();

            if (!$user) {
                return response()->json([
                    'ret_code' => 1,
                    'ret_msg' => 'User not found',
                    'sdk_error_code' => 1004,
                    'data' => [],
                ]);
            }

            // User avatar
            $avatarUrl = '';

            if ($user->image) {
                $avatarUrl = Helper::showImage($user->image, true);
            }

            // Gender
            $gender = '';

            if ($user->gender === 'male') {
                $gender = 'male';
            } elseif ($user->gender === 'female') {
                $gender = 'female';
            }

            return response()->json([
                'ret_code' => 0,
                'ret_msg' => '',
                'sdk_error_code' => 0,
                'data' => [
                    'uid' => (string) $user->uid,
                    'nick_name' => $user->name ?? '',
                    'avatar_url' => $avatarUrl,
                    'gender' => $gender,
                    'is_ai' => 0,
                    'ai_level' => 0,
                ],
            ]);
        } catch (\Exception $e) {

            Log::error('SUD getUserInfo Error', [
                'message' => $e->getMessage(),
                'uid' => $uid,
            ]);

            return response()->json([
                'ret_code' => 1,
                'ret_msg' => 'Unable to get user information',
                'sdk_error_code' => 1004,
                'data' => [],
            ]);
        }
    }

    public function reportGameInfo(Request $request)
    {
        try {

            Log::info('SUD Report Game Info', [
                'request' => $request->all(),
            ]);

            return response()->json([
                'ret_code' => 0,
                'ret_msg' => '',
                'sdk_error_code' => 0,
                'data' => [
                    'received' => true,
                ],
            ]);
        } catch (\Exception $e) {

            Log::error('SUD reportGameInfo Error', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'ret_code' => 1,
                'ret_msg' => 'Unable to process game information',
                'sdk_error_code' => 1004,
                'data' => [],
            ]);
        }
    }

    public function notify(Request $request)
    {
        try {

            //  Get raw request body

            $rawBody = $request->getContent();

            //  Get SUD headers

            $sudAppId = $request->header('Sud-AppId');
            $sudTimestamp = $request->header('Sud-Timestamp');
            $sudNonce = $request->header('Sud-Nonce');
            $sudSignature = $request->header('Sud-Signature');

            //  Verify App ID

            if (
                !$sudAppId ||
                (string) $sudAppId !== (string) config('services.sud.app_id')
            ) {
                Log::warning('SUD Notify Invalid App ID', [
                    'app_id' => $sudAppId,
                ]);

                return response()->json([
                    'ret_code' => 1,
                    'ret_msg' => 'Invalid app id',
                ]);
            }

            // Signature verification

            if (!$sudTimestamp || !$sudNonce || !$sudSignature) {

                Log::warning('SUD Notify Missing Signature Headers');

                return response()->json([
                    'ret_code' => 1,
                    'ret_msg' => 'Missing signature headers',
                ]);
            }

            $signContent =
                $sudAppId . "\n" .
                $sudTimestamp . "\n" .
                $sudNonce . "\n" .
                $rawBody . "\n";

            $expectedSignature = hash_hmac(
                'sha1',
                $signContent,
                config('services.sud.app_secret')
            );

            if (!hash_equals($expectedSignature, $sudSignature)) {

                Log::warning('SUD Notify Invalid Signature', [
                    'expected' => $expectedSignature,
                    'received' => $sudSignature,
                ]);

                return response()->json([
                    'ret_code' => 1,
                    'ret_msg' => 'Invalid signature',
                ]);
            }

            //   Get JSON data

            $notifyData = json_decode($rawBody, true);

            if (!is_array($notifyData)) {

                return response()->json([
                    'ret_code' => 1,
                    'ret_msg' => 'Invalid request body',
                ]);
            }

            // Basic notification fields

            $notifyId = $notifyData['notify_id'] ?? null;
            $notifyTime = $notifyData['notify_time'] ?? null;
            $appId = $notifyData['app_id'] ?? null;
            $event = $notifyData['notify_event'] ?? null;
            $data = $notifyData['data'] ?? [];

            // App ID inside body verify

            if (
                !$appId ||
                (string) $appId !== (string) config('services.sud.app_id')
            ) {

                return response()->json([
                    'ret_code' => 1,
                    'ret_msg' => 'Invalid notification app id',
                ]);
            }

            //   Log complete notification

            Log::info('SUD Notify Received', [
                'notify_id' => $notifyId,
                'notify_time' => $notifyTime,
                'event' => $event,
                'data' => $data,
            ]);

            // Handle User Settlement

            if ($event === 'sud.mg.merchant.user.settle') {

                Log::info('SUD User Settlement', [
                    'notify_id' => $notifyId,
                    'data' => $data,
                ]);

                /*
            |--------------------------------------------------------------------------
            | IMPORTANT:
            | Abhi coins update nahi kar rahe.
            |
            | Pehle actual SUD settlement payload verify karenge.
            |--------------------------------------------------------------------------
            */
            }

            //  10. Handle Single Game Settlement
            elseif ($event === 'sud.mg.merchant.match.settle') {

                Log::info('SUD Single Game Settlement', [
                    'notify_id' => $notifyId,
                    'data' => $data,
                ]);

                /*
            |--------------------------------------------------------------------------
            | IMPORTANT:
            | Abhi coins update nahi kar rahe.
            |--------------------------------------------------------------------------
            */
            }

            //    Other events
            else {

                Log::info('SUD Other Notification Event', [
                    'notify_id' => $notifyId,
                    'event' => $event,
                    'data' => $data,
                ]);
            }

            // SUD success response

            return response()->json([
                'ret_code' => 0,
                'ret_msg' => 'SUCCESS',
            ]);
        } catch (\Exception $e) {

            Log::error('SUD Notify Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'ret_code' => 1,
                'ret_msg' => 'Internal server error',
            ]);
        }
    }
}
