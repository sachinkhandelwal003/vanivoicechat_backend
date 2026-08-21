<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Helper\Helper;
use App\Models\AppUser;
use App\Models\Game;
use App\Models\GameSession;
use App\Models\GameSessionPlayer;
use Illuminate\Http\Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class GameController extends Controller
{

public function gameList(Request $request)
    {
        $games = Game::where('status', 1)
            ->orderBy('sort_order')
            ->get();

        $data = $games->map(function ($game) {

            return [
                'id' => $game->id,
                'name' => $game->name,
                'slug' => $game->slug,
                'sud_game_id' => $game->sud_game_id,
                'sud_game_type' => $game->sud_game_type,
                'description' => $game->description,
                'icon' => Helper::showImage($game->icon, true),
                'banner' => Helper::showImage($game->banner, true),
                'entry_coins' => (int) $game->entry_coins,
                'min_coins' => (int) $game->min_coins,
                'max_coins' => (int) $game->max_coins,
                'is_featured' => (bool) $game->is_featured,
                'sort_order' => (int) $game->sort_order,
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'Game list fetched successfully',
            'games' => $data,
        ]);
    }

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

            $reportType = $request->input('report_type');
            $reportMsg = $request->input('report_msg', []);

            if (!$reportType) {
                return response()->json([
                    'ret_code' => 1,
                    'ret_msg' => 'report_type is required',
                    'sdk_error_code' => 1001,
                    'data' => [],
                ], 400);
            }

            if (!in_array($reportType, ['game_start', 'game_settle'])) {
                return response()->json([
                    'ret_code' => 1,
                    'ret_msg' => 'Invalid report_type',
                    'sdk_error_code' => 1002,
                    'data' => [],
                ], 400);
            }

            if (empty($reportMsg)) {
                return response()->json([
                    'ret_code' => 1,
                    'ret_msg' => 'report_msg is required',
                    'sdk_error_code' => 1003,
                    'data' => [],
                ], 400);
            }

            $gameRoundId = $reportMsg['game_round_id'] ?? null;

            if (!$gameRoundId) {
                return response()->json([
                    'ret_code' => 1,
                    'ret_msg' => 'game_round_id is required',
                    'sdk_error_code' => 1004,
                    'data' => [],
                ], 400);
            }

            // GAME START


            if ($reportType === 'game_start') {

                DB::transaction(function () use ($request, $reportMsg, $gameRoundId) {

                    /*
                |--------------------------------------------------------------------------
                | Find existing game
                |--------------------------------------------------------------------------
                |
                | SUD can send the same report again.
                | game_round_id is our unique identifier.
                |
                */

                    $gameSession = GameSession::where(
                        'game_round_id',
                        $gameRoundId
                    )->first();

                    //  Create Game Session


                    if (!$gameSession) {

                        $gameSession = GameSession::create([
                            'mg_id' => $reportMsg['mg_id'] ?? null,

                            'mg_id_str' => $reportMsg['mg_id_str'] ?? null,

                            'room_id' => $reportMsg['room_id'] ?? null,

                            'game_mode' => $reportMsg['game_mode'] ?? null,

                            'game_mode_ex' => $reportMsg['game_mode_ex'] ?? null,

                            'game_round_id' => $gameRoundId,

                            'report_game_info_key' =>
                            $reportMsg['report_game_info_key']
                                ?? $request->input('report_game_info_key'),

                            'report_game_info_extras' =>
                            $request->input('report_game_info_extras'),

                            'status' => 'started',

                            'battle_start_at' =>
                            $reportMsg['battle_start_at'] ?? null,

                            'start_payload' => $request->all(),
                        ]);
                    } else {

                        /*
                    |--------------------------------------------------------------------------
                    | If duplicate game_start comes
                    |--------------------------------------------------------------------------
                    |
                    | Don't create another game.
                    | Just update missing/basic information.
                    |
                    */

                        $gameSession->update([
                            'mg_id' => $reportMsg['mg_id']
                                ?? $gameSession->mg_id,

                            'mg_id_str' => $reportMsg['mg_id_str']
                                ?? $gameSession->mg_id_str,

                            'room_id' => $reportMsg['room_id']
                                ?? $gameSession->room_id,

                            'game_mode' => $reportMsg['game_mode']
                                ?? $gameSession->game_mode,

                            'game_mode_ex' => $reportMsg['game_mode_ex']
                                ?? $gameSession->game_mode_ex,

                            'battle_start_at' => $reportMsg['battle_start_at']
                                ?? $gameSession->battle_start_at,

                            'start_payload' => $request->all(),
                        ]);
                    }

                    // Save Players


                    $players = $reportMsg['players'] ?? [];

                    foreach ($players as $player) {

                        $uid = isset($player['uid'])
                            ? (string) $player['uid']
                            : null;

                        if (!$uid) {
                            continue;
                        }
                        // Find Vani User


                        $user = AppUser::where('uid', $uid)->first();

                        //  Create / Update Player


                        GameSessionPlayer::updateOrCreate(
                            [
                                'game_session_id' => $gameSession->id,
                                'uid' => $uid,
                            ],
                            [
                                'user_id' => $user?->id,

                                'is_ai' =>
                                (int) ($player['is_ai'] ?? 0),

                                'ai_level' =>
                                (int) ($player['ai_level'] ?? 0),
                            ]
                        );
                    }
                });


                Log::info('SUD Game Started', [
                    'game_round_id' => $gameRoundId,
                    'mg_id' => $reportMsg['mg_id'] ?? null,
                    'room_id' => $reportMsg['room_id'] ?? null,
                ]);


                return response()->json([
                    'ret_code' => 0,
                    'ret_msg' => '',
                    'sdk_error_code' => 0,
                    'data' => [
                        'received' => true,
                    ],
                ]);
            }


            //   GAME SETTLE


            if ($reportType === 'game_settle') {

                DB::transaction(function () use ($request, $reportMsg, $gameRoundId) {

                    /*
                |--------------------------------------------------------------------------
                | Find Game Session
                |--------------------------------------------------------------------------
                */

                    $gameSession = GameSession::where(
                        'game_round_id',
                        $gameRoundId
                    )
                        ->lockForUpdate()
                        ->first();

                    /*
                |--------------------------------------------------------------------------
                | If game_start callback was missed
                |--------------------------------------------------------------------------
                |
                | We still create the game session so that
                | settlement data is not lost.
                |
                */

                    if (!$gameSession) {

                        $gameSession = GameSession::create([
                            'mg_id' => $reportMsg['mg_id'] ?? null,

                            'mg_id_str' => $reportMsg['mg_id_str'] ?? null,

                            'room_id' => $reportMsg['room_id'] ?? null,

                            'game_mode' => $reportMsg['game_mode'] ?? null,

                            'game_mode_ex' => $reportMsg['game_mode_ex'] ?? null,

                            'game_round_id' => $gameRoundId,

                            'report_game_info_key' =>
                            $reportMsg['report_game_info_key'] ?? null,

                            'report_game_info_extras' =>
                            $reportMsg['extras']
                                ?? $request->input('report_game_info_extras'),

                            'status' => 'completed',

                            'battle_start_at' =>
                            $reportMsg['battle_start_at'] ?? null,

                            'battle_end_at' =>
                            $reportMsg['battle_end_at'] ?? null,

                            'battle_duration' =>
                            $reportMsg['battle_duration'] ?? null,

                            'settle_payload' => $request->all(),
                        ]);
                    } else {

                        //    Update Game Session


                        $gameSession->update([
                            'mg_id' => $reportMsg['mg_id']
                                ?? $gameSession->mg_id,

                            'mg_id_str' => $reportMsg['mg_id_str']
                                ?? $gameSession->mg_id_str,

                            'room_id' => $reportMsg['room_id']
                                ?? $gameSession->room_id,

                            'game_mode' => $reportMsg['game_mode']
                                ?? $gameSession->game_mode,

                            'game_mode_ex' => $reportMsg['game_mode_ex']
                                ?? $gameSession->game_mode_ex,

                            'status' => 'completed',

                            'battle_start_at' =>
                            $reportMsg['battle_start_at']
                                ?? $gameSession->battle_start_at,

                            'battle_end_at' =>
                            $reportMsg['battle_end_at'] ?? null,

                            'battle_duration' =>
                            $reportMsg['battle_duration'] ?? null,

                            'settle_payload' => $request->all(),
                        ]);
                    }


                    //   Save Player Results


                    $results = $reportMsg['results'] ?? [];

                    foreach ($results as $result) {

                        $uid = isset($result['uid'])
                            ? (string) $result['uid']
                            : null;

                        if (!$uid) {
                            continue;
                        }

                        // Find Vani User


                        $user = AppUser::where('uid', $uid)->first();

                        // Find Existing Player


                        $player = GameSessionPlayer::where(
                            'game_session_id',
                            $gameSession->id
                        )
                            ->where('uid', $uid)
                            ->first();


                        //  If player wasn't received in game_start


                        if (!$player) {

                            $player = new GameSessionPlayer();

                            $player->game_session_id =
                                $gameSession->id;

                            $player->uid = $uid;

                            $player->user_id = $user?->id;
                        }

                        // Update Result


                        $player->user_id = $user?->id;

                        $player->is_ai =
                            (int) ($result['is_ai'] ?? 0);

                        $player->rank =
                            $result['rank'] ?? null;

                        $player->is_escaped =
                            (int) ($result['is_escaped'] ?? 0);

                        $player->is_win =
                            $result['is_win'] ?? null;

                        $player->score =
                            (int) ($result['score'] ?? 0);

                        $player->commission_score =
                            (int) ($result['commission_score'] ?? 0);

                        $player->award =
                            (int) ($result['award'] ?? 0);

                        $player->role =
                            $result['role'] ?? null;

                        $player->is_managed =
                            (int) ($result['is_managed'] ?? 0);

                        //   NOTE: Coin values are NOT calculated here.


                        $player->save();
                    }
                });


                Log::info('SUD Game Settled', [
                    'game_round_id' => $gameRoundId,
                    'mg_id' => $reportMsg['mg_id'] ?? null,
                    'room_id' => $reportMsg['room_id'] ?? null,
                    'results_count' => count($reportMsg['results'] ?? []),
                ]);


                return response()->json([
                    'ret_code' => 0,
                    'ret_msg' => '',
                    'sdk_error_code' => 0,
                    'data' => [
                        'received' => true,
                    ],
                ]);
            }


            // Fallback


            return response()->json([
                'ret_code' => 1,
                'ret_msg' => 'Unsupported report type',
                'sdk_error_code' => 1005,
                'data' => [],
            ], 400);
        } catch (\Throwable $e) {

            Log::error('SUD Report Game Info Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'ret_code' => 1,
                'ret_msg' => 'Unable to process game information',
                'sdk_error_code' => 1004,
                'data' => [],
            ], 500);
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
