<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppUser;
use App\Models\Banner;
use App\Models\Room;
use App\Helper\Helper;
use App\Models\Broadcast;
use App\Models\BroadcastPrice;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\GiftTransaction;
use App\Models\RoomFollow;
use App\Models\RoomMember;
use App\Models\RoomVisit;
use App\Models\AppRule;
use App\Models\Report;
use App\Models\StoreUids;
use App\Models\PremiumNumber;
use App\Models\VipTransaction;
use App\Models\SvipTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Events\BroadcastMessageSent;

class HomeController extends Controller
{

    public function banner()
    {
        $user = Auth::user();
        $timezone = $user->timezone ?? 'UTC';

        $timezoneMap = [
            'IST' => 'Asia/Kolkata',
            'UTC' => 'UTC',
        ];
        $timezone = $timezoneMap[$timezone] ?? $timezone;

        try {
            $nowUtc = Carbon::now($timezone)->setTimezone('UTC');
        } catch (\Exception $e) {
            $nowUtc = Carbon::now('UTC');
        }
        $banners = Banner::with('country')->where(function ($q) use ($nowUtc) {
            $q->whereNull('start_time')
                ->orWhere('start_time', '<=', $nowUtc);
        })
            ->where(function ($q) use ($nowUtc) {
                $q->whereNull('end_time')
                    ->orWhere('end_time', '>=', $nowUtc);
            })
            ->get();

        $bannerData = $banners->map(function ($item) {
            $redirectAddress = null;

            if ($item->jump === 'h5') {
                $redirectAddress = $item->address;
            }

            if ($item->jump === 'app') {

                if ($item->type_address_app === 'personal' && !empty($item->uid)) {
                    $redirectAddress = "app://user/{$item->uid}";
                }

                if ($item->type_address_app === 'room' && !empty($item->room_id)) {
                    $redirectAddress = "app://enterRoom?roomId={$item->room_id}";
                }
            }
            return [
                'large_banner'     => Helper::showImage($item->large_banner, true),
                'small_banner'   => Helper::showImage($item->small_banner, true),
                'jump'   => $item->jump,
                'redirect_address' => $redirectAddress,
                'type_address_app'   => $item->type_address_app,
                'uid'   => $item->uid,
                'room_id'   => $item->room_id,
                'display'   => $item->display,
                'start_time'   => $item->start_time,
                'end_time'   => $item->end_time,
                'region_id' => $item->region,
                'region_name' => optional($item->country)->name,
                'description'   => $item->description,
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'Banners fetched successfully',
            'data' => $bannerData
        ], 200);
    }


    public function topCharms(Request $request)
    {
        $type = $request->get('type', 'today');

        $country = auth()->user()->country;

        if ($type === 'week') {
            $from = now()->startOfWeek();
            $to   = now()->endOfWeek();
        } elseif ($type === 'month') {
            $from = now()->startOfMonth();
            $to   = now()->endOfMonth();
        } else {
            $from = now()->startOfDay();
            $to   = now()->endOfDay();
        }

        $topUsers = GiftTransaction::select(
            'receiver_id',
            DB::raw('SUM(total_value) as total_points')
        )
            ->whereBetween('created_at', [$from, $to])
            ->whereHas('receiver', function ($q) use ($country) {
                $q->where('country', $country)
                    ->whereDoesntHave('activeSvip.svip.privileges', function ($q) {
                        $q->where('slug', 'rank_invisible')
                            ->where('svip_level_privileges.is_active', 1);
                    });
            })
            ->groupBy('receiver_id')
            ->orderByDesc('total_points')
            ->limit(100)
            ->with(['receiver:id,uid,name,image'])
            ->get();


        $topUsers->each(function ($item) {
            if ($item->receiver && $item->receiver->image) {
                if (!Str::startsWith($item->receiver->image, ['http://', 'https://'])) {
                    $item->receiver->image = Helper::showImage($item->receiver->image, true);
                }
            }

            $item->receiver->nickname_meta = Helper::getNicknameMeta($item->receiver->id);
        });

        return response()->json([
            'status' => true,
            'type'   => $type,
            'data'   => $topUsers
        ]);
    }

    public function topWealth(Request $request)
    {
        $type = $request->get('type', 'today');

        $country = auth()->user()->country;

        if ($type === 'week') {
            $from = now()->startOfWeek();
            $to   = now()->endOfWeek();
        } elseif ($type === 'month') {
            $from = now()->startOfMonth();
            $to   = now()->endOfMonth();
        } else {
            $from = now()->startOfDay();
            $to   = now()->endOfDay();
        }

        $topUsers = GiftTransaction::select(
            'sender_id',
            DB::raw('SUM(total_value) as total_points')
        )
            ->whereBetween('created_at', [$from, $to])
            ->whereHas('sender', function ($q) use ($country) {
                $q->where('country', $country)
                    ->whereDoesntHave('activeSvip.svip.privileges', function ($q) {
                        $q->where('slug', 'rank_invisible')
                            ->where('svip_level_privileges.is_active', 1);
                    });
            })
            ->groupBy('sender_id')
            ->orderByDesc('total_points')
            ->limit(100)
            ->with(['sender:id,uid,name,image'])
            ->get();


        $topUsers->each(function ($item) {
            if ($item->sender && $item->sender->image) {
                if (!Str::startsWith($item->sender->image, ['http://', 'https://'])) {
                    $item->sender->image = Helper::showImage($item->sender->image, true);
                }
            }

            $item->sender->nickname_meta = Helper::getNicknameMeta($item->sender->id);
        });

        return response()->json([
            'status' => true,
            'type'   => $type,
            'data'   => $topUsers
        ]);
    }

    public function topRoom(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            $type = $request->get('type', 'today');

            if ($type === 'week') {
                $from = now()->startOfWeek();
                $to   = now()->endOfWeek();
            } elseif ($type === 'month') {
                $from = now()->startOfMonth();
                $to   = now()->endOfMonth();
            } else {
                $type = 'today';
                $from = now()->startOfDay();
                $to   = now()->endOfDay();
            }

            $topRooms = GiftTransaction::select(
                'room_id',
                DB::raw('SUM(total_value) as total_points')
            )
                ->whereNotNull('room_id')
                ->whereBetween('created_at', [$from, $to])
                ->whereHas('room', function ($q) use ($user) {
                    $q->where('country', $user->country)
                        ->whereHas('user', function ($q) {

                            $q->whereDoesntHave('activeSvip.svip.privileges', function ($q) {
                                $q->where('slug', 'rank_invisible')
                                    ->where('svip_level_privileges.is_active', 1);
                            });
                        });
                })
                ->groupBy('room_id')
                ->orderByDesc('total_points')
                ->limit(100)
                ->with([
                    'room:id,room_name,room_image,user_id,country',
                    'room.user:id,name,image'
                ])
                ->get();

            $topRooms->each(function ($item) {
                if ($item->room?->room_image) {
                    $item->room->room_image = Helper::showImage($item->room->room_image, true);
                }

                if ($item->room?->user?->image) {
                    if (!Str::startsWith($item->room->user->image, ['http://', 'https://'])) {
                        $item->room->user->image = Helper::showImage($item->room->user->image, true);
                    }
                }

                $item->total_points = (int) $item->total_points;
            });

            return response()->json([
                'status' => true,
                'type'   => $type,
                'data'   => $topRooms
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Failed to fetch top room',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function hotRooms()
    {
        $user = Auth::user();

        $this->clearExpiredItemsForAllUsers();

        $followedRoomIds = DB::table('room_follows')
            ->where('user_id', $user->id)
            ->pluck('room_id')
            ->toArray();

        $joinedRoomIds = DB::table('room_members')
            ->where('user_id', $user->id)
            ->whereNull('left_at')
            ->pluck('room_id')
            ->toArray();

        $hiddenRoomOwnerIds = SvipTransaction::where(function ($q) {
            $q->whereNull('end_at')
                ->orWhere('end_at', '>=', now());
        })
            ->whereHas('svip.privileges', function ($q) {
                $q->where('slug', 'room_invisible')
                    ->where('svip_level_privileges.is_active', 1);
            })
            // ->whereHas('user', function ($q) use ($user) {
            //     $q->where('room_invisible', 1);
            //         // ->where('id', '!=', $user->id);
            // })
            ->whereHas('user', function ($q) {
                $q->where('room_invisible', 1);
            })
            ->pluck('user_id')
            ->toArray();


        //  Top Your Room Users

        $topRoomUsers = SvipTransaction::select(
            'user_id',
            'svip_id'
        )
            ->where(function ($q) {
                $q->whereNull('end_at')
                    ->orWhere('end_at', '>=', now());
            })
            ->whereHas('svip.privileges', function ($q) {
                $q->where('slug', 'top_your_room')
                    ->where('svip_level_privileges.is_active', 1);
            });

        // $rooms = Room::with([
        //     'user:id,uid,name,image,country,active_uid_id',
        //     'user.countryData:id,name,iso',
        //     'user.premium:user_id,premium_number,valid_days,created_at'
        // ])
        //     ->withCount('onlineUsers as online_count')
        //     ->where('status', 1)
        //     ->where('country', $user->country)
        //     ->whereNotIn('user_id', $hiddenRoomOwnerIds)
        //     ->orderByDesc('online_count')
        //     ->orderByDesc('total_points')
        //     // ->limit(100)
        //     ->paginate(10);

        $rooms = Room::with([
            'user:id,uid,name,image,country,active_uid_id',
            'user.countryData:id,name,iso',
            'user.premium:user_id,premium_number,valid_days,created_at'
        ])
            ->leftJoinSub(
                $topRoomUsers,
                'svip_room_priority',
                function ($join) {
                    $join->on(
                        'rooms.user_id',
                        '=',
                        'svip_room_priority.user_id'
                    );
                }
            )
            ->select(
                'rooms.*',
                DB::raw('COALESCE(svip_room_priority.svip_id,0) as room_priority')
            )
            ->withCount('onlineUsers as online_count')
            ->where('rooms.status', 1)
            ->where('rooms.is_banned', 0)
            ->where('rooms.country', $user->country)
            ->whereNotIn('rooms.user_id', $hiddenRoomOwnerIds)
            ->orderByDesc('rooms.is_pinned')   // Pinned rooms first
            ->orderByDesc('rooms.pinned_at')   // Pinned order (latest pinned first)
            ->orderByDesc('room_priority')
            ->orderByDesc('online_count')
            ->orderByDesc('rooms.total_points')
            ->paginate(10);


        $rooms->getCollection()->transform(function ($room) use ($followedRoomIds, $joinedRoomIds) {

            if ($room->user) {

                // Default System UID
                $displayUid = $room->user->uid;
                $uidBadgeColor = null;

                // 1. Premium UID Check
                $premiumUid = PremiumNumber::where('user_id', $room->user->id)
                    ->where('end_at', '>', now())
                    ->latest()
                    ->first();

                if ($premiumUid) {

                    $displayUid = $premiumUid->premium_number;
                    $uidBadgeColor = '#fcd01c';
                } else {

                    // 2. Store UID Check
                    if ($room->user->active_uid_id) {

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
                                $displayUid = $storeUid->unique_id;
                                $uidBadgeColor = $storeUid->rank_badge_color ?? null;
                            }
                        }
                    }
                }

                $room->user->uid = $displayUid;
                $room->user->uid_badge_color = $uidBadgeColor;
            }

            if ($room->room_image) {
                $room->room_image = Helper::showImage($room->room_image, true);
            }

            if ($room->user && $room->user->image) {
                $room->user->image = Helper::showImage($room->user->image, true);
            }

            if ($room->user->countryData && $room->user->countryData->iso) {
                $room->user->flag =
                    'https://flagcdn.com/w40/' .
                    strtolower($room->user->countryData->iso) .
                    '.png';
            } else {
                $room->user->flag = null;
            }

            $room->online_count = (int) $room->online_count;
            $room->room_priority = (int) ($room->room_priority ?? 0);
            $room->is_top_room = $room->room_priority > 0;
            $room->is_follow = in_array($room->id, $followedRoomIds);
            $room->is_joined = in_array($room->id, $joinedRoomIds);

            return $room;
        });


        return response()->json([
            'status' => true,
            'message' => 'Hot Room List Fetched Successfully',
            'password' => $user->password,
            'data'   => $rooms,
            'pagination' => [
                'current_page' => $rooms->currentPage(),
                'per_page'     => $rooms->perPage(),
                'total'        => $rooms->total(),
                'last_page'    => $rooms->lastPage(),
                'from'         => $rooms->firstItem(),
                'to'           => $rooms->lastItem(),
            ],
        ]);
    }
    private function clearExpiredItemsForAllUsers()
    {
        $users = AppUser::where(function ($q) {
            $q->whereNotNull('active_frame_id')
                ->orWhereNotNull('active_car_id')
                ->orWhereNotNull('active_entry_id')
                ->orWhereNotNull('active_voice_id')
                ->orWhereNotNull('active_card_id')
                ->orWhereNotNull('active_chat_bubble_id')
                ->orWhereNotNull('active_uid_id');
        })->get();

        foreach ($users as $user) {

            $updates = [];

            $itemColumns = [
                'active_frame_id'       => 'frame',
                'active_car_id'         => 'entry',
                'active_entry_id'       => 'entry_tag',
                'active_voice_id'       => 'voice',
                'active_card_id'        => 'profile_card',
                'active_chat_bubble_id' => 'chat_bubble',
                'active_uid_id'         => 'id',
            ];

            foreach ($itemColumns as $column => $type) {

                $itemId = $user->{$column};

                if (!$itemId) {
                    continue;
                }

                $hasDelivery = DB::table('item_deliveries')
                    ->where('recipient', $user->id)
                    ->where('type', $type)
                    ->where('item_id', $itemId)
                    ->where(function ($q) {
                        $q->whereNull('end_at')
                            ->orWhere('end_at', '>', now());
                    })
                    ->exists();

                $hasGift = DB::table('item_gift_transactions')
                    ->where('receiver_id', $user->id)
                    ->where('type', $type)
                    ->where('item_id', $itemId)
                    ->where(function ($q) {
                        $q->whereNull('end_at')
                            ->orWhere('end_at', '>', now());
                    })
                    ->exists();

                // store/gift item expired
                if (!$hasDelivery && !$hasGift) {

                    $updates[$column] = null;

                    switch ($column) {

                        case 'active_frame_id':
                            $updates['active_frame_type'] = null;
                            break;

                        case 'active_voice_id':
                            $updates['active_voice_type'] = null;
                            break;

                        case 'active_chat_bubble_id':
                            $updates['active_chat_bubble_type'] = null;
                            break;

                        case 'active_card_id':
                            $updates['active_profile_card_type'] = null;
                            break;

                        case 'active_car_id':
                            $updates['active_entry_type'] = null;
                            break;

                        case 'active_entry_id':
                            $updates['active_entry_tag_type'] = null;
                            break;
                    }
                }
            }

            // VIP check
            $hasVip = VipTransaction::where('user_id', $user->id)
                ->where('end_at', '>', now())
                ->exists();

            if (!$hasVip) {

                if ($user->active_frame_type === 'vip') {
                    $updates['active_frame_id'] = null;
                    $updates['active_frame_type'] = null;
                }

                if ($user->active_voice_type === 'vip') {
                    $updates['active_voice_id'] = null;
                    $updates['active_voice_type'] = null;
                }

                if ($user->active_chat_bubble_type === 'vip') {
                    $updates['active_chat_bubble_id'] = null;
                    $updates['active_chat_bubble_type'] = null;
                }

                if ($user->active_profile_card_type === 'vip') {
                    $updates['active_card_id'] = null;
                    $updates['active_profile_card_type'] = null;
                }

                if ($user->active_entry_type === 'vip') {
                    $updates['active_car_id'] = null;
                    $updates['active_entry_type'] = null;
                }

                if ($user->active_entry_tag_type === 'vip') {
                    $updates['active_entry_id'] = null;
                    $updates['active_entry_tag_type'] = null;
                }
            }

            // SVIP check
            $hasSvip = SvipTransaction::where('user_id', $user->id)
                ->where('end_at', '>', now())
                ->exists();

            if (!$hasSvip) {

                if ($user->active_frame_type === 'svip') {
                    $updates['active_frame_id'] = null;
                    $updates['active_frame_type'] = null;
                }

                if ($user->active_voice_type === 'svip') {
                    $updates['active_voice_id'] = null;
                    $updates['active_voice_type'] = null;
                }

                if ($user->active_chat_bubble_type === 'svip') {
                    $updates['active_chat_bubble_id'] = null;
                    $updates['active_chat_bubble_type'] = null;
                }

                if ($user->active_profile_card_type === 'svip') {
                    $updates['active_card_id'] = null;
                    $updates['active_profile_card_type'] = null;
                }

                if ($user->active_entry_type === 'svip') {
                    $updates['active_car_id'] = null;
                    $updates['active_entry_type'] = null;
                }

                if ($user->active_entry_tag_type === 'svip') {
                    $updates['active_entry_id'] = null;
                    $updates['active_entry_tag_type'] = null;
                }
            }

            if (!empty($updates)) {
                AppUser::where('id', $user->id)->update($updates);
            }
        }
    }

    public function newRooms()
    {
        $user = Auth::user();
        $sevenDaysAgo = now()->subDays(7);

        $followedRoomIds = DB::table('room_follows')
            ->where('user_id', $user->id)
            ->pluck('room_id')
            ->toArray();

        $joinedRoomIds = DB::table('room_members')
            ->where('user_id', $user->id)
            ->whereNull('left_at')
            ->pluck('room_id')
            ->toArray();

        $rooms = Room::with(
            'user:id,uid,name,image,country,active_uid_id',
            'user.countryData:id,name,iso'
        )
            ->where('status', 1)
            ->where('is_banned', 0)
            ->where('created_at', '>=', $sevenDaysAgo)
            ->withCount('onlineUsers as online_count')
            ->where('country', $user->country)
            ->orderByDesc('created_at')
            ->paginate(10);

        $rooms->getCollection()->transform(function ($room) use ($followedRoomIds, $joinedRoomIds) {

            if ($room->room_image) {
                $room->room_image = Helper::showImage($room->room_image, true);
            }

            if ($room->user && $room->user->image) {
                $room->user->image = Helper::showImage($room->user->image, true);
            }

            if ($room->user->countryData && $room->user->countryData->iso) {
                $room->user->flag =
                    'https://flagcdn.com/w40/' .
                    strtolower($room->user->countryData->iso) .
                    '.png';
            } else {
                $room->user->flag = null;
            }

            $room->online_count = (int) $room->online_count;

            if ($room->user) {

                // Default System UID
                $displayUid = $room->user->uid;
                $uidBadgeColor = null;

                // 1. Premium UID
                $premiumUid = PremiumNumber::where('user_id', $room->user->id)
                    ->where('end_at', '>', now())
                    ->latest()
                    ->first();

                if ($premiumUid) {

                    $displayUid = $premiumUid->premium_number;
                    $uidBadgeColor = '#fcd01c';
                } else {

                    // 2. Store UID
                    if ($room->user->active_uid_id) {

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
                                $displayUid = $storeUid->unique_id;
                                $uidBadgeColor = $storeUid->rank_badge_color ?? null;
                            }
                        }
                    }
                }

                $room->user->uid = $displayUid;
                $room->user->uid_badge_color = $uidBadgeColor;
            }

            $room->is_follow = in_array($room->id, $followedRoomIds);
            $room->is_joined = in_array($room->id, $joinedRoomIds);

            return $room;
        });

        return response()->json([
            'status'     => true,
            'message'    => 'New Room List Fetched Successfully',
            'data'       => $rooms,
            'pagination' => [
                'current_page' => $rooms->currentPage(),
                'per_page'     => $rooms->perPage(),
                'total'        => $rooms->total(),
                'last_page'    => $rooms->lastPage(),
                'from'         => $rooms->firstItem(),
                'to'           => $rooms->lastItem(),
            ],
        ]);
    }


