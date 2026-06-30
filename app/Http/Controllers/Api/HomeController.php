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
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

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
            DB::raw('SUM(coin_value) as total_points')
        )
            ->whereBetween('created_at', [$from, $to])
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
            DB::raw('SUM(coin_value) as total_points')
        )
            ->whereBetween('created_at', [$from, $to])
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
                DB::raw('SUM(coin_value) as total_points')
            )
                ->whereNotNull('room_id')
                ->whereBetween('created_at', [$from, $to])
                ->whereHas('room', function ($q) use ($user) {
                    $q->where('country', $user->country);
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

        $followedRoomIds = DB::table('room_follows')
            ->where('user_id', $user->id)
            ->pluck('room_id')
            ->toArray();

        $joinedRoomIds = DB::table('room_members')
            ->where('user_id', $user->id)
            ->whereNull('left_at')
            ->pluck('room_id')
            ->toArray();

        $rooms = Room::with([
            'user:id,uid,name,image,country',
            'user.countryData:id,name,iso',
            'user.premium:user_id,premium_number,valid_days,created_at'
        ])
            ->withCount('onlineUsers as online_count')
            ->where('status', 1)
            ->where('country', $user->country)
            ->orderByDesc('total_points')
            // ->limit(100)
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
            'user:id,uid,name,image,country',
            'user.countryData:id,name,iso'
        )
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
                        'user:id,uid,name,image,country',
                        'user.countryData:id,name,iso'
                    )->withCount('onlineUsers as online_count');
                }
            ])
            ->get();

        $rooms = $visits->map(function ($visit) use ($followedRoomIds, $joinedRoomIds) {

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
            } else {
                $room->user->flag = null;
            }

            $room->online_count = (int) $room->online_count;

            $room->last_visited_at = $visit->last_visited_at;

            $room->is_follow = in_array($room->id, $followedRoomIds);
            $room->is_joined = in_array($room->id, $joinedRoomIds);

            return $room;
        })->filter()->values();

        return response()->json([
            'status'  => true,
            'message' => 'Visited Rooms Fetched Successfully',
            'data'    => $rooms
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
                        'user:id,uid,name,image,country',
                        'user.countryData:id,name,iso'
                    )->withCount('onlineUsers as online_count');
                }
            ])
            ->limit(100)
            ->get();

        $rooms = $follows->map(function ($follow) use ($joinedRoomIds) {

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

            $room->online_count = (int) $room->online_count;

            $room->followed_at = $follow->created_at;

            $room->is_follow = true;
            $room->is_joined = in_array($room->id, $joinedRoomIds);

            return $room;
        })->filter()->values();

        return response()->json([
            'status'  => true,
            'message' => 'Following Rooms Fetched Successfully',
            'data'    => $rooms
        ]);
    }


    public function joinRoom(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'room_id' => 'required|exists:rooms,id'
        ]);

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
                        'user:id,uid,name,image,country',
                        'user.countryData:id,name,iso'
                    )->withCount('onlineUsers as online_count');
                }
            ])
            ->get();

        $rooms = $memberships->map(function ($member) use ($followedRoomIds) {

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

            $room->online_count = (int) $room->online_count;

            $room->joined_at = $member->joined_at;

            $room->is_joined = true;
            $room->is_follow = in_array($room->id, $followedRoomIds);

            return $room;
        })->filter()->values();

        return response()->json([
            'status'  => true,
            'message' => 'Joined Rooms Fetched Successfully',
            'data'    => $rooms
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

            $users = AppUser::query()
                ->with([
                    'countryData:id,name,iso'
                ])
                ->select('id', 'name', 'uid', 'image', 'country', 'gender')
                ->where(function ($query) use ($keyword) {
                    $query->where('uid', $keyword)
                        ->orWhere('uid', 'LIKE', "%{$keyword}%")
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

                    return [
                        'id'      => $user->id,
                        'name'    => $user->name,
                        'uid'     => $user->uid,
                        'gender'  => $user->gender,
                        'country' => $user->country,
                        'image'   => $user->image,
                        'flag'    => $user->flag,
                    ];
                })
                ->values();

            $rooms = Room::query()
                ->with([
                    'user:id,name,uid,image,country,gender',
                    'user.countryData:id,name,iso',
                ])
                ->select(
                    'id',
                    'user_id',
                    'room_id',
                    'is_locked'
                )
                ->whereHas('user', function ($query) use ($keyword) {
                    $query->where('uid', $keyword)
                        ->orWhere('uid', 'LIKE', "%{$keyword}%")
                        ->orWhere('name', 'LIKE', "%{$keyword}%");
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

                    return [
                        'id'        => $room->id,
                        'room_id'   => $room->room_id,
                        'is_locked' => (int) $room->is_locked,
                        'user'      => $room->user ? [
                            'id'      => $room->user->id,
                            'name'    => $room->user->name,
                            'uid'     => $room->user->uid,
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
}
