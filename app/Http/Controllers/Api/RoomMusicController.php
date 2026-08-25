<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\AppUser;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\RoomMusicState;
use App\Models\RoomMusicPlaylist;
use App\Models\RoomMusicActivePlayer;
use App\Events\RoomMusicPlayed;
use App\Events\RoomMusicPaused;
use App\Events\RoomMusicOptionsUpdated;
use App\Events\RoomMusicVolumeUpdated;
use App\Events\RoomMusicResumed;
use App\Events\RoomMusicSeeked;
use App\Events\RoomMusicSongDeleted;
use App\Events\RoomSettingsUpdated;
use App\Helper\Helper;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Services\AgoraCloudPlayerService;
use App\Services\AgoraTokenService;
use Illuminate\Support\Facades\Log;

class RoomMusicController extends Controller
{

    public function addSong(Request $request): JsonResponse
    {
        $validation = Validator::make($request->all(), [
            'room_id' => 'required|integer|exists:rooms,id',
            'title' => 'required|string|max:255',
            'artist' => 'nullable|string|max:255',
            'audio' => 'nullable|file|mimes:mp3,mpeg,wav,aac,m4a,ogg|max:51200',
            'audio_url' => 'nullable|string|max:1000',
            'duration_seconds' => 'nullable|integer|min:0',
        ]);

        if ($validation->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validation->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $user = Auth::user();

            if (!$user) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthenticated',
                ], 401);
            }

            $room = Room::find($request->room_id);

            if (!$room) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Room not found',
                ], 404);
            }

            if (!$request->hasFile('audio') && empty($request->audio_url)) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Audio file or audio_url is required',
                ], 422);
            }

            \Log::info('ADD SONG REQUEST', [
                'room_id' => $request->room_id,
                'title' => $request->title,
                'artist' => $request->artist,
                'has_audio_file' => $request->hasFile('audio'),
                'audio_url_input' => $request->audio_url,
                'duration_seconds' => $request->duration_seconds,
                'user_id' => $user->id,
            ]);

            $finalAudioUrl = $request->audio_url;

            if ($request->hasFile('audio')) {
                $file = $request->file('audio');

                \Log::info('ADD SONG FILE DEBUG', [
                    'original_name' => $file->getClientOriginalName(),
                    'extension' => $file->getClientOriginalExtension(),
                    'mime' => $file->getMimeType(),
                    'size' => $file->getSize(),
                ]);

                $finalAudioUrl = Helper::saveFile($file, 'room_music');

                \Log::info('ADD SONG SAVED PATH', [
                    'saved_audio_path' => $finalAudioUrl,
                    'storage_exists' => !empty($finalAudioUrl)
                        ? \Storage::disk('public')->exists($finalAudioUrl)
                        : false,
                    'storage_size' => (!empty($finalAudioUrl) && \Storage::disk('public')->exists($finalAudioUrl))
                        ? \Storage::disk('public')->size($finalAudioUrl)
                        : null,
                    'storage_mime' => (!empty($finalAudioUrl) && \Storage::disk('public')->exists($finalAudioUrl))
                        ? \Storage::disk('public')->mimeType($finalAudioUrl)
                        : null,
                ]);
            }

            if (empty($finalAudioUrl)) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Audio upload failed',
                ], 422);
            }

            $lastPosition = RoomMusicPlaylist::where('room_id', $room->id)
                ->where('user_id', $user->id)
                ->max('position');

            $nextPosition = ($lastPosition ?? 0) + 1;

            $isFirstSong = !RoomMusicPlaylist::where('room_id', $room->id)
                ->where('user_id', $user->id)
                ->exists();

            $song = RoomMusicPlaylist::create([
                'room_id' => $room->id,
                'user_id' => $user->id,
                'title' => $request->title,
                'artist' => $request->artist,
                'audio_url' => $finalAudioUrl,
                'duration_seconds' => (int) ($request->duration_seconds ?? 0),
                'position' => $nextPosition,
                'is_active' => true,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Song added successfully',
                'data' => [
                    'id' => $song->id,
                    'room_id' => $song->room_id,
                    'title' => $song->title,
                    'artist' => $song->artist,
                    'audio_url' => $song->audio_url,
                    'duration_seconds' => $song->duration_seconds,
                    'position' => $song->position,
                    'is_active' => $song->is_active,
                    'is_first_song' => $isFirstSong,
                ]
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            \Log::error('ADD SONG FAILED', [
                'room_id' => $request->room_id ?? null,
                'title' => $request->title ?? null,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to add song',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // public function playSong(Request $request): JsonResponse
    // {
    //     $validation = Validator::make($request->all(), [
    //         'room_id' => 'required|integer|exists:rooms,id',
    //         'playlist_id' => 'required|integer|exists:room_music_playlist,id',
    //         'current_position_sec' => 'nullable|integer|min:0',
    //     ]);

    //     if ($validation->fails()) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => $validation->errors()
    //         ], 422);
    //     }

    //     DB::beginTransaction();

    //     try {
    //         $user = Auth::user();

    //         if (!$user) {
    //             DB::rollBack();
    //             return response()->json([
    //                 'status' => false,
    //                 'message' => 'Unauthenticated',
    //             ], 401);
    //         }

    //         $room = Room::find($request->room_id);

    //         if (!$room) {
    //             DB::rollBack();
    //             return response()->json([
    //                 'status' => false,
    //                 'message' => 'Room not found',
    //             ], 404);
    //         }

    //         $song = RoomMusicPlaylist::where('id', $request->playlist_id)
    //             ->where('room_id', $room->id)
    //             ->where('is_active', 1)
    //             ->first();

    //         if (!$song) {
    //             DB::rollBack();
    //             return response()->json([
    //                 'status' => false,
    //                 'message' => 'Song not found in this room playlist',
    //             ], 404);
    //         }

    //         if (empty($song->audio_url)) {
    //             DB::rollBack();
    //             return response()->json([
    //                 'status' => false,
    //                 'message' => 'Audio URL missing',
    //             ], 422);
    //         }

    //         $streamUrl = Helper::showImage($song->audio_url, true);

    //         if (!filter_var($streamUrl, FILTER_VALIDATE_URL)) {
    //             DB::rollBack();
    //             return response()->json([
    //                 'status' => false,
    //                 'message' => 'Invalid audio URL',
    //                 'error' => $streamUrl,
    //             ], 422);
    //         }

    //         $positionSec = (int) ($request->current_position_sec ?? 0);
    //         $channelName = 'room_' . $room->id;

    //         /** @var \App\Services\AgoraCloudPlayerService $cloudPlayerService */
    //         $cloudPlayerService = app(\App\Services\AgoraCloudPlayerService::class);

    //         /** @var \App\Services\AgoraTokenService $agoraTokenService */
    //         $agoraTokenService = app(\App\Services\AgoraTokenService::class);

    //         $existingUserActivePlayer = \App\Models\RoomMusicActivePlayer::where('room_id', $room->id)
    //             ->where('started_by', $user->id)
    //             ->where('is_active', true)
    //             ->whereIn('status', ['playing', 'paused'])
    //             ->lockForUpdate()
    //             ->latest('id')
    //             ->first();

    //         if ($existingUserActivePlayer) {
    //             \Log::info('Existing active player found for same user in room, stopping old player first', [
    //                 'room_id' => $room->id,
    //                 'user_id' => $user->id,
    //                 'old_active_player_id' => $existingUserActivePlayer->id,
    //                 'old_agora_player_id' => $existingUserActivePlayer->agora_player_id,
    //                 'old_playlist_id' => $existingUserActivePlayer->playlist_id,
    //                 'old_status' => $existingUserActivePlayer->status,
    //             ]);

    //             if (!empty($existingUserActivePlayer->agora_player_id)) {
    //                 try {
    //                     $deleteOldResponse = $cloudPlayerService->deletePlayer($existingUserActivePlayer->agora_player_id);

    //                     \Log::info('Old same-user Agora player deleted before new play', [
    //                         'room_id' => $room->id,
    //                         'user_id' => $user->id,
    //                         'old_active_player_id' => $existingUserActivePlayer->id,
    //                         'old_agora_player_id' => $existingUserActivePlayer->agora_player_id,
    //                         'delete_response' => $deleteOldResponse,
    //                     ]);

    //                     usleep(800000);
    //                 } catch (\Throwable $e) {
    //                     \Log::warning('Failed to delete old same-user Agora player before new play', [
    //                         'room_id' => $room->id,
    //                         'user_id' => $user->id,
    //                         'old_active_player_id' => $existingUserActivePlayer->id,
    //                         'old_agora_player_id' => $existingUserActivePlayer->agora_player_id,
    //                         'error' => $e->getMessage(),
    //                     ]);
    //                 }
    //             }

    //             $existingUserActivePlayer->update([
    //                 'status' => 'stopped',
    //                 'is_active' => false,
    //                 'started_at' => null,
    //             ]);
    //         }

    //         $lastUserVolume = \App\Models\RoomMusicActivePlayer::where('room_id', $room->id)
    //             ->where('started_by', $user->id)
    //             ->orderByDesc('id')
    //             ->value('volume');

    //         $defaultVolume = is_null($lastUserVolume) ? 100 : (int) $lastUserVolume;

    //         // HAR PLAYER KE LIYE UNIQUE UID
    //         do {
    //             $systemUid = random_int(100000, 999999999);
    //         } while (
    //             \App\Models\RoomMusicActivePlayer::where('system_uid', $systemUid)
    //             ->where('is_active', 1)
    //             ->exists()
    //         );

    //         // HAR PLAYER KE LIYE UNIQUE PLAYER NAME
    //         $playerName = 'room_' . $room->id . '_song_' . $song->id . '_user_' . $user->id . '_' . time() . '_' . random_int(1000, 9999);

    //         \Log::info('playSong started', [
    //             'room_id' => $room->id,
    //             'playlist_id' => $song->id,
    //             'song_title' => $song->title,
    //             'audio_url' => $streamUrl,
    //             'channel_name' => $channelName,
    //             'player_name' => $playerName,
    //             'system_uid' => $systemUid,
    //             'requested_position' => $positionSec,
    //             'user_id' => $user->id,
    //             'default_volume' => $defaultVolume,
    //         ]);

    //         $token = $agoraTokenService->generateRtcToken($channelName, $systemUid);

    //         if (empty($token)) {
    //             DB::rollBack();
    //             return response()->json([
    //                 'status' => false,
    //                 'message' => 'Agora token generation failed',
    //             ], 500);
    //         }

    //         $payload = [
    //             'player' => [
    //                 'name' => $playerName,
    //                 'streamUrl' => $streamUrl,
    //                 'channelName' => $channelName,
    //                 'token' => $token,
    //                 'uid' => $systemUid,
    //                 'idleTimeout' => 300,
    //             ]
    //         ];

    //         \Log::info('Agora create player payload', [
    //             'room_id' => $room->id,
    //             'payload' => [
    //                 'player' => [
    //                     'name' => $playerName,
    //                     'streamUrl' => $streamUrl,
    //                     'channelName' => $channelName,
    //                     'uid' => $systemUid,
    //                     'idleTimeout' => 300,
    //                     'token_present' => !empty($token),
    //                 ]
    //             ]
    //         ]);

    //         $response = null;
    //         $playerId = null;
    //         $attempts = 0;
    //         $maxAttempts = 3;

    //         while ($attempts < $maxAttempts) {
    //             $attempts++;

    //             $response = $cloudPlayerService->createPlayer($payload);

    //             \Log::info('Agora create player attempt', [
    //                 'room_id' => $room->id,
    //                 'attempt' => $attempts,
    //                 'response' => $response,
    //             ]);

    //             if (!empty($response['ok'])) {
    //                 $playerId = $response['player_id'] ?? null;
    //                 break;
    //             }

    //             $status = (int) ($response['status'] ?? 0);

    //             if (in_array($status, [404, 429, 500, 502, 503, 504], true)) {
    //                 if ($attempts < $maxAttempts) {
    //                     $waitSeconds = match ($attempts) {
    //                         1 => 5,
    //                         2 => 10,
    //                         default => 15,
    //                     };

    //                     \Log::warning('Agora create retry scheduled', [
    //                         'room_id' => $room->id,
    //                         'attempt' => $attempts,
    //                         'status' => $status,
    //                         'wait_seconds' => $waitSeconds,
    //                     ]);

    //                     sleep($waitSeconds);
    //                     continue;
    //                 }
    //             }

    //             break;
    //         }

    //         if (!$playerId) {
    //             DB::rollBack();

    //             return response()->json([
    //                 'status' => false,
    //                 'message' => 'Agora failed',
    //                 'error' => $response['body'] ?? 'Agora player id missing',
    //                 'agora_debug' => [
    //                     'status_code' => $response['status'] ?? null,
    //                     'request_id' => $response['request_id'] ?? null,
    //                     'resource_id' => $response['resource_id'] ?? null,
    //                     'attempts' => $attempts,
    //                 ]
    //             ], 500);
    //         }

    //         $verifyResponse = null;
    //         $verifyStatus = null;

    //         try {
    //             usleep(1500000);

    //             $verifyResponse = $cloudPlayerService->getPlayer($playerId);

    //             \Log::info('Agora player verify response', [
    //                 'room_id' => $room->id,
    //                 'player_id' => $playerId,
    //                 'verify_response' => $verifyResponse,
    //             ]);

    //             $verifyStatus =
    //                 $verifyResponse['json']['player']['status']
    //                 ?? $verifyResponse['body']['player']['status']
    //                 ?? null;
    //         } catch (\Throwable $e) {
    //             \Log::warning('Agora player verify failed', [
    //                 'room_id' => $room->id,
    //                 'player_id' => $playerId,
    //                 'error' => $e->getMessage(),
    //             ]);
    //         }

    //         $activePlayer = \App\Models\RoomMusicActivePlayer::create([
    //             'room_id' => $room->id,
    //             'playlist_id' => $song->id,
    //             'started_by' => $user->id,
    //             'agora_player_id' => $playerId,
    //             'player_name' => $playerName,
    //             'system_uid' => $systemUid,
    //             'agora_sequence' => 0,
    //             'current_position_sec' => $positionSec,
    //             'volume' => $defaultVolume,
    //             'is_loop' => false,
    //             'is_active' => true,
    //             'status' => 'playing',
    //             'started_at' => now(),
    //         ]);

    //         DB::commit();

    //         $song->load('addedBy:id,name,image');

    //         broadcast(new RoomMusicPlayed($room->id, $song, $activePlayer, $user))->toOthers();

    //         return response()->json([
    //             'status' => true,
    //             'message' => 'Song played successfully',
    //             'data' => [
    //                 'song' => [
    //                     'id' => $song->id,
    //                     'room_id' => $song->room_id,
    //                     'title' => $song->title,
    //                     'artist' => $song->artist,
    //                     'audio_url' => $streamUrl,
    //                     'duration_seconds' => $song->duration_seconds,
    //                     'position' => $song->position,
    //                     'is_active' => $song->is_active,
    //                     'added_by' => [
    //                         'id' => optional($song->addedBy)->id,
    //                         'name' => optional($song->addedBy)->name,
    //                         'image' => optional($song->addedBy)->image
    //                             ? (
    //                                 Str::startsWith(optional($song->addedBy)->image, ['http://', 'https://'])
    //                                 ? optional($song->addedBy)->image
    //                                 : Helper::showImage(optional($song->addedBy)->image, true)
    //                             )
    //                             : null,
    //                     ],
    //                 ],
    //                 'music_state' => [
    //                     'active_player_id' => $activePlayer->id,
    //                     'room_id' => $activePlayer->room_id,
    //                     'current_playlist_id' => $activePlayer->playlist_id,
    //                     'agora_player_id' => $activePlayer->agora_player_id,
    //                     'system_uid' => $activePlayer->system_uid,
    //                     'is_music_active' => $activePlayer->is_active,
    //                     'status' => $activePlayer->status,
    //                     'current_position_sec' => $activePlayer->current_position_sec,
    //                     'started_at' => optional($activePlayer->started_at)?->format('Y-m-d H:i:s'),
    //                     'volume' => $activePlayer->volume,
    //                     'is_loop' => $activePlayer->is_loop,
    //                     'is_shuffle' => false,
    //                     'last_action_by' => $activePlayer->started_by,
    //                 ],
    //                 'agora_debug' => [
    //                     'channel_name' => $channelName,
    //                     'player_name' => $playerName,
    //                     'system_uid' => $systemUid,
    //                     'player_id' => $playerId,
    //                     'verify_status' => $verifyStatus,
    //                     'verify_response' => $verifyResponse,
    //                     'attempts' => $attempts,
    //                     'request_id' => $response['request_id'] ?? null,
    //                     'resource_id' => $response['resource_id'] ?? null,
    //                 ],
    //             ]
    //         ]);
    //     } catch (\Throwable $e) {
    //         DB::rollBack();

    //         \Log::error('playSong failed', [
    //             'room_id' => $request->room_id ?? null,
    //             'playlist_id' => $request->playlist_id ?? null,
    //             'error' => $e->getMessage(),
    //             'line' => $e->getLine(),
    //             'file' => $e->getFile(),
    //             'trace' => $e->getTraceAsString(),
    //         ]);

    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Failed to play song',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    // }

    public function playSong(Request $request): JsonResponse
    {
        $validation = Validator::make($request->all(), [
            'room_id' => 'required|integer|exists:rooms,id',
            'playlist_id' => 'required|integer|exists:room_music_playlist,id',
            'current_position_sec' => 'nullable|integer|min:0',
            'auto_play' => 'nullable|boolean',
        ]);

        if ($validation->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validation->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $user = Auth::user();

            if (!$user) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthenticated',
                ], 401);
            }

            $room = Room::find($request->room_id);

            if (!$room) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Room not found',
                ], 404);
            }

            $song = RoomMusicPlaylist::where('id', $request->playlist_id)
                ->where('room_id', $room->id)
                ->where('is_active', 1)
                ->first();

            if (!$song) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Song not found in this room playlist',
                ], 404);
            }

            if (empty($song->audio_url)) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Audio URL missing',
                ], 422);
            }

            $streamUrl = Helper::showImage($song->audio_url, true);

            if (!filter_var($streamUrl, FILTER_VALIDATE_URL)) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid audio URL',
                    'error' => $streamUrl,
                ], 422);
            }

            $positionSec = (int) ($request->current_position_sec ?? 0);
            $isAutoPlay = (bool) $request->input('auto_play', false);
            $channelName = 'room_' . $room->id;

            /** @var \App\Services\AgoraCloudPlayerService $cloudPlayerService */
            $cloudPlayerService = app(\App\Services\AgoraCloudPlayerService::class);

            /** @var \App\Services\AgoraTokenService $agoraTokenService */
            $agoraTokenService = app(\App\Services\AgoraTokenService::class);

            /*
        |--------------------------------------------------------------------------
        | SAME USER ek room me ek hi active song chala sake
        |--------------------------------------------------------------------------
        */
            $existingUserActivePlayer = \App\Models\RoomMusicActivePlayer::where('room_id', $room->id)
                ->where('started_by', $user->id)
                ->where('is_active', true)
                ->whereIn('status', ['playing', 'paused'])
                ->lockForUpdate()
                ->latest('id')
                ->first();

            if ($existingUserActivePlayer) {
                \Log::info('Existing active player found for same user in room, stopping old player first', [
                    'room_id' => $room->id,
                    'user_id' => $user->id,
                    'old_active_player_id' => $existingUserActivePlayer->id,
                    'old_agora_player_id' => $existingUserActivePlayer->agora_player_id,
                    'old_playlist_id' => $existingUserActivePlayer->playlist_id,
                    'old_status' => $existingUserActivePlayer->status,
                    'auto_play' => $isAutoPlay,
                ]);

                if (!empty($existingUserActivePlayer->agora_player_id)) {
                    try {
                        $deleteOldResponse = $cloudPlayerService->deletePlayer($existingUserActivePlayer->agora_player_id);

                        \Log::info('Old same-user Agora player deleted before new play', [
                            'room_id' => $room->id,
                            'user_id' => $user->id,
                            'old_active_player_id' => $existingUserActivePlayer->id,
                            'old_agora_player_id' => $existingUserActivePlayer->agora_player_id,
                            'delete_response' => $deleteOldResponse,
                        ]);

                        if (!$isAutoPlay) {
                            usleep(300000);
                        }
                    } catch (\Throwable $e) {
                        \Log::warning('Failed to delete old same-user Agora player before new play', [
                            'room_id' => $room->id,
                            'user_id' => $user->id,
                            'old_active_player_id' => $existingUserActivePlayer->id,
                            'old_agora_player_id' => $existingUserActivePlayer->agora_player_id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                $existingUserActivePlayer->update([
                    'status' => 'stopped',
                    'is_active' => false,
                    // 'started_at' => null,
                ]);
            }

            /*
        |--------------------------------------------------------------------------
        | LAST USER VOLUME remember karo
        |--------------------------------------------------------------------------
        */
            $lastUserPlayer  = \App\Models\RoomMusicActivePlayer::where('room_id', $room->id)
                ->where('started_by', $user->id)
                ->orderByDesc('id')
                ->first();

            $defaultVolume = $lastUserPlayer && !is_null($lastUserPlayer->volume)
                ? (int) $lastUserPlayer->volume
                : 100;

            $defaultIsLoop = $lastUserPlayer
                ? (bool) $lastUserPlayer->is_loop
                : false;

            \Log::info('Resolved default volume for new player', [
                'room_id' => $room->id,
                'user_id' => $user->id,
                'last_user_volume' => $lastUserPlayer,
                'default_volume_used' => $defaultVolume,
            ]);

            /*
        |--------------------------------------------------------------------------
        | HAR PLAYER KE LIYE UNIQUE UID
        |--------------------------------------------------------------------------
        */
            do {
                $systemUid = random_int(100000, 999999999);
            } while (
                \App\Models\RoomMusicActivePlayer::where('system_uid', $systemUid)
                ->where('is_active', 1)
                ->exists()
            );

            /*
        |--------------------------------------------------------------------------
        | HAR PLAYER KE LIYE UNIQUE PLAYER NAME
        |--------------------------------------------------------------------------
        */
            $playerName = 'room_' . $room->id . '_song_' . $song->id . '_user_' . $user->id . '_' . time() . '_' . random_int(1000, 9999);

            \Log::info('playSong started', [
                'room_id' => $room->id,
                'playlist_id' => $song->id,
                'song_title' => $song->title,
                'audio_url' => $streamUrl,
                'channel_name' => $channelName,
                'player_name' => $playerName,
                'system_uid' => $systemUid,
                'requested_position' => $positionSec,
                'user_id' => $user->id,
                'default_volume' => $defaultVolume,
                'auto_play' => $isAutoPlay,
            ]);

            $token = $agoraTokenService->generateRtcToken($channelName, $systemUid);

            if (empty($token)) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Agora token generation failed',
                ], 500);
            }

            $payload = [
                'player' => [
                    'name' => $playerName,
                    'streamUrl' => $streamUrl,
                    'channelName' => $channelName,
                    'token' => $token,
                    'uid' => $systemUid,
                    'idleTimeout' => 300,
                ]
            ];

            \Log::info('Agora create player payload', [
                'room_id' => $room->id,
                'payload' => [
                    'player' => [
                        'name' => $playerName,
                        'streamUrl' => $streamUrl,
                        'channelName' => $channelName,
                        'uid' => $systemUid,
                        'idleTimeout' => 300,
                        'token_present' => !empty($token),
                    ]
                ]
            ]);

            $response = null;
            $playerId = null;
            $attempts = 0;
            $maxAttempts = $isAutoPlay ? 2 : 3;

            while ($attempts < $maxAttempts) {
                $attempts++;

                $response = $cloudPlayerService->createPlayer($payload);

                \Log::info('Agora create player attempt', [
                    'room_id' => $room->id,
                    'attempt' => $attempts,
                    'response' => $response,
                    'auto_play' => $isAutoPlay,
                ]);

                if (!empty($response['ok'])) {
                    $playerId = $response['player_id'] ?? null;
                    break;
                }

                $status = (int) ($response['status'] ?? 0);

                if (in_array($status, [404, 429, 500, 502, 503, 504], true) && $attempts < $maxAttempts) {
                    $waitMs = match ($attempts) {
                        1 => $isAutoPlay ? 250 : 500,
                        2 => $isAutoPlay ? 600 : 1200,
                        default => 1500,
                    };

                    \Log::warning('Agora create retry scheduled', [
                        'room_id' => $room->id,
                        'attempt' => $attempts,
                        'status' => $status,
                        'wait_ms' => $waitMs,
                        'auto_play' => $isAutoPlay,
                    ]);

                    usleep($waitMs * 1000);
                    continue;
                }

                break;
            }

            if (!$playerId) {
                DB::rollBack();

                return response()->json([
                    'status' => false,
                    'message' => 'Agora failed',
                    'error' => $response['body'] ?? 'Agora player id missing',
                    'agora_debug' => [
                        'status_code' => $response['status'] ?? null,
                        'request_id' => $response['request_id'] ?? null,
                        'resource_id' => $response['resource_id'] ?? null,
                        'attempts' => $attempts,
                        'auto_play' => $isAutoPlay,
                    ]
                ], 500);
            }

            $verifyResponse = null;
            $verifyStatus = null;

            if (!$isAutoPlay) {
                try {
                    usleep(500000);

                    $verifyResponse = $cloudPlayerService->getPlayer($playerId);

                    \Log::info('Agora player verify response', [
                        'room_id' => $room->id,
                        'player_id' => $playerId,
                        'verify_response' => $verifyResponse,
                    ]);

                    $verifyStatus =
                        $verifyResponse['json']['player']['status']
                        ?? $verifyResponse['body']['player']['status']
                        ?? null;
                } catch (\Throwable $e) {
                    \Log::warning('Agora player verify failed', [
                        'room_id' => $room->id,
                        'player_id' => $playerId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            /*
        |--------------------------------------------------------------------------
        | NEW PLAYER PAR REMEMBERED VOLUME APPLY KARO
        |--------------------------------------------------------------------------
        */
            $agoraSequence = 0;
            $volumeSetResponse = null;

            if ($defaultVolume !== 100) {
                try {
                    $volumeSetResponse = $cloudPlayerService->updatePlayerVolume(
                        $playerId,
                        1,
                        $defaultVolume,
                        false
                    );

                    \Log::info('Applied remembered volume to new player', [
                        'room_id' => $room->id,
                        'user_id' => $user->id,
                        'player_id' => $playerId,
                        'volume' => $defaultVolume,
                        'response' => $volumeSetResponse,
                    ]);

                    if (!empty($volumeSetResponse['ok'])) {
                        $agoraSequence = 1;
                    }
                } catch (\Throwable $e) {
                    \Log::warning('Failed to apply remembered volume to new player', [
                        'room_id' => $room->id,
                        'user_id' => $user->id,
                        'player_id' => $playerId,
                        'volume' => $defaultVolume,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $activePlayer = \App\Models\RoomMusicActivePlayer::create([
                'room_id' => $room->id,
                'playlist_id' => $song->id,
                'started_by' => $user->id,
                'agora_player_id' => $playerId,
                'player_name' => $playerName,
                'system_uid' => $systemUid,
                'agora_sequence' => $agoraSequence,
                'current_position_sec' => $positionSec,
                'volume' => $defaultVolume,
                'is_loop' => $defaultIsLoop,
                'is_active' => true,
                'status' => 'playing',
                'started_at' => now(),
            ]);

            DB::commit();

            $song->load('addedBy:id,name,image');

            broadcast(new RoomMusicPlayed($room->id, $song, $activePlayer, $user))->toOthers();

            return response()->json([
                'status' => true,
                'message' => 'Song played successfully',
                'data' => [
                    'song' => [
                        'id' => $song->id,
                        'room_id' => $song->room_id,
                        'title' => $song->title,
                        'artist' => $song->artist,
                        'audio_url' => $streamUrl,
                        'duration_seconds' => $song->duration_seconds,
                        'position' => $song->position,
                        'is_active' => $song->is_active,
                        'added_by' => [
                            'id' => optional($song->addedBy)->id,
                            'name' => optional($song->addedBy)->name,
                            'image' => optional($song->addedBy)->image
                                ? (
                                    Str::startsWith(optional($song->addedBy)->image, ['http://', 'https://'])
                                    ? optional($song->addedBy)->image
                                    : Helper::showImage(optional($song->addedBy)->image, true)
                                )
                                : null,
                        ],
                    ],
                    'music_state' => [
                        'active_player_id' => $activePlayer->id,
                        'room_id' => $activePlayer->room_id,
                        'current_playlist_id' => $activePlayer->playlist_id,
                        'agora_player_id' => $activePlayer->agora_player_id,
                        'system_uid' => $activePlayer->system_uid,
                        'is_music_active' => $activePlayer->is_active,
                        'status' => $activePlayer->status,
                        'current_position_sec' => $activePlayer->current_position_sec,
                        'started_at' => !empty($activePlayer->started_at)
                            ? \Carbon\Carbon::parse($activePlayer->started_at)->format('Y-m-d H:i:s')
                            : null,
                        'volume' => $activePlayer->volume,
                        'is_loop' => $activePlayer->is_loop,
                        'is_shuffle' => false,
                        'last_action_by' => $activePlayer->started_by,
                    ],
                    'agora_debug' => [
                        'channel_name' => $channelName,
                        'player_name' => $playerName,
                        'system_uid' => $systemUid,
                        'player_id' => $playerId,
                        'verify_status' => $verifyStatus,
                        'verify_response' => $verifyResponse,
                        'attempts' => $attempts,
                        'request_id' => $response['request_id'] ?? null,
                        'resource_id' => $response['resource_id'] ?? null,
                        'remembered_volume' => $defaultVolume,
                        'is_loop_used' => $defaultIsLoop,
                        'applied_initial_sequence' => $agoraSequence,
                        'volume_set_response' => $volumeSetResponse,
                        'auto_play' => $isAutoPlay,
                    ],
                ]
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            \Log::error('playSong failed', [
                'room_id' => $request->room_id ?? null,
                'playlist_id' => $request->playlist_id ?? null,
                'auto_play' => $request->input('auto_play', false),
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to play song',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function musicList(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthenticated',
                ], 401);
            }

            $playlist = RoomMusicPlaylist::with('addedBy:id,name,image')
                // ->where('room_id', $room->id)
                ->where('user_id', $user->id)
                ->where('is_active', 1)
                ->orderBy('position', 'asc')
                ->get();

            $actualPosition = 0;

            $playlistData = $playlist->map(function ($song) {
                return [
                    'id' => $song->id,
                    'room_id' => $song->room_id,
                    'title' => $song->title,
                    'artist' => $song->artist,
                    'audio_url' => Helper::showImage($song->audio_url, true),
                    'duration_seconds' => $song->duration_seconds,
                    'position' => $song->position,
                    'is_active' => $song->is_active,
                    'added_by' => [
                        'id' => optional($song->addedBy)->id,
                        'name' => optional($song->addedBy)->name,
                        'image' => optional($song->addedBy)->image,
                    ],
                    'created_at' => $song->created_at ? $song->created_at->format('Y-m-d H:i:s') : null,
                    'updated_at' => $song->updated_at ? $song->updated_at->format('Y-m-d H:i:s') : null,
                ];
            });

            return response()->json([
                'status' => true,
                'message' => 'Music list fetched successfully',
                'data' => $playlistData,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch music list',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function pauseSong(Request $request): JsonResponse
    {
        $validation = Validator::make($request->all(), [
            'room_id' => 'required|integer|exists:rooms,id',
            'active_player_id' => 'required|integer|exists:room_music_active_players,id',
            'current_position_sec' => 'required|integer|min:0',
        ]);

        if ($validation->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validation->errors(),
            ], 422);
        }

        DB::beginTransaction();

        try {
            $user = Auth::user();

            if (!$user) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthenticated',
                ], 401);
            }

            $room = Room::find($request->room_id);

            if (!$room) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Room not found',
                ], 404);
            }

            $musicState = \App\Models\RoomMusicActivePlayer::where('id', $request->active_player_id)
                ->where('room_id', $room->id)
                ->lockForUpdate()
                ->first();

            if (!$musicState) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Active player not found',
                ], 404);
            }

            if (empty($musicState->agora_player_id)) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Agora player id not found',
                ], 404);
            }

            if ($musicState->status !== 'playing') {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Music is not currently playing',
                ], 422);
            }

            $cloudPlayerService = app(\App\Services\AgoraCloudPlayerService::class);

            $sequence = (int) $musicState->agora_sequence;
            $agoraResponse = null;
            $usedSequence = $sequence;

            // Retry if sequence stale
            for ($i = 0; $i < 3; $i++) {
                $usedSequence = $sequence + $i;

                $agoraResponse = $cloudPlayerService->pausePlayer(
                    $musicState->agora_player_id,
                    $usedSequence
                );

                if (!empty($agoraResponse['ok'])) {
                    break;
                }

                $body = strtolower($agoraResponse['body'] ?? '');

                if (!str_contains($body, 'sequence is too small')) {
                    break;
                }
            }

            if (!$agoraResponse || empty($agoraResponse['ok'])) {
                if ((int) ($agoraResponse['status'] ?? 0) === 404) {
                    $musicState->update([
                        'status' => 'stopped',
                        'is_active' => false,
                        // 'started_at' => null,
                        'agora_sequence' => $usedSequence + 1,
                    ]);

                    DB::commit();

                    return response()->json([
                        'status' => false,
                        'message' => 'Agora player not found or already destroyed',
                        'error' => $agoraResponse['body'] ?? null,
                    ], 404);
                }

                DB::rollBack();

                return response()->json([
                    'status' => false,
                    'message' => 'Agora pause failed',
                    'error' => $agoraResponse['body'] ?? 'Unknown Agora error',
                    'current_db_sequence' => $sequence,
                ], 500);
            }

            $musicState->update([
                'status' => 'paused',
                'current_position_sec' => (int) $request->current_position_sec,
                // 'started_at' => null,
                'agora_sequence' => $usedSequence + 1,
            ]);

            $musicState->refresh();

            DB::commit();

            broadcast(new RoomMusicPaused($room->id, $musicState, $user))->toOthers();

            return response()->json([
                'status' => true,
                'message' => 'Music paused successfully',
                'data' => [
                    'room_id' => $musicState->room_id,
                    'current_playlist_id' => $musicState->playlist_id,
                    'active_player_id' => $musicState->id,
                    'agora_player_id' => $musicState->agora_player_id,
                    'agora_sequence' => $musicState->agora_sequence,
                    'status' => $musicState->status,
                    'current_position_sec' => $musicState->current_position_sec,
                    'started_at' => $musicState->started_at,
                    'last_action_by' => $user->id,
                    'is_music_active' => $musicState->is_active,
                ]
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            \Log::error('pauseSong failed', [
                'room_id' => $request->room_id ?? null,
                'active_player_id' => $request->active_player_id ?? null,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to pause music',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function resumeSong(Request $request): JsonResponse
    {
        $validation = Validator::make($request->all(), [
            'room_id' => 'required|integer|exists:rooms,id',
            'active_player_id' => 'required|integer|exists:room_music_active_players,id',
        ]);

        if ($validation->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validation->errors(),
            ], 422);
        }

        DB::beginTransaction();

        try {
            $user = Auth::user();

            if (!$user) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthenticated',
                ], 401);
            }

            $room = Room::find($request->room_id);

            if (!$room) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Room not found',
                ], 404);
            }

            $musicState = \App\Models\RoomMusicActivePlayer::where('id', $request->active_player_id)
                ->where('room_id', $room->id)
                ->lockForUpdate()
                ->first();

            if (!$musicState) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Active player not found',
                ], 404);
            }

            if (!$musicState->playlist_id) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'No song selected to resume',
                ], 422);
            }

            $song = RoomMusicPlaylist::where('id', $musicState->playlist_id)
                ->where('room_id', $room->id)
                ->where('is_active', 1)
                ->first();

            if (!$song) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Current song not found',
                ], 404);
            }

            if (empty($musicState->agora_player_id)) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Agora player not found for this active session',
                ], 422);
            }

            if ($musicState->status !== 'paused') {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'This song is not currently paused',
                ], 422);
            }

            /** @var \App\Services\AgoraCloudPlayerService $cloudPlayerService */
            $cloudPlayerService = app(\App\Services\AgoraCloudPlayerService::class);

            $nextSequence = ((int) $musicState->agora_sequence) + 1;

            \Log::info('resumeSong started', [
                'room_id' => $room->id,
                'active_player_id' => $musicState->id,
                'player_id' => $musicState->agora_player_id,
                'current_sequence' => (int) $musicState->agora_sequence,
                'next_sequence' => $nextSequence,
                'playlist_id' => $musicState->playlist_id,
                'user_id' => $user->id,
            ]);

            $beforeVerify = null;
            try {
                $beforeVerify = $cloudPlayerService->getPlayer($musicState->agora_player_id);

                \Log::info('Agora player before resume verify', [
                    'room_id' => $room->id,
                    'active_player_id' => $musicState->id,
                    'player_id' => $musicState->agora_player_id,
                    'verify_response' => $beforeVerify,
                ]);
            } catch (\Throwable $e) {
                \Log::warning('Agora player before resume verify failed', [
                    'room_id' => $room->id,
                    'active_player_id' => $musicState->id,
                    'player_id' => $musicState->agora_player_id,
                    'error' => $e->getMessage(),
                ]);
            }

            $resumeResponse = null;
            $attempts = 0;
            $maxAttempts = 3;

            while ($attempts < $maxAttempts) {
                $attempts++;

                $resumeResponse = $cloudPlayerService->resumePlayer(
                    $musicState->agora_player_id,
                    $nextSequence
                );

                \Log::info('Agora resume player attempt', [
                    'room_id' => $room->id,
                    'active_player_id' => $musicState->id,
                    'attempt' => $attempts,
                    'player_id' => $musicState->agora_player_id,
                    'sequence' => $nextSequence,
                    'response' => $resumeResponse,
                ]);

                if (!empty($resumeResponse['ok'])) {
                    break;
                }

                $status = (int) ($resumeResponse['status'] ?? 0);
                $bodyString = is_string($resumeResponse['body'] ?? null)
                    ? $resumeResponse['body']
                    : json_encode($resumeResponse['body'] ?? []);

                if ($bodyString && stripos($bodyString, 'sequence is too small') !== false) {
                    $nextSequence++;

                    \Log::warning('Agora resume got sequence too small, incrementing sequence', [
                        'room_id' => $room->id,
                        'active_player_id' => $musicState->id,
                        'player_id' => $musicState->agora_player_id,
                        'new_sequence' => $nextSequence,
                        'attempt' => $attempts,
                    ]);

                    usleep(500000);
                    continue;
                }

                if (in_array($status, [404, 429, 500, 502, 503, 504], true)) {
                    if ($attempts < $maxAttempts) {
                        $waitSeconds = match ($attempts) {
                            1 => 2,
                            2 => 5,
                            default => 8,
                        };

                        \Log::warning('Agora resume retry scheduled', [
                            'room_id' => $room->id,
                            'active_player_id' => $musicState->id,
                            'attempt' => $attempts,
                            'status' => $status,
                            'wait_seconds' => $waitSeconds,
                        ]);

                        sleep($waitSeconds);
                        continue;
                    }
                }

                break;
            }

            if (empty($resumeResponse['ok'])) {
                if ((int) ($resumeResponse['status'] ?? 0) === 404) {
                    $musicState->update([
                        'status' => 'stopped',
                        'is_active' => false,
                        // 'started_at' => null,
                        'agora_sequence' => $nextSequence,
                    ]);

                    DB::commit();

                    return response()->json([
                        'status' => false,
                        'message' => 'Agora player not found or already destroyed',
                        'error' => $resumeResponse['body'] ?? null,
                        'agora_debug' => [
                            'active_player_id' => $musicState->id,
                            'player_id' => $musicState->agora_player_id,
                            'sequence' => $nextSequence,
                            'status_code' => $resumeResponse['status'] ?? null,
                            'request_id' => $resumeResponse['request_id'] ?? null,
                            'resource_id' => $resumeResponse['resource_id'] ?? null,
                            'attempts' => $attempts,
                        ]
                    ], 404);
                }

                DB::rollBack();

                return response()->json([
                    'status' => false,
                    'message' => 'Agora resume failed',
                    'error' => $resumeResponse['body'] ?? 'Unknown Agora error',
                    'agora_debug' => [
                        'active_player_id' => $musicState->id,
                        'player_id' => $musicState->agora_player_id,
                        'sequence' => $nextSequence,
                        'status_code' => $resumeResponse['status'] ?? null,
                        'request_id' => $resumeResponse['request_id'] ?? null,
                        'resource_id' => $resumeResponse['resource_id'] ?? null,
                        'attempts' => $attempts,
                    ]
                ], 500);
            }

            $musicState->update([
                'status' => 'playing',
                'started_at' => now(),
                'agora_sequence' => $nextSequence,
                'is_active' => true,
            ]);

            $musicState->refresh();

            DB::commit();

            $afterVerify = null;
            $afterVerifyStatus = null;

            try {
                usleep(1200000);

                $afterVerify = $cloudPlayerService->getPlayer($musicState->agora_player_id);

                \Log::info('Agora player after resume verify', [
                    'room_id' => $room->id,
                    'active_player_id' => $musicState->id,
                    'player_id' => $musicState->agora_player_id,
                    'verify_response' => $afterVerify,
                ]);

                $afterVerifyStatus =
                    $afterVerify['json']['player']['status']
                    ?? $afterVerify['body']['player']['status']
                    ?? null;
            } catch (\Throwable $e) {
                \Log::warning('Agora player after resume verify failed', [
                    'room_id' => $room->id,
                    'active_player_id' => $musicState->id,
                    'player_id' => $musicState->agora_player_id,
                    'error' => $e->getMessage(),
                ]);
            }

            $message = 'Music resumed successfully';

            broadcast(new RoomMusicResumed($room->id, $song, $musicState, $user, $message))->toOthers();

            return response()->json([
                'status' => true,
                'message' => $message,
                'data' => [
                    'song' => [
                        'id' => $song->id,
                        'room_id' => $song->room_id,
                        'title' => $song->title,
                        'artist' => $song->artist,
                        'duration_seconds' => $song->duration_seconds,
                        'position' => $song->position,
                        'is_active' => $song->is_active,
                        'audio_url' => $song->audio_url
                            ? \Helper::showImage($song->audio_url, true)
                            : null,
                    ],
                    'music_state' => [
                        'room_id' => $musicState->room_id,
                        'current_playlist_id' => $musicState->playlist_id,
                        'active_player_id' => $musicState->id,
                        'agora_player_id' => $musicState->agora_player_id,
                        'system_uid' => $musicState->system_uid,
                        'is_music_active' => (bool) $musicState->is_active,
                        'status' => $musicState->status,
                        'current_position_sec' => $musicState->current_position_sec,
                        'agora_sequence' => (int) $musicState->agora_sequence,
                        'started_at' => $musicState->started_at
                            ? $musicState->started_at->format('Y-m-d H:i:s')
                            : null,
                        'volume' => $musicState->volume,
                        'is_loop' => (bool) $musicState->is_loop,
                        'is_shuffle' => false,
                        'last_action_by' => $user->id,
                    ],
                    'agora_debug' => [
                        'active_player_id' => $musicState->id,
                        'player_id' => $musicState->agora_player_id,
                        'sequence_used' => $nextSequence,
                        'resume_response' => $resumeResponse,
                        'before_verify' => $beforeVerify,
                        'after_verify_status' => $afterVerifyStatus,
                        'after_verify' => $afterVerify,
                        'attempts' => $attempts,
                    ],
                ]
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            \Log::error('resumeSong failed', [
                'room_id' => $request->room_id ?? null,
                'active_player_id' => $request->active_player_id ?? null,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to resume music',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function seekSong(Request $request): JsonResponse
    {
        $validation = Validator::make($request->all(), [
            'room_id' => 'required|integer|exists:rooms,id',
            'active_player_id' => 'required|integer|exists:room_music_active_players,id',
            'seek_position_sec' => 'required|integer|min:0',
        ]);

        if ($validation->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validation->errors(),
            ], 422);
        }

        DB::beginTransaction();

        try {
            $user = Auth::user();

            if (!$user) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthenticated',
                ], 401);
            }

            $room = Room::find($request->room_id);

            if (!$room) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Room not found',
                ], 404);
            }

            $musicState = \App\Models\RoomMusicActivePlayer::where('id', $request->active_player_id)
                ->where('room_id', $room->id)
                ->lockForUpdate()
                ->first();

            if (!$musicState) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Active player not found',
                ], 404);
            }

            if (!$musicState->playlist_id) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'No song selected to seek',
                ], 422);
            }

            $song = RoomMusicPlaylist::where('id', $musicState->playlist_id)
                ->where('room_id', $room->id)
                ->where('is_active', 1)
                ->first();

            if (!$song) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Current song not found',
                ], 404);
            }

            if (empty($musicState->agora_player_id)) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Agora player not found for this active session',
                ], 422);
            }

            $seekPosition = (int) $request->seek_position_sec;

            if (
                !is_null($song->duration_seconds)
                && $song->duration_seconds > 0
                && $seekPosition > (int) $song->duration_seconds
            ) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Seek position cannot be greater than song duration',
                ], 422);
            }

            /** @var \App\Services\AgoraCloudPlayerService $cloudPlayerService */
            $cloudPlayerService = app(\App\Services\AgoraCloudPlayerService::class);

            $nextSequence = ((int) $musicState->agora_sequence) + 1;
            $isPause = $musicState->status !== 'playing';
            $volume = is_null($musicState->volume) ? 100 : (int) $musicState->volume;

            \Log::info('seekSong started', [
                'room_id' => $room->id,
                'active_player_id' => $musicState->id,
                'player_id' => $musicState->agora_player_id,
                'playlist_id' => $musicState->playlist_id,
                'current_sequence' => (int) $musicState->agora_sequence,
                'next_sequence' => $nextSequence,
                'seek_position_sec' => $seekPosition,
                'current_status' => $musicState->status,
                'isPause_sent_to_agora' => $isPause,
                'volume_sent_to_agora' => $volume,
                'user_id' => $user->id,
            ]);

            $beforeVerify = null;
            try {
                $beforeVerify = $cloudPlayerService->getPlayer($musicState->agora_player_id);

                \Log::info('Agora player before seek verify', [
                    'room_id' => $room->id,
                    'active_player_id' => $musicState->id,
                    'player_id' => $musicState->agora_player_id,
                    'verify_response' => $beforeVerify,
                ]);
            } catch (\Throwable $e) {
                \Log::warning('Agora player before seek verify failed', [
                    'room_id' => $room->id,
                    'active_player_id' => $musicState->id,
                    'player_id' => $musicState->agora_player_id,
                    'error' => $e->getMessage(),
                ]);
            }

            $seekResponse = null;
            $attempts = 0;
            $maxAttempts = 3;

            while ($attempts < $maxAttempts) {
                $attempts++;

                $seekResponse = $cloudPlayerService->seekPlayer(
                    $musicState->agora_player_id,
                    $nextSequence,
                    $seekPosition,
                    $isPause,
                    $volume
                );

                \Log::info('Agora seek player attempt', [
                    'room_id' => $room->id,
                    'active_player_id' => $musicState->id,
                    'attempt' => $attempts,
                    'player_id' => $musicState->agora_player_id,
                    'sequence' => $nextSequence,
                    'seek_position_sec' => $seekPosition,
                    'response' => $seekResponse,
                ]);

                if (!empty($seekResponse['ok'])) {
                    break;
                }

                $status = (int) ($seekResponse['status'] ?? 0);
                $bodyString = is_string($seekResponse['body'] ?? null)
                    ? $seekResponse['body']
                    : json_encode($seekResponse['body'] ?? []);

                if ($bodyString && stripos($bodyString, 'sequence is too small') !== false) {
                    $nextSequence++;

                    \Log::warning('Agora seek got sequence too small, incrementing sequence', [
                        'room_id' => $room->id,
                        'active_player_id' => $musicState->id,
                        'player_id' => $musicState->agora_player_id,
                        'new_sequence' => $nextSequence,
                        'attempt' => $attempts,
                    ]);

                    usleep(500000);
                    continue;
                }

                if (in_array($status, [404, 429, 500, 502, 503, 504], true)) {
                    if ($attempts < $maxAttempts) {
                        $waitSeconds = match ($attempts) {
                            1 => 2,
                            2 => 5,
                            default => 8,
                        };

                        \Log::warning('Agora seek retry scheduled', [
                            'room_id' => $room->id,
                            'active_player_id' => $musicState->id,
                            'attempt' => $attempts,
                            'status' => $status,
                            'wait_seconds' => $waitSeconds,
                        ]);

                        sleep($waitSeconds);
                        continue;
                    }
                }

                break;
            }

            if (empty($seekResponse['ok'])) {
                if ((int) ($seekResponse['status'] ?? 0) === 404) {
                    $musicState->update([
                        'status' => 'stopped',
                        'is_active' => false,
                        // 'started_at' => null,
                        'agora_sequence' => $nextSequence,
                    ]);

                    DB::commit();

                    return response()->json([
                        'status' => false,
                        'message' => 'Agora player not found or already destroyed',
                        'error' => $seekResponse['body'] ?? null,
                        'agora_debug' => [
                            'active_player_id' => $musicState->id,
                            'player_id' => $musicState->agora_player_id,
                            'sequence' => $nextSequence,
                            'seek_position_sec' => $seekPosition,
                            'status_code' => $seekResponse['status'] ?? null,
                            'request_id' => $seekResponse['request_id'] ?? null,
                            'resource_id' => $seekResponse['resource_id'] ?? null,
                            'attempts' => $attempts,
                        ]
                    ], 404);
                }

                DB::rollBack();

                return response()->json([
                    'status' => false,
                    'message' => 'Agora seek failed',
                    'error' => $seekResponse['body'] ?? 'Unknown Agora error',
                    'agora_debug' => [
                        'active_player_id' => $musicState->id,
                        'player_id' => $musicState->agora_player_id,
                        'sequence' => $nextSequence,
                        'seek_position_sec' => $seekPosition,
                        'status_code' => $seekResponse['status'] ?? null,
                        'request_id' => $seekResponse['request_id'] ?? null,
                        'resource_id' => $seekResponse['resource_id'] ?? null,
                        'attempts' => $attempts,
                    ]
                ], 500);
            }

            // $updateData = [
            //     'current_position_sec' => $seekPosition,
            //     'agora_sequence' => $nextSequence,
            // ];

            // if ($musicState->status === 'playing') {
            //     $updateData['started_at'] = now();
            //     $updateData['is_active'] = true;
            // } else {
            //     $updateData['started_at'] = null;
            //     $updateData['is_active'] = true;
            // }

            $updateData = [
                'current_position_sec' => $seekPosition,
                'agora_sequence' => $nextSequence,
                'is_active' => true,
            ];

            if ($musicState->status === 'playing') {
                $updateData['started_at'] = now();
            }

            $musicState->update($updateData);
            $musicState->refresh();

            DB::commit();

            $afterVerify = null;
            $afterVerifyStatus = null;

            try {
                usleep(1200000);

                $afterVerify = $cloudPlayerService->getPlayer($musicState->agora_player_id);

                \Log::info('Agora player after seek verify', [
                    'room_id' => $room->id,
                    'active_player_id' => $musicState->id,
                    'player_id' => $musicState->agora_player_id,
                    'verify_response' => $afterVerify,
                ]);

                $afterVerifyStatus =
                    $afterVerify['json']['player']['status']
                    ?? $afterVerify['body']['player']['status']
                    ?? null;
            } catch (\Throwable $e) {
                \Log::warning('Agora player after seek verify failed', [
                    'room_id' => $room->id,
                    'active_player_id' => $musicState->id,
                    'player_id' => $musicState->agora_player_id,
                    'error' => $e->getMessage(),
                ]);
            }

            $message = 'Music seek updated successfully';

            broadcast(new RoomMusicSeeked($room->id, $song, $musicState, $user, $message))->toOthers();

            return response()->json([
                'status' => true,
                'message' => $message,
                'data' => [
                    'song' => [
                        'id' => $song->id,
                        'room_id' => $song->room_id,
                        'title' => $song->title,
                        'artist' => $song->artist,
                        'duration_seconds' => $song->duration_seconds,
                        'position' => $song->position,
                        'is_active' => $song->is_active,
                        'audio_url' => $song->audio_url
                            ? \Helper::showImage($song->audio_url, true)
                            : null,
                    ],
                    'music_state' => [
                        'room_id' => $musicState->room_id,
                        'current_playlist_id' => $musicState->playlist_id,
                        'active_player_id' => $musicState->id,
                        'agora_player_id' => $musicState->agora_player_id,
                        'system_uid' => $musicState->system_uid,
                        'status' => $musicState->status,
                        'current_position_sec' => $musicState->current_position_sec,
                        'agora_sequence' => (int) $musicState->agora_sequence,
                        'started_at' => !empty($musicState->started_at)
                            ? \Carbon\Carbon::parse($musicState->started_at)->format('Y-m-d H:i:s')
                            : null,
                        'volume' => $musicState->volume,
                        'is_loop' => (bool) $musicState->is_loop,
                        'is_shuffle' => false,
                        'last_action_by' => $user->id,
                        'is_music_active' => (bool) $musicState->is_active,
                    ],
                    'agora_debug' => [
                        'active_player_id' => $musicState->id,
                        'player_id' => $musicState->agora_player_id,
                        'sequence_used' => $nextSequence,
                        'seek_position_sec' => $seekPosition,
                        'seek_response' => $seekResponse,
                        'before_verify' => $beforeVerify,
                        'after_verify_status' => $afterVerifyStatus,
                        'after_verify' => $afterVerify,
                        'attempts' => $attempts,
                    ],
                ]
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            \Log::error('seekSong failed', [
                'room_id' => $request->room_id ?? null,
                'active_player_id' => $request->active_player_id ?? null,
                'seek_position_sec' => $request->seek_position_sec ?? null,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to update music seek position',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateVolume(Request $request): JsonResponse
    {
        $validation = Validator::make($request->all(), [
            'room_id' => 'required|integer|exists:rooms,id',
            'active_player_id' => 'required|integer|exists:room_music_active_players,id',
            'volume' => 'required|integer|min:0|max:100',
        ]);

        if ($validation->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validation->errors(),
            ], 422);
        }

        DB::beginTransaction();

        try {
            $user = Auth::user();

            if (!$user) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthenticated',
                ], 401);
            }

            $room = Room::find($request->room_id);

            if (!$room) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Room not found',
                ], 404);
            }

            $musicState = \App\Models\RoomMusicActivePlayer::where('id', $request->active_player_id)
                ->where('room_id', $room->id)
                ->lockForUpdate()
                ->first();

            if (!$musicState) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Active player not found',
                ], 404);
            }

            $requestedVolume = (int) $request->volume;

            if (empty($musicState->agora_player_id)) {
                $musicState->update([
                    'volume' => $requestedVolume,
                ]);

                $musicState->refresh();

                DB::commit();

                broadcast(new RoomMusicVolumeUpdated($room->id, $musicState, $user))->toOthers();

                return response()->json([
                    'status' => true,
                    'message' => 'Music volume updated successfully',
                    'data' => [
                        'active_player_id' => $musicState->id,
                        'room_id' => $musicState->room_id,
                        'current_playlist_id' => $musicState->playlist_id,
                        'agora_player_id' => $musicState->agora_player_id,
                        'status' => $musicState->status,
                        'current_position_sec' => $musicState->current_position_sec,
                        'agora_sequence' => (int) $musicState->agora_sequence,
                        'started_at' => !empty($musicState->started_at)
                            ? \Carbon\Carbon::parse($musicState->started_at)->format('Y-m-d H:i:s')
                            : null,
                        'volume' => $musicState->volume,
                        'is_loop' => (bool) $musicState->is_loop,
                        'is_shuffle' => false,
                        'last_action_by' => $user->id,
                        'is_music_active' => (bool) $musicState->is_active,
                        'agora_debug' => [
                            'note' => 'No active Agora player found, only DB volume updated',
                        ],
                    ]
                ]);
            }

            /** @var \App\Services\AgoraCloudPlayerService $cloudPlayerService */
            $cloudPlayerService = app(\App\Services\AgoraCloudPlayerService::class);

            $nextSequence = ((int) $musicState->agora_sequence) + 1;
            $isPause = $musicState->status !== 'playing';

            \Log::info('updateVolume started', [
                'room_id' => $room->id,
                'active_player_id' => $musicState->id,
                'player_id' => $musicState->agora_player_id,
                'requested_volume' => $requestedVolume,
                'current_sequence' => (int) $musicState->agora_sequence,
                'next_sequence' => $nextSequence,
                'current_status' => $musicState->status,
                'isPause_sent_to_agora' => $isPause,
                'user_id' => $user->id,
            ]);

            $beforeVerify = null;
            try {
                $beforeVerify = $cloudPlayerService->getPlayer($musicState->agora_player_id);

                \Log::info('Agora player before volume verify', [
                    'room_id' => $room->id,
                    'active_player_id' => $musicState->id,
                    'player_id' => $musicState->agora_player_id,
                    'verify_response' => $beforeVerify,
                ]);
            } catch (\Throwable $e) {
                \Log::warning('Agora player before volume verify failed', [
                    'room_id' => $room->id,
                    'active_player_id' => $musicState->id,
                    'player_id' => $musicState->agora_player_id,
                    'error' => $e->getMessage(),
                ]);
            }

            $volumeResponse = null;
            $attempts = 0;
            $maxAttempts = 3;

            while ($attempts < $maxAttempts) {
                $attempts++;

                $volumeResponse = $cloudPlayerService->updatePlayerVolume(
                    $musicState->agora_player_id,
                    $nextSequence,
                    $requestedVolume,
                    $isPause
                );

                \Log::info('Agora volume update attempt', [
                    'room_id' => $room->id,
                    'active_player_id' => $musicState->id,
                    'attempt' => $attempts,
                    'player_id' => $musicState->agora_player_id,
                    'sequence' => $nextSequence,
                    'volume' => $requestedVolume,
                    'response' => $volumeResponse,
                ]);

                if (!empty($volumeResponse['ok'])) {
                    break;
                }

                $status = (int) ($volumeResponse['status'] ?? 0);
                $bodyString = is_string($volumeResponse['body'] ?? null)
                    ? $volumeResponse['body']
                    : json_encode($volumeResponse['body'] ?? []);

                if ($bodyString && stripos($bodyString, 'sequence is too small') !== false) {
                    $nextSequence++;

                    \Log::warning('Agora volume update got sequence too small, incrementing sequence', [
                        'room_id' => $room->id,
                        'active_player_id' => $musicState->id,
                        'player_id' => $musicState->agora_player_id,
                        'new_sequence' => $nextSequence,
                        'attempt' => $attempts,
                    ]);

                    usleep(500000);
                    continue;
                }

                if (in_array($status, [404, 429, 500, 502, 503, 504], true)) {
                    if ($attempts < $maxAttempts) {
                        $waitSeconds = match ($attempts) {
                            1 => 2,
                            2 => 5,
                            default => 8,
                        };

                        \Log::warning('Agora volume retry scheduled', [
                            'room_id' => $room->id,
                            'active_player_id' => $musicState->id,
                            'attempt' => $attempts,
                            'status' => $status,
                            'wait_seconds' => $waitSeconds,
                        ]);

                        sleep($waitSeconds);
                        continue;
                    }
                }

                break;
            }

            if (empty($volumeResponse['ok'])) {
                if ((int) ($volumeResponse['status'] ?? 0) === 404) {
                    $musicState->update([
                        'status' => 'stopped',
                        'is_active' => false,
                        // 'started_at' => null,
                        'agora_sequence' => $nextSequence,
                    ]);

                    DB::commit();

                    return response()->json([
                        'status' => false,
                        'message' => 'Agora player not found or already destroyed',
                        'error' => $volumeResponse['body'] ?? null,
                        'agora_debug' => [
                            'active_player_id' => $musicState->id,
                            'player_id' => $musicState->agora_player_id,
                            'sequence' => $nextSequence,
                            'requested_volume' => $requestedVolume,
                            'status_code' => $volumeResponse['status'] ?? null,
                            'request_id' => $volumeResponse['request_id'] ?? null,
                            'resource_id' => $volumeResponse['resource_id'] ?? null,
                            'attempts' => $attempts,
                        ]
                    ], 404);
                }

                DB::rollBack();

                return response()->json([
                    'status' => false,
                    'message' => 'Agora volume update failed',
                    'error' => $volumeResponse['body'] ?? 'Unknown Agora error',
                    'agora_debug' => [
                        'active_player_id' => $musicState->id,
                        'player_id' => $musicState->agora_player_id,
                        'sequence' => $nextSequence,
                        'requested_volume' => $requestedVolume,
                        'status_code' => $volumeResponse['status'] ?? null,
                        'request_id' => $volumeResponse['request_id'] ?? null,
                        'resource_id' => $volumeResponse['resource_id'] ?? null,
                        'attempts' => $attempts,
                    ]
                ], 500);
            }

            $musicState->update([
                'volume' => $requestedVolume,
                'agora_sequence' => $nextSequence,
            ]);

            $musicState->refresh();

            DB::commit();

            $afterVerify = null;
            $afterVerifyStatus = null;

            try {
                usleep(1200000);

                $afterVerify = $cloudPlayerService->getPlayer($musicState->agora_player_id);

                \Log::info('Agora player after volume verify', [
                    'room_id' => $room->id,
                    'active_player_id' => $musicState->id,
                    'player_id' => $musicState->agora_player_id,
                    'verify_response' => $afterVerify,
                ]);

                $afterVerifyStatus =
                    $afterVerify['json']['player']['status']
                    ?? $afterVerify['body']['player']['status']
                    ?? null;
            } catch (\Throwable $e) {
                \Log::warning('Agora player after volume verify failed', [
                    'room_id' => $room->id,
                    'active_player_id' => $musicState->id,
                    'player_id' => $musicState->agora_player_id,
                    'error' => $e->getMessage(),
                ]);
            }

            broadcast(new RoomMusicVolumeUpdated($room->id, $musicState, $user))->toOthers();

            return response()->json([
                'status' => true,
                'message' => 'Music volume updated successfully',
                'data' => [
                    'active_player_id' => $musicState->id,
                    'room_id' => $musicState->room_id,
                    'current_playlist_id' => $musicState->playlist_id,
                    'agora_player_id' => $musicState->agora_player_id,
                    'system_uid' => $musicState->system_uid,
                    'status' => $musicState->status,
                    'current_position_sec' => $musicState->current_position_sec,
                    'agora_sequence' => (int) $musicState->agora_sequence,
                    'started_at' => !empty($musicState->started_at)
                        ? \Carbon\Carbon::parse($musicState->started_at)->format('Y-m-d H:i:s')
                        : null,
                    'volume' => $musicState->volume,
                    'is_loop' => (bool) $musicState->is_loop,
                    'is_shuffle' => false,
                    'last_action_by' => $user->id,
                    'is_music_active' => (bool) $musicState->is_active,
                    'agora_debug' => [
                        'active_player_id' => $musicState->id,
                        'player_id' => $musicState->agora_player_id,
                        'sequence_used' => $nextSequence,
                        'requested_volume' => $requestedVolume,
                        'before_verify' => $beforeVerify,
                        'after_verify_status' => $afterVerifyStatus,
                        'after_verify' => $afterVerify,
                        'volume_response' => $volumeResponse,
                        'attempts' => $attempts,
                    ],
                ]
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            \Log::error('updateVolume failed', [
                'room_id' => $request->room_id ?? null,
                'active_player_id' => $request->active_player_id ?? null,
                'volume' => $request->volume ?? null,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to update music volume',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateMusicOptions(Request $request): JsonResponse
    {
        $validation = Validator::make($request->all(), [
            'room_id' => 'required|integer|exists:rooms,id',
            'active_player_id' => 'required|integer|exists:room_music_active_players,id',
            'is_loop' => 'required|boolean',
        ]);

        if ($validation->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validation->errors(),
            ], 422);
        }

        DB::beginTransaction();

        try {
            $user = Auth::user();

            if (!$user) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthenticated',
                ], 401);
            }

            $musicState = \App\Models\RoomMusicActivePlayer::where('id', $request->active_player_id)
                ->where('room_id', $request->room_id)
                ->lockForUpdate()
                ->first();

            if (!$musicState) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Active player not found',
                ], 404);
            }

            $musicState->update([
                'is_loop' => (bool) $request->is_loop,
            ]);

            $musicState->refresh();

            DB::commit();

            broadcast(new RoomMusicOptionsUpdated($request->room_id, $musicState, $user))->toOthers();

            return response()->json([
                'status' => true,
                'message' => 'Music loop option updated successfully',
                'data' => [
                    'active_player_id' => $musicState->id,
                    'room_id' => $musicState->room_id,
                    'current_playlist_id' => $musicState->playlist_id,
                    'agora_player_id' => $musicState->agora_player_id,
                    'system_uid' => $musicState->system_uid,
                    'status' => $musicState->status,
                    'current_position_sec' => $musicState->current_position_sec,
                    'started_at' => !empty($musicState->started_at)
                        ? \Carbon\Carbon::parse($musicState->started_at)->format('Y-m-d H:i:s')
                        : null,
                    'volume' => $musicState->volume,
                    'is_loop' => (bool) $musicState->is_loop,
                    'is_shuffle' => false,
                    'last_action_by' => $user->id,
                    'is_music_active' => (bool) $musicState->is_active,
                    'agora_sequence' => (int) $musicState->agora_sequence,
                ]
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            \Log::error('updateMusicOptions failed', [
                'room_id' => $request->room_id ?? null,
                'active_player_id' => $request->active_player_id ?? null,
                'is_loop' => $request->is_loop ?? null,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to update music option',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function deleteSong(Request $request): JsonResponse
    {
        $validation = Validator::make($request->all(), [
            'room_id' => 'required|integer|exists:rooms,id',
            'playlist_id' => 'required|integer|exists:room_music_playlist,id',
        ]);

        if ($validation->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validation->errors(),
            ], 422);
        }

        DB::beginTransaction();

        try {
            $user = Auth::user();

            if (!$user) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthenticated',
                ], 401);
            }

            $room = Room::find($request->room_id);

            if (!$room) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Room not found',
                ], 404);
            }

            $song = RoomMusicPlaylist::where('id', $request->playlist_id)
                ->where('room_id', $room->id)
                ->where('user_id', $user->id)
                ->first();

            if (!$song) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Song not found in this room',
                ], 404);
            }

            /** @var \App\Services\AgoraCloudPlayerService $cloudPlayerService */
            $cloudPlayerService = app(\App\Services\AgoraCloudPlayerService::class);

            // Snapshot before delete
            $deletedSong = clone $song;
            $deletedSong->loadMissing('addedBy:id,name,image');

            /*
        |--------------------------------------------------------------------------
        | Is song ke active players nikalo (same room + same song + same owner)
        |--------------------------------------------------------------------------
        */
            $activePlayers = \App\Models\RoomMusicActivePlayer::where('room_id', $room->id)
                ->where('playlist_id', $song->id)
                ->where('started_by', $user->id)
                ->where('is_active', true)
                ->whereIn('status', ['playing', 'paused'])
                ->lockForUpdate()
                ->get();

            $isCurrentSong = $activePlayers->isNotEmpty();

            \Log::info('deleteSong started', [
                'room_id' => $room->id,
                'playlist_id' => $song->id,
                'song_title' => $song->title,
                'is_current_song' => $isCurrentSong,
                'active_player_ids' => $activePlayers->pluck('id')->values()->all(),
                'agora_player_ids' => $activePlayers->pluck('agora_player_id')->filter()->values()->all(),
                'user_id' => $user->id,
            ]);

            $agoraDeleteResponses = [];
            $deletedActivePlayerIds = [];

            foreach ($activePlayers as $activePlayer) {
                $agoraDeleteResponse = null;
                $agoraDeleteAttempts = 0;
                $maxAttempts = 3;

                if (!empty($activePlayer->agora_player_id)) {
                    while ($agoraDeleteAttempts < $maxAttempts) {
                        $agoraDeleteAttempts++;

                        $agoraDeleteResponse = $cloudPlayerService->deletePlayer($activePlayer->agora_player_id);

                        \Log::info('Agora delete player attempt during deleteSong', [
                            'room_id' => $room->id,
                            'active_player_id' => $activePlayer->id,
                            'attempt' => $agoraDeleteAttempts,
                            'player_id' => $activePlayer->agora_player_id,
                            'response' => $agoraDeleteResponse,
                        ]);

                        if (!empty($agoraDeleteResponse['ok'])) {
                            break;
                        }

                        $status = (int) ($agoraDeleteResponse['status'] ?? 0);

                        if (in_array($status, [404, 429, 500, 502, 503, 504], true)) {
                            if ($status === 404) {
                                \Log::warning('Agora player already missing during deleteSong, treating as deleted', [
                                    'room_id' => $room->id,
                                    'active_player_id' => $activePlayer->id,
                                    'player_id' => $activePlayer->agora_player_id,
                                ]);
                                break;
                            }

                            if ($agoraDeleteAttempts < $maxAttempts) {
                                $waitMs = match ($agoraDeleteAttempts) {
                                    1 => 300,
                                    2 => 700,
                                    default => 1200,
                                };

                                \Log::warning('Agora delete retry scheduled during deleteSong', [
                                    'room_id' => $room->id,
                                    'active_player_id' => $activePlayer->id,
                                    'attempt' => $agoraDeleteAttempts,
                                    'status' => $status,
                                    'wait_ms' => $waitMs,
                                ]);

                                usleep($waitMs * 1000);
                                continue;
                            }
                        }

                        break;
                    }

                    if (
                        !empty($agoraDeleteResponse) &&
                        empty($agoraDeleteResponse['ok']) &&
                        (int) ($agoraDeleteResponse['status'] ?? 0) !== 404
                    ) {
                        DB::rollBack();

                        return response()->json([
                            'status' => false,
                            'message' => 'Agora delete failed',
                            'error' => $agoraDeleteResponse['body'] ?? 'Unknown Agora error',
                            'agora_debug' => [
                                'active_player_id' => $activePlayer->id,
                                'player_id' => $activePlayer->agora_player_id,
                                'status_code' => $agoraDeleteResponse['status'] ?? null,
                                'request_id' => $agoraDeleteResponse['request_id'] ?? null,
                                'resource_id' => $agoraDeleteResponse['resource_id'] ?? null,
                                'attempts' => $agoraDeleteAttempts,
                            ]
                        ], 500);
                    }
                }

                $activePlayer->update([
                    'status' => 'stopped',
                    'is_active' => false,
                    // 'started_at' => null,
                ]);

                $deletedActivePlayerIds[] = $activePlayer->id;
                $agoraDeleteResponses[] = [
                    'active_player_id' => $activePlayer->id,
                    'agora_player_id' => $activePlayer->agora_player_id,
                    'response' => $agoraDeleteResponse,
                    'attempts' => $agoraDeleteAttempts,
                ];
            }

            // permanently delete song
            $songOwnerId = $song->user_id;
            $song->delete();

            // reorder remaining songs of same owner in same room
            $remainingSongs = RoomMusicPlaylist::where('room_id', $room->id)
                ->where('user_id', $songOwnerId)
                ->orderBy('position', 'asc')
                ->get();

            foreach ($remainingSongs as $index => $remainingSong) {
                $remainingSong->update([
                    'position' => $index + 1,
                ]);
            }

            $nextSong = RoomMusicPlaylist::where('room_id', $room->id)
                ->where('user_id', $songOwnerId)
                ->where('is_active', 1)
                ->orderBy('position', 'asc')
                ->first();

            $message = $isCurrentSong
                ? 'Current song deleted successfully'
                : 'Song deleted successfully';

            DB::commit();

            broadcast(new RoomMusicSongDeleted(
                $room->id,
                $deletedSong,
                $nextSong,
                $activePlayers->first(),
                $user,
                $message
            ))->toOthers();

            return response()->json([
                'status' => true,
                'message' => $message,
                'data' => [
                    'deleted_song' => [
                        'id' => $deletedSong->id,
                        'room_id' => $deletedSong->room_id,
                        'title' => $deletedSong->title,
                        'artist' => $deletedSong->artist,
                        'audio_url' => $deletedSong->audio_url
                            ? \Helper::showImage($deletedSong->audio_url, true)
                            : null,
                        'duration_seconds' => $deletedSong->duration_seconds,
                        'position' => $deletedSong->position,
                        'is_active' => $deletedSong->is_active,
                        'added_by' => [
                            'id' => optional($deletedSong->addedBy)->id,
                            'name' => optional($deletedSong->addedBy)->name,
                            'image' => optional($deletedSong->addedBy)->image
                                ? (
                                    \Illuminate\Support\Str::startsWith(optional($deletedSong->addedBy)->image, ['http://', 'https://'])
                                    ? optional($deletedSong->addedBy)->image
                                    : \Helper::showImage(optional($deletedSong->addedBy)->image, true)
                                )
                                : null,
                        ],
                    ],
                    'next_song' => $nextSong ? [
                        'id' => $nextSong->id,
                        'room_id' => $nextSong->room_id,
                        'title' => $nextSong->title,
                        'artist' => $nextSong->artist,
                        'audio_url' => $nextSong->audio_url
                            ? \Helper::showImage($nextSong->audio_url, true)
                            : null,
                        'duration_seconds' => $nextSong->duration_seconds,
                        'position' => $nextSong->position,
                        'is_active' => $nextSong->is_active,
                    ] : null,
                    'music_state' => [
                        'deleted_current_song' => $isCurrentSong,
                        'deleted_active_player_ids' => $deletedActivePlayerIds,
                    ],
                    'agora_debug' => [
                        'deleted_current_song' => $isCurrentSong,
                        'delete_responses' => $agoraDeleteResponses,
                    ],
                ]
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            \Log::error('deleteSong failed', [
                'room_id' => $request->room_id ?? null,
                'playlist_id' => $request->playlist_id ?? null,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to delete song',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function onSongFinished(Request $request): JsonResponse
    {
        $validation = Validator::make($request->all(), [
            'room_id' => 'required|integer|exists:rooms,id',
            'active_player_id' => 'required|integer|exists:room_music_active_players,id',
        ]);

        if ($validation->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validation->errors(),
            ], 422);
        }

        try {
            $responseData = $this->playNextOrLoopSong(
                (int) $request->room_id,
                (int) $request->active_player_id
            );

            if (!$responseData) {
                return response()->json([
                    'status' => false,
                    'message' => 'No song found to play',
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Song finished handled successfully',
                'data' => $responseData,
            ]);
        } catch (\Throwable $e) {
            \Log::error('onSongFinished failed', [
                'room_id' => $request->room_id ?? null,
                'active_player_id' => $request->active_player_id ?? null,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to handle finished song',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    protected function playNextOrLoopSong(int $roomId, int $activePlayerId): ?array
    {
        $nextSongId = null;
        $triggeredBy = null;
        $playAsUserId = null;
        $finishedPlayer = null;

        DB::transaction(function () use ($roomId, $activePlayerId, &$nextSongId, &$triggeredBy, &$playAsUserId, &$finishedPlayer) {
            $musicState = \App\Models\RoomMusicActivePlayer::where('id', $activePlayerId)
                ->where('room_id', $roomId)
                ->lockForUpdate()
                ->first();

            if (!$musicState || !$musicState->playlist_id) {
                return;
            }

            $finishedPlayer = $musicState;
            $triggeredBy = AppUser::find($musicState->started_by);
            $playAsUserId = $musicState->started_by;

            $currentSong = RoomMusicPlaylist::where('room_id', $roomId)
                ->where('id', $musicState->playlist_id)
                ->where('is_active', 1)
                ->first();

            if (!$currentSong) {
                $musicState->update([
                    'status' => 'stopped',
                    'is_active' => false,
                    // 'started_at' => null,
                ]);
                return;
            }

            $playlistOwnerId = $currentSong->user_id;

            // finished player ko mark kar do
            $musicState->update([
                'status' => 'stopped',
                'is_active' => false,
                // 'started_at' => null,
            ]);

            // loop on => same song again
            if ((bool) $musicState->is_loop) {
                $nextSongId = $currentSong->id;
                return;
            }

            // loop off => next song user playlist me
            $nextSong = RoomMusicPlaylist::where('room_id', $roomId)
                ->where('is_active', 1)
                ->where('user_id', $playlistOwnerId)
                ->where('position', '>', $currentSong->position)
                ->orderBy('position', 'asc')
                ->first();

            if (!$nextSong) {
                $nextSong = RoomMusicPlaylist::where('room_id', $roomId)
                    ->where('is_active', 1)
                    ->where('user_id', $playlistOwnerId)
                    ->orderBy('position', 'asc')
                    ->first();
            }

            if ($nextSong) {
                $nextSongId = $nextSong->id;
            }
        });

        if (!$nextSongId || !$playAsUserId) {
            return null;
        }

        $playResponse = $this->triggerPlaySongForRoom($roomId, $nextSongId, $playAsUserId);

        $decoded = $playResponse->getData(true);

        if (!isset($decoded['status']) || !$decoded['status']) {
            throw new \Exception($decoded['message'] ?? 'Failed to auto play next song');
        }

        $songData = $decoded['data']['song'] ?? null;
        $musicStateData = $decoded['data']['music_state'] ?? null;

        return [
            'action' => 'play',
            'room_id' => (string) $roomId,
            'song' => [
                'id' => $songData['id'] ?? null,
                'title' => $songData['title'] ?? null,
                'artist' => $songData['artist'] ?? null,
                'audio_url' => $songData['audio_url'] ?? null,
                'duration_seconds' => $songData['duration_seconds'] ?? null,
            ],
            'music_state' => [
                'active_player_id' => $musicStateData['active_player_id'] ?? null,
                'status' => $musicStateData['status'] ?? null,
                'current_position_sec' => $musicStateData['current_position_sec'] ?? 0,
                'volume' => $musicStateData['volume'] ?? 100,
                'is_loop' => $musicStateData['is_loop'] ?? 0,
                'is_shuffle' => $musicStateData['is_shuffle'] ?? false,
                'last_action_by' => $musicStateData['last_action_by'] ?? null,
            ],
            'triggered_by' => [
                'id' => $triggeredBy?->id,
                'name' => $triggeredBy?->name,
            ],
        ];
    }

    // protected function triggerPlaySongForRoom(int $roomId, int $playlistId, int $userId): JsonResponse
    // {
    //     $fakeRequest = Request::create('/internal/play-song', 'POST', [
    //         'room_id' => $roomId,
    //         'playlist_id' => $playlistId,
    //         'current_position_sec' => 0,
    //     ]);

    //     $playAsUser = AppUser::find($userId);

    //     if (!$playAsUser) {
    //         throw new \Exception('Playback user not found');
    //     }

    //     $fakeRequest->setUserResolver(function () use ($playAsUser) {
    //         return $playAsUser;
    //     });

    //     return $this->playSong($fakeRequest);
    // }



    protected function triggerPlaySongForRoom(int $roomId, int $playlistId, int $userId): JsonResponse
    {
        $fakeRequest = Request::create('/internal/play-song', 'POST', [
            'room_id' => $roomId,
            'playlist_id' => $playlistId,
            'current_position_sec' => 0,
            'auto_play' => 1,
        ]);

        $playAsUser = AppUser::find($userId);

        if (!$playAsUser) {
            throw new \Exception('Playback user not found');
        }

        $fakeRequest->setUserResolver(function () use ($playAsUser) {
            return $playAsUser;
        });

        return $this->playSong($fakeRequest);
    }
}