    public function storeRoomVisit(Request $request)
    {
        $user = Auth::user();
        $roomId = $request->room_id;

        RoomVisit::updateOrCreate(
            [
                'user_id' => $user->id,
                'room_id' => $roomId,
            ],
            [
                'last_visited_at' => now(),
            ]
        );

        return response()->json([
            'status' => true,
            'message' => 'Room visit stored'
        ]);
    }

    public function getRoomVisitedList()
    {
        $user = Auth::user();

        $followedRoomIds = DB::table('room_follows')
            ->where('user_id', $user->id)
            ->pluck('room_id')
            ->toArray();

        $joinedRoomIds = DB::table('room_members')
            ->where('user_id', $user->id)
            ->whereNull('left_at')
            ->pluck('room_id')
            ->toArray();

        $visits = RoomVisit::where('user_id', $user->id)
            ->orderByDesc('last_visited_at')
            ->with([
                'room' => function ($q) {
                    $q->with(
                        'user:id,uid,name,image,country,active_uid_id',
                        'user.countryData:id,name,iso'
                    )->withCount('onlineUsers as online_count');
                }
            ])
            ->paginate(10);

        $visits->getCollection()->transform(function ($visit) use ($followedRoomIds, $joinedRoomIds) {

            $room = $visit->room;

            if (!$room) {
                return null;
            }

            if ($room->room_image) {
                $room->room_image = Helper::showImage($room->room_image, true);
            }

            if ($room->user && $room->user->image) {
                $room->user->image = Helper::showImage($room->user->image, true);
            }

            if ($room->user && $room->user->countryData && $room->user->countryData->iso) {
                $room->user->flag =
                    'https://flagcdn.com/w40/' .
                    strtolower($room->user->countryData->iso) .
                    '.png';
            } else if ($room->user) {
                $room->user->flag = null;
            }

            $room->online_count = (int) $room->online_count;

            $room->last_visited_at = $visit->last_visited_at;

            if ($room->user) {

                // Default System UID
                $displayUid = $room->user->uid;
                $uidBadgeColor = null;

                $room->user->nickname_meta = Helper::getNicknameMeta($room->user->id);

                // Premium UID
                $premiumUid = PremiumNumber::where('user_id', $room->user->id)
                    ->where('end_at', '>', now())
                    ->latest()
                    ->first();

                if ($premiumUid) {

                    $displayUid = $premiumUid->premium_number;
                    $uidBadgeColor = '#fcd01c';
                } else {

                    // Store UID
                    if ($room->user->active_uid_id) {

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
                                $displayUid = $storeUid->unique_id;
                                $uidBadgeColor = $storeUid->rank_badge_color ?? null;
                            }
                        }
                    }
                }

                $room->user->uid = $displayUid;
                $room->user->uid_badge_color = $uidBadgeColor;
            }

            $room->is_follow = in_array($room->id, $followedRoomIds);
            $room->is_joined = in_array($room->id, $joinedRoomIds);

            return $room;
        });

        $visits->setCollection(
            $visits->getCollection()->filter()->values()
        );

        return response()->json([
            'status'  => true,
            'message' => 'Visited Rooms Fetched Successfully',
            'data'    => $visits,
            'pagination' => [
                'current_page' => $visits->currentPage(),
                'per_page'     => $visits->perPage(),
                'total'        => $visits->total(),
                'last_page'    => $visits->lastPage(),
                'from'         => $visits->firstItem(),
                'to'           => $visits->lastItem(),
            ],
        ]);
    }

    public function followRoom(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'room_id' => 'required|exists:rooms,id'
        ]);

        RoomFollow::firstOrCreate([
            'user_id' => $user->id,
            'room_id' => $request->room_id,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Room followed'
        ]);
    }

    public function unfollowRoom(Request $request)
    {
        $user = Auth::user();

        RoomFollow::where([
            'user_id' => $user->id,
            'room_id' => $request->room_id,
        ])->delete();

        return response()->json([
            'status' => true,
            'message' => 'Room unfollowed'
        ]);
    }


    public function getFollowingRoomList()
    {
        $user = Auth::user();

        $joinedRoomIds = DB::table('room_members')
            ->where('user_id', $user->id)
            ->whereNull('left_at')
            ->pluck('room_id')
            ->toArray();

        $follows = RoomFollow::where('user_id', $user->id)
            ->with([
                'room' => function ($q) {
                    $q->with(
                        'user:id,uid,name,image,country,active_uid_id',
                        'user.countryData:id,name,iso'
                    )->withCount('onlineUsers as online_count');
                }
            ])
            ->orderByDesc('created_at')
            ->paginate(10);

        $follows->getCollection()->transform(function ($follow) use ($joinedRoomIds) {

            $room = $follow->room;

            if (!$room) {
                return null;
            }

            if ($room->room_image) {
                $room->room_image = Helper::showImage($room->room_image, true);
            }

            if ($room->user && $room->user->image) {
                $room->user->image = Helper::showImage($room->user->image, true);
            }

            if ($room->user && $room->user->countryData && $room->user->countryData->iso) {
                $room->user->flag =
                    'https://flagcdn.com/w40/' .
                    strtolower($room->user->countryData->iso) .
                    '.png';
            } else if ($room->user) {
                $room->user->flag = null;
            }

            $room->online_count = (int) $room->online_count;

            if ($room->user) {

                // Default System UID
                $displayUid = $room->user->uid;
                $uidBadgeColor = null;

                $room->user->nickname_meta = Helper::getNicknameMeta($room->user->id);

                // Premium UID
                $premiumUid = PremiumNumber::where('user_id', $room->user->id)
                    ->where('end_at', '>', now())
                    ->latest()
                    ->first();

                if ($premiumUid) {

                    $displayUid = $premiumUid->premium_number;
                    $uidBadgeColor = '#fcd01c';
                } else {

                    // Store UID
                    if ($room->user->active_uid_id) {

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
                                $displayUid = $storeUid->unique_id;
                                $uidBadgeColor = $storeUid->rank_badge_color ?? null;
                            }
                        }
                    }
                }

                $room->user->uid = $displayUid;
                $room->user->uid_badge_color = $uidBadgeColor;
            }

            $room->followed_at = $follow->created_at;

            $room->is_follow = true;
            $room->is_joined = in_array($room->id, $joinedRoomIds);

            return $room;
        });

        $follows->setCollection(
            $follows->getCollection()->filter()->values()
        );

        return response()->json([
            'status'  => true,
            'message' => 'Following Rooms Fetched Successfully',
            'data'    => $follows,
            'pagination' => [
                'current_page' => $follows->currentPage(),
                'per_page'     => $follows->perPage(),
                'total'        => $follows->total(),
                'last_page'    => $follows->lastPage(),
                'from'         => $follows->firstItem(),
                'to'           => $follows->lastItem(),
            ],
        ]);
    }



    public function joinRoom(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'room_id' => 'required|exists:rooms,id'
        ]);

        $room = Room::find($request->room_id);

        if (!$room) {
            return response()->json([
                'status' => false,
                'message' => 'Room not found'
            ], 404);
        }

        $existing = RoomMember::where([
            'user_id' => $user->id,
            'room_id' => $request->room_id,
            'left_at' => null
        ])->first();

        if ($existing) {
            return response()->json([
                'status' => false,
                'message' => 'Already joined'
            ], 422);
        }

        $currentMembers = RoomMember::where('room_id', $request->room_id)
            ->whereNull('left_at')
            ->count();

        if ($currentMembers >= $room->member_limit) {

            return response()->json([
                'status' => false,
                'message' => "Room is full. Maximum {$room->member_limit} members allowed."
            ], 422);
        }

        RoomMember::create([
            'user_id' => $user->id,
            'room_id' => $request->room_id,
            'joined_at' => now(),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Joined room'
        ]);
    }


    public function unjoinRoom(Request $request)
    {
        $user = Auth::user();

        RoomMember::where([
            'user_id' => $user->id,
            'room_id' => $request->room_id,
            'left_at' => null
        ])->update([
            'left_at' => now()
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Left room'
        ]);
    }


    public function getJoinedRoomsList()
    {
        $user = Auth::user();

        $followedRoomIds = DB::table('room_follows')
            ->where('user_id', $user->id)
            ->pluck('room_id')
            ->toArray();

        $memberships = RoomMember::where('user_id', $user->id)
            ->whereNull('left_at')
            ->orderByDesc('joined_at')
            ->with([
                'room' => function ($q) {
                    $q->with(
                        'user:id,uid,name,image,country,active_uid_id',
                        'user.countryData:id,name,iso'
                    )->withCount('onlineUsers as online_count');
                }
            ])
            ->paginate(10);

        $memberships->getCollection()->transform(function ($member) use ($followedRoomIds) {

            $room = $member->room;

            if (!$room) {
                return null;
            }

            if ($room->room_image) {
                $room->room_image = Helper::showImage($room->room_image, true);
            }

            if ($room->user && $room->user->image) {
                $room->user->image = Helper::showImage($room->user->image, true);
            }

            if ($room->user && $room->user->countryData && $room->user->countryData->iso) {
                $room->user->flag =
                    'https://flagcdn.com/w40/' .
                    strtolower($room->user->countryData->iso) .
                    '.png';
            } else if ($room->user) {
                $room->user->flag = null;
            }

            $room->online_count = (int) $room->online_count;

            if ($room->user) {

                // Default System UID
                $displayUid = $room->user->uid;
                $uidBadgeColor = null;

                $room->user->nickname_meta = Helper::getNicknameMeta($room->user->id);

                // Premium UID
                $premiumUid = PremiumNumber::where('user_id', $room->user->id)
                    ->where('end_at', '>', now())
                    ->latest()
                    ->first();

                if ($premiumUid) {

                    $displayUid = $premiumUid->premium_number;
                    $uidBadgeColor = '#fcd01c';
                } else {

                    // Store UID
                    if ($room->user->active_uid_id) {

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
                                $displayUid = $storeUid->unique_id;
                                $uidBadgeColor = $storeUid->rank_badge_color ?? null;
                            }
                        }
                    }
                }

                $room->user->uid = $displayUid;
                $room->user->uid_badge_color = $uidBadgeColor;
            }
            $room->joined_at = $member->joined_at;

            $room->is_joined = true;
            $room->is_follow = in_array($room->id, $followedRoomIds);

            return $room;
        });

        $memberships->setCollection(
            $memberships->getCollection()->filter()->values()
        );

        return response()->json([
            'status'  => true,
            'message' => 'Joined Rooms Fetched Successfully',
            'data'    => $memberships,
            'pagination' => [
                'current_page' => $memberships->currentPage(),
                'per_page'     => $memberships->perPage(),
                'total'        => $memberships->total(),
                'last_page'    => $memberships->lastPage(),
                'from'         => $memberships->firstItem(),
                'to'           => $memberships->lastItem(),
            ],
        ]);
    }


    public function sendBroadcast(Request $request)
    {
        $user = Auth::user();

        $validate = Validator::make($request->all(), [
            'message' => 'required|string|max:200',
        ]);

        if ($validate->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validate->errors()
            ]);
        }

        $regionCode = strtoupper($user->country);

        $price = BroadcastPrice::where('region_code', $regionCode)
            ->where('status', 1)
            ->first();

        if (!$price) {
            return response()->json([
                'status' => false,
                'message' => 'Broadcast not available in your region'
            ], 422);
        }

        DB::transaction(function () use ($user, $request, $price) {

            $lockedUser = AppUser::where('id', $user->id)
                ->lockForUpdate()
                ->first();

            if ($lockedUser->total_points < $price->price) {
                abort(422, 'Insufficient balance');
            }

            $lockedUser->decrement('total_points', $price->price);

            $broadcast = Broadcast::create([
                'user_id'     => $user->id,
                'message'     => $request->message,
                'cost'        => $price->price,
                'region_code' => $user->country,
            ]);

            event(new BroadcastMessageSent($user, $request->message));
        });

        return response()->json([
            'status' => true,
            'message' => 'Broadcast sent successfully'
        ]);
    }

    public function listBroadcasts()
    {
        $user = Auth::user();

        $broadcasts = Broadcast::with('user:id,name,image')
            ->where('region_code', $user->country)
            ->latest()
            ->get();

        $broadcasts->each(function ($item) {
            if ($item->user && $item->user->image) {
                if (Str::startsWith($item->user->image, ['http://', 'https://'])) {
                    $item->user->image = $item->user->image;
                } else {
                    $item->user->image = Helper::showImage($item->user->image, true);
                }
            }
        });

        return response()->json([
            'status' => true,
            'data'   => $broadcasts
        ]);
    }

    public function broadcastPrice()
    {
        $user = Auth::user();

        if (!$user || !$user->country) {
            return response()->json([
                'status'  => false,
                'message' => 'User region not found',
                'data'    => null
            ], 400);
        }

        $userRegion = strtoupper($user->country);

        $bPrice = BroadcastPrice::where('region_code', $userRegion)->first();

        if (!$bPrice) {
            return response()->json([
                'status'  => false,
                'message' => 'Broadcast price not available for this region',
                'data'    => null
            ], 404);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Data fetched successfully',
            'data'    => $bPrice
        ]);
    }


    public function search(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'keyword' => 'required|string|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        try {
            $keyword = trim($request->keyword);
            $authUser = Auth::user();
            $userCountry = strtolower(trim($authUser->country));

            $matchedUserIds = collect();

            // System UID
            $matchedUserIds = $matchedUserIds->merge(
                AppUser::where('uid', 'LIKE', "%{$keyword}%")
                    ->pluck('id')
            );

            // Premium UID
            $matchedUserIds = $matchedUserIds->merge(
                PremiumNumber::where('premium_number', 'LIKE', "%{$keyword}%")
                    ->where('end_at', '>', now())
                    ->pluck('user_id')
            );

            // Store UID
            $storeUidIds = StoreUids::where('unique_id', 'LIKE', "%{$keyword}%")
                ->pluck('id');

            if ($storeUidIds->count()) {

                $purchaseUserIds = DB::table('item_deliveries')
                    ->where('type', 'id')
                    ->whereIn('item_id', $storeUidIds)
                    ->where('end_at', '>', now())
                    ->pluck('recipient');

                $giftUserIds = DB::table('item_gift_transactions')
                    ->where('type', 'id')
                    ->whereIn('item_id', $storeUidIds)
                    ->where('end_at', '>', now())
                    ->pluck('receiver_id');

                $matchedUserIds = $matchedUserIds
                    ->merge($purchaseUserIds)
                    ->merge($giftUserIds);
            }

            $matchedUserIds = $matchedUserIds->unique()->toArray();

            $users = AppUser::query()
                ->with([
                    'countryData:id,name,iso'
                ])
                ->select('id', 'name', 'uid', 'image', 'country', 'gender', 'active_uid_id')
                ->whereRaw('LOWER(country) = ?', [$userCountry])
                // ->where(function ($query) use ($keyword) {
                //     $query->where('uid', $keyword)
                //         ->orWhere('uid', 'LIKE', "%{$keyword}%")
                //         ->orWhere('name', 'LIKE', "%{$keyword}%");
                // })
                ->where(function ($query) use ($keyword, $matchedUserIds) {

                    $query->whereIn('id', $matchedUserIds)
                        ->orWhere('name', 'LIKE', "%{$keyword}%");
                })
                ->orderByRaw("CASE WHEN uid = ? THEN 0 ELSE 1 END", [$keyword])
                ->limit(20)
                ->get()
                ->map(function ($user) {

                    if ($user->image) {
                        $user->image = Helper::showImage($user->image, true);
                    }

                    if ($user->countryData && $user->countryData->iso) {
                        $user->flag =
                            'https://flagcdn.com/w40/' .
                            strtolower($user->countryData->iso) .
                            '.png';
                    } else {
                        $user->flag = null;
                    }

                    $displayUid = $user->uid;
                    $uidBadgeColor = null;

                    // Premium UID
                    $premiumUid = PremiumNumber::where('user_id', $user->id)
                        ->where('end_at', '>', now())
                        ->latest()
                        ->first();

                    if ($premiumUid) {

                        $displayUid = $premiumUid->premium_number;
                        $uidBadgeColor = '#fcd01c';
                    } else {

                        // Store UID
                        if ($user->active_uid_id) {

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
                                    $uidBadgeColor = $storeUid->rank_badge_color ?? null;
                                }
                            }
                        }
                    }

                    return [
                        'id'      => $user->id,
                        'name'    => $user->name,
                        // 'uid'     => $user->uid,
                        'uid' => $displayUid,
                        'uid_badge_color' => $uidBadgeColor,
                        'gender'  => $user->gender,
                        'country' => $user->country,
                        'image'   => $user->image,
                        'flag'    => $user->flag,
                    ];
                })
                ->values();

            $rooms = Room::query()
                ->with([
                    'user:id,name,uid,image,country,gender,active_uid_id',
                    'user.countryData:id,name,iso',
                ])
                ->select(
                    'id',
                    'user_id',
                    'room_id',
                    'is_locked'
                )
                ->whereHas('user', function ($query) use ($keyword, $userCountry, $matchedUserIds) {
                    $query->whereRaw('LOWER(country) = ?', [$userCountry])
                        // ->where(function ($q) use ($keyword) {

                        //     $q->where('uid', $keyword)
                        //         ->orWhere('uid', 'LIKE', "%{$keyword}%")
                        //         ->orWhere('name', 'LIKE', "%{$keyword}%");
                        // });

                        ->where(function ($q) use ($keyword, $matchedUserIds) {

                            $q->whereIn('id', $matchedUserIds)
                                ->orWhere('name', 'LIKE', "%{$keyword}%");
                        });
                })
                ->limit(20)
                ->get()
                ->map(function ($room) {

                    if ($room->user && $room->user->image) {
                        $room->user->image = Helper::showImage($room->user->image, true);
                    }

                    if (
                        $room->user &&
                        $room->user->countryData &&
                        $room->user->countryData->iso
                    ) {
                        $room->user->flag =
                            'https://flagcdn.com/w40/' .
                            strtolower($room->user->countryData->iso) .
                            '.png';
                    } else {
                        $room->user->flag = null;
                    }
                    $displayUid = $room->user->uid;
                    $uidBadgeColor = null;

                    // Premium UID
                    $premiumUid = PremiumNumber::where('user_id', $room->user->id)
                        ->where('end_at', '>', now())
                        ->latest()
                        ->first();

                    if ($premiumUid) {

                        $displayUid = $premiumUid->premium_number;
                        $uidBadgeColor = '#fcd01c';
                    } else {

                        if ($room->user->active_uid_id) {

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
                                    $displayUid = $storeUid->unique_id;
                                    $uidBadgeColor = $storeUid->rank_badge_color ?? null;
                                }
                            }
                        }
                    }
                    return [
                        'id'        => $room->id,
                        'room_id'   => $room->room_id,
                        'is_locked' => (int) $room->is_locked,
                        'user'      => $room->user ? [
                            'id'      => $room->user->id,
                            'name'    => $room->user->name,
                            // 'uid'     => $room->user->uid,
                            'uid' => $displayUid,
                            'uid_badge_color' => $uidBadgeColor,
                            'gender'  => $room->user->gender,
                            'country' => $room->user->country,
                            'image'   => $room->user->image,
                            'flag'    => $room->user->flag,
                        ] : null,
                    ];
                })
                ->values();

            return response()->json([
                'status'  => true,
                'message' => 'Search data fetched successfully',
                'data'    => [
                    'keyword' => $keyword,
                    'users'   => $users,
                    'rooms'   => $rooms,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function getRules($type)
    {
        $rules = AppRule::where('type', $type)
            ->where('status', 1)
            ->latest()
            ->get();

        if ($rules->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No rules found',
                'data' => []
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Rules fetched successfully',
            'data' => $rules->map(function ($rule) {
                return [
                    'id' => $rule->id,
                    'heading' => $rule->heading,
                    'type' => $rule->type,
                    'rule' => $rule->rule,
                ];
            })
        ]);
    }


    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'report_type' => 'required|in:user,room',
            'reported_id' => 'required|integer',
            'reason'      => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'status'  => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        if ($request->report_type === 'user') {
            $exists = AppUser::where('id', $request->reported_id)->exists();

            if (!$exists) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Reported user not found',
                ], 404);
            }

            if ((int) $request->reported_id === (int) $user->id) {
                return response()->json([
                    'status'  => false,
                    'message' => 'You cannot report yourself',
                ], 400);
            }
        }

        if ($request->report_type === 'room') {
            $exists = Room::where('id', $request->reported_id)->exists();

            if (!$exists) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Reported room not found',
                ], 404);
            }
        }

        $report = Report::create([
            'reporter_id'  => $user->id,
            'report_type'  => $request->report_type,
            'reported_id'  => $request->reported_id,
            'reason'       => $request->reason,
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Report submitted successfully',
            'data'    => $report,
        ]);
    }

    public function webhookHandle(Request $request)
    {
        try {

            \Log::info('================ PUSHER WEBHOOK START ================');

            \Log::info('Full Webhook Payload', [
                'payload' => $request->all()
            ]);

            if (empty($request->events)) {

                \Log::warning('No events found in webhook');

                return response()->json([
                    'status' => false,
                    'message' => 'No events found'
                ]);
            }

            foreach ($request->events as $event) {

                DB::beginTransaction();

                try {

                    \Log::info('================ EVENT START ================');

                    \Log::info('Webhook Event Received', [
                        'event_name' => $event['name'] ?? null,
                        'channel' => $event['channel'] ?? null,
                        'user_id' => $event['user_id'] ?? null,
                        'socket_id' => $event['socket_id'] ?? null,
                    ]);

                    //   Only Handle member_removed


                    if (($event['name'] ?? null) !== 'member_removed') {

                        \Log::info('Skipping event because it is not member_removed', [
                            'received_event' => $event['name'] ?? null
                        ]);

                        DB::commit();

                        continue;
                    }

                    // Channel


                    $channel = $event['channel'] ?? null;

                    \Log::info('Webhook Channel', [
                        'channel' => $channel
                    ]);

                    if (!$channel) {

                        \Log::warning('Channel missing in webhook');

                        DB::rollBack();

                        continue;
                    }

                    //   Room ID


                    $roomId = (int) str_replace(
                        'presence-room-online.',
                        '',
                        $channel
                    );

                    \Log::info('Extracted Room ID', [
                        'room_id' => $roomId
                    ]);

                    if (!$roomId) {

                        \Log::warning('Invalid room id extracted', [
                            'channel' => $channel
                        ]);

                        DB::rollBack();

                        continue;
                    }

                    //   User ID

                    $userId = (int) ($event['user_id'] ?? 0);

                    \Log::info('Extracted User ID', [
                        'user_id' => $userId
                    ]);

                    if (!$userId) {

                        \Log::warning('Invalid user id in webhook');

                        DB::rollBack();

                        continue;
                    }

                    \Log::info(
                        "User {$userId} disconnected from room {$roomId}"
                    );

                    /*
                |--------------------------------------------------------------------------
                | Find User
                |--------------------------------------------------------------------------
                */

                    $user = \App\Models\AppUser::find($userId);

                    \Log::info('User Fetch Result', [
                        'user_found' => $user ? true : false,
                        'user_name' => $user->name ?? null,
                    ]);

                    if (!$user) {

                        \Log::warning('User not found');

                        DB::rollBack();

                        continue;
                    }

                    /*
                |--------------------------------------------------------------------------
                | Find User Seat
                |--------------------------------------------------------------------------
                */

                    $mySeat = \App\Models\RoomSeat::where('room_id', $roomId)
                        ->where('user_id', $userId)
                        ->first();

                    \Log::info('Seat Fetch Result', [
                        'seat_found' => $mySeat ? true : false,
                        'seat_no' => $mySeat->seat_no ?? null,
                        'is_on_mic' => $mySeat->is_on_mic ?? null,
                    ]);

                    $oldSeatNo = $mySeat->seat_no ?? null;

                    $oldIsOnMic = $mySeat->is_on_mic ?? 0;

                    /*
                |--------------------------------------------------------------------------
                | Agora Active Players
                |--------------------------------------------------------------------------
                */

                    $activePlayers = \App\Models\RoomMusicActivePlayer::where('room_id', $roomId)
                        ->where('started_by', $userId)
                        ->where('is_active', true)
                        ->whereIn('status', ['playing', 'paused'])
                        ->lockForUpdate()
                        ->get();

                    \Log::info('Active Players Count', [
                        'count' => $activePlayers->count()
                    ]);

                    /** @var \App\Services\AgoraCloudPlayerService $cloudPlayerService */
                    $cloudPlayerService = app(
                        \App\Services\AgoraCloudPlayerService::class
                    );

                    foreach ($activePlayers as $activePlayer) {

                        \Log::info('Processing Active Player', [
                            'active_player_id' => $activePlayer->id,
                            'agora_player_id' => $activePlayer->agora_player_id,
                            'status' => $activePlayer->status,
                        ]);

                        if (!empty($activePlayer->agora_player_id)) {

                            try {

                                $deleteResponse = $cloudPlayerService->deletePlayer(
                                    $activePlayer->agora_player_id
                                );

                                \Log::info('Agora Player Deleted', [
                                    'response' => $deleteResponse
                                ]);
                            } catch (\Throwable $e) {

                                \Log::warning(
                                    'Failed to delete Agora player',
                                    [
                                        'error' => $e->getMessage()
                                    ]
                                );
                            }
                        }

                        $activePlayer->update([
                            'status' => 'stopped',
                            'is_active' => false,
                            'started_at' => null,
                        ]);

                        \Log::info('Active Player Updated To Stopped', [
                            'active_player_id' => $activePlayer->id
                        ]);
                    }

                    /*
                |--------------------------------------------------------------------------
                | Remove Room Seat
                |--------------------------------------------------------------------------
                */

                    $seatDeleted = \App\Models\RoomSeat::where('room_id', $roomId)
                        ->where('user_id', $userId)
                        ->delete();

                    \Log::info('Room Seat Deleted', [
                        'deleted_rows' => $seatDeleted
                    ]);

                    /*
                |--------------------------------------------------------------------------
                | Remove Presence
                |--------------------------------------------------------------------------
                */

                    $presenceDeleted = \App\Models\RoomPresence::where([
                        'room_id' => $roomId,
                        'user_id' => $userId,
                    ])->delete();

                    \Log::info('Room Presence Deleted', [
                        'deleted_rows' => $presenceDeleted
                    ]);

                    /*
                |--------------------------------------------------------------------------
                | Online Count
                |--------------------------------------------------------------------------
                */

                    $onlineCount = \App\Models\RoomPresence::where(
                        'room_id',
                        $roomId
                    )->count();

                    \Log::info('Updated Online Count', [
                        'online_count' => $onlineCount
                    ]);

                    /*
                |--------------------------------------------------------------------------
                | Current User Data
                |--------------------------------------------------------------------------
                */

                    $currentUser = [
                        'id' => $user->id,
                        'name' => $user->name,
                        'image' => !empty($user->image)
                            ? \App\Helper\Helper::showImage(
                                $user->image,
                                true
                            )
                            : null,
                        'seat_no' => $oldSeatNo,
                        'is_on_mic' => $oldIsOnMic,
                    ];

                    \Log::info('Current User Payload', $currentUser);

                    /*
                |--------------------------------------------------------------------------
                | Updated Seats
                |--------------------------------------------------------------------------
                */

                    $seats = app(
                        \App\Http\Controllers\Api\RoomController::class
                    )->getRoomSeats($roomId);

                    \Log::info('Updated Seats Generated');

                    DB::commit();

                    \Log::info('Database Transaction Committed');

                    /*
                |--------------------------------------------------------------------------
                | Broadcast Events
                |--------------------------------------------------------------------------
                */

                    broadcast(
                        new \App\Events\RoomPresenceUpdated(
                            $roomId,
                            $onlineCount,
                            [],
                            'leave',
                            $currentUser
                        )
                    );

                    \Log::info('RoomPresenceUpdated Broadcasted');

                    broadcast(
                        new \App\Events\RoomSeatUpdated(
                            $roomId,
                            'leave',
                            $seats,
                            $oldSeatNo,
                            $currentUser
                        )
                    );

                    \Log::info('RoomSeatUpdated Broadcasted');

                    \Log::info(
                        "User {$userId} auto removed from room {$roomId}"
                    );

                    \Log::info('================ EVENT END ================');
                } catch (\Throwable $e) {

                    DB::rollBack();

                    \Log::error('Webhook Event Error', [
                        'message' => $e->getMessage(),
                        'line' => $e->getLine(),
                        'file' => $e->getFile(),
                    ]);
                }
            }

            \Log::info('================ PUSHER WEBHOOK END ================');

            return response()->json([
                'status' => true
            ]);
        } catch (\Throwable $e) {

            \Log::error('Webhook Fatal Error', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
