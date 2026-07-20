<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Helper\Helper;
use App\Models\AppUser;
use App\Models\Room;
use App\Models\Gift;
use App\Models\GiftTransaction;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\RoomPresence;
use App\Models\UserLevel;
use App\Models\RoomSeat;
use App\Models\RoomMember;
use App\Models\RoomMessage;
use App\Models\RoomAdmin;
use App\Models\RoomUserRole;
use App\Models\RoomSetting;
use App\Models\RoomMusicState;
use App\Models\RoomMessageClear;
use App\Models\ChatBubble;
use App\Models\Level;
use App\Models\WCLevel;
use App\Models\Medal;
use App\Models\UserMedal;
use App\Models\RelationshipInvitation;
use App\Models\StoreUids;
use App\Models\PremiumNumber;
use App\Models\Vip;
use App\Models\Svip;
use App\Models\SvipTransaction;
use App\Models\Friendship;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Events\RoomPresenceUpdated;
use App\Events\RoomSeatUpdated;
use App\Events\RoomMessageSent;
use App\Events\RoomSeatSettingUpdated;
use App\Events\RoomSeatMicUpdated;
use App\Events\RoomUserBanned;
use App\Events\RoomUserUnbanned;
use App\Events\RoomLockUpdated;
use App\Events\RoomAccessUpdated;
use App\Events\RoomGiftSent;
use App\Events\RoomSeatMessageSent;
use App\Events\RoomSeatCountUpdated;
use App\Events\RoomMessagesCleared;
use App\Services\Agora\RtcTokenBuilder2;
use App\Services\FirebaseService;
use App\Traits\RoomPermissionTrait;
use Artisan;

class RoomController extends Controller
{
    use RoomPermissionTrait;

    public function getAuthUserRoom()
    {
        $user = Auth::user();

        $room = Room::with('user:id,name,uid,image,gender,active_frame_id,active_uid_id,active_card_id,active_chat_bubble_id,active_voice_id,active_frame_type,active_voice_type,active_chat_bubble_type,active_profile_card_type', 'theme:id,name,icon')
            ->where('user_id', $user->id)
            ->where('status', 1)
            ->first();

        $isFollow = false;
        $isJoined = false;
        $onlineCount = 0;
        $onlineUsers = [];
        $mySeatNo = null;
        $agora = null;
        $musicStateResponse = null;
        $currentSongResponse = null;

        // default access add karo
        $access = [
            'mic_permission' => 0,
            'message_permission' => 0,
            'admin_can_play_music' => 0,
        ];

        if ($room) {
            $room->room_event_banner = asset('storage/room_event_banner.jpg');
            if ($room->room_image) {
                $room->room_image = Helper::showImage($room->room_image, true);
            }

            if ($room->user && $room->user->image) {
                if (Str::startsWith($room->user->image, ['http://', 'https://'])) {
                    $room->user->image = $room->user->image;
                } else {
                    $room->user->image = Helper::showImage($room->user->image, true);
                }
            } else {
                $room->user->image = null;
            }

            $roomOwnerUid = $room->user->uid;
            $roomOwnerUidBadge = null;
            $uidBadgeColor = null;

            $premiumUid = PremiumNumber::where('user_id', $room->user->id)
                ->where('end_at', '>', now())
                ->latest()
                ->first();

            if ($premiumUid) {

                $roomOwnerUid = $premiumUid->premium_number;
                $roomOwnerUidBadge = asset('storage/1000175794.png');
                $uidBadgeColor = '#fcd01c';
            } elseif ($room->user->active_uid_id) {

                $storeUid = StoreUids::find($room->user->active_uid_id);

                if ($storeUid) {

                    $hasValidPurchase = DB::table('item_deliveries')
                        ->where('recipient', $room->user->id)
                        ->where('type', 'id')
                        ->where('item_id', $storeUid->id)
                        ->where('end_at', '>', now())
                        ->exists();

                    $hasValidGift = DB::table('item_gift_transactions')
                        ->where('receiver_id', $room->user->id)
                        ->where('type', 'id')
                        ->where('item_id', $storeUid->id)
                        ->where('end_at', '>', now())
                        ->exists();

                    if ($hasValidPurchase || $hasValidGift) {

                        $roomOwnerUid = $storeUid->unique_id;

                        $roomOwnerUidBadge = !empty($storeUid->rank_badge)
                            ? Helper::showImage($storeUid->rank_badge, true)
                            : null;
                        $uidBadgeColor = $storeUid->rank_badge_color ?? null;
                    }
                }
            }

            $room->user->uid = $roomOwnerUid;
            $room->user->uid_badge = $roomOwnerUidBadge;
            $room->user->uid_badge_color = $uidBadgeColor;

            if ($room->theme) {
                $theme = [
                    'id'    => $room->theme->id,
                    'name'  => $room->theme->name,
                    'image' => Helper::showImage($room->theme->icon, true),
                ];

                $room->unsetRelation('theme');
                $room->theme = $theme;
            } else {
                $room->theme = null;
            }

            $isFollow = DB::table('room_follows')
                ->where('room_id', $room->id)
                ->where('user_id', $user->id)
                ->exists();

            $isJoined = DB::table('room_members')
                ->where('room_id', $room->id)
                ->where('user_id', $user->id)
                ->whereNull('left_at')
                ->exists();

            $onlineCount = RoomPresence::where('room_id', $room->id)->count();

            $seatData = RoomSeat::with([
                'user:id,uid,name,image,gender,country,active_frame_id,active_uid_id,active_card_id,active_chat_bubble_id,active_voice_id,active_frame_type,active_voice_type,active_chat_bubble_type,active_profile_card_type',
                'user.countryData:id,name,iso',
                'user.wcLevels.levelData',
                'user.userMedals.medal'
            ])
                ->where('room_id', $room->id)
                ->get()
                ->keyBy('seat_no');

            $seatSettings = DB::table('room_seat_settings')
                ->where('room_id', $room->id)
                ->get()
                ->keyBy('seat_no');

            $setting = RoomSetting::where('room_id', $room->id)->first();

            $access = [
                'mic_permission' => $setting ? (int) $setting->mic_permission : 0,
                'message_permission' => $setting ? (int) $setting->message_permission : 0,
                'admin_can_play_music' => $setting ? (int) $setting->admin_can_play_music : 0,
            ];

            $seatUsers = $seatData->pluck('user')->filter()->values();

            $itemsByUserId = $this->getFrameAndUidForUsers($seatUsers);

            $roles = []; // agar pehle kahin define nahi kiya to isko bhi default de do

            for ($i = 1; $i <= (int) $room->room_seat; $i++) {
                $seat = $seatData->get($i);
                $setting = $seatSettings->get($i);

                $userId = $seat && $seat->user ? $seat->user->id : null;

                $role = null;

                if ($userId && (int) $room->user_id === (int) $userId) {
                    $role = 'owner';
                } elseif ($userId) {
                    $role = $roles[$userId] ?? 'guest';
                }

                $isMySeat = $seat && (int) $seat->user_id === (int) $user->id;

                if ($isMySeat) {
                    $mySeatNo = $i;
                }

                $itemData = $userId
                    ? ($itemsByUserId[$userId] ?? [

                        'frame' => null,
                        'uid' => null,
                        'profile' => null,
                        'voice' => null,
                        'data_cards' => null,
                    ])
                    : [
                        'frame' => null,
                        'uid' => null,
                        'profile' => null,
                        'voice' => null,
                        'data_cards' => null,
                    ];

                $wealthLevel = $seat?->user?->wcLevels
                    ?->where('type', 'wealth')
                    ->first();

                $charmLevel = $seat?->user?->wcLevels
                    ?->where('type', 'charm')
                    ->first();


                $medals = $seat?->user?->userMedals
                    ?->where('is_equipped', 1)
                    ->sortBy('slot_no')
                    ->take(3)
                    ->map(function ($item) {

                        return [
                            'id' => $item->medal->id,
                            'name' => $item->medal->title,
                            'icon' => Helper::showImage(
                                $item->medal->icon,
                                true
                            )
                        ];
                    })
                    ->values();


                $flag = $seat?->user?->countryData?->iso
                    ? 'https://flagcdn.com/w40/' .
                    strtolower($seat->user->countryData->iso) .
                    '.png'
                    : null;

                $displayUid = $seat && $seat->user ? $seat->user->uid : null;
                $uidBadge = null;
                $uidBadgeColor = null;
                $nicknameMeta = $seat && $seat->user ? Helper::getNicknameMeta($seat->user->id)
                    : [
                        'animated' => false,
                        'color' => null,
                        'effect' => null,
                    ];

                if ($seat && $seat->user) {

                    $premiumUid = PremiumNumber::where('user_id', $seat->user->id)
                        ->where('end_at', '>', now())
                        ->latest()
                        ->first();

                    if ($premiumUid) {

                        $displayUid = $premiumUid->premium_number;
                        $uidBadge = asset('storage/1000175794.png');
                        $uidBadgeColor = '#fcd01c';
                    } elseif ($seat->user->active_uid_id) {

                        $storeUid = StoreUids::find($seat->user->active_uid_id);

                        if ($storeUid) {

                            $hasValidPurchase = DB::table('item_deliveries')
                                ->where('recipient', $seat->user->id)
                                ->where('type', 'id')
                                ->where('item_id', $storeUid->id)
                                ->where('end_at', '>', now())
                                ->exists();

                            $hasValidGift = DB::table('item_gift_transactions')
                                ->where('receiver_id', $seat->user->id)
                                ->where('type', 'id')
                                ->where('item_id', $storeUid->id)
                                ->where('end_at', '>', now())
                                ->exists();

                            if ($hasValidPurchase || $hasValidGift) {

                                $displayUid = $storeUid->unique_id;

                                $uidBadge = !empty($storeUid->rank_badge)
                                    ? Helper::showImage($storeUid->rank_badge, true)
                                    : null;
                                $uidBadgeColor = $storeUid->rank_badge_color ?? null;
                            }
                        }
                    }
                }
                $onlineUsers[] = [
                    'id' => $seat && $seat->user ? $seat->user->id : null,
                    // 'uid' => $seat && $seat->user ? $seat->user->uid : null,
                    'uid' => $displayUid,
                    'uid_badge' => $uidBadge,
                    'uid_badge_color' => $uidBadgeColor,
                    'name' => $seat && $seat->user ? $seat->user->name : null,
                    'nickname_meta' => $nicknameMeta,
                    'gender' => $seat && $seat->user ? $seat->user->gender : null,
                    'image' => ($seat && $seat->user && !empty($seat->user->image))
                        ? Helper::showImage($seat->user->image, true)
                        : null,
                    'seat_no' => $i,
                    'is_occupied' => $seat ? true : false,
                    'is_my_seat' => $isMySeat,
                    'is_locked' => $setting ? (bool)$setting->is_locked : false,
                    'is_muted'  => $setting ? (bool)$setting->is_muted_by_host : false,
                    'is_on_mic'  => $seat ? $seat->is_on_mic : 0,
                    'role' => $role,

                    'frame' => $itemData['frame'],
                    'store_id' => $itemData['uid'],
                    'chat_bubble' => $itemData['chatBubble'] ?? null,
                    'voice' => $itemData['voice'] ?? null,
                    'profile' => $itemData['profile'] ?? null,

                    'left_relation' => null,

                    'right_relation' => null,

                    'flag' => $flag,

                    'wealth_level' => [
                        'level' => $wealthLevel?->level ?? 1,
                        'icon' => $wealthLevel?->levelData?->icon
                            ? Helper::showImage(
                                $wealthLevel->levelData->icon,
                                true
                            )
                            : null
                    ],

                    'charm_level' => [
                        'level' => $charmLevel?->level ?? 1,
                        'icon' => $charmLevel?->levelData?->icon
                            ? Helper::showImage(
                                $charmLevel->levelData->icon,
                                true
                            )
                            : null
                    ],

                    'medals' => $medals,

                    'role_badges' => $seat && $seat->user ? Helper::getUserRoleBadges($seat->user->id) : [],
                ];
            }

            $usersCollection = collect($onlineUsers);

            foreach ($onlineUsers as $key => $seatUser) {

                if (!$seatUser['id']) {
                    continue;
                }

                // LEFT SEAT

                $leftSeat = $usersCollection->firstWhere('seat_no', $seatUser['seat_no'] - 1);

                if ($leftSeat && !empty($leftSeat['id'])) {
                    $leftRelation = $this->getSeatRelation($seatUser['id'], $leftSeat['id']);

                    if ($leftRelation) {
                        $onlineUsers[$key]['left_relation'] = $leftRelation;
                    }
                }

                // RIGHT SEAT

                $rightSeat = $usersCollection->firstWhere('seat_no', $seatUser['seat_no'] + 1);

                if ($rightSeat && !empty($rightSeat['id'])) {
                    $rightRelation = $this->getSeatRelation($seatUser['id'], $rightSeat['id']);

                    if ($rightRelation) {
                        $onlineUsers[$key]['right_relation'] = $rightRelation;
                    }
                }
            }

            $agora = [
                'app_id' => env('AGORA_APP_ID'),
                'channel_name' => 'room_' . $room->id,
                'uid' => (int) $user->id,
                'role' => $mySeatNo ? 'broadcaster' : 'audience',
            ];


            $musicState = RoomMusicState::with(['currentSong.addedBy:id,name,image'])
                ->where('room_id', $room->id)
                ->first();

            if ($musicState) {
                $musicStateResponse = [
                    'room_id' => $musicState->room_id,
                    'current_playlist_id' => $musicState->current_playlist_id,
                    'status' => $musicState->status,
                    'current_position_sec' => (int) $musicState->current_position_sec,
                    'started_at' => $musicState->started_at
                        ? \Carbon\Carbon::parse($musicState->started_at)->format('Y-m-d H:i:s')
                        : null,
                    'volume' => (int) $musicState->volume,
                    'is_loop' => (bool) $musicState->is_loop,
                    'is_shuffle' => (bool) $musicState->is_shuffle,
                    'last_action_by' => $musicState->last_action_by,
                ];

                if ($musicState->currentSong) {
                    $currentSong = $musicState->currentSong;

                    $currentSongResponse = [
                        'id' => $currentSong->id,
                        'room_id' => $currentSong->room_id,
                        'title' => $currentSong->title,
                        'artist' => $currentSong->artist,
                        'audio_url' => $currentSong->audio_url,
                        'duration_seconds' => $currentSong->duration_seconds,
                        'position' => $currentSong->position,
                        'is_active' => $currentSong->is_active,
                        'added_by' => [
                            'id' => optional($currentSong->addedBy)->id,
                            'name' => optional($currentSong->addedBy)->name,
                            'image' => optional($currentSong->addedBy)->image
                                ? (Str::startsWith(optional($currentSong->addedBy)->image, ['http://', 'https://'])
                                    ? optional($currentSong->addedBy)->image
                                    : Helper::showImage(optional($currentSong->addedBy)->image, true))
                                : null,
                        ],
                    ];
                }
            }
        }

        return response()->json([
            'status'       => true,
            'message'      => 'Room data fetched successfully',
            'is_room'      => $room ? true : false,
            'is_follow'    => $isFollow,
            'is_joined'    => $isJoined,
            'online_count' => $onlineCount,
            'online_users' => $onlineUsers,
            'agora'        => $agora,
            'room'         => $room,
            'access'       => $access,
            'music_state' => $musicStateResponse,
            'current_song' => $currentSongResponse,
        ]);
    }

    public function createRoom(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'room_name'  => 'required',
            'room_image' => 'required',
            'bio'        => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validation->errors()
            ]);
        }
        $user = Auth::user();
        $alreadyRoom = Room::where('user_id', $user->id)->exists();

        if ($alreadyRoom) {

            return response()->json([
                'status' => false,
                'message' => 'You already have a room'
            ], 409);
        }
        $roomImage = null;
        if ($request->hasFile('room_image')) {
            $roomImage = Helper::saveFile($request->file('room_image'), 'room_image');
        }

        $room = Room::create([
            'user_id'  => $user->id,
            'room_name'  => $request->room_name,
            'room_image' => $roomImage,
            'bio'        => $request->bio,
            'country'    => ucwords(strtolower($user->country)),
            'room_seat'  => 10,
        ]);

        $baseRoomId = 100000;
        $room->room_id = $baseRoomId + $room->id;
        $room->save();

        return response()->json([
            'status'  => true,
            'message' => 'Room created successfully',
            'data'    => $room,
        ], 200);
    }

    public function updateRoomSeat(Request $request)
    {
        $user = Auth::user();
        $validation = Validator::make($request->all(), [
            'id'  => 'required|exists:rooms,id',
            'room_seat' => 'required',
        ]);

        if ($validation->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validation->errors()
            ]);
        }

        $updateSeat = Room::find($request->id);

        if (!$updateSeat) {
            return response()->json([
                'status'  => false,
                'message' => 'Room not found'
            ], 404);
        }

        $updateSeat->update([
            'room_seat' => $request->room_seat
        ]);

        $updateSeat->refresh();

        event(new RoomSeatCountUpdated($updateSeat->id, [
            'room_id'   => $updateSeat->id,
            'room_seat' => (int) $updateSeat->room_seat,
            'message'   => $user->name . ' changed the number of seats to ' . $updateSeat->room_seat,
        ]));

        return response()->json([
            'status'  => true,
            'message' => 'Seats Update Successfully',
            'data'    => $updateSeat
        ]);
    }

    public function getGiftList(Request $request)
    {
        $auth = Auth::user();
        $query = Gift::where('status', 1);

        if ($request->filled('cover_type')) {
            $query->where('cover_type', $request->cover_type);
        } else {
            $query->where('cover_type', 'gift');
        }

        $gifts = $query->orderBy('id', 'desc')->get();

        foreach ($gifts as $gift) {
            if ($gift->cover) {
                $gift->cover = Helper::showImage($gift->cover, true);
            }

            if ($gift->file_path) {
                $gift->file_path = Helper::showImage($gift->file_path, true);
            }
        }

        return response()->json([
            'status'  => true,
            'message' => 'Gifts fetched successfully',
            'total_coins' => $auth->total_points,
            'data'    => $gifts
        ]);
    }

    public function sendGift(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'room_id'       => 'required|exists:rooms,id',
            'gift_id'       => 'required|exists:gifts,id',
            'receiver_ids'  => 'required|array|min:1',
            'receiver_ids.*' => 'exists:app_users,id',
            'multiplier'    => 'nullable|integer|min:1|max:999'
        ]);

        if ($validation->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validation->errors()
            ]);
        }

        $sender = Auth::user();
        $multiplier = $request->multiplier ?? 1;

        DB::beginTransaction();

        try {
            $gift = Gift::where('id', $request->gift_id)
                ->where('status', 1)
                ->first();

            if (!$gift) {
                return response()->json([
                    'status' => false,
                    'message' => 'Gift not available'
                ], 400);
            }

            $receiverIds = collect($request->receiver_ids)
                ->unique()
                // ->reject(fn($id) => $id == $sender->id)
                ->values();

            if ($receiverIds->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'No valid receivers found'
                ], 400);
            }

            $receiverCount = $receiverIds->count();
            $singleGiftCost = (int) $gift->price * $multiplier;
            $totalCost = $gift->price * $receiverCount * $multiplier;

            if ($sender->total_points < $totalCost) {
                return response()->json([
                    'status' => false,
                    'message' => 'Insufficient balance'
                ], 400);
            }

            $giftImage = !empty($gift->cover)
                ? Helper::showImage($gift->cover, true)
                : null;

            $luckySound = $gift->cover_type === 'lucky'
                ? asset('storage/lucky_gift_sound/lucky-gift-sound.mp3')
                : null;

            $giftData = [
                'id'    => $gift->id,
                'name'  => $gift->name ?? null,
                'image' => $giftImage,
                'svga'  => !empty($gift->file_path) ? Helper::showImage($gift->file_path, true) : null,
                'price' => $gift->price,
                'is_lucky' => $gift->cover_type === 'lucky',
                'lucky_sound' => $luckySound,
            ];

            $transactions = [];
            $receiverUsers = [];

            foreach ($receiverIds as $receiverId) {
                $transaction = GiftTransaction::create([
                    'room_id'     => $request->room_id,
                    'sender_id'   => $sender->id,
                    'receiver_id' => $receiverId,
                    'gift_id'     => $gift->id,
                    'coin_value'  => $gift->price,
                    'multiplier'  => $multiplier,
                    'total_value' => $singleGiftCost,
                ]);

                $transactions[] = $transaction;

                AppUser::where('id', $receiverId)->update([
                    'total_points' => DB::raw('total_points + ' . $singleGiftCost),
                    'total_value'  => DB::raw('total_value + ' . $singleGiftCost),
                ]);


                $user = AppUser::select('id', 'name', 'uid', 'image', 'total_points', 'total_value', 'user_level')
                    ->where('id', $receiverId)
                    ->first();


                if ($user) {
                    $level = UserLevel::where('experience_cap', '<=', $user->total_value)
                        ->orderByDesc('experience_cap')
                        ->first();

                    if ($level && $user->user_level != $level->grade) {
                        $user->user_level = $level->grade;
                        $user->save();
                    }

                    $receiverUsers[] = $user->fresh();
                }
                $this->updateWCLevel($receiverId, 'charm');
                $this->updateUserMedals($receiverId, 'charm');
            }

            AppUser::where('id', $sender->id)->decrement('total_points', $totalCost);

            //Level Acceleration Logic for Wealth Level
            $wealthExp = $totalCost;

            $multiplier = 1;

            if (
                Helper::hasVipPrivilege($sender->id, 'level_accelerator_10')
            ) {
                $multiplier = 1.10;
            } elseif (
                Helper::hasVipPrivilege($sender->id, 'level_accelerator_7')
            ) {
                $multiplier = 1.07;
            } elseif (
                Helper::hasVipPrivilege($sender->id, 'level_accelerator_5')
            ) {
                $multiplier = 1.05;
            } elseif (
                Helper::hasVipPrivilege($sender->id, 'level_accelerator_3')
            ) {
                $multiplier = 1.03;
            }

            $wealthExp = floor($wealthExp * $multiplier);

            $wc = WCLevel::firstOrCreate(
                [
                    'user_id' => $sender->id,
                    'type' => 'wealth'
                ],
                [
                    'level' => 1,
                    'exp' => 0
                ]
            );

            $wc->increment('exp', $wealthExp);

            $wc->refresh();

            $level = Level::where('type', 'wealth')
                ->where('required_exp', '<=', $wc->exp)
                ->orderByDesc('required_exp')
                ->first();

            if ($level) {
                $wc->update(['level' => $level->level]);
            }

            // $this->updateWCLevel($sender->id, 'wealth');

            Room::where('id', $request->room_id)->increment('total_points', $totalCost);

            $roomOwnerId = Room::where('id', $request->room_id)->value('user_id');
            // dd( $roomOwnerId);

            if ($roomOwnerId) {
                $this->updateUserMedals($roomOwnerId, 'room_wealth');
            }

            $familyId = FamilyMember::where('user_id', $sender->id)
                ->whereNull('left_at')
                ->value('family_id');

            if ($familyId) {
                Family::where('id', $familyId)
                    ->increment('total_points', $totalCost);
            }


            $singleReceiverId = $receiverCount === 1 ? $receiverIds->first() : null;

            $roomMessage = RoomMessage::create([
                'room_id'        => $request->room_id,
                'user_id'        => $sender->id,
                'message'        => 'sent a gift',
                'message_type'   => 'gift',
                'target_user_id' => $singleReceiverId,
                'gift_id'        => $gift->id,
                'gift_qty'       => $multiplier,
                'meta_json'      => [
                    'gift' => [
                        'id'    => $gift->id,
                        'name'  => $gift->name ?? null,
                        'image' => !empty($gift->cover) ? Helper::showImage($gift->cover, true) : null,
                        'svga'  => !empty($gift->file_path) ? Helper::showImage($gift->file_path, true) : null,
                        'price' => $gift->price,
                        'is_lucky' => $gift->cover_type === 'lucky',
                        'lucky_sound' => $gift->cover_type === 'lucky'
                            ? 'https://vanivoicechat.kotiboxglobaltech.online/public/storage/lucky_gift_sound/lucky-gift-sound.mp3'
                            : null,
                    ],
                    'sender' => [
                        'id'    => $sender->id,
                        'name'  => $sender->name,
                        'image' => !empty($sender->image) ? Helper::showImage($sender->image, true) : null,
                    ],
                    'receivers' => collect($receiverUsers)->map(function ($user) {
                        return [
                            'id'         => $user->id,
                            'name'       => $user->name ?? null,
                            'image'      => !empty($user->image) ? Helper::showImage($user->image, true) : null,
                            'user_level' => $user->user_level ?? null,
                        ];
                    })->values()->toArray(),
                    'receiver_ids'   => $receiverIds->toArray(),
                    'receiver_count' => $receiverCount,
                    'multiplier'     => (int) $multiplier,
                    'gift_qty'       => (int) $multiplier,
                    'single_cost'    => (int) $gift->price,
                    'total_cost'     => (int) $totalCost,
                ],
            ]);

            DB::commit();

            $sender = AppUser::select('id', 'name', 'uid', 'image', 'total_points')
                ->where('id', $sender->id)
                ->first();

            $eventPayload = [
                'type'    => 'gift',
                'room_id' => (int) $request->room_id,
                'message' => ($sender->name ?? 'User') . ' sent ' . ($gift->name ?? 'Gift') . ' x' . $multiplier,

                'gift' => [
                    'id'          => $gift->id,
                    'title'       => $gift->name,
                    'name'        => $gift->name,
                    'image'       => $giftImage,
                    'svga'        => !empty($gift->file_path) ? Helper::showImage($gift->file_path, true) : null,
                    'price'       => (int) $gift->price,
                    'is_lucky'    => $gift->cover_type === 'lucky',
                    'lucky_sound' => $luckySound,
                ],

                'sender' => [
                    'id'           => $sender->id,
                    'name'         => $sender->name,
                    'uid'          => $sender->uid,
                    'image'        => !empty($sender->image)
                        ? Helper::showImage($sender->image, true)
                        : null,
                    'total_points' => (int) $sender->total_points,
                ],

                'receivers' => collect($receiverUsers)->map(function ($user) {
                    return [
                        'id'          => $user->id,
                        'name'        => $user->name,
                        'uid'         => $user->uid,
                        'image'       => !empty($user->image)
                            ? Helper::showImage($user->image, true)
                            : null,
                        'user_level'  => $user->user_level,
                    ];
                })->values()->toArray(),

                'transaction_ids' => collect($transactions)
                    ->pluck('id')
                    ->values()
                    ->toArray(),

                'multiplier'     => (int) $multiplier,
                'receiver_count' => (int) $receiverCount,
                'total_cost'     => (int) $totalCost,
            ];

            try {
                event(new RoomGiftSent(
                    roomId: (int) $request->room_id,
                    payload: $eventPayload
                ));
            } catch (\Throwable $e) {
                \Log::error('Gift Pusher broadcast failed', [
                    'room_id' => $request->room_id,
                    'error'   => $e->getMessage(),
                ]);
            }

            return response()->json([
                'status' => true,
                'message' => 'Gift sent successfully',
                'receiver_count' => $receiverCount,
                'multiplier' => $multiplier,
                'total_cost' => $totalCost,
                'transactions' => $transactions,
                'gift' => $giftData,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function updateWCLevel($userId, $type)
    {
        // Current exp

        if ($type == 'wealth') {
            $currentExp = GiftTransaction::where('sender_id', $userId)->sum('total_value');
        } elseif ($type == 'charm') {
            $currentExp = GiftTransaction::where('receiver_id', $userId)->sum('total_value');
        } else {
            return;
        }

        // Current level

        $level = Level::where('type', $type)
            ->where('required_exp', '<=', $currentExp)
            ->orderByDesc('required_exp')
            ->first();

        // New user → level 1

        if (!$level) {
            $level = Level::where('type', $type)->orderBy('required_exp')->first();
        }

        // Save

        WCLevel::updateOrCreate(
            [
                'user_id' => $userId,
                'type' => $type
            ],
            [
                'level' => $level->level,
                'exp' => $currentExp
            ]
        );
    }

    private function updateUserMedals($userId, $type)
    {
        $currentValue = 0;

        //    Wealth / Charm
        if (in_array($type, ['wealth', 'charm'])) {

            $wcLevel = WcLevel::where('user_id', $userId)->where('type', $type)->first();
            if (!$wcLevel) {
                return;
            }
            $currentValue = (int) $wcLevel->level;
        }

        // Room Members
        elseif ($type == 'room_members') {

            $roomId = Room::where('user_id', $userId)->value('id');

            if (!$roomId) {
                return;
            }
            $currentValue = RoomMember::where('room_id', $roomId)
                ->distinct('user_id')->count();
        }

        // Room Wealth
        elseif ($type == 'room_wealth') {

            $roomId = Room::where('user_id', $userId)->value('id');
            if (!$roomId) {
                return;
            }
            $currentValue = GiftTransaction::where('room_id', $roomId)
                ->sum('total_value');
        }

        // First Recharge
        // elseif ($type == 'first_recharge') {

        //         $currentValue = Recharge::where('user_id', $userId)
        //             ->exists() ? 1 : 0;
        //     }

        else {
            return;
        }

        // \Log::info('MEDAL CHECK', [
        //     'type' => $type,
        //     'value' => $currentValue
        // ]);

        $medals = Medal::where('type', 'achievement')
            ->orderBy('target_value')
            ->get();

        foreach ($medals as $medal) {

            $title = strtolower($medal->title);

            // Medal filter

            if ($type == 'wealth' && !str_contains($title, 'wealth')) {
                continue;
            }

            if ($type == 'charm' && !str_contains($title, 'charm')) {
                continue;
            }

            if ($type == 'room_members' && !str_contains($title, 'room member')) {
                continue;
            }

            if ($type == 'room_wealth' && !str_contains($title, 'room wealth')) {
                continue;
            }

            if ($type == 'first_recharge' && !str_contains($title, 'recharge')) {
                continue;
            }

            // Wealth/Charm → level based

            if (in_array($type, ['wealth', 'charm'])) {

                if ($currentValue < (int) $medal->level) {
                    continue;
                }
            }

            // Others → target based
            else {
                if ($currentValue < (int) $medal->target_value) {
                    continue;
                }
            }

            UserMedal::firstOrCreate(
                [
                    'user_id' => $userId,
                    'medal_id' => $medal->id
                ],
                [
                    'is_equipped' => 0,
                    'achieved_at' => now()
                ]
            );

            // \Log::info('MEDAL UNLOCKED', [
            //     'user_id' => $userId,
            //     'medal' => $medal->title
            // ]);
        }
    }


    public function join(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'room_id' => 'required|integer'
        ]);

        if ($validation->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validation->errors()
            ], 422);
        }

        $user = Auth::user();
        $roomId = (int) $request->room_id;

        $room = Room::find($roomId);

        if (!$room) {
            return response()->json([
                'status' => false,
                'message' => 'Room not found'
            ], 404);
        }

        if ((bool) $room->is_locked) {
            $isOwner = (int) $room->user_id === (int) $user->id;

            if (!$isOwner) {

                if (!$request->filled('password')) {
                    return response()->json([
                        'status' => false,
                        'message' => 'This room is locked. Please enter password.',
                        'is_room_lock' => true
                    ], 403);
                }

                if (!Hash::check($request->password, $room->password)) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Invalid room password',
                        'is_room_lock' => true
                    ], 403);
                }
            }
        }

        $blocked = DB::table('user_blocks')
            ->where('room_id', $room->id)
            ->where('blocker_id', $room->user_id)
            ->where('blocked_user_id', $user->id)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();

        if ($blocked) {

            $message = is_null($blocked->expires_at)
                ? 'You are permanently banned from this room'
                : 'You are banned from this room';

            return response()->json([
                'status' => false,
                'message' => $message,
            ], 403);
        }

        // RoomPresence::firstOrCreate([
        //     'room_id' => $roomId,
        //     'user_id' => $user->id,
        // ]);

        RoomPresence::updateOrCreate(
            [
                'room_id' => $roomId,
                'user_id' => $user->id,
            ],
            [
                'last_ping_at' => now(),
            ]
        );

        if ((int) $room->user_id !== (int) $user->id) {

            RoomUserRole::firstOrCreate(
                [
                    'room_id' => $roomId,
                    'user_id' => $user->id,
                ],
                [
                    'role' => 'guest',
                    'assigned_by' => $room->user_id,
                ]
            );
        }

        $presences = RoomPresence::with([
            'user:id,name,image,country,gender,active_car_id,active_frame_id,active_chat_bubble_id,active_theme_id,active_uid_id,active_voice_id,active_entry_id,active_card_id,active_entry_type,active_entry_tag_type,active_frame_type,active_chat_bubble_type,active_voice_type,active_profile_card_type',
            'user.countryData:id,name,iso',
            'user.wcLevels.levelData',
            'user.userMedals.medal'
        ])
            ->where('room_id', $roomId)
            ->latest()
            ->get();

        $userIds = $presences->pluck('user_id')->filter()->unique()->values();

        $roles = RoomUserRole::where('room_id', $roomId)
            ->whereIn('user_id', $userIds)
            ->pluck('role', 'user_id');

        $onlineUsers = $presences->pluck('user')->filter()->values();

        $itemsByUserId = $this->getActiveItemsForUsers($onlineUsers);

        $users = $presences->map(function ($presence) use ($room, $roles, $itemsByUserId) {

            $presenceUser = $presence->user;

            $role = (int) $room->user_id === (int) $presence->user_id
                ? 'owner'
                : ($roles[$presence->user_id] ?? 'guest');


            $wealthLevel = $presenceUser?->wcLevels
                ?->where('type', 'wealth')
                ->first();

            $charmLevel = $presenceUser?->wcLevels
                ?->where('type', 'charm')
                ->first();


            $medals = $presenceUser?->userMedals
                ?->where('is_equipped', 1)
                ->sortBy('slot_no')
                ->take(3)
                ->map(function ($item) {

                    return [
                        'id' => $item->medal->id,
                        'name' => $item->medal->title,
                        'icon' => Helper::showImage($item->medal->icon, true)
                    ];
                })
                ->values();


            $flag = $presenceUser?->countryData?->iso
                ? 'https://flagcdn.com/w40/' . strtolower($presenceUser->countryData->iso) . '.png'
                : null;


            $itemData = $itemsByUserId[$presence->user_id] ?? [
                'entry' => null,
                'frame' => null,
                'chat_bubble' => null,
                'theme' => null,
                'uid' => null,
                'voice' => null,
                'enter_tag' => null,
                'profile' => null,
            ];

            return [
                'id' => $presenceUser->id ?? null,
                'name' => $presenceUser->name ?? null,
                'gender' => $presenceUser->gender ?? null,

                'image' => !empty($presenceUser->image)
                    ? Helper::showImage($presenceUser->image, true)
                    : null,

                'role' => $role,

                'flag' => $flag,

                'wealth_level' => [
                    'level' => $wealthLevel?->level ?? 1,
                    'icon' => $wealthLevel?->levelData?->icon
                        ? Helper::showImage($wealthLevel->levelData->icon, true)
                        : null
                ],

                'charm_level' => [
                    'level' => $charmLevel?->level ?? 1,
                    'icon' => $charmLevel?->levelData?->icon
                        ? Helper::showImage($charmLevel->levelData->icon, true)
                        : null
                ],

                'medals' => $medals,

                'entry' => $itemData['entry'],
                'frame' => $itemData['frame'],
                'chat_bubble' => $itemData['chat_bubble'],
                'theme' => $itemData['theme'],
                'uid' => $itemData['uid'],
                'voice' => $itemData['voice'],
                'enter_tag' => $itemData['enter_tag'],
                'profile' => $itemData['profile'],
            ];
        })->values();

        $onlineCount = $presences->count();

        $currentUser = $users
            ->where('id', $user->id)
            ->first();

        $roomOwnerId = $room->user_id;

        if ($roomOwnerId) {
            $this->updateUserMedals($roomOwnerId, 'room_members');
        }

        broadcast(new RoomPresenceUpdated(
            $roomId,
            $onlineCount,
            [],
            'join',
            $currentUser
        ))->toOthers();

        return response()->json([
            'status' => true,
            'room_id' => $roomId,
            'online_count' => $onlineCount,
            'users' => $users,
            'type' => 'join',
            'user' => $currentUser,
        ]);
    }


    private function getActiveItemsForUsers($users): array
    {
        $users = collect($users)->filter();

        $carIds = $users->pluck('active_car_id')->filter()->unique()->values();
        $frameIds = $users->pluck('active_frame_id')->filter()->unique()->values();
        $chatBubbleIds = $users->pluck('active_chat_bubble_id')->filter()->unique()->values();
        $themeIds = $users->pluck('active_theme_id')->filter()->unique()->values();
        $uidIds = $users->pluck('active_uid_id')->filter()->unique()->values();
        $voiceIds = $users->pluck('active_voice_id')->filter()->unique()->values();
        $entryTagIds = $users->pluck('active_entry_id')->filter()->unique()->values();
        $cardIds = $users->pluck('active_card_id')->filter()->unique()->values();

        $cars = DB::table('cars')
            ->whereIn('id', $carIds)
            ->get()
            ->keyBy('id');

        $frames = DB::table('frames')
            ->whereIn('id', $frameIds)
            ->get()
            ->keyBy('id');

        $chatBubbles = DB::table('chat_bubbles')
            ->whereIn('id', $chatBubbleIds)
            ->get()
            ->keyBy('id');

        $themes = DB::table('themes')
            ->whereIn('id', $themeIds)
            ->get()
            ->keyBy('id');

        $uids = DB::table('store_uids')
            ->whereIn('id', $uidIds)
            ->get()
            ->keyBy('id');

        $voices = DB::table('voices')
            ->whereIn('id', $voiceIds)
            ->get()
            ->keyBy('id');

        $entryTags = DB::table('entry_tags')
            ->whereIn('id', $entryTagIds)
            ->get()
            ->keyBy('id');

        $dataCards = DB::table('data_cards')
            ->whereIn('id', $cardIds)
            ->get()
            ->keyBy('id');

        $mapped = [];

        foreach ($users as $user) {
            $entry = null;
            $frame = null;
            $chatBubble = null;
            $theme = null;
            $uid = null;
            $voice = null;
            $enterTag = null;
            $profile = null;

            if (!empty($user->active_car_id)) {

                if (
                    empty($user->active_entry_type)
                    || $user->active_entry_type === 'store'
                ) {

                    if (isset($cars[$user->active_car_id])) {

                        $car = $cars[$user->active_car_id];

                        $entry = [
                            'id' => $car->id,
                            'name' => $car->name ?? null,
                            'image' => !empty($car->icon)
                                ? Helper::showImage($car->icon, true)
                                : null,
                            'file' => !empty($car->gif)
                                ? Helper::showImage($car->gif, true)
                                : null,
                        ];
                    }
                } elseif ($user->active_entry_type === 'vip') {

                    $vip = Vip::find($user->active_car_id);

                    if ($vip) {

                        $entry = [
                            'id' => $vip->id,
                            'name' => $vip->name,
                            'image' => !empty($vip->voice_frame)
                                ? asset('storage/' . $vip->voice_frame)
                                : null,
                            'file' => !empty($vip->voice_animation)
                                ? asset('storage/' . $vip->voice_animation)
                                : null,
                        ];
                    }
                } elseif ($user->active_entry_type === 'svip') {

                    $svip = Svip::find($user->active_car_id);

                    if ($svip) {

                        $entry = [
                            'id' => $svip->id,
                            'name' => $svip->name,
                            'image' => !empty($svip->entrance_image)
                                ? asset('storage/' . $svip->entrance_image)
                                : null,
                            'file' => !empty($svip->entrance_animation)
                                ? asset('storage/' . $svip->entrance_animation)
                                : null,
                        ];
                    }
                }
            }

            if (!empty($user->active_frame_id) && isset($frames[$user->active_frame_id])) {
                $frameData = $frames[$user->active_frame_id];
                $frame = [
                    'id' => $frameData->id,
                    'name' => $frameData->name ?? null,
                    'image' => !empty($frameData->icon) ? Helper::showImage($frameData->icon, true) : null,
                    'file' => !empty($frameData->gif) ? Helper::showImage($frameData->gif, true) : null,
                ];
            }

            if (!empty($user->active_chat_bubble_id) && isset($chatBubbles[$user->active_chat_bubble_id])) {
                $chatBubbleData = $chatBubbles[$user->active_chat_bubble_id];
                $chatBubble = [
                    'id' => $chatBubbleData->id,
                    'name' => $chatBubbleData->name ?? null,
                    'image' => !empty($chatBubbleData->icon) ? Helper::showImage($chatBubbleData->icon, true) : null,
                ];
            }

            if (!empty($user->active_theme_id) && isset($themes[$user->active_theme_id])) {
                $themeData = $themes[$user->active_theme_id];
                $theme = [
                    'id' => $themeData->id,
                    'name' => $themeData->name ?? null,
                    'image' => !empty($themeData->icon) ? Helper::showImage($themeData->icon, true) : null,
                ];
            }

            if (!empty($user->active_uid_id) && isset($uids[$user->active_uid_id])) {
                $uidData = $uids[$user->active_uid_id];
                $uid = [
                    'id' => $uidData->id,
                    'uid' => $uidData->unique_id ?? null,
                    'image' => !empty($uidData->badge) ? Helper::showImage($uidData->badge, true) : null,
                ];
            }

            if (!empty($user->active_voice_id) && isset($voices[$user->active_voice_id])) {
                $voiceData = $voices[$user->active_voice_id];
                $voice = [
                    'id' => $voiceData->id,
                    'name' => $voiceData->name ?? null,
                    'image' => !empty($voiceData->icon) ? Helper::showImage($voiceData->icon, true) : null,
                    'file' => !empty($voiceData->gif) ? Helper::showImage($voiceData->gif, true) : null,
                ];
            }

            if (!empty($user->active_entry_id)) {

                if (
                    empty($user->active_entry_tag_type)
                    || $user->active_entry_tag_type === 'store'
                ) {

                    if (isset($entryTags[$user->active_entry_id])) {

                        $enterTagData = $entryTags[$user->active_entry_id];

                        $enterTag = [
                            'id' => $enterTagData->id,
                            'name' => $enterTagData->name ?? null,
                            'image' => !empty($enterTagData->icon)
                                ? Helper::showImage($enterTagData->icon, true)
                                : null,
                            'file' => !empty($enterTagData->gif)
                                ? Helper::showImage($enterTagData->gif, true)
                                : null,
                            'img_key' => $enterTagData->img_key ?? null,
                            'text_key' => $enterTagData->text_key ?? null,
                            'frame_key' => $enterTagData->frame_key ?? null,
                        ];
                    }
                } elseif ($user->active_entry_tag_type === 'vip') {

                    $vip = Vip::find($user->active_entry_id);

                    if ($vip) {

                        $enterTag = [
                            'id' => $vip->id,
                            'name' => $vip->name,
                            'image' => !empty($vip->entry_tag)
                                ? asset('storage/' . $vip->entry_tag)
                                : null,
                            'file' => !empty($vip->entry_tag_animation)
                                ? asset('storage/' . $vip->entry_tag_animation)
                                : null,
                            'img_key' => $vip->img_key ?? null,
                            'text_key' => $vip->text_key ?? null,
                            'frame_key' => $vip->frame_key ?? null,
                        ];
                    }
                } elseif ($user->active_entry_tag_type === 'svip') {

                    $svip = Svip::find($user->active_entry_id);

                    if ($svip) {

                        $enterTag = [
                            'id' => $svip->id,
                            'name' => $svip->name,
                            'image' => !empty($svip->entry)
                                ? asset('storage/' . $svip->entry)
                                : null,
                            'file' => !empty($svip->entry_animation)
                                ? asset('storage/' . $svip->entry_animation)
                                : null,
                            'img_key' => $svip->img_key ?? null,
                            'text_key' => $svip->text_key ?? null,
                            'frame_key' => $svip->frame_key ?? null,
                        ];
                    }
                }
            }

            if (!empty($user->active_card_id) && isset($dataCards[$user->active_card_id])) {
                $profileData = $dataCards[$user->active_card_id];
                $profile = [
                    'id' => $profileData->id,
                    'name' => $profileData->name ?? null,
                    'image' => !empty($profileData->icon) ? Helper::showImage($profileData->icon, true) : null,
                    'file' => !empty($profileData->gif) ? Helper::showImage($profileData->gif, true) : null,
                ];
            }

            $mapped[$user->id] = [
                'entry' => $entry,
                'frame' => $frame,
                'chat_bubble' => $chatBubble,
                'theme' => $theme,
                'uid' => $uid,
                'voice' => $voice,
                'enter_tag' => $enterTag,
                'profile' => $profile,
            ];
        }

        return $mapped;
    }

    public function leave(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'room_id' => 'required|integer'
        ]);

        if ($validation->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validation->errors()
            ], 422);
        }

        $user = Auth::user();
        $roomId = (int) $request->room_id;

        DB::beginTransaction();

        try {
            /** @var \App\Services\AgoraCloudPlayerService $cloudPlayerService */
            $cloudPlayerService = app(\App\Services\AgoraCloudPlayerService::class);

            $mySeat = RoomSeat::where('room_id', $roomId)
                ->where('user_id', $user->id)
                ->first();

            $oldSeatNo = $mySeat->seat_no ?? null;
            $oldIsOnMic = $mySeat->is_on_mic ?? 0;

            $activePlayers = \App\Models\RoomMusicActivePlayer::where('room_id', $roomId)
                ->where('started_by', $user->id)
                ->where('is_active', true)
                ->whereIn('status', ['playing', 'paused'])
                ->lockForUpdate()
                ->get();

            foreach ($activePlayers as $activePlayer) {
                \Log::info('Stopping user active player on room leave', [
                    'room_id' => $roomId,
                    'user_id' => $user->id,
                    'active_player_id' => $activePlayer->id,
                    'agora_player_id' => $activePlayer->agora_player_id,
                    'playlist_id' => $activePlayer->playlist_id,
                    'status' => $activePlayer->status,
                ]);

                if (!empty($activePlayer->agora_player_id)) {
                    try {
                        $deleteResponse = $cloudPlayerService->deletePlayer($activePlayer->agora_player_id);

                        \Log::info('Agora player deleted on room leave', [
                            'room_id' => $roomId,
                            'user_id' => $user->id,
                            'active_player_id' => $activePlayer->id,
                            'agora_player_id' => $activePlayer->agora_player_id,
                            'delete_response' => $deleteResponse,
                        ]);
                    } catch (\Throwable $e) {
                        \Log::warning('Failed to delete Agora player on room leave', [
                            'room_id' => $roomId,
                            'user_id' => $user->id,
                            'active_player_id' => $activePlayer->id,
                            'agora_player_id' => $activePlayer->agora_player_id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                $activePlayer->update([
                    'status' => 'stopped',
                    'is_active' => false,
                    'started_at' => null,
                ]);
            }

            RoomSeat::where('room_id', $roomId)
                ->where('user_id', $user->id)
                ->delete();

            RoomPresence::where([
                'room_id' => $roomId,
                'user_id' => $user->id,
            ])->delete();

            $seatData = DB::table('room_seats')
                ->where('room_id', $roomId)
                ->get()
                ->keyBy('user_id');

            $users = RoomPresence::with('user:id,name,image')
                ->where('room_id', $roomId)
                ->latest()
                ->get()
                ->map(function ($presence) use ($seatData) {
                    $seat = $seatData->get($presence->user_id);

                    return [
                        'id' => $presence->user->id ?? null,
                        'name' => $presence->user->name ?? null,
                        'image' => !empty($presence->user->image)
                            ? \App\Helper\Helper::showImage($presence->user->image, true)
                            : null,
                        'seat_no' => $seat->seat_no ?? null,
                        'is_on_mic' => $seat->is_on_mic ?? 0,
                    ];
                })
                ->values();

            $onlineCount = RoomPresence::where('room_id', $roomId)->count();

            $currentUser = [
                'id' => $user->id,
                'name' => $user->name,
                'image' => !empty($user->image)
                    ? \App\Helper\Helper::showImage($user->image, true)
                    : null,
                'seat_no' => $oldSeatNo,
                'is_on_mic' => $oldIsOnMic,
            ];

            $seats = $this->getRoomSeats($roomId);

            DB::commit();

            /*
        |--------------------------------------------------------------------------
        | Lightweight presence broadcast
        |--------------------------------------------------------------------------
        | API response same rahega.
        | Pusher me users full array nahi bhej rahe.
        | Sirf leaving user ka data bhej rahe hain.
        */
            broadcast(new RoomPresenceUpdated(
                $roomId,
                $onlineCount,
                [],
                'leave',
                $currentUser
            ))->toOthers();

            broadcast(new RoomSeatUpdated(
                $roomId,
                'leave',
                $seats,
                $oldSeatNo,
                $currentUser
            ))->toOthers();

            return response()->json([
                'status' => true,
                'room_id' => $roomId,
                'online_count' => $onlineCount,
                'users' => $users,
                'type' => 'leave',
                'user' => $currentUser,
                'seat_deleted' => !is_null($oldSeatNo),
                'seats' => $seats,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }


    public function count($roomId)
    {
        return response()->json([
            'status' => true,
            'online_count' => RoomPresence::where('room_id', $roomId)->count()
        ]);
    }

    public function getRoomDetails(Request $request)
    {
        $user   = Auth::user();
        $roomId = $request->room_id;
        $hasTakeAnySeatPrivilege = Helper::hasVipPrivilege($user->id, 'take_any_seat');
        $room = Room::with('user:id,name,uid,image,gender,active_frame_id,active_uid_id,active_card_id,active_chat_bubble_id,active_voice_id,active_frame_type,active_voice_type,active_chat_bubble_type,active_profile_card_type', 'theme:id,name,icon')
            ->where('id', $roomId)
            ->where('status', 1)
            ->first();

        $isFollow = false;
        $isJoined = false;
        $onlineCount = 0;
        $onlineUsers = [];
        $mySeatNo = null;
        $agora = null;
        $musicStateResponse = null;
        $currentSongResponse = null;

        if ($room) {
            $room->room_event_banner = asset('storage/room_event_banner.jpg');

            if ($room->room_image) {
                $room->room_image = Helper::showImage($room->room_image, true);
            }

            if ($room->user && $room->user->image) {
                if (Str::startsWith($room->user->image, ['http://', 'https://'])) {
                    $room->user->image = $room->user->image;
                } else {
                    $room->user->image = Helper::showImage($room->user->image, true);
                }
            } else {
                $room->user->image = null;
            }

            $roomOwnerUid = $room->user->uid;
            $roomOwnerUidBadge = null;
            $uidBadgeColor = null;

            $premiumUid = PremiumNumber::where('user_id', $room->user->id)
                ->where('end_at', '>', now())
                ->latest()
                ->first();

            if ($premiumUid) {

                $roomOwnerUid = $premiumUid->premium_number;
                $roomOwnerUidBadge = asset('storage/1000175794.png');
                $uidBadgeColor = '#FFD700';
            } elseif ($room->user->active_uid_id) {

                $storeUid = StoreUids::find($room->user->active_uid_id);

                if ($storeUid) {

                    $hasValidPurchase = DB::table('item_deliveries')
                        ->where('recipient', $room->user->id)
                        ->where('type', 'id')
                        ->where('item_id', $storeUid->id)
                        ->where('end_at', '>', now())
                        ->exists();

                    $hasValidGift = DB::table('item_gift_transactions')
                        ->where('receiver_id', $room->user->id)
                        ->where('type', 'id')
                        ->where('item_id', $storeUid->id)
                        ->where('end_at', '>', now())
                        ->exists();

                    if ($hasValidPurchase || $hasValidGift) {

                        $roomOwnerUid = $storeUid->unique_id;

                        $roomOwnerUidBadge = !empty($storeUid->rank_badge)
                            ? Helper::showImage($storeUid->rank_badge, true)
                            : null;
                        $uidBadgeColor = $storeUid->rank_badge_color ?? null;
                    }
                }
            }

            $room->user->uid = $roomOwnerUid;
            $room->user->uid_badge = $roomOwnerUidBadge;
            $room->user->uid_badge_color = $uidBadgeColor;

            $ownerNickname = Helper::getNicknameMeta($room->user->id);
            $room->user->animated = $ownerNickname['animated'];
            $room->user->color = $ownerNickname['color'];
            $room->user->effect = $ownerNickname['effect'];

            if ($room->theme) {
                $theme = [
                    'id'    => $room->theme->id,
                    'name'  => $room->theme->name,
                    'image' => Helper::showImage($room->theme->icon, true),
                ];

                $room->unsetRelation('theme');

                $room->theme = $theme;
            } else {
                $room->theme = null;
            }

            $isFollow = DB::table('room_follows')
                ->where('room_id', $room->id)
                ->where('user_id', $user->id)
                ->exists();

            $isJoined = DB::table('room_members')
                ->where('room_id', $room->id)
                ->where('user_id', $user->id)
                ->whereNull('left_at')
                ->exists();

            $onlineCount = RoomPresence::where('room_id', $room->id)->count();
            $seatData = RoomSeat::with([
                'user:id,uid,name,image,country,gender,active_frame_id,active_uid_id,active_card_id,active_chat_bubble_id,active_voice_id,active_frame_type,active_voice_type,active_chat_bubble_type,active_profile_card_type',
                'user.countryData:id,name,iso',
                'user.wcLevels.levelData',
                'user.userMedals.medal'
            ])
                ->where('room_id', $room->id)
                ->get()
                ->keyBy('seat_no');

            $seatSettings = DB::table('room_seat_settings')
                ->where('room_id', $room->id)
                ->get()
                ->keyBy('seat_no');

            // $roles = RoomUserRole::where('room_id', $room->id)
            //     ->pluck('role', 'user_id');

            $roles = RoomUserRole::where('room_id', $room->id)
                ->pluck('role', 'user_id');

            $setting = RoomSetting::where('room_id', $room->id)->first();

            $access = [
                'mic_permission' => $setting ? (int) $setting->mic_permission : 0,
                'message_permission' => $setting ? (int) $setting->message_permission : 0,
                'admin_can_play_music' => $setting ? (int) $setting->admin_can_play_music : 0,
                'can_take_any_seat' => $hasTakeAnySeatPrivilege,
            ];

            $seatUsers = $seatData->pluck('user')->filter()->values();

            $itemsByUserId = $this->getFrameAndUidForUsers($seatUsers);

            for ($i = 1; $i <= (int) $room->room_seat; $i++) {
                $seat = $seatData->get($i);
                $setting = $seatSettings->get($i);

                $userId = $seat && $seat->user ? $seat->user->id : null;

                // default role
                $role = null;

                // host check
                if ($userId && (int) $room->user_id === (int) $userId) {
                    $role = 'owner';
                } elseif ($userId) {
                    $role = $roles[$userId] ?? 'guest';
                }

                $isMySeat = $seat && (int) $seat->user_id === (int) $user->id;

                if ($isMySeat) {
                    $mySeatNo = $i;
                }

                $itemData = $userId
                    ? ($itemsByUserId[$userId] ?? [

                        'frame' => null,
                        'uid' => null,
                        'profile' => null,
                        'chat_bubble' => null,
                        'voice' => null,
                    ])
                    : [
                        'frame' => null,
                        'uid' => null,
                        'profile' => null,
                        'chat_bubble' => null,
                        'voice' => null,
                    ];

                $wealthLevel = $seat?->user?->wcLevels
                    ?->where('type', 'wealth')
                    ->first();

                $charmLevel = $seat?->user?->wcLevels
                    ?->where('type', 'charm')
                    ->first();


                $medals = $seat?->user?->userMedals
                    ?->where('is_equipped', 1)
                    ->sortBy('slot_no')
                    ->take(3)
                    ->map(function ($item) {

                        return [
                            'id' => $item->medal->id,
                            'name' => $item->medal->title,
                            'icon' => Helper::showImage(
                                $item->medal->icon,
                                true
                            )
                        ];
                    })
                    ->values();


                $flag = $seat?->user?->countryData?->iso
                    ? 'https://flagcdn.com/w40/' .
                    strtolower($seat->user->countryData->iso) .
                    '.png'
                    : null;

                $displayUid = $seat && $seat->user ? $seat->user->uid : null;
                $uidBadge = null;
                $uidBadgeColor = null;

                $nicknameMeta = $seat && $seat->user ? Helper::getNicknameMeta($seat->user->id)
                    : [
                        'animated' => false,
                        'color' => null,
                        'effect' => null,
                    ];

                if ($seat && $seat->user) {

                    $premiumUid = PremiumNumber::where('user_id', $seat->user->id)
                        ->where('end_at', '>', now())
                        ->latest()
                        ->first();

                    if ($premiumUid) {

                        $displayUid = $premiumUid->premium_number;
                        $uidBadge = asset('storage/1000175794.png');
                        $uidBadgeColor = '#fcd01c';
                    } elseif ($seat->user->active_uid_id) {

                        $storeUid = StoreUids::find($seat->user->active_uid_id);

                        if ($storeUid) {

                            $hasValidPurchase = DB::table('item_deliveries')
                                ->where('recipient', $seat->user->id)
                                ->where('type', 'id')
                                ->where('item_id', $storeUid->id)
                                ->where('end_at', '>', now())
                                ->exists();

                            $hasValidGift = DB::table('item_gift_transactions')
                                ->where('receiver_id', $seat->user->id)
                                ->where('type', 'id')
                                ->where('item_id', $storeUid->id)
                                ->where('end_at', '>', now())
                                ->exists();

                            if ($hasValidPurchase || $hasValidGift) {

                                $displayUid = $storeUid->unique_id;

                                $uidBadge = !empty($storeUid->rank_badge)
                                    ? Helper::showImage($storeUid->rank_badge, true)
                                    : null;
                                $uidBadgeColor = $storeUid->rank_badge_color ?? null;
                            }
                        }
                    }
                }

                $onlineUsers[] = [
                    'id' => $seat && $seat->user ? $seat->user->id : null,
                    // 'uid' => $seat && $seat->user ? $seat->user->uid : null,
                    'uid' => $displayUid,
                    'uid_badge' => $uidBadge,
                    'uid_badge_color' => $uidBadgeColor,
                    'name' => $seat && $seat->user ? $seat->user->name : null,
                    'nickname_meta' => $nicknameMeta,
                    'gender' => $seat && $seat->user ? $seat->user->gender : null,
                    'image' => ($seat && $seat->user && !empty($seat->user->image))
                        ? Helper::showImage($seat->user->image, true)
                        : null,
                    'seat_no' => $i,
                    'is_occupied' => $seat ? true : false,
                    'is_my_seat' => $seat && (int) $seat->user_id === (int) $user->id,
                    'is_locked' => $setting ? (bool)$setting->is_locked : false,
                    'is_muted'  => $setting ? (bool)$setting->is_muted_by_host : false,
                    'can_take_any_seat' => $hasTakeAnySeatPrivilege,
                    'is_on_mic'  => $seat ? $seat->is_on_mic : 0,
                    'role' => $role,

                    'frame' => $itemData['frame'],
                    'store_id' => $itemData['uid'],
                    'chat_bubble' => $itemData['chatBubble'] ?? null,
                    'voice' => $itemData['voice'] ?? null,
                    'profile' => $itemData['profile'] ?? null,

                    'left_relation' => null,

                    'right_relation' => null,

                    'flag' => $flag,

                    'wealth_level' => [
                        'level' => $wealthLevel?->level ?? 1,
                        'icon' => $wealthLevel?->levelData?->icon
                            ? Helper::showImage(
                                $wealthLevel->levelData->icon,
                                true
                            )
                            : null
                    ],

                    'charm_level' => [
                        'level' => $charmLevel?->level ?? 1,
                        'icon' => $charmLevel?->levelData?->icon
                            ? Helper::showImage(
                                $charmLevel->levelData->icon,
                                true
                            )
                            : null
                    ],

                    'medals' => $medals,

                    'role_badges' => $seat && $seat->user ? Helper::getUserRoleBadges($seat->user->id) : [],


                ];
            }

            $usersCollection = collect($onlineUsers);

            foreach ($onlineUsers as $key => $seatUser) {

                if (!$seatUser['id']) {
                    continue;
                }

                // LEFT SEAT

                $leftSeat = $usersCollection->firstWhere('seat_no', $seatUser['seat_no'] - 1);

                if ($leftSeat && !empty($leftSeat['id'])) {
                    $leftRelation = $this->getSeatRelation($seatUser['id'], $leftSeat['id']);

                    if ($leftRelation) {
                        $onlineUsers[$key]['left_relation'] = $leftRelation;
                    }
                }

                // RIGHT SEAT

                $rightSeat = $usersCollection->firstWhere('seat_no', $seatUser['seat_no'] + 1);

                if ($rightSeat && !empty($rightSeat['id'])) {
                    $rightRelation = $this->getSeatRelation($seatUser['id'], $rightSeat['id']);

                    if ($rightRelation) {
                        $onlineUsers[$key]['right_relation'] = $rightRelation;
                    }
                }
            }

            $agora = [
                'app_id' => env('AGORA_APP_ID'),
                'channel_name' => 'room_' . $room->id,
                'uid' => (int) $user->id,
                'role' => $mySeatNo ? 'broadcaster' : 'audience',
            ];
            $musicState = RoomMusicState::with(['currentSong.addedBy:id,name,image'])
                ->where('room_id', $room->id)
                ->first();

            if ($musicState) {
                $musicStateResponse = [
                    'room_id' => $musicState->room_id,
                    'current_playlist_id' => $musicState->current_playlist_id,
                    'status' => $musicState->status,
                    'current_position_sec' => (int) $musicState->current_position_sec,
                    'started_at' => $musicState->started_at
                        ? \Carbon\Carbon::parse($musicState->started_at)->format('Y-m-d H:i:s')
                        : null,
                    'volume' => (int) $musicState->volume,
                    'is_loop' => (bool) $musicState->is_loop,
                    'is_shuffle' => (bool) $musicState->is_shuffle,
                    'last_action_by' => $musicState->last_action_by,
                ];

                if ($musicState->currentSong) {
                    $currentSong = $musicState->currentSong;

                    $currentSongResponse = [
                        'id' => $currentSong->id,
                        'room_id' => $currentSong->room_id,
                        'title' => $currentSong->title,
                        'artist' => $currentSong->artist,
                        'audio_url' => $currentSong->audio_url,
                        'duration_seconds' => $currentSong->duration_seconds,
                        'position' => $currentSong->position,
                        'is_active' => $currentSong->is_active,
                        'added_by' => [
                            'id' => optional($currentSong->addedBy)->id,
                            'name' => optional($currentSong->addedBy)->name,
                            'image' => optional($currentSong->addedBy)->image
                                ? (Str::startsWith(optional($currentSong->addedBy)->image, ['http://', 'https://'])
                                    ? optional($currentSong->addedBy)->image
                                    : Helper::showImage(optional($currentSong->addedBy)->image, true))
                                : null,
                        ],
                    ];
                }
            }
        }

        return response()->json([
            'status'    => true,
            'message'   => 'Room data fetched successfully',
            'is_room'   => (bool) $room,
            'is_follow' => $isFollow,
            'is_joined' => $isJoined,
            'online_count' => $onlineCount,
            'online_users' => $onlineUsers,
            'agora' => $agora,
            'room'      => $room,
            'access' => $access,
            'music_state' => $musicStateResponse,
            'current_song' => $currentSongResponse,
        ]);
    }

    private function getSeatRelation($userOneId, $userTwoId)
    {
        if (!$userOneId || !$userTwoId) {
            return null;
        }

        $relation = RelationshipInvitation::with(
            'relationshipItem:id,name,icon,gif'
        )
            ->where('status', 'accept')
            ->whereIn('type', ['cp', 'brother', 'sister', 'confident'])

            ->where(function ($q)
            use ($userOneId, $userTwoId) {

                $q->where(function ($sub)
                use ($userOneId, $userTwoId) {

                    $sub->where('sender_id', $userOneId)
                        ->where('receiver_id', $userTwoId);
                })

                    ->orWhere(function ($sub)
                    use ($userOneId, $userTwoId) {

                        $sub->where('sender_id', $userTwoId)
                            ->where('receiver_id', $userOneId);
                    });
            })->first();

        if (!$relation) {
            return null;
        }

        return [
            'type' => strtolower($relation->type),
            'image' => $relation->relationshipItem?->gif
                ? Helper::showImage($relation->relationshipItem->gif, true) : null,
        ];
    }

    // private function getFrameAndUidForUsers($users): array
    // {
    //     $users = collect($users)->filter();

    //     $frameIds = $users->pluck('active_frame_id')->filter()->unique()->values();
    //     $uidIds = $users->pluck('active_uid_id')->filter()->unique()->values();
    //     $chatBubbleIds = $users->pluck('active_chat_bubble_id')->filter()->unique()->values();
    //     $voiceIds = $users->pluck('active_voice_id')->filter()->unique()->values();
    //     $profileCardIds = $users->pluck('active_card_id')->filter()->unique()->values();


    //     $frames = DB::table('frames')
    //         ->whereIn('id', $frameIds)
    //         ->get()
    //         ->keyBy('id');

    //     $uids = DB::table('store_uids')
    //         ->whereIn('id', $uidIds)
    //         ->get()
    //         ->keyBy('id');

    //     $chatBubbles = DB::table('chat_bubbles')
    //         ->whereIn('id', $chatBubbleIds)
    //         ->get()
    //         ->keyBy('id');

    //     $voices = DB::table('voices')
    //         ->whereIn('id', $voiceIds)
    //         ->get()
    //         ->keyBy('id');
    //     $profileCards = DB::table('data_cards')
    //         ->whereIn('id', $profileCardIds)
    //         ->get()
    //         ->keyBy('id');

    //     $mapped = [];

    //     foreach ($users as $user) {
    //         $frame = null;
    //         $uid = null;
    //         $chatBubble = null;
    //         $voice = null;
    //         $profileCard = null;

    //         if (!empty($user->active_frame_id) && isset($frames[$user->active_frame_id])) {
    //             $frameData = $frames[$user->active_frame_id];

    //             $frame = [
    //                 'id' => $frameData->id,
    //                 'name' => $frameData->name ?? null,
    //                 'image' => !empty($frameData->icon)
    //                     ? Helper::showImage($frameData->icon, true)
    //                     : null,
    //                 'file' => !empty($frameData->gif)
    //                     ? Helper::showImage($frameData->gif, true)
    //                     : null,
    //             ];
    //         }

    //         if (!empty($user->active_uid_id) && isset($uids[$user->active_uid_id])) {
    //             $uidData = $uids[$user->active_uid_id];

    //             $uid = [
    //                 'id' => $uidData->id,
    //                 'uid' => $uidData->unique_id ?? null,
    //                 'image' => !empty($uidData->badge)
    //                     ? Helper::showImage($uidData->badge, true)
    //                     : null,
    //             ];
    //         }
    //         if (!empty($user->active_chat_bubble_id) && isset($chatBubbles[$user->active_chat_bubble_id])) {
    //             $chatBubbleData = $chatBubbles[$user->active_chat_bubble_id];

    //             $chatBubble = [
    //                 'id' => $chatBubbleData->id,
    //                 'name' => $chatBubbleData->name ?? null,
    //                 'image' => !empty($chatBubbleData->icon)
    //                     ? Helper::showImage($chatBubbleData->icon, true)
    //                     : null,
    //                 'slice_rect' => $chatBubbleData->slice_rect ?? null,

    //                 'padding_rect' => $chatBubbleData->padding_rect ?? null,

    //             ];
    //         }

    //         if (!empty($user->active_voice_id) && isset($voices[$user->active_voice_id])) {
    //             $voiceData = $voices[$user->active_voice_id];
    //             $voice = [
    //                 'id' => $voiceData->id,
    //                 'name' => $voiceData->name ?? null,
    //                 'image' => !empty($voiceData->icon) ? Helper::showImage($voiceData->icon, true) : null,
    //                 'file' => !empty($voiceData->gif) ? Helper::showImage($voiceData->gif, true) : null,
    //             ];
    //         }
    //         if (!empty($user->active_card_id) && isset($profileCards[$user->active_card_id])) {
    //             $profileCardData = $profileCards[$user->active_card_id];
    //             $profileCard = [
    //                 'id' => $profileCardData->id,
    //                 'name' => $profileCardData->name ?? null,
    //                 'image' => !empty($profileCardData->icon) ? Helper::showImage($profileCardData->icon, true) : null,
    //                 'file' => !empty($profileCardData->gif) ? Helper::showImage($profileCardData->gif, true) : null,
    //             ];
    //         }

    //         $mapped[$user->id] = [
    //             'frame' => $frame,
    //             'uid' => $uid,
    //             'chatBubble' => $chatBubble,
    //             'voice' => $voice,
    //             'profile' => $profileCard,
    //         ];
    //     }

    //     return $mapped;
    // }


    private function getFrameAndUidForUsers($users): array
    {
        $users = collect($users)->filter();

        $frameIds = $users->pluck('active_frame_id')->filter()->unique()->values();
        $uidIds = $users->pluck('active_uid_id')->filter()->unique()->values();
        $chatBubbleIds = $users->pluck('active_chat_bubble_id')->filter()->unique()->values();
        $voiceIds = $users->pluck('active_voice_id')->filter()->unique()->values();
        $profileCardIds = $users->pluck('active_card_id')->filter()->unique()->values();

        $frames = DB::table('frames')
            ->whereIn('id', $frameIds)
            ->get()
            ->keyBy('id');

        $uids = DB::table('store_uids')
            ->whereIn('id', $uidIds)
            ->get()
            ->keyBy('id');

        $chatBubbles = DB::table('chat_bubbles')
            ->whereIn('id', $chatBubbleIds)
            ->get()
            ->keyBy('id');

        $voices = DB::table('voices')
            ->whereIn('id', $voiceIds)
            ->get()
            ->keyBy('id');

        $profileCards = DB::table('data_cards')
            ->whereIn('id', $profileCardIds)
            ->get()
            ->keyBy('id');

        $vips = DB::table('vips')->get()->keyBy('id');

        $svips = DB::table('svips')->get()->keyBy('id');

        $relationshipItems = DB::table('relationship_items')->get()->keyBy('id');

        $mapped = [];

        foreach ($users as $user) {

            $frame = null;
            $uid = null;
            $chatBubble = null;
            $voice = null;
            $profileCard = null;

            /*
        |--------------------------------------------------------------------------
        | FRAME
        |--------------------------------------------------------------------------
        */
            if (!empty($user->active_frame_id)) {

                $frameType = $user->active_frame_type ?? 'store';

                if ($frameType === 'store' && isset($frames[$user->active_frame_id])) {

                    $frameData = $frames[$user->active_frame_id];

                    $frame = [
                        'id' => $frameData->id,
                        'name' => $frameData->name ?? null,
                        'image' => !empty($frameData->icon)
                            ? Helper::showImage($frameData->icon, true)
                            : null,
                        'file' => !empty($frameData->gif)
                            ? Helper::showImage($frameData->gif, true)
                            : null,
                    ];
                } elseif ($frameType === 'vip' && isset($vips[$user->active_frame_id])) {

                    $vip = $vips[$user->active_frame_id];

                    $frame = [
                        'id' => $vip->id,
                        'name' => $vip->name,
                        'image' => !empty($vip->image_frame)
                            ? Helper::showImage($vip->image_frame, true)
                            : null,
                        'file' => !empty($vip->image_frame_animation)
                            ? Helper::showImage($vip->image_frame_animation, true)
                            : null,
                    ];
                } elseif ($frameType === 'svip' && isset($svips[$user->active_frame_id])) {

                    $svip = $svips[$user->active_frame_id];

                    $frame = [
                        'id' => $svip->id,
                        'name' => $svip->name,
                        'image' => !empty($svip->headwear)
                            ? Helper::showImage($svip->headwear, true)
                            : null,
                        'file' => !empty($svip->headwear_animation)
                            ? Helper::showImage($svip->headwear_animation, true)
                            : null,
                    ];
                } elseif ($frameType === 'cp' && isset($relationshipItems[$user->active_frame_id])) {

                    $cp = $relationshipItems[$user->active_frame_id];

                    $frame = [
                        'id' => $cp->id,
                        'name' => $cp->name,
                        'image' => !empty($cp->frame)
                            ? Helper::showImage($cp->frame, true)
                            : null,
                        'file' => !empty($cp->frame_animation)
                            ? Helper::showImage($cp->frame_animation, true)
                            : null,
                    ];
                }
            }

            /*
        |--------------------------------------------------------------------------
        | UID
        |--------------------------------------------------------------------------
        */
            if (!empty($user->active_uid_id) && isset($uids[$user->active_uid_id])) {

                $uidData = $uids[$user->active_uid_id];

                $uid = [
                    'id' => $uidData->id,
                    'uid' => $uidData->unique_id ?? null,
                    'image' => !empty($uidData->badge)
                        ? Helper::showImage($uidData->badge, true)
                        : null,
                ];
            }

            /*
        |--------------------------------------------------------------------------
        | CHAT BUBBLE
        |--------------------------------------------------------------------------
        */
            if (!empty($user->active_chat_bubble_id)) {

                $bubbleType = $user->active_chat_bubble_type ?? 'store';

                if (
                    $bubbleType === 'store'
                    && isset($chatBubbles[$user->active_chat_bubble_id])
                ) {

                    $chatBubbleData = $chatBubbles[$user->active_chat_bubble_id];

                    $chatBubble = [
                        'id' => $chatBubbleData->id,
                        'name' => $chatBubbleData->name,
                        'image' => !empty($chatBubbleData->icon)
                            ? Helper::showImage($chatBubbleData->icon, true)
                            : null,
                        'slice_rect' => $chatBubbleData->slice_rect,
                        'padding_rect' => $chatBubbleData->padding_rect,
                    ];
                } elseif (
                    $bubbleType === 'vip'
                    && isset($vips[$user->active_chat_bubble_id])
                ) {

                    $vip = $vips[$user->active_chat_bubble_id];

                    $chatBubble = [
                        'id' => $vip->id,
                        'name' => $vip->name,
                        'image' => !empty($vip->chat_card)
                            ? Helper::showImage($vip->chat_card, true)
                            : null,
                        'slice_rect' => null,
                        'padding_rect' => null,
                    ];
                } elseif ($bubbleType === 'svip' && isset($svips[$user->active_chat_bubble_id])) {

                    $svip = $svips[$user->active_chat_bubble_id];

                    $chatBubble = [
                        'id' => $svip->id,
                        'name' => $svip->name,
                        'image' => !empty($svip->bubble)
                            ? Helper::showImage($svip->bubble, true)
                            : null,
                        'slice_rect' => null,
                        'padding_rect' => null,
                    ];
                }
            }

            /*
        |--------------------------------------------------------------------------
        | VOICE
        |--------------------------------------------------------------------------
        */
            if (!empty($user->active_voice_id)) {

                $voiceType = $user->active_voice_type ?? 'store';

                if (
                    $voiceType === 'store'
                    && isset($voices[$user->active_voice_id])
                ) {

                    $voiceData = $voices[$user->active_voice_id];

                    $voice = [
                        'id' => $voiceData->id,
                        'name' => $voiceData->name,
                        'image' => !empty($voiceData->icon)
                            ? Helper::showImage($voiceData->icon, true)
                            : null,
                        'file' => !empty($voiceData->gif)
                            ? Helper::showImage($voiceData->gif, true)
                            : null,
                    ];
                } elseif (
                    $voiceType === 'vip'
                    && isset($vips[$user->active_voice_id])
                ) {

                    $vip = $vips[$user->active_voice_id];

                    $voice = [
                        'id' => $vip->id,
                        'name' => $vip->name,
                        'image' => !empty($vip->voice_frame)
                            ? Helper::showImage($vip->voice_frame, true)
                            : null,
                        'file' => !empty($vip->voice_animation)
                            ? Helper::showImage($vip->voice_animation, true)
                            : null,
                    ];
                } elseif (
                    $voiceType === 'svip'
                    && isset($svips[$user->active_voice_id])
                ) {

                    $svip = $svips[$user->active_voice_id];

                    $voice = [
                        'id' => $svip->id,
                        'name' => $svip->name,
                        'image' => !empty($svip->voice_image)
                            ? Helper::showImage($svip->voice_image, true)
                            : null,
                        'file' => !empty($svip->voice_animation)
                            ? Helper::showImage($svip->voice_animation, true)
                            : null,
                    ];
                }
            }

            /*
        |--------------------------------------------------------------------------
        | PROFILE CARD
        |--------------------------------------------------------------------------
        */
            if (!empty($user->active_card_id)) {

                $profileType = $user->active_profile_card_type ?? 'store';

                if (
                    $profileType === 'store'
                    && isset($profileCards[$user->active_card_id])
                ) {

                    $profileCardData = $profileCards[$user->active_card_id];

                    $profileCard = [
                        'id' => $profileCardData->id,
                        'name' => $profileCardData->name,
                        'image' => !empty($profileCardData->icon)
                            ? Helper::showImage($profileCardData->icon, true)
                            : null,
                        'file' => !empty($profileCardData->gif)
                            ? Helper::showImage($profileCardData->gif, true)
                            : null,
                    ];
                } elseif (
                    $profileType === 'vip'
                    && isset($vips[$user->active_card_id])
                ) {

                    $vip = $vips[$user->active_card_id];

                    $profileCard = [
                        'id' => $vip->id,
                        'name' => $vip->name,
                        'image' => !empty($vip->profile_frame)
                            ? Helper::showImage($vip->profile_frame, true)
                            : null,
                        'file' => !empty($vip->profile_frame_animation)
                            ? Helper::showImage($vip->profile_frame_animation, true)
                            : null,
                    ];
                } elseif (
                    $profileType === 'svip'
                    && isset($svips[$user->active_card_id])
                ) {

                    $svip = $svips[$user->active_card_id];

                    $profileCard = [
                        'id' => $svip->id,
                        'name' => $svip->name,
                        'image' => !empty($svip->profile_card)
                            ? Helper::showImage($svip->profile_card, true)
                            : null,
                        'file' => !empty($svip->profile_animation)
                            ? Helper::showImage($svip->profile_animation, true)
                            : null,
                    ];
                }
            }

            $mapped[$user->id] = [
                'frame' => $frame,
                'uid' => $uid,
                'chatBubble' => $chatBubble,
                'voice' => $voice,
                'profile' => $profileCard,
            ];
        }

        return $mapped;
    }

    public function userLevel()
    {
        $users = AppUser::with('levelInfo')
            ->select('id', 'name', 'total_value', 'user_level')
            ->get();

        return response()->json($users);
    }


    public function getRoomSeats($roomId, $totalSeats = null)
    {
        if ($totalSeats === null) {
            $room = Room::select('room_seat')->find($roomId);
            $totalSeats = $room ? (int) $room->room_seat : 0;
        }

        $takenSeats = RoomSeat::with('user:id,name,image')
            ->where('room_id', $roomId)
            ->get()
            ->keyBy('seat_no');

        $seats = [];

        for ($i = 1; $i <= $totalSeats; $i++) {
            $seat = $takenSeats->get($i);

            $seats[] = [
                'seat_no' => $i,
                'is_occupied' => (bool) $seat,
                'is_on_mic' => $seat ? (int) $seat->is_on_mic : 0,
                'user' => $seat ? [
                    'id' => $seat->user->id ?? null,
                    'name' => $seat->user->name ?? null,
                    'image' => !empty($seat->user->image)
                        ? Helper::showImage($seat->user->image, true)
                        : null,
                ] : null,

                'left_relation' => null,

                'right_relation' => null,
            ];
        }

        $seatCollection = collect($seats);

        foreach ($seats as $key => $seatUser) {

            if (
                empty($seatUser['user'])
                ||
                empty($seatUser['user']['id'])
            ) {
                continue;
            }

            $currentUserId =
                $seatUser['user']['id'];

            //  LEFT SEAT


            $leftSeat = $seatCollection
                ->firstWhere(
                    'seat_no',
                    $seatUser['seat_no'] - 1
                );

            if (
                $leftSeat
                &&
                !empty($leftSeat['user'])
                &&
                !empty($leftSeat['user']['id'])
            ) {

                $leftRelation =
                    $this->getSeatRelation(
                        $currentUserId,
                        $leftSeat['user']['id']
                    );

                if ($leftRelation) {

                    $seats[$key]['left_relation']
                        = $leftRelation;
                }
            }

            //  RIGHT SEAT


            $rightSeat = $seatCollection
                ->firstWhere(
                    'seat_no',
                    $seatUser['seat_no'] + 1
                );

            if (
                $rightSeat
                &&
                !empty($rightSeat['user'])
                &&
                !empty($rightSeat['user']['id'])
            ) {

                $rightRelation =
                    $this->getSeatRelation(
                        $currentUserId,
                        $rightSeat['user']['id']
                    );

                if ($rightRelation) {

                    $seats[$key]['right_relation']
                        = $rightRelation;
                }
            }
        }

        return $seats;
    }
    public function takeSeat(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'room_id' => 'required|integer',
            'seat_no' => 'required|integer',
        ]);

        if ($validation->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validation->errors(),
            ], 422);
        }

        $user = Auth::user();
        $hasTakeAnySeatPrivilege = Helper::hasVipPrivilege($user->id, 'take_any_seat');
        $roomId = $request->room_id;
        $seatNo = $request->seat_no;

        $isJoined = RoomPresence::where('room_id', $roomId)
            ->where('user_id', $user->id)
            ->exists();

        if (!$isJoined) {
            return response()->json([
                'status' => false,
                'message' => 'Please join room first',
            ], 403);
        }

        DB::beginTransaction();

        try {
            $seatTaken = RoomSeat::where('room_id', $roomId)
                ->where('seat_no', $seatNo)
                ->lockForUpdate()
                ->first();

            if ($seatTaken) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Seat already occupied',
                ], 409);
            }

            $mySeat = RoomSeat::where('room_id', $roomId)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if ($mySeat) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'You already have a seat, use change seat api',
                ], 409);
            }

            $seatSetting = DB::table('room_seat_settings')
                ->where('room_id', $roomId)
                ->where('seat_no', $seatNo)
                ->first();

            if (!$hasTakeAnySeatPrivilege && $seatSetting && $seatSetting->is_locked) {
                DB::rollBack();

                return response()->json([
                    'status' => false,
                    'message' => 'This seat is locked.',
                ], 403);
            }

            RoomSeat::create([
                'room_id' => $roomId,
                'user_id' => $user->id,
                'seat_no' => $seatNo,
                'is_on_mic' => 1
            ]);

            DB::commit();

            $seats = $this->getRoomSeats($roomId);

            broadcast(new RoomSeatUpdated(
                $roomId,
                'take',
                $seats,
                $seatNo,
                [
                    'id' => $user->id,
                    'name' => $user->name,
                    'image' => !empty($user->image) ? \App\Helper\Helper::showImage($user->image, true) : null,
                ]
            ))->toOthers();

            return response()->json([
                'status' => true,
                'message' => 'Seat taken successfully',
                'seats' => $seats,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function changeSeat(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'room_id' => 'required|integer',
            'seat_no' => 'required|integer',
        ]);

        if ($validation->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validation->errors(),
            ], 422);
        }

        $user = Auth::user();
        $roomId = $request->room_id;
        $newSeatNo = $request->seat_no;
        $hasTakeAnySeatPrivilege = Helper::hasVipPrivilege($user->id, 'take_any_seat');

        $isJoined = RoomPresence::where('room_id', $roomId)
            ->where('user_id', $user->id)
            ->exists();

        if (!$isJoined) {
            return response()->json([
                'status' => false,
                'message' => 'Please join room first',
            ], 403);
        }

        DB::beginTransaction();

        try {
            $mySeat = RoomSeat::where('room_id', $roomId)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if (!$mySeat) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'is_seated' => false,
                    'message' => 'You have not taken any seat yet',
                ], 404);
            }

            if ((int) $mySeat->seat_no === (int) $newSeatNo) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'You are already on this seat',
                ], 409);
            }

            $newSeatTaken = RoomSeat::where('room_id', $roomId)
                ->where('seat_no', $newSeatNo)
                ->lockForUpdate()
                ->first();

            if ($newSeatTaken) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Selected seat is already occupied',
                ], 409);
            }

            $seatSetting = DB::table('room_seat_settings')
                ->where('room_id', $roomId)
                ->where('seat_no', $newSeatNo)
                ->first();

            if (!$hasTakeAnySeatPrivilege && $seatSetting && $seatSetting->is_locked) {
                DB::rollBack();

                return response()->json([
                    'status' => false,
                    'message' => 'This seat is locked.',
                ], 403);
            }

            $mySeat->update([
                'seat_no' => $newSeatNo
            ]);

            DB::commit();

            $seats = $this->getRoomSeats($roomId);

            broadcast(new RoomSeatUpdated(
                $roomId,
                'change',
                $seats,
                $newSeatNo,
                [
                    'id' => $user->id,
                    'name' => $user->name,
                    'image' => !empty($user->image) ? Helper::showImage($user->image, true) : null,
                ]
            ))->toOthers();

            return response()->json([
                'status' => true,
                'message' => 'Seat changed successfully',
                'is_seated' => true,
                'seats' => $seats,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function takeOrChangeSeat(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'room_id' => 'required|integer',
            'seat_no' => 'required|integer',
        ]);

        if ($validation->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validation->errors(),
            ], 422);
        }

        $user = Auth::user();
        $roomId = (int) $request->room_id;
        $seatNo = (int) $request->seat_no;

        $isJoined = RoomPresence::where('room_id', $roomId)
            ->where('user_id', $user->id)
            ->exists();

        if (!$isJoined) {
            return response()->json([
                'status' => false,
                'message' => 'Please join room first',
            ], 403);
        }

        if (!$this->canUseMic($roomId, $user->id)) {
            return response()->json([
                'status' => false,
                'message' => 'You are not allowed to use mic in this room',
            ], 403);
        }

        DB::beginTransaction();

        try {
            // Check user's current seat
            $mySeat = RoomSeat::where('room_id', $roomId)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            // Check requested seat
            $requestedSeat = RoomSeat::where('room_id', $roomId)
                ->where('seat_no', $seatNo)
                ->lockForUpdate()
                ->first();

            // If requested seat is occupied by another user
            if ($requestedSeat && (int) $requestedSeat->user_id !== (int) $user->id) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Selected seat is already occupied',
                ], 409);
            }

            // If user already on same seat
            if ($mySeat && (int) $mySeat->seat_no === (int) $seatNo) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'You are already on this seat',
                    'is_seated' => true,
                ], 409);
            }

            $action = 'take';

            if ($mySeat) {
                // Change seat
                $mySeat->update([
                    'seat_no' => $seatNo,
                ]);
                $action = 'change';
                $message = 'Seat changed successfully';
            } else {
                // Take seat
                RoomSeat::create([
                    'room_id' => $roomId,
                    'user_id' => $user->id,
                    'seat_no' => $seatNo,
                    'is_on_mic' => 1,
                ]);
                $action = 'take';
                $message = 'Seat taken successfully';
            }

            DB::commit();

            $seats = $this->getRoomSeats($roomId);

            broadcast(new RoomSeatUpdated(
                $roomId,
                $action,
                $seats,
                $seatNo,
                [
                    'id' => $user->id,
                    'name' => $user->name,
                    'image' => !empty($user->image)
                        ? \App\Helper\Helper::showImage($user->image, true)
                        : null,
                ]
            ))->toOthers();

            return response()->json([
                'status' => true,
                'message' => $message,
                'is_seated' => true,
                'action' => $action,
                'seats' => $seats,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateSeatMicStatus(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'room_id' => 'required|integer',
            'seat_no' => 'required|integer',
            'is_on_mic' => 'required|in:0,1',
        ]);

        if ($validation->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validation->errors(),
            ], 422);
        }

        $user = Auth::user();
        $roomId = (int) $request->room_id;
        $seatNo = (int) $request->seat_no;
        $isOnMic = (int) $request->is_on_mic;

        $seat = RoomSeat::where('room_id', $roomId)
            ->where('seat_no', $seatNo)
            ->first();

        if (!$seat) {
            return response()->json([
                'status' => false,
                'message' => 'Seat not found',
            ], 404);
        }

        // only seat user can mute/unmute self
        if ((int) $seat->user_id !== (int) $user->id) {
            return response()->json([
                'status' => false,
                'message' => 'You are not allowed to update this seat mic',
            ], 403);
        }

        $seat->update([
            'is_on_mic' => $isOnMic,
        ]);

        $seats = $this->getRoomSeats($roomId);

        broadcast(new RoomSeatMicUpdated(
            $roomId,
            $seatNo,
            $isOnMic,
            $seats,
            [
                'id' => $user->id,
                'name' => $user->name,
                'image' => !empty($user->image)
                    ? \App\Helper\Helper::showImage($user->image, true)
                    : null,
            ]
        ))->toOthers();

        return response()->json([
            'status' => true,
            'message' => $isOnMic ? 'Mic unmuted successfully' : 'Mic muted successfully',
            'seats' => $seats,
        ]);
    }

    public function leaveSeat(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'room_id' => 'required|integer',
        ]);

        if ($validation->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validation->errors(),
            ], 422);
        }

        $user = Auth::user();
        $roomId = $request->room_id;

        $seat = RoomSeat::where('room_id', $roomId)
            ->where('user_id', $user->id)
            ->first();

        if (!$seat) {
            return response()->json([
                'status' => false,
                'message' => 'No seat assigned',
            ], 404);
        }

        $seatNo = $seat->seat_no;
        $seat->delete();

        $seats = $this->getRoomSeats($roomId);

        broadcast(new RoomSeatUpdated(
            $roomId,
            'leave',
            $seats,
            $seatNo,
            [
                'id' => $user->id,
                'name' => $user->name,
                'image' => !empty($user->image) ? Helper::showImage($user->image, true) : null,
            ]
        ))->toOthers();

        return response()->json([
            'status' => true,
            'message' => 'Seat released successfully',
            'seats' => $seats,
        ]);
    }

    public function agoraToken(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'room_id' => 'required|integer|exists:rooms,id',
        ]);

        if ($validation->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validation->errors(),
            ], 422);
        }

        $user = Auth::user();
        $roomId = (int) $request->room_id;

        $room = Room::where('id', $roomId)
            ->where('status', 1)
            ->first();

        if (!$room) {
            return response()->json([
                'status' => false,
                'message' => 'Room not found',
            ], 404);
        }

        $isJoined = RoomPresence::where('room_id', $roomId)
            ->where('user_id', $user->id)
            ->exists();

        if (!$isJoined) {
            return response()->json([
                'status' => false,
                'message' => 'Please join room first',
            ], 403);
        }

        $hasSeat = RoomSeat::where('room_id', $roomId)
            ->where('user_id', $user->id)
            ->exists();

        $appId = env('AGORA_APP_ID');
        $appCertificate = env('AGORA_APP_CERTIFICATE');
        $expireSeconds = (int) env('AGORA_TOKEN_EXPIRE', 3600);

        $channelName = 'room_' . $roomId;
        $uid = (int) $user->id;

        $role = $hasSeat
            ? RtcTokenBuilder2::ROLE_PUBLISHER
            : RtcTokenBuilder2::ROLE_SUBSCRIBER;

        $token = RtcTokenBuilder2::buildTokenWithUid(
            $appId,
            $appCertificate,
            $channelName,
            $uid,
            $role,
            $expireSeconds,
            $expireSeconds
        );

        return response()->json([
            'status' => true,
            'message' => 'Agora token generated successfully',
            'data' => [
                'app_id' => $appId,
                'channel_name' => $channelName,
                'uid' => $uid,
                'token' => $token,
                'role' => $hasSeat ? 'broadcaster' : 'audience',
                'expires_in' => $expireSeconds,
            ]
        ]);
    }

    public function sendMessage(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'room_id' => 'required|integer|exists:rooms,id',
            'message' => 'required|string|max:2000',
        ]);

        $room = Room::where('id', $validated['room_id'])
            ->where('status', 1)
            ->first();

        if (! $room) {
            return response()->json([
                'status' => false,
                'message' => 'Room not found',
                'data' => [],
            ], 404);
        }

        $isMember = RoomPresence::where('room_id', $room->id)
            ->where('user_id', $user->id)
            ->exists();

        if (! $isMember) {
            return response()->json([
                'status' => false,
                'message' => 'You must join the room first',
                'data' => [],
            ], 403);
        }

        if (! $this->canSendMessage($room->id, $user->id)) {
            return response()->json([
                'status' => false,
                'message' => 'You are not allowed to send messages in this room',
                'data' => [],
            ], 403);
        }

        $messageText = trim($validated['message']);

        if ($messageText === '') {
            return response()->json([
                'status' => false,
                'message' => 'Message cannot be empty',
                'data' => [],
            ], 422);
        }

        // $chatBubble = null;

        // if (!empty($user->active_chat_bubble_id)) {
        //     $bubble = ChatBubble::where('id', $user->active_chat_bubble_id)
        //         ->where('status', 1)
        //         ->first();

        //     if ($bubble) {
        //         $chatBubble = [
        //             'id' => $bubble->id,
        //             'name' => $bubble->name,
        //             'image' => Helper::showImage($bubble->icon, true),
        //             'slice_rect' => $bubble->slice_rect,
        //             'padding_rect' => $bubble->padding_rect,

        //         ];
        //     }
        // }

        $chatBubble = null;

        if (!empty($user->active_chat_bubble_id)) {

            /*
    |--------------------------------------------------------------------------
    | STORE CHAT BUBBLE
    |--------------------------------------------------------------------------
    */
            if (
                empty($user->active_chat_bubble_type)
                || $user->active_chat_bubble_type === 'store'
            ) {

                $bubble = ChatBubble::where('id', $user->active_chat_bubble_id)
                    ->where('status', 1)
                    ->first();

                if ($bubble) {

                    $chatBubble = [
                        'id' => $bubble->id,
                        'name' => $bubble->name,
                        'image' => Helper::showImage($bubble->icon, true),
                        'slice_rect' => $bubble->slice_rect,
                        'padding_rect' => $bubble->padding_rect,
                    ];
                }
            }

            /*
    |--------------------------------------------------------------------------
    | VIP CHAT BUBBLE
    |--------------------------------------------------------------------------
    */ elseif ($user->active_chat_bubble_type === 'vip') {

                $vip = Vip::find($user->active_chat_bubble_id);

                if ($vip) {

                    $chatBubble = [
                        'id' => $vip->id,
                        'name' => $vip->name,
                        'image' => asset('storage/' . $vip->chat_card),

                        // response structure same rakhne ke liye
                        'slice_rect' => null,
                        'padding_rect' => null,
                    ];
                }
            }

            /*
    |--------------------------------------------------------------------------
    | SVIP CHAT BUBBLE
    |--------------------------------------------------------------------------
    */ elseif ($user->active_chat_bubble_type === 'svip') {

                $svip = Svip::find($user->active_chat_bubble_id);

                if ($svip) {

                    $chatBubble = [
                        'id' => $svip->id,
                        'name' => $svip->name,
                        'image' => !empty($svip->bubble)
                            ? asset('storage/' . $svip->bubble)
                            : null,

                        'slice_rect' => null,
                        'padding_rect' => null,
                    ];
                }
            }
        }

        $wealthLevel = WCLevel::with([
            'levelData' => function ($q) {
                $q->where('type', 'wealth');
            }
        ])
            ->where('user_id', $user->id)
            ->where('type', 'wealth')
            ->first();

        // dd($wealthLevel->levelData);

        $charmLevel = WCLevel::with([
            'levelData' => function ($q) {
                $q->where('type', 'charm');
            }
        ])
            ->where('user_id', $user->id)
            ->where('type', 'charm')
            ->first();



        $equippedMedals = UserMedal::with('medal')
            ->where('user_id', $user->id)
            ->where('is_equipped', 1)
            ->orderBy('slot_no')
            ->get()
            ->map(function ($item) {

                return [

                    'id' => $item->medal->id,
                    'name' => $item->medal->title,
                    'icon' => Helper::showImage($item->medal->icon, true)
                ];
            })
            ->values();

        $roomMessage = RoomMessage::create([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'message' => $messageText,
            'message_type' => 'text',
        ]);

        $roomMessage->load('user:id,name,uid,image,gender');

        $nicknameMeta = Helper::getNicknameMeta($user->id);
        $roomMessage->user->nickname_meta =
            [
                'animated' => $nicknameMeta['animated'] ?? false,
                'color' => $nicknameMeta['color'] ?? null,
                'effect' => $nicknameMeta['effect'] ?? null,
            ];

        $roomMessage->user->wealth_icon =
            $wealthLevel?->levelData?->icon
            ? Helper::showImage(
                $wealthLevel->levelData->icon,
                true
            )
            : null;


        $roomMessage->user->charm_icon =
            $charmLevel?->levelData?->icon
            ? Helper::showImage(
                $charmLevel->levelData->icon,
                true
            )
            : null;


        $roomMessage->user->medals =
            $equippedMedals;


        $roomMessage->chat_bubble = $chatBubble;

        // $roomMessage->user->role_badge = $user ? Helper::getUserRoleBadges($user->id) : [];

        broadcast(new RoomMessageSent($roomMessage))->toOthers();

        return response()->json([
            'status' => true,
            'message' => 'Message sent successfully',
            'data' => [
                'id' => $roomMessage->id,
                'room_id' => $roomMessage->room_id,
                'user_id' => $roomMessage->user_id,
                'message' => $roomMessage->message,
                'message_type' => $roomMessage->message_type,
                'chat_bubble' => $roomMessage->chat_bubble,
                'created_at' => $roomMessage->created_at->toDateTimeString(),
                'user' => [
                    'id' => $roomMessage->user?->id,
                    'name' => $roomMessage->user?->name,
                    'nickname_meta' => $nicknameMeta,
                    'uid' => $roomMessage->user?->uid,
                    'image' => Helper::showImage($roomMessage->user?->image, true),
                ],
            ],
        ]);
    }

    public function updateRoomBasicInfo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'room_id'    => 'required|exists:rooms,id',
            'room_name'  => 'nullable|string|max:255',
            'room_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'notice'     => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()], 422);
        }

        $room = Room::where('id', $request->room_id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$room) {
            return response()->json(['status' => false, 'message' => 'Room not found or unauthorized'], 403);
        }

        if ($request->filled('room_name')) {
            $room->room_name = $request->room_name;
        }

        if ($request->has('notice')) {
            $room->bio = $request->notice;
        }

        if ($request->hasFile('room_image')) {
            $room->room_image = Helper::saveFile($request->file('room_image'), 'room_image');
        }

        $room->save();

        return response()->json([
            'status' => true,
            'message' => 'Room settings updated successfully',
            'data' => [
                'room_name'  => $room->room_name,
                'notice'     => $room->bio,
                'room_image' => Helper::showImage($room->room_image, true)
            ]
        ]);
    }

    private function isHost($roomId)
    {
        $user = Auth::user();

        if (!$user) return false;

        return DB::table('rooms')
            ->where('id', $roomId)
            ->where('user_id', $user->id)
            ->exists();
    }
    public function toggleLockSeat(Request $request)
    {
        try {
            $request->validate([
                'room_id' => 'required|integer|exists:rooms,id',
                'seat_no' => 'required|integer',
            ]);

            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $role = $this->getRoomUserRole($request->room_id, $user->id);

            if (!in_array($role, ['owner', 'admin'])) {
                return response()->json([
                    'status' => false,
                    'message' => 'Only owner or admin can lock/unlock mic'
                ], 403);
            }

            // TARGET USER CHECK (IMPORTANT)
            $seatUser = RoomSeat::where('room_id', $request->room_id)
                ->where('seat_no', $request->seat_no)
                ->first();

            if ($seatUser) {

                $targetUserId = $seatUser->user_id;

                $targetRole = $this->getRoomUserRole($request->room_id, $targetUserId);

                if ($role === 'admin' && $targetRole === 'owner') {
                    return response()->json([
                        'status' => false,
                        'message' => 'Admin cannot lock owner seat'
                    ], 403);
                }

                if ($role === 'admin' && $targetRole === 'admin') {
                    return response()->json([
                        'status' => false,
                        'message' => 'Admin cannot lock another admin seat'
                    ], 403);
                }
            }

            // seat find or create
            $seatSetting = DB::table('room_seat_settings')
                ->where('room_id', $request->room_id)
                ->where('seat_no', $request->seat_no)
                ->first();

            if (!$seatSetting) {
                DB::table('room_seat_settings')->insert([
                    'room_id' => $request->room_id,
                    'seat_no' => $request->seat_no,
                    'is_locked' => 0,
                    'is_muted_by_host' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $seatSetting = DB::table('room_seat_settings')
                    ->where('room_id', $request->room_id)
                    ->where('seat_no', $request->seat_no)
                    ->first();
            }

            $newLockStatus = !((bool) $seatSetting->is_locked);

            DB::table('room_seat_settings')
                ->where('room_id', $request->room_id)
                ->where('seat_no', $request->seat_no)
                ->update([
                    'is_locked' => $newLockStatus,
                    'updated_at' => now()
                ]);

            broadcast(new RoomSeatSettingUpdated(
                $request->room_id,
                [
                    'type' => 'lock',
                    'seat_no' => $request->seat_no,
                    'is_locked' => $newLockStatus
                ]
            ))->toOthers();

            if ($newLockStatus) {
                $messageText = $user->name . ' has locked Microphone No. ' . $request->seat_no;

                $messageType = 'seat_locked';
                $successMessage = 'Mic locked';
            } else {
                $messageText = $user->name . ' has unlocked Microphone No. ' . $request->seat_no;

                $messageType = 'seat_unlocked';
                $successMessage = 'Mic unlocked';
            }

            $roomMessage = RoomMessage::create([
                'room_id' => $request->room_id,
                'user_id' => $user->id,
                'message' => $messageText,
                'message_type' => $messageType,
            ]);

            broadcast(new RoomSeatMessageSent(
                $request->room_id,
                [
                    'id' => $roomMessage->id,
                    'room_id' => $roomMessage->room_id,
                    'user_id' => $roomMessage->user_id,
                    'message' => $roomMessage->message,
                    'message_type' => $roomMessage->message_type,
                    'created_at' => $roomMessage->created_at ? $roomMessage->created_at->toDateTimeString() : null,
                    'sender' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'uid' => $user->uid ?? null,
                        'image' => !empty($user->image) ? \Helper::showImage($user->image, true) : null,
                    ]
                ]
            ))->toOthers();

            return response()->json([
                'status' => true,
                'message' => $newLockStatus ? 'Mic locked' : 'Mic unlocked',
                'is_locked' => $newLockStatus
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function toggleMuteSeat(Request $request)
    {
        try {
            $request->validate([
                'room_id' => 'required|integer|exists:rooms,id',
                'seat_no' => 'required|integer',
            ]);

            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $role = $this->getRoomUserRole($request->room_id, $user->id);

            if (!in_array($role, ['owner', 'admin'])) {
                return response()->json([
                    'status' => false,
                    'message' => 'Only owner or admin can mute/unmute'
                ], 403);
            }

            // CHECK SEAT USER
            $seatUser = RoomSeat::where('room_id', $request->room_id)
                ->where('seat_no', $request->seat_no)
                ->first();

            if ($seatUser) {

                $targetUserId = $seatUser->user_id;

                $targetRole = $this->getRoomUserRole($request->room_id, $targetUserId);

                // admin cannot mute owner
                if ($role === 'admin' && $targetRole === 'owner') {
                    return response()->json([
                        'status' => false,
                        'message' => 'Admin cannot mute owner'
                    ], 403);
                }

                // admin cannot mute another admin
                if ($role === 'admin' && $targetRole === 'admin') {
                    return response()->json([
                        'status' => false,
                        'message' => 'Admin cannot mute another admin'
                    ], 403);
                }
            }

            $seatSetting = DB::table('room_seat_settings')
                ->where('room_id', $request->room_id)
                ->where('seat_no', $request->seat_no)
                ->first();

            if (!$seatSetting) {
                DB::table('room_seat_settings')->insert([
                    'room_id' => $request->room_id,
                    'seat_no' => $request->seat_no,
                    'is_locked' => 0,
                    'is_muted_by_host' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $seatSetting = DB::table('room_seat_settings')
                    ->where('room_id', $request->room_id)
                    ->where('seat_no', $request->seat_no)
                    ->first();
            }

            $newMuteStatus = !((bool) $seatSetting->is_muted_by_host);

            DB::table('room_seat_settings')
                ->where('room_id', $request->room_id)
                ->where('seat_no', $request->seat_no)
                ->update([
                    'is_muted_by_host' => $newMuteStatus,
                    'updated_at' => now()
                ]);


            broadcast(new RoomSeatSettingUpdated(
                $request->room_id,
                [
                    'type' => 'mute',
                    'seat_no' => $request->seat_no,
                    'is_muted_by_host' => $newMuteStatus
                ]
            ))->toOthers();

            if ($newMuteStatus) {
                $messageText = $user->name . ' has muted Microphone No. ' . $request->seat_no;

                $messageType = 'seat_muted';
                $successMessage = 'Mic muted';
            } else {
                $messageText = $user->name . ' has unmuted Microphone No. ' . $request->seat_no;

                $messageType = 'seat_unmuted';
                $successMessage = 'Mic unmuted';
            }

            $roomMessage = RoomMessage::create([
                'room_id' => $request->room_id,
                'user_id' => $user->id,
                'message' => $messageText,
                'message_type' => $messageType,
            ]);

            broadcast(new RoomSeatSettingUpdated(
                $request->room_id,
                [
                    'type' => 'mute',
                    'seat_no' => $request->seat_no,
                    'is_muted_by_host' => $newMuteStatus,
                    'room_message' => [
                        'id' => $roomMessage->id,
                        'room_id' => $roomMessage->room_id,
                        'user_id' => $roomMessage->user_id,
                        'message' => $roomMessage->message,
                        'message_type' => $roomMessage->message_type,
                        'created_at' => $roomMessage->created_at?->toDateTimeString(),
                        'sender' => [
                            'id' => $user->id,
                            'name' => $user->name,
                            'uid' => $user->uid ?? null,
                            'image' => !empty($user->image) ? \Helper::showImage($user->image, true) : null,
                        ]
                    ]
                ]
            ))->toOthers();

            return response()->json([
                'status' => true,
                'message' => $newMuteStatus ? 'Mic muted' : 'Mic unmuted',
                'is_muted' => $newMuteStatus
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getRoomUsersList($roomId)
    {
        $authUserId = Auth::id();
        $room = Room::select('id', 'user_id')->find($roomId);

        if (!$room) {
            return response()->json([
                'status' => false,
                'message' => 'Room not found'
            ], 404);
        }

        $friendIds = Friendship::where('status', 'accepted')
            ->where(function ($q) use ($authUserId) {
                $q->where('user_one', $authUserId)
                    ->orWhere('user_two', $authUserId);
            })
            ->get()
            ->map(function ($friend) use ($authUserId) {

                return (int) $friend->user_one === (int) $authUserId
                    ? $friend->user_two
                    : $friend->user_one;
            })
            ->toArray();

        // roles ek baar load karo
        $roles = RoomUserRole::where('room_id', $roomId)
            ->pluck('role', 'user_id');

        $presenceData = RoomPresence::with([
            'user:id,name,uid,image,gender,country,active_frame_id,active_uid_id,active_voice_id,active_card_id,active_frame_type,active_voice_type,active_chat_bubble_type,active_profile_card_type',
            'user.countryData:id,name,iso',
            'user.wcLevels.levelData',
            'user.userMedals.medal'
        ])
            ->where('room_id', $roomId)
            ->orderBy('joined_at', 'desc')
            ->get();



        $presenceUsers = $presenceData->pluck('user')->filter()->values();

        $onlineUserIds = $presenceUsers->pluck('id')->toArray();

        $cpRelations = RelationshipInvitation::with([
            'sender:id,uid,name,image,active_frame_id,active_frame_type',
            'sender.activeFrame:id,name,icon,gif',
            'receiver:id,uid,name,image,active_frame_id,active_frame_type',
            'receiver.activeFrame:id,name,icon,gif',
            'relationshipItem:id,name,type,icon,gif,ring,avatar,frame,badge',
        ])
            ->where('status', 'accept')
            ->whereRaw('LOWER(type)=?', ['cp'])
            ->where(function ($q) use ($onlineUserIds) {
                $q->whereIn('sender_id', $onlineUserIds)
                    ->orWhereIn('receiver_id', $onlineUserIds);
            })->get();

        $itemsByUserId = $this->getFrameAndUidForUsers($presenceUsers);

        $usersList = $presenceData->map(function ($presence) use ($room, $roles, $itemsByUserId, $cpRelations, $friendIds, $authUserId) {

            $userId = $presence->user->id ?? null;

            // default role
            $role = 'guest';

            // host check
            if ((int) $room->user_id === (int) $userId) {
                $role = 'owner';
            } else {
                $role = $roles[$userId] ?? 'guest';
            }

            $itemData = $userId
                ? ($itemsByUserId[$userId] ?? [
                    'frame' => null,
                    'uid' => null,
                    'voice' => null,
                    'profile' => null,

                ])
                : [
                    'frame' => null,
                    'uid' => null,
                    'voice' => null,
                    'profile' => null,

                ];

            $user = $presence->user;

            $displayUid = $user?->uid;
            $uidBadge = null;
            $uidBadgeColor = null;

            $nicknameMeta = $user
                ? Helper::getNicknameMeta($user->id)
                : [
                    'animated' => false,
                    'color' => null,
                    'effect' => null,
                ];

            if ($user) {

                $premiumUid = PremiumNumber::where('user_id', $user->id)
                    ->where('end_at', '>', now())
                    ->latest()
                    ->first();

                if ($premiumUid) {

                    $displayUid = $premiumUid->premium_number;

                    $uidBadge = asset('storage/1000175794.png');
                    $uidBadgeColor = '#fcd01c';
                } elseif ($user->active_uid_id) {

                    $storeUid = StoreUids::find($user->active_uid_id);

                    if ($storeUid) {

                        $hasValidPurchase = DB::table('item_deliveries')
                            ->where('recipient', $user->id)
                            ->where('type', 'id')
                            ->where('item_id', $storeUid->id)
                            ->where('end_at', '>', now())
                            ->exists();

                        $hasValidGift = DB::table('item_gift_transactions')
                            ->where('receiver_id', $user->id)
                            ->where('type', 'id')
                            ->where('item_id', $storeUid->id)
                            ->where('end_at', '>', now())
                            ->exists();

                        if ($hasValidPurchase || $hasValidGift) {

                            $displayUid = $storeUid->unique_id;

                            $uidBadge = !empty($storeUid->rank_badge)
                                ? Helper::showImage($storeUid->rank_badge, true)
                                : null;
                            $uidBadgeColor = $storeUid->badge_color ?? null;
                        }
                    }
                }
            }

            $wealthLevel = $user?->wcLevels?->where('type', 'wealth')->first();

            $charmLevel = $user?->wcLevels?->where('type', 'charm')->first();


            $medals = $user?->userMedals
                ?->where('is_equipped', 1)
                ->sortBy('slot_no')
                ->take(3)
                ->map(function ($item) {

                    return [
                        'id' => $item->medal->id,
                        'name' => $item->medal->title,
                        'icon' => Helper::showImage($item->medal->icon, true)
                    ];
                })
                ->values();

            $flag = null;

            if ($user?->countryData?->iso) {
                $flag = 'https://flagcdn.com/w40/' . strtolower($user->countryData->iso) . '.png';
            }

            $cpRelation = $cpRelations->filter(function ($relation)
            use ($userId) {
                return
                    (int) $relation->sender_id === (int) $userId
                    ||
                    (int) $relation->receiver_id === (int) $userId;
            })
                ->map(function ($relation)
                use ($userId) {
                    $cpUser =
                        (int) $relation->sender_id === (int) $userId
                        ? $relation->receiver
                        : $relation->sender;

                    return [

                        'id' => $cpUser->id,
                        'uid' => $cpUser->uid,
                        'name' => $cpUser->name,
                        'image' => !empty($cpUser->image) ? Helper::showImage($cpUser->image,  true) : null,
                        'frame' => [
                            'id' => $cpUser?->activeFrame?->id,
                            'name' => $cpUser?->activeFrame?->name,
                            'icon' => $cpUser?->activeFrame?->icon
                                ? Helper::showImage($cpUser->activeFrame->icon, true) : null,
                            'svga' => $cpUser?->activeFrame?->gif
                                ? Helper::showImage($cpUser->activeFrame->gif, true) : null,
                        ],
                        'cp_data' => [
                            'id' => $relation?->relationshipItem?->id,
                            'name' => $relation?->relationshipItem?->name,
                            'icon' => $relation?->relationshipItem?->icon ? Helper::showImage($relation->relationshipItem->icon, true) : null,
                        ],
                    ];
                })
                ->values();

            return [
                'id'    => $presence->user->id ?? null,
                'is_friend' => $userId ? in_array($userId, $friendIds) : false,
                'auth_has_any_private_message' => Helper::hasVipPrivilege($authUserId, 'any_private_message'),
                'name'  => $presence->user->name ?? 'Unknown',
                'nickname_meta' => $nicknameMeta,
                'is_rank_top' => $userId ? Helper::hasVipPrivilege($userId, 'rank_the_top') : false,
                'gender'  => $presence?->user?->gender,
                // 'uid'   => $presence->user->uid ?? '',
                'uid' => $displayUid,
                'uid_badge' => $uidBadge,
                'uid_badge_color' => $uidBadgeColor,
                'image' => ($presence->user && !empty($presence->user->image))
                    ? Helper::showImage($presence->user->image, true)
                    : null,
                'joined_at' => $presence->joined_at,
                'role' => $role, // ONLY ADD THIS
                'frame' => $itemData['frame'],
                'uid_data' => $itemData['uid'],
                'voice' => $itemData['voice'] ?? null,
                'profile' => $itemData['profile'] ?? null,

                'flag' => $flag,


                'wealth_level' => [
                    'level' => $wealthLevel?->level ?? 1,
                    'icon' => $wealthLevel?->levelData?->icon
                        ? Helper::showImage($wealthLevel->levelData->icon, true) : null
                ],


                'charm_level' => [
                    'level' => $charmLevel?->level ?? 1,
                    'icon' =>  $charmLevel?->levelData?->icon
                        ? Helper::showImage($charmLevel->levelData->icon, true) : null
                ],

                'medals' => $medals,
                'role_badges' => $userId  ? Helper::getUserRoleBadges($userId) : [],
                'cp_relation' => $cpRelation,
            ];
        })->values();

        $usersList = $usersList->sortByDesc(function ($user) {
            return $user['is_rank_top'] ? 1 : 0;
        })->values();

        $onlineCount = $usersList->count();

        broadcast(new RoomPresenceUpdated(
            (int)$roomId,
            $onlineCount,
            $usersList,
            'list_update',
            null
        ))->toOthers();

        return response()->json([
            'status'  => true,
            'message' => 'Active users list fetched successfully',
            'online_count' => $onlineCount,
            'users'   => $usersList
        ], 200);
    }

    public function bannedUserFromRoom(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'room_id'  => 'required|exists:rooms,id',
            'user_id'  => 'required|exists:app_users,id',
            'action'   => 'required|in:ban,unban',
            'ban_time' => 'required_if:action,ban',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        $admin = Auth::user();
        $targetId = $request->user_id;
        $roomId = $request->room_id;

        if (!$this->isHost($roomId)) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        if ($request->action === 'unban') {
            DB::table('user_blocks')
                ->where('blocker_id', $admin->id)
                ->where('blocked_user_id', $targetId)
                ->delete();

            broadcast(new RoomUserUnbanned(
                $roomId,
                $targetId,
                [
                    'id' => $admin->id,
                    'name' => $admin->name,
                    'image' => !empty($admin->image) ? Helper::showImage($admin->image, true) : null,
                ]
            ))->toOthers();

            return response()->json([
                'status' => true,
                'message' => 'User has been unbanned'
            ]);
        }

        $expiresAt = null;
        $banTime = strtolower(trim($request->ban_time));

        if ($banTime === 'forever') {
            $expiresAt = null;
        } else {

            if (preg_match('/(\d+)\s*(min|mins|minute|minutes|hour|hours|day|days|month|months)/', $banTime, $matches)) {

                $value = (int) $matches[1];
                $unit  = $matches[2];

                switch ($unit) {
                    case 'min':
                    case 'mins':
                    case 'minute':
                    case 'minutes':
                        $expiresAt = now()->addMinutes($value);
                        break;

                    case 'hour':
                    case 'hours':
                        $expiresAt = now()->addHours($value);
                        break;

                    case 'day':
                    case 'days':
                        $expiresAt = now()->addDays($value);
                        break;

                    case 'month':
                    case 'months':
                        $expiresAt = now()->addMonths($value);
                        break;
                }
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid ban duration'
                ], 422);
            }
        }

        DB::beginTransaction();
        try {

            RoomPresence::where('room_id', $roomId)
                ->where('user_id', $targetId)
                ->delete();

            RoomSeat::where('room_id', $roomId)
                ->where('user_id', $targetId)
                ->delete();

            DB::table('user_blocks')->updateOrInsert(
                [
                    'blocker_id' => $admin->id,
                    'blocked_user_id' => $targetId,
                    'room_id'  =>  $roomId
                ],
                [
                    'expires_at' => $expiresAt,
                    'updated_at' => now(),
                    'created_at' => now()
                ]
            );

            DB::commit();

            $targetUser = AppUser::find($targetId);

            $seats = $this->getRoomSeats($roomId);

            $onlineUsers = RoomPresence::with('user:id,name,image')
                ->where('room_id', $roomId)
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->user->id ?? null,
                        'name' => $item->user->name ?? null,
                        'image' => !empty($item->user->image)
                            ? Helper::showImage($item->user->image, true)
                            : null,
                    ];
                })
                ->values()
                ->toArray();

            $onlineCount = count($onlineUsers);

            broadcast(new RoomUserBanned(
                $roomId,
                $targetId,
                [
                    'id' => $admin->id,
                    'name' => $admin->name,
                    'image' => !empty($admin->image) ? Helper::showImage($admin->image, true) : null,
                ],
                $seats,
                $onlineCount,
                $onlineUsers,
                $banTime,
                $expiresAt
            ));

            return response()->json([
                'status' => true,
                'message' => 'User banned successfully',
                'data' => [
                    'banned_user' => [
                        'id'    => $targetUser->id,
                        'name'  => $targetUser->name,
                        'image' => !empty($targetUser->image) ? Helper::showImage($targetUser->image, true) : null,
                    ],
                    'ban_duration' => $banTime,
                    'expires_at'   => $expiresAt ? $expiresAt->toDateTimeString() : 'forever',
                    'online_count' => $onlineCount,
                    'online_users' => $onlineUsers,
                    'seats' => $seats,
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

    public function getBannedUsersList($roomId)
    {
        $room = Room::find($roomId);
        if (!$room) return response()->json(['status' => false, 'message' => 'Room not found'], 404);

        $hostId = $room->user_id;

        // Auto-remove expired bans
        DB::table('user_blocks')
            ->where('blocker_id', $hostId)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->delete();

        // Fetch list with Joins for both Banned User and Operator
        $bannedUsers = DB::table('user_blocks')
            ->join('app_users as banned', 'user_blocks.blocked_user_id', '=', 'banned.id')
            ->join('app_users as operator', 'user_blocks.blocker_id', '=', 'operator.id')
            ->where('user_blocks.blocker_id', $hostId)
            ->select(
                'banned.id as banned_id',
                'banned.name as banned_name',
                'banned.uid as banned_uid',
                'banned.image as banned_image',
                'operator.id as operator_id',
                'operator.name as operator_name',
                'operator.image as operator_image',
                'user_blocks.expires_at',
                'user_blocks.created_at as banned_at'
            )
            ->get();

        $data = $bannedUsers->map(function ($user) {
            return [
                'banned_user' => [
                    'id'    => $user->banned_id,
                    'name'  => $user->banned_name,
                    'uid'   => $user->banned_uid,
                    'image' => Helper::showImage($user->banned_image, true),
                    'date'  => \Carbon\Carbon::parse($user->banned_at)->format('d M Y'),
                    'expires_at' => $user->expires_at ? \Carbon\Carbon::parse($user->expires_at)->format('d M Y, h:i A') : 'Permanent'
                ],
                'operator' => [
                    'id'    => $user->operator_id,
                    'name'  => $user->operator_name,
                    'image' => Helper::showImage($user->operator_image, true)
                ]
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'Banned users list fetched successfully',
            'data' => $data
        ]);
    }


    public function inviteToMic(Request $request)
    {
        try {
            $request->validate([
                'room_id'   => 'required|integer|exists:rooms,id',
                'seat_no'   => 'required|integer',
                'user_id'   => 'required|integer|exists:app_users,id',
            ]);

            $authUser = Auth::user();

            if (!$authUser) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $room = Room::where('id', $request->room_id)->first();

            if (!$room) {
                return response()->json([
                    'status' => false,
                    'message' => 'Room not found'
                ], 404);
            }

            // ROLE CHECK (OWNER + ADMIN)
            $myRole = $this->getRoomUserRole($room->id, $authUser->id);

            if (!in_array($myRole, ['owner', 'admin'])) {
                return response()->json([
                    'status' => false,
                    'message' => 'Only owner or admin can invite to mic'
                ], 403);
            }

            // TARGET USER ROLE CHECK
            $targetRole = $this->getRoomUserRole($room->id, $request->user_id);

            if ((int)$authUser->id === (int)$request->user_id) {
                return response()->json([
                    'status' => false,
                    'message' => 'You cannot invite yourself to mic'
                ], 409);
            }

            //admin cannot invite owner
            // if ($myRole === 'admin' && $targetRole === 'owner') {
            //     return response()->json([
            //         'status' => false,
            //         'message' => 'Admin cannot invite owner'
            //     ], 403);
            // }

            // // admin cannot invite admin
            // if ($myRole === 'admin' && $targetRole === 'admin') {
            //     return response()->json([
            //         'status' => false,
            //         'message' => 'Admin cannot invite another admin'
            //     ], 403);
            // }

            // CHECK SEAT LOCK
            $seatSetting = DB::table('room_seat_settings')
                ->where('room_id', $room->id)
                ->where('seat_no', $request->seat_no)
                ->first();

            if ($seatSetting && $seatSetting->is_locked) {
                return response()->json([
                    'status' => false,
                    'message' => 'Seat is locked'
                ], 403);
            }

            $seatExists = RoomSeat::where('room_id', $room->id)
                ->where('seat_no', $request->seat_no)
                ->exists();

            if ($seatExists) {
                return response()->json([
                    'status' => false,
                    'message' => 'Seat already occupied'
                ], 409);
            }

            $alreadySeated = RoomSeat::where('room_id', $room->id)
                ->where('user_id', $request->user_id)
                ->first();

            if ($alreadySeated) {
                return response()->json([
                    'status' => false,
                    'message' => 'User already on seat'
                ], 409);
            }

            $isJoined = RoomPresence::where('room_id', $room->id)
                ->where('user_id', $request->user_id)
                ->exists();

            if (!$isJoined) {
                return response()->json([
                    'status' => false,
                    'message' => 'User is not in room'
                ], 409);
            }

            $user = AppUser::find($request->user_id);

            $targetUser = AppUser::select('id', 'name', 'image', 'fcm_token')
                ->where('id', $request->user_id)
                ->first();
            $data = [
                'type' => 'room_mic_invite',
                'action' => 'invite_to_mic',

                'room_id' => (string) $room->id,
                'room_name' => (string) ($room->room_name ?? 'Room'),

                'seat_no' => (string) $request->seat_no,

                'invited_user_id' => (string) $targetUser->id,
                'invited_user_name' => (string) $targetUser->name,

                'host_id' => (string) $authUser->id,
                'host_name' => (string) $authUser->name,
                'host_image' => !empty($authUser->image)
                    ? Helper::showImage($authUser->image, true)
                    : '',

                'timestamp' => (string) now()->timestamp,
            ];

            $firebase = new FirebaseService();

            if (!empty($targetUser->fcm_token)) {
                $firebase = new FirebaseService();
                $firebase->sendNotification(
                    $targetUser->fcm_token,
                    '🎤 Mic Invitation',
                    $authUser->name . ' invited you to join mic',
                    $data
                );
            }

            broadcast(new \App\Events\RoomMicInvitationSent(
                $room->id,
                $targetUser->id,
                $request->seat_no,
                [
                    'id' => $authUser->id,
                    'name' => $authUser->name,
                    'image' => !empty($authUser->image)
                        ? Helper::showImage($authUser->image, true)
                        : null,
                ],
                $data
            ))->toOthers();

            return response()->json([
                'status' => true,
                'message' => 'User invited to mic successfully',
                'data' => $data
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function updateRoomUserRole(Request $request)
    {
        DB::beginTransaction();

        try {
            $validator = Validator::make($request->all(), [
                'room_id' => 'required|integer|exists:rooms,id',
                'user_id' => 'required|integer|exists:app_users,id',
                'role' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors(),
                ], 422);
            }

            $authUser = Auth::user();

            if (!$authUser) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthenticated',
                ], 401);
            }

            $roomId = (int) $request->room_id;
            $targetUserId = (int) $request->user_id;
            $role = trim(strtolower($request->role));

            $room = Room::find($roomId);

            if (!$room) {
                return response()->json([
                    'status' => false,
                    'message' => 'Room not found',
                ], 404);
            }

            // only host can change role
            if ((int) $room->user_id !== (int) $authUser->id) {
                return response()->json([
                    'status' => false,
                    'message' => 'Only host can update user role',
                ], 403);
            }

            // host role cannot be changed
            if ((int) $room->user_id === (int) $targetUserId) {
                return response()->json([
                    'status' => false,
                    'message' => 'Host role cannot be changed',
                ], 409);
            }

            // More Admin Privilege Logic

            if ($role === 'admin') {

                // Default admin limit
                $maxAdminLimit = 1;

                // Current admin count
                $currentAdminCount = RoomUserRole::where('room_id', $roomId)
                    ->where('role', 'admin')
                    ->count();

                // If target user is already admin, skip limit check
                $alreadyAdmin = RoomUserRole::where('room_id', $roomId)
                    ->where('user_id', $targetUserId)
                    ->where('role', 'admin')
                    ->exists();

                // Host active svip
                $activeSvip = SvipTransaction::with('svip')
                    ->where('user_id', $authUser->id)
                    ->where('end_at', '>=', now())
                    ->first();

                if ($activeSvip) {

                    $hasMoreAdminPrivilege = $activeSvip->svip
                        ->privileges()
                        ->where('slug', 'more_admin')
                        ->wherePivot('is_active', 1)
                        ->exists();

                    if ($hasMoreAdminPrivilege) {
                        // $maxAdminLimit = $activeSvip->svip->admin_limit ?? 4;  // in this replace 4 with the  the svip limit
                        $maxAdminLimit += ($activeSvip->svip->admin_limit ?? 0); // in this sum the default limit with the svip limit if needed
                    }
                }

                if (!$alreadyAdmin && $currentAdminCount >= $maxAdminLimit) {
                    return response()->json([
                        'status' => false,
                        'message' => "Maximum {$maxAdminLimit} admins allowed."
                    ], 422);
                }
            }

            // user should be in room currently
            $isPresent = RoomPresence::where('room_id', $roomId)
                ->where('user_id', $targetUserId)
                ->exists();

            if (!$isPresent) {
                return response()->json([
                    'status' => false,
                    'message' => 'User is not in room',
                ], 409);
            }

            $targetUser = AppUser::select('id', 'name', 'image')
                ->where('id', $targetUserId)
                ->first();

            if (!$targetUser) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found',
                ], 404);
            }

            // update role table
            $roleRow = RoomUserRole::updateOrCreate(
                [
                    'room_id' => $roomId,
                    'user_id' => $targetUserId,
                ],
                [
                    'role' => $role,
                    'assigned_by' => $authUser->id,
                ]
            );

            // sync room_admins table
            if ($role === 'admin') {
                RoomAdmin::updateOrCreate(
                    [
                        'room_id' => $roomId,
                        'user_id' => $targetUserId,
                    ],
                    [
                        'created_by' => $authUser->id,
                    ]
                );
            } else {
                RoomAdmin::where('room_id', $roomId)
                    ->where('user_id', $targetUserId)
                    ->delete();
            }

            // sync room_members table
            if (in_array($role, ['admin', 'member'])) {

                $activeMember = RoomMember::where('room_id', $roomId)
                    ->where('user_id', $targetUserId)
                    ->whereNull('left_at')
                    ->latest('id')
                    ->first();

                if (!$activeMember) {
                    RoomMember::create([
                        'room_id' => $roomId,
                        'user_id' => $targetUserId,
                        'joined_at' => now(),
                        'left_at' => null,
                    ]);
                }
            } elseif ($role === 'guest') {

                $activeMember = RoomMember::where('room_id', $roomId)
                    ->where('user_id', $targetUserId)
                    ->whereNull('left_at')
                    ->latest('id')
                    ->first();

                if ($activeMember) {
                    $activeMember->update([
                        'left_at' => now(),
                    ]);
                }
            }

            DB::commit();

            $updatedBy = [
                'id' => $authUser->id,
                'name' => $authUser->name,
                'image' => !empty($authUser->image)
                    ? Helper::showImage($authUser->image, true)
                    : null,
            ];

            $userData = [
                'id' => $targetUser->id,
                'name' => $targetUser->name,
                'image' => !empty($targetUser->image)
                    ? Helper::showImage($targetUser->image, true)
                    : null,
            ];

            broadcast(new \App\Events\RoomUserRoleUpdated(
                $roomId,
                $targetUserId,
                $role,
                $updatedBy,
                $userData
            ))->toOthers();

            return response()->json([
                'status' => true,
                'message' => 'User role updated successfully',
                'data' => [
                    'room_id' => $roomId,
                    'user_id' => $targetUserId,
                    'role' => $roleRow->role,
                    'assigned_by' => $roleRow->assigned_by,
                    'max_admin_limit' => $maxAdminLimit,
                    'user' => $userData,
                ],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    public function getRoomAdmins(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'room_id' => 'required|integer|exists:rooms,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        $roomId = (int) $request->room_id;

        $room = Room::with('user:id,name,uid,image')->find($roomId);

        if (!$room) {
            return response()->json([
                'status' => false,
                'message' => 'Room not found'
            ], 404);
        }

        // get admins
        $admins = RoomUserRole::where('room_id', $roomId)
            ->where('role', 'admin')
            ->with('user:id,name,uid,image')
            ->get();

        $adminList = [];

        // include host (optional but recommended)
        if ($room->user) {
            $adminList[] = [
                'id'    => $room->user->id,
                'name'  => $room->user->name,
                'uid'   => $room->user->uid,
                'image' => !empty($room->user->image)
                    ? Helper::showImage($room->user->image, true)
                    : null,
                'role'  => 'owner',
            ];
        }

        //  admins list
        foreach ($admins as $admin) {

            if (!$admin->user) continue;

            $adminList[] = [
                'id'    => $admin->user->id,
                'name'  => $admin->user->name,
                'uid'   => $admin->user->uid,
                'image' => !empty($admin->user->image)
                    ? Helper::showImage($admin->user->image, true)
                    : null,
                'role'  => 'admin',
            ];
        }

        return response()->json([
            'status' => true,
            'message' => 'Room admin list fetched successfully',
            'total' => count($adminList),
            'admins' => $adminList
        ]);
    }


    public function lockRoom(Request $request)
    {
        $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'password' => 'required|string|min:4'
        ]);

        $user = Auth::user();
        $room = Room::find($request->room_id);

        if ($room->user_id != $user->id) {
            return response()->json([
                'status' => false,
                'message' => 'Only host can lock room'
            ], 403);
        }

        $room->update([
            'is_locked' => 1,
            'password' => bcrypt($request->password)
        ]);

        broadcast(new RoomLockUpdated($room->id, true, true))->toOthers();

        return response()->json([
            'status' => true,
            'message' => 'Room locked successfully'
        ]);
    }

    public function unlockRoom(Request $request)
    {
        $room = Room::find($request->room_id);

        $room->update([
            'is_locked' => 0,
            'password' => null
        ]);

        broadcast(new RoomLockUpdated($room->id, false, false))->toOthers();

        return response()->json([
            'status' => true,
            'message' => 'Room unlocked'
        ]);
    }

    public function updateRoomAccess(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'room_id' => 'required|exists:rooms,id',
            'mic_permission' => 'required|in:0,1,2',
            'message_permission' => 'required|in:0,1',
            'admin_can_play_music' => 'required|in:0,1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();
        $room = Room::find($request->room_id);

        if ((int)$room->user_id !== (int)$user->id) {
            return response()->json([
                'status' => false,
                'message' => 'Only host can update settings',
            ], 403);
        }

        $setting = RoomSetting::updateOrCreate(
            ['room_id' => $room->id],
            [
                'mic_permission' => $request->mic_permission,
                'message_permission' => $request->message_permission,
                'admin_can_play_music' => $request->admin_can_play_music,
            ]
        );

        broadcast(new RoomAccessUpdated($room->id, 'room_access_update', $setting))->toOthers();

        return response()->json([
            'status' => true,
            'message' => 'Room access updated',
            'data' => $setting
        ]);
    }

    public function removeUserFromSeat(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'room_id' => 'required|integer|exists:rooms,id',
            'user_id' => 'required|integer|exists:app_users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();

        try {
            $authUser = Auth::user();
            $roomId = (int) $request->room_id;
            $targetUserId = (int) $request->user_id;

            $room = Room::find($roomId);

            if (!$room) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Room not found',
                ], 404);
            }

            $authRole = 'guest';
            if ((int) $room->user_id === (int) $authUser->id) {
                $authRole = 'owner';
            } else {
                $authRole = RoomUserRole::where('room_id', $roomId)
                    ->where('user_id', $authUser->id)
                    ->value('role') ?? 'guest';
            }

            if (!in_array($authRole, ['owner', 'admin'])) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Only owner or admin can remove user from seat',
                ], 403);
            }

            $seat = RoomSeat::where('room_id', $roomId)
                ->where('user_id', $targetUserId)
                ->lockForUpdate()
                ->first();

            if (!$seat) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'User is not on seat',
                ], 404);
            }

            // optional: admin cannot remove owner
            if ((int) $room->user_id === (int) $targetUserId) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Owner cannot be removed from seat',
                ], 409);
            }

            // optional: admin cannot remove another admin
            if ($authRole === 'admin') {
                $targetRole = RoomUserRole::where('room_id', $roomId)
                    ->where('user_id', $targetUserId)
                    ->value('role') ?? 'guest';

                if ($targetRole === 'admin') {
                    DB::rollBack();
                    return response()->json([
                        'status' => false,
                        'message' => 'Admin cannot remove another admin from seat',
                    ], 403);
                }
            }

            // Check "Avoid Being Kicked" privilege
            $hasSvipAntiKick = SvipTransaction::where('user_id', $targetUserId)
                ->where('end_at', '>=', now())
                ->whereHas('svip.privileges', function ($q) {
                    $q->where('svip_privileges.slug', 'avoid_being_kicked')
                        ->where('svip_level_privileges.is_active', 1);
                })
                ->exists();

            $hasVipAntiKick = Helper::hasVipPrivilege($targetUserId, 'anti_kick');


            $cannotBeKicked = $hasVipAntiKick || $hasSvipAntiKick;

            if ($cannotBeKicked) {

                DB::rollBack();

                return response()->json([
                    'status' => false,
                    'message' =>  'This user has Anti Kick privilege and cannot be removed from the seat.',
                ], 403);
            }

            $seatNo = (int) $seat->seat_no;

            $targetUser = AppUser::select('id', 'name', 'image')
                ->find($targetUserId);

            $seat->delete();

            DB::commit();

            $seats = $this->getRoomSeats($roomId);

            broadcast(new RoomSeatUpdated(
                $roomId,
                'remove_from_seat',
                $seats,
                $seatNo,
                [
                    'id' => $targetUser?->id,
                    'name' => $targetUser?->name,
                    'image' => !empty($targetUser?->image)
                        ? Helper::showImage($targetUser->image, true)
                        : null,
                ]
            ))->toOthers();

            return response()->json([
                'status' => true,
                'message' => 'User removed from seat successfully',
                'action' => 'remove_from_seat',
                'seats' => $seats,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    public function getRoomMessages(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'room_id' => 'required|exists:rooms,id',
            // 'limit'   => 'nullable|integer|min:1|max:100',
            // 'page'    => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => $validator->errors(),
            ], 422);
        }

        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthenticated',
                ], 401);
            }

            $limit = 100;

            $clearRow = RoomMessageClear::where('room_id', $request->room_id)
                ->where('user_id', $user->id)
                ->first();

            $messagesQuery = RoomMessage::with([
                'user:id,name,uid,image,gender,total_points,user_level',
                'targetUser:id,name,uid,image,total_points,user_level',
                'gift:id,name,cover,price'
            ])
                ->where('room_id', $request->room_id);

            // member/self clear support
            if ($clearRow && $clearRow->cleared_at) {
                $messagesQuery->where('created_at', '>', $clearRow->cleared_at);
            }

            $messages = $messagesQuery
                ->orderBy('id', 'desc')
                ->paginate($limit);

            $formatted = $messages->getCollection()->map(function ($message) {
                $meta = is_array($message->meta_json) ? $message->meta_json : [];
                $wealthLevel = null;
                $charmLevel = null;
                $equippedMedals = [];

                if ($message->user) {

                    $wealthLevel = WCLevel::with([
                        'levelData' => fn($q) =>
                        $q->where('type', 'wealth')
                    ])
                        ->where('user_id', $message->user->id)
                        ->where('type', 'wealth')
                        ->first();


                    $charmLevel = WCLevel::with([
                        'levelData' => fn($q) =>
                        $q->where('type', 'charm')
                    ])
                        ->where('user_id', $message->user->id)
                        ->where('type', 'charm')
                        ->first();


                    $equippedMedals = UserMedal::with('medal')
                        ->where('user_id', $message->user->id)
                        ->where('is_equipped', 1)
                        ->orderBy('slot_no')
                        ->get()
                        ->map(function ($item) {

                            return [
                                'id' => $item->medal->id,
                                'name' => $item->medal->title,
                                'icon' => Helper::showImage($item->medal->icon, true)
                            ];
                        })
                        ->values();

                    $nicknameMeta = [
                        'animated' => false,
                        'color' => null,
                        'effect' => null,
                    ];

                    if ($message->user) {
                        $nicknameMeta = Helper::getNicknameMeta($message->user->id);
                    }
                }
                $sender = $message->user ? [
                    'id'           => $message->user->id,
                    'name'         => $message->user->name,
                    'nickname_meta' => $nicknameMeta,
                    'gender'         => $message->user->gender,
                    'uid'          => $message->user->uid,
                    'image'        => !empty($message->user->image)
                        ? (preg_match('/^https?:\/\//', $message->user->image)
                            ? $message->user->image
                            : Helper::showImage($message->user->image, true))
                        : null,

                    'wealth_level' => [
                        'level' => $wealthLevel?->level ?? 1,
                        'icon' => $wealthLevel?->levelData?->icon
                            ? Helper::showImage($wealthLevel->levelData->icon, true)
                            : null
                    ],


                    'charm_level' => [
                        'level' => $charmLevel?->level ?? 1,
                        'icon' => $charmLevel?->levelData?->icon
                            ? Helper::showImage($charmLevel->levelData->icon, true)
                            : null
                    ],


                    'medals' => $equippedMedals
                ] : null;

                $targetNickname = [
                    'animated' => false,
                    'color' => null,
                    'effect' => null,
                ];

                if ($message->targetUser) {
                    $targetNickname = Helper::getNicknameMeta($message->targetUser->id);
                }

                $targetUser = $message->targetUser ? [
                    'id'           => $message->targetUser->id,
                    'name'         => $message->targetUser->name,
                    'nickname_meta' => $targetNickname,
                    'uid'          => $message->targetUser->uid,
                    'image'        => !empty($message->targetUser->image)
                        ? (preg_match('/^https?:\/\//', $message->targetUser->image)
                            ? $message->targetUser->image
                            : Helper::showImage($message->targetUser->image, true))
                        : null,
                ] : null;

                $gift = null;
                if ($message->message_type === 'gift') {
                    $giftImage = null;

                    if (!empty($meta['gift']['image'])) {
                        $giftImage = $meta['gift']['image'];
                    } elseif ($message->gift) {
                        $giftPath = $message->gift->image ?? $message->gift->cover ?? null;
                        $giftImage = !empty($giftPath) ? Helper::showImage($giftPath, true) : null;
                    }

                    $gift = [
                        'id'    => $meta['gift']['id'] ?? $message->gift_id,
                        'name'  => $meta['gift']['name']
                            ?? $message->gift->name
                            ?? null,
                        'image' => $giftImage,
                        'qty'   => $message->gift_qty,
                    ];
                }

                $receivers = [];
                if (!empty($meta['receivers']) && is_array($meta['receivers'])) {
                    $receivers = collect($meta['receivers'])->map(function ($receiver) {
                        return [
                            'id'           => $receiver['id'] ?? null,
                            'name'         => $receiver['name'] ?? null,
                            'uid'          => $receiver['uid'] ?? null,
                            'image'        => $receiver['image'] ?? null,
                        ];
                    })->values()->toArray();
                } elseif ($targetUser) {
                    $receivers[] = $targetUser;
                }

                return [
                    'id'             => $message->id,
                    'type'           => $message->message_type,
                    'room_id'        => $message->room_id,
                    'message'        => $message->message,
                    'sender'         => $sender,
                    'target_user'    => $targetUser,
                    'gift'           => $gift,
                    'gift_qty'       => $message->gift_qty,
                    'receivers'      => $receivers,
                    'meta_json'      => $meta,
                ];
            })->reverse()->values();

            return response()->json([
                'status' => true,
                'message' => 'Room messages fetched successfully',
                'data' => $formatted,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch room messages',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function clearRoomMessages(Request $request): JsonResponse
    {
        $validation = Validator::make($request->all(), [
            'room_id' => 'required|integer|exists:rooms,id',
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

            $role = $this->getRoomUserRolee($room->id, $user->id);

            // OWNER / ADMIN → CLEAR ALL

            if (in_array($role, ['owner', 'admin'])) {

                RoomMessage::where('room_id', $room->id)->delete();

                // clear history bhi remove
                RoomMessageClear::where('room_id', $room->id)->delete();

                DB::commit();

                //  REALTIME BROADCAST
                broadcast(new RoomMessagesCleared(
                    $room->id,
                    $user->id,
                    'all',
                    'Room chat cleared by ' . $role
                ))->toOthers();

                return response()->json([
                    'status' => true,
                    'message' => 'All room messages cleared successfully',
                    'clear_type' => 'all',
                ]);
            }

            // MEMBER → SELF CLEAR

            RoomMessageClear::updateOrCreate(
                [
                    'room_id' => $room->id,
                    'user_id' => $user->id,
                ],
                [
                    'cleared_at' => now(),
                ]
            );

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Your chat cleared successfully',
                'clear_type' => 'self',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            \Log::error('CLEAR ROOM MESSAGE ERROR', [
                'error' => $e->getMessage(),
                'room_id' => $request->room_id ?? null,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    protected function getRoomUserRolee($roomId, $userId)
    {
        $room = \App\Models\Room::find($roomId);

        if (!$room) {
            return 'guest';
        }

        // OWNER
        if ((int)$room->user_id === (int)$userId) {
            return 'owner';
        }

        // CHECK IN room_user_role TABLE
        $roleRow = \DB::table('room_user_roles')
            ->where('room_id', $roomId)
            ->where('user_id', $userId)
            ->first();

        if ($roleRow && $roleRow->role) {
            return $roleRow->role; // admin / guest / member
        }

        return 'guest';
    }

    public function getEffectSettings()
    {
        $user = Auth::user();

        return response()->json([
            'status' => true,
            'data' => [
                'gift_effect' => (bool) $user->gift_effect,
                'enter_effect' => (bool) $user->enter_effect,
                'gift_message' => (bool) $user->gift_message,
                'lucky_gift_sound' => (bool) $user->lucky_gift_sound,
            ]
        ]);
    }

    public function updateEffectSettings(Request $request)
    {
        $user = Auth::user();

        $user->update([
            'gift_effect' => $request->gift_effect ?? $user->gift_effect,
            'enter_effect' => $request->enter_effect ?? $user->enter_effect,
            'gift_message' => $request->gift_message ?? $user->gift_message,
            'lucky_gift_sound' => $request->lucky_gift_sound ?? $user->lucky_gift_sound,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Settings updated successfully'
        ]);
    }


    public function getRoomMembers(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'room_id' => 'required|integer|exists:rooms,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        try {
            $roomId = (int) $request->room_id;

            $room = DB::table('rooms')->where('id', $roomId)->first();

            if (!$room) {
                return response()->json([
                    'status' => false,
                    'message' => 'Room not found'
                ], 404);
            }

            // Active members (left_at = null)
            $memberUserIds = DB::table('room_members')
                ->where('room_id', $roomId)
                ->whereNull('left_at')
                ->pluck('user_id')
                ->unique()
                ->values();

            // Admin IDs
            $adminUserIds = DB::table('room_admins')
                ->where('room_id', $roomId)
                ->pluck('user_id')
                ->map(fn($id) => (int) $id)
                ->toArray();

            // Total counts
            $totalMembers = $memberUserIds->count();
            $totalAdmins = count($adminUserIds);

            $members = DB::table('app_users')
                ->whereIn('id', $memberUserIds)
                ->get()
                ->map(function ($user) use ($room, $adminUserIds) {

                    $nicknameMeta = Helper::getNicknameMeta($user->id);

                    if ((int) $room->user_id === (int) $user->id) {
                        $role = 'owner';
                    } elseif (in_array((int) $user->id, $adminUserIds)) {
                        $role = 'admin';
                    } else {
                        $role = 'member';
                    }

                    $displayUid = $user->uid;
                    $uidBadge = null;
                    $uidBadgeColor = null;

                    // Premium UID
                    $premiumUid = PremiumNumber::where('user_id', $user->id)
                        ->where('end_at', '>', now())
                        ->latest()
                        ->first();

                    if ($premiumUid) {

                        $displayUid = $premiumUid->premium_number;
                        $uidBadge = asset('storage/1000175794.png');
                        $uidBadgeColor = '#fcd01c';
                    } else {

                        // Store UID
                        if (!empty($user->active_uid_id)) {

                            $storeUid = StoreUids::find($user->active_uid_id);

                            if ($storeUid) {

                                $hasValidPurchase = DB::table('item_deliveries')
                                    ->where('recipient', $user->id)
                                    ->where('type', 'id')
                                    ->where('item_id', $storeUid->id)
                                    ->where('end_at', '>', now())
                                    ->exists();

                                $hasValidGift = DB::table('item_gift_transactions')
                                    ->where('receiver_id', $user->id)
                                    ->where('type', 'id')
                                    ->where('item_id', $storeUid->id)
                                    ->where('end_at', '>', now())
                                    ->exists();

                                if ($hasValidPurchase || $hasValidGift) {

                                    $displayUid = $storeUid->unique_id;

                                    $uidBadge = !empty($storeUid->rank_badge)
                                        ? Helper::showImage($storeUid->rank_badge, true)
                                        : null;
                                    $uidBadgeColor = $storeUid->rank_badge_color ?? null;
                                }
                            }
                        }
                    }

                    return [
                        'id' => $user->id,
                        // 'uid' => $user->uid ?? null,
                        'uid' => $displayUid,
                        'uid_badge' => $uidBadge,
                        'uid_badge_color' => $uidBadgeColor,
                        'name' => $user->name ?? null,
                        'nickname_meta' => $nicknameMeta,
                        'image' => Helper::showImage($user->image ?? null, true),
                        'gender' => $user->gender ?? null,
                        'country' => $user->country ?? null,
                        'role' => $role,
                    ];
                })
                ->sortBy(function ($user) {
                    return match ($user['role']) {
                        'owner' => 1,
                        'admin' => 2,
                        default => 3,
                    };
                })
                ->values();

            return response()->json([
                'status' => true,
                'message' => 'Room members fetched successfully',
                'data' => [
                    'room_id' => $roomId,

                    //NEW COUNTS
                    'total_members' => $totalMembers,
                    'total_admins' => $totalAdmins,

                    'members' => $members,
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function ping1(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'room_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();

        RoomPresence::where('room_id', $request->room_id)
            ->where('user_id', $user->id)
            ->update([
                'last_ping_at' => now()
            ]);

        return response()->json([
            'status' => true,
            'message' => 'Ping updated'
        ]);
    }

    public function ping(Request $request)
    {
        \Log::info('PING API HIT');

        $validator = Validator::make($request->all(), [
            'room_id' => 'required|integer',
        ]);

        if ($validator->fails()) {

            \Log::info('PING VALIDATION FAILED', [
                'errors' => $validator->errors()
            ]);

            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();

        \Log::info('PING AUTH USER', [
            'user_id' => $user?->id,
            'room_id' => $request->room_id,
        ]);

        $updated = RoomPresence::where('room_id', $request->room_id)
            ->where('user_id', $user->id)
            ->update([
                'last_ping_at' => now()
            ]);

        \Log::info('PING UPDATED ROWS', [
            'updated_rows' => $updated
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Ping updated'
        ]);
    }

    public function run()
    {
        Artisan::call('room:check-dead-users');

        return response()->json([
            'status' => true,
            'message' => 'Command executed successfully',
            'output' => Artisan::output()
        ]);
    }

    public function run1()
    {
        \Log::info('MANUAL SCHEDULER RUN START');

        Artisan::call('schedule:run');

        \Log::info('MANUAL SCHEDULER RUN END');

        return response()->json([
            'status' => true,
            'message' => 'Scheduler executed successfully',
            'output' => Artisan::output()
        ]);
    }

    public function toggleRoomInvisible()
    {
        $user = Auth::user();

        $hasPrivilege = SvipTransaction::where('user_id', $user->id)
            ->where('end_at', '>=', now())
            ->whereHas('svip.privileges', function ($q) {
                $q->where('slug', 'room_invisible')
                    ->where('svip_level_privileges.is_active', 1);
            })
            ->exists();

        if (!$hasPrivilege) {
            return response()->json([
                'status' => false,
                'message' => 'Room invisible privilege not available.'
            ]);
        }

        $user->room_invisible = !$user->room_invisible;
        $user->save();

        return response()->json([
            'status' => true,
            'message' => $user->room_invisible
                ? 'Room invisible enabled.'
                : 'Room invisible disabled.',
            'room_invisible' => (bool) $user->room_invisible
        ]);
    }
}
