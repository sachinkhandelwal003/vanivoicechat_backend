<?php

namespace App\Http\Controllers\Api;

use App\Helper\Helper;
use App\Http\Controllers\Controller;
use App\Models\AppUser;
use App\Models\Post;
use App\Models\ProfileVisitor;
use App\Models\UserFollow;
use App\Models\AdminAccount;
use App\Models\Agency;
use App\Models\BdUser;
use App\Models\Host;
use App\Models\CoinSeller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Carbon\Carbon;


class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware(["auth:api"]);
    }


    public function profileRegistration(Request $request)
    {
        $user = Auth::user();
        $validate = Validator::make($request->all(), [
            'gender' => 'required',
            'birthdate' => 'required',
            'country' => 'required',
            'image'     => 'nullable|image|mimes:jpg,jpeg,png,webp',
        ]);

        if ($validate->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validate->errors()
            ], 422);
        }

        $data = [
            'gender'    => $request->gender,
            'birthdate' => $request->birthdate,
            'country'   => $request->country,
        ];

        if ($request->hasFile('image')) {
            $data['image'] = Helper::saveFile($request->file('image'), 'profile_image');
        }

        $user->update($data);

        return response()->json([
            'status' => true,
            'message' => 'User Details Saved Successfully',
        ]);
    }

    // public function getUserDetails()
    // {
    //     $user = Auth::user();
    //     $userId = Auth::id();
    //     $image = null;

    //     if ($user->image) {
    //         if (Str::startsWith($user->image, ['http://', 'https://'])) {
    //             $image = $user->image;
    //         } else {
    //             $image = Helper::showImage($user->image, true);
    //         }
    //     }

    //     if (BdUser::where('user_id', $user->id)->where('status', 1)->exists()) {
    //         $roles = ['bd'];
    //     } else {

    //         if (AdminAccount::where('user_id', $user->id)->where('status', 1)->exists()) {
    //             $roles[] = 'admin';
    //         }

    //         if (Agency::where('user_id', $user->id)->where('status', 1)->exists()) {
    //             $roles[] = 'agency';
    //         }

    //         if (Host::where('user_id', $user->id)->where('status', 1)->exists()) {
    //             $roles[] = 'host';
    //         }

    //         // Coin Seller / Merchant logic
    //         $coinSeller = CoinSeller::where('user_id', $user->id)
    //             ->where('status', 1)
    //             ->first();

    //         if ($coinSeller) {
    //             if ($coinSeller->is_merchant == 1) {
    //                 $roles[] = 'merchant';
    //             } else {
    //                 $roles[] = 'coinseller';
    //             }
    //         }

    //         if (empty($roles)) {
    //             $roles[] = 'user';
    //         }
    //     }

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'User Details fethed Successfuly',
    //         'data' =>  [
    //             'id' => $user->id,
    //             'uid' => $user->uid,
    //             'name' => $user->name,
    //             'email' => $user->email,
    //             'gender' => $user->gender,
    //             'image' => $image,
    //             'following' => DB::table('user_follows')->where('follower_id', $userId)->count(),
    //             'fans' => DB::table('user_follows')->where('following_id', $userId)->count(),
    //             'visitors'  => DB::table('profile_visitors')->where('user_id', $userId)->count(),
    //             'user_roles' => $roles
    //         ]
    //     ]);
    // }



    public function getUserDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'nullable|integer|exists:app_users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        try {
            $authUser = Auth::user();

            if (!$authUser) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $userId = $request->filled('user_id')
                ? (int) $request->user_id
                : (int) $authUser->id;

            $user = AppUser::find($userId);

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found'
                ], 404);
            }

            $image = null;

            if ($user->image) {
                if (Str::startsWith($user->image, ['http://', 'https://'])) {
                    $image = $user->image;
                } else {
                    $image = Helper::showImage($user->image, true);
                }
            }

            $roles = [];

            if (BdUser::where('user_id', $user->id)->where('status', 1)->exists()) {
                $roles = ['bd'];
            } else {
                if (AdminAccount::where('user_id', $user->id)->where('status', 1)->exists()) {
                    $roles[] = 'admin';
                }

                if (Agency::where('user_id', $user->id)->where('status', 1)->exists()) {
                    $roles[] = 'agency';
                }

                if (Host::where('user_id', $user->id)->where('status', 1)->exists()) {
                    $roles[] = 'host';
                }

                $coinSeller = CoinSeller::where('user_id', $user->id)
                    ->where('status', 1)
                    ->first();

                if ($coinSeller) {
                    if ((int) $coinSeller->is_merchant === 1) {
                        $roles[] = 'merchant';
                    } else {
                        $roles[] = 'coinseller';
                    }
                }

                if (empty($roles)) {
                    $roles[] = 'user';
                }
            }

            $isOwnProfile = (int) $authUser->id === (int) $userId;

            // Auth user ne opened profile user ko follow kiya hai ya nahi
            $isFollowing = false;

            // Auth user aur opened profile user friends hain ya nahi
            $isFriend = false;

            if (!$isOwnProfile) {
                $isFollowing = DB::table('user_follows')
                    ->where('follower_id', $authUser->id)
                    ->where('following_id', $userId)
                    ->exists();

                $isFriend = DB::table('friendships')
                    ->where('status', 'accepted')
                    ->where(function ($q) use ($authUser, $userId) {
                        $q->where(function ($sub) use ($authUser, $userId) {
                            $sub->where('user_one', $authUser->id)
                                ->where('user_two', $userId);
                        })
                            ->orWhere(function ($sub) use ($authUser, $userId) {
                                $sub->where('user_one', $userId)
                                    ->where('user_two', $authUser->id);
                            });
                    })
                    ->exists();
            }

            return response()->json([
                'status' => true,
                'message' => 'User Details fethed Successfuly',
                'data' =>  [
                    'is_own_profile' => $isOwnProfile,

                    'id' => $user->id,
                    'uid' => $user->uid,
                    'name' => $user->name,
                    'email' => $user->email,
                    'gender' => $user->gender,
                    'image' => $image,

                    'following' => DB::table('user_follows')
                        ->where('follower_id', $userId)
                        ->count(),

                    'fans' => DB::table('user_follows')
                        ->where('following_id', $userId)
                        ->count(),

                    'visitors' => DB::table('profile_visitors')
                        ->where('user_id', $userId)
                        ->count(),

                    'user_roles' => $roles,

                    'is_following' => $isFollowing,
                    'is_friend' => $isFriend,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function isEmailBind()
    {
        $user = Auth::user();

        return response()->json([
            'status'  => true,
            'message' => 'Data Fetched Successfully',
            'email'   => $user->email,
            'is_bind' => !is_null($user->is_email_bind),
        ]);
    }


    public function followUser(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'user_id' => 'required|exists:app_users,id'
        ]);

        if ($validate->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validate->errors()
            ]);
        }

        $authUser = Auth::user();

        if ($authUser->id == $request->user_id) {
            return response()->json([
                'status' => false,
                'message' => 'You cannot follow yourself'
            ], 400);
        }

        $exists = UserFollow::where('follower_id', $authUser->id)
            ->where('following_id', $request->user_id)
            ->exists();

        if ($exists) {
            UserFollow::where('follower_id', $authUser->id)
                ->where('following_id', $request->user_id)
                ->delete();

            return response()->json([
                'status' => true,
                'message' => 'Unfollowed successfully'
            ]);
        }

        UserFollow::insert([
            'follower_id'  => $authUser->id,
            'following_id' => $request->user_id
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Followed successfully'
        ]);
    }

    public function myFollowing()
    {
        $userId = Auth::id();

        $following = UserFollow::with('user')
            ->where('follower_id', $userId)->get()
            ->map(function ($follow) {
                return [
                    'id'   => $follow->user->id,
                    'uid'  => $follow->user->uid ?? null,
                    'name' => $follow->user->name,
                    'gender' => $follow->user->gender,
                    'image' => $follow->user->image
                        ? Helper::showImage($follow->user->image, true)
                        : null,
                ];
            });

        return response()->json([
            'status' => true,
            'data'   => $following
        ]);
    }

    public function myFans()
    {
        $userId = Auth::id();

        $fans = UserFollow::with('fan')
            ->where('following_id', $userId)
            ->get()
            ->map(function ($fan) {
                return [
                    'id'   => $fan->fan->id,
                    'uid'  => $fan->fan->uid ?? null,
                    'name' => $fan->fan->name,
                    'gender' => $fan->fan->gender,
                    'image' => $fan->fan->image
                        ? Helper::showImage($fan->fan->image, true)
                        : null,
                ];
            });

        return response()->json([
            'status' => true,
            'data'   => $fans
        ]);
    }

    public function visitProfile(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'user_id' => 'required|exists:app_users,id'
        ]);

        if ($validate->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validate->errors()
            ], 422);
        }

        $visitorId = Auth::id();

        if ($visitorId == $request->user_id) {
            return response()->json([
                'status' => true,
                'message' => 'Self visit ignored'
            ]);
        }

        ProfileVisitor::updateOrInsert(
            [
                'visitor_id' => $visitorId,
                'user_id'    => $request->user_id
            ],
            [
                'created_at' => now(),
                'updated_at' => now()
            ]
        );

        return response()->json([
            'status' => true,
            'message' => 'Profile visit recorded'
        ]);
    }

    public function profileVisitors()
    {
        $userId = Auth::id();

        $visitors = ProfileVisitor::with('visitor')
            ->where('user_id', $userId)
            ->orderByDesc('updated_at')
            ->get()
            ->map(function ($visit) {
                return [
                    'id'   => $visit->visitor->id,
                    'uid'  => $visit->visitor->uid ?? null,
                    'name' => $visit->visitor->name,
                    'gender' => $visit->visitor->gender,
                    'image' => $visit->visitor->image
                        ? Helper::showImage($visit->visitor->image, true)
                        : null,
                ];
            });
        return response()->json([
            'status' => true,
            'data'   => $visitors
        ]);
    }

    public function profileStats()
    {
        $userId = Auth::id();

        return response()->json([
            'status'    => true,
            'following' => DB::table('user_follows')->where('follower_id', $userId)->count(),
            'fans' => DB::table('user_follows')->where('following_id', $userId)->count(),
            'visitors'  => DB::table('profile_visitors')->where('user_id', $userId)->count(),
        ]);
    }

    // public function getUserPosts(Request $request)
    // {
    //     try {
    //         $userId = auth()->id();

    //         $posts = Post::where('user_id', $userId)
    //             ->with(['topic', 'media'])
    //             ->orderBy('created_at', 'desc')
    //             ->get()
    //             ->map(function ($post) {

    //                 $post->topic_name = $post->topic->name ?? null;
    //                 unset($post->topic);

    //                 if ($post->media) {
    //                     foreach ($post->media as $media) {
    //                         $media->file_path = Helper::showImage($media->file_path ?? null, true);
    //                     }
    //                 }

    //                 return $post;
    //             });

    //         return response()->json([
    //             'status' => true,
    //             'message' => 'Posts fetched successfully',
    //             'data' => $posts
    //         ], 200);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => $e->getMessage()
    //         ], 500);
    //     }
    // }



    public function getUserPosts(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'nullable|integer|exists:app_users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        try {
            $authUser = Auth::user();

            if (!$authUser) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

            // user_id aaye to other user posts, warna auth user posts
            $userId = $request->filled('user_id')
                ? (int) $request->user_id
                : (int) $authUser->id;

            $posts = Post::where('user_id', $userId)
                ->with(['topic', 'media'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($post) {
                    $post->topic_name = $post->topic->name ?? null;
                    unset($post->topic);

                    if ($post->media) {
                        foreach ($post->media as $media) {
                            $media->file_path = Helper::showImage($media->file_path ?? null, true);
                        }
                    }

                    return $post;
                });

            return response()->json([
                'status' => true,
                'message' => 'Posts fetched successfully',
                'data' => [
                    'is_own_profile' => (int) $authUser->id === (int) $userId,
                    'user_id' => $userId,
                    'posts' => $posts
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }


    public function uploadAlbum(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,gif,mp4|max:20240'
        ]);

        $user = Auth::user();

        try {
            $path = Helper::saveFile($request->file('file'), 'album');

            $album = DB::table('user_albums')->insertGetId([
                'app_user_id' => $user->id,
                'file' => $path,
                'file_type' => $request->file('file')->getClientMimeType(),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Uploaded successfully',
                'data' => [
                    'id' => $album,
                    'file' => Helper::showImage($path, true)
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // public function getMyAlbum()
    // {
    //     $user = Auth::user();

    //     $album = DB::table('user_albums')
    //         ->where('app_user_id', $user->id)
    //         ->whereNull('deleted_at')
    //         ->latest()
    //         ->get()
    //         ->map(function ($item) {
    //             return [
    //                 'id' => $item->id,
    //                 'file' => Helper::showImage($item->file, true),
    //                 'type' => $item->file_type,
    //                 'created_at' => $item->created_at
    //             ];
    //         });

    //     return response()->json([
    //         'status' => true,
    //         'data' => $album
    //     ]);
    // }



    public function getMyAlbum(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'nullable|integer|exists:app_users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        try {
            $authUser = Auth::user();

            if (!$authUser) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

            // user_id aaye to other user album, warna auth user album
            $userId = $request->filled('user_id')
                ? (int) $request->user_id
                : (int) $authUser->id;

            $album = DB::table('user_albums')
                ->where('app_user_id', $userId)
                ->whereNull('deleted_at')
                ->latest()
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'file' => Helper::showImage($item->file, true),
                        'type' => $item->file_type,
                        'created_at' => $item->created_at
                    ];
                });

            return response()->json([
                'status' => true,
                'data' => [
                    'is_own_profile' => (int) $authUser->id === (int) $userId,
                    'user_id' => $userId,
                    'album' => $album
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }


    public function deleteAlbum($id)
    {
        $user = Auth::user();

        $album = DB::table('user_albums')
            ->where('id', $id)
            ->where('app_user_id', $user->id)
            ->first();

        if (!$album) {
            return response()->json([
                'status' => false,
                'message' => 'Album not found'
            ], 404);
        }

        DB::table('user_albums')
            ->where('id', $id)
            ->update([
                'deleted_at' => now()
            ]);

        return response()->json([
            'status' => true,
            'message' => 'Deleted successfully'
        ]);
    }

    // public function getProfileOverview(Request $request)
    // {
    //     try {
    //         $user = Auth::user();

    //         // MEDAL (LEVEL)
    //         $level = DB::table('user_level')
    //             ->where('grade', $user->user_level)
    //             ->first();

    //         $medal = $level ? [
    //             'id' => $level->id,
    //             'name' => $level->name,
    //             'icon' => Helper::showImage($level->icon, true),
    //             'avatar_corner' => Helper::showImage($level->avatar_corner, true),
    //         ] : null;

    //         // FRAMES
    //         $frameIds = DB::table('item_gift_transactions')
    //             ->where('receiver_id', $user->id)
    //             ->where('type', 'frame')
    //             ->pluck('item_id')
    //             ->unique();

    //         $frames = DB::table('frames')
    //             ->whereIn('id', $frameIds)
    //             ->get()
    //             ->map(function ($f) {
    //                 return [
    //                     'id' => $f->id,
    //                     'name' => $f->name,
    //                     'icon' => Helper::showImage($f->icon, true),
    //                     'gif' => Helper::showImage($f->gif, true),
    //                 ];
    //             });

    //         // VEHICLES (car type)
    //         $vehicleIds = DB::table('item_gift_transactions')
    //             ->where('receiver_id', $user->id)
    //             ->where('type', 'car')
    //             ->pluck('item_id')
    //             ->unique();

    //         $vehicles = DB::table('cars')
    //             ->whereIn('id', $vehicleIds)
    //             ->get()
    //             ->map(function ($v) {
    //                 return [
    //                     'id' => $v->id,
    //                     'name' => $v->name,
    //                     'icon' => Helper::showImage($v->icon, true),
    //                     'gif' => Helper::showImage($v->gif ?? null, true),
    //                 ];
    //             });

    //         // GUARDIANS
    //         $guardians = DB::table('item_gift_transactions')
    //             ->select('sender_id', DB::raw('SUM(total_coins) as total'))
    //             ->where('receiver_id', $user->id)
    //             ->groupBy('sender_id')
    //             ->orderByDesc('total')
    //             ->limit(3)
    //             ->get()
    //             ->map(function ($g) {
    //                 $u = DB::table('app_users')->where('id', $g->sender_id)->first();

    //                 return [
    //                     'id' => $u->id ?? null,
    //                     'name' => $u->name ?? null,
    //                     'image' => Helper::showImage($u->image ?? null, true),
    //                     'coins' => (int)$g->total
    //                 ];
    //             });

    //         // ALBUM
    //         $album = DB::table('user_albums')
    //             ->where('app_user_id', $user->id)
    //             ->whereNull('deleted_at')
    //             ->latest()
    //             ->limit(6)
    //             ->get()
    //             ->map(function ($m) {
    //                 return [
    //                     'id' => $m->id,
    //                     'file' => Helper::showImage($m->file, true),
    //                     'type' => $m->file_type
    //                 ];
    //             });

    //         // GIFTS
    //         $gifts = DB::table('item_gift_transactions')
    //             ->where('receiver_id', $user->id)
    //             ->latest()
    //             ->limit(5)
    //             ->get();

    //         return response()->json([
    //             'status' => true,
    //             'data' => [
    //                 'guardian' => $guardians,
    //                 'album' => $album,
    //                 'frames' => $frames,
    //                 'vehicles' => $vehicles,
    //                 'medal' => $medal,
    //                 'gifts' => $gifts
    //             ]
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => $e->getMessage()
    //         ], 500);
    //     }
    // }



    public function getProfileOverview(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'nullable|integer|exists:app_users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        try {
            $authUser = Auth::user();

            if (!$authUser) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

            // Agar user_id request me hai to other user, warna auth user
            $profileUserId = $request->filled('user_id')
                ? (int) $request->user_id
                : (int) $authUser->id;

            $user = DB::table('app_users')->where('id', $profileUserId)->first();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // MEDAL / LEVEL
            $level = DB::table('user_level')
                ->where('grade', $user->user_level)
                ->first();

            $medal = $level ? [
                'id' => $level->id,
                'name' => $level->name,
                'icon' => Helper::showImage($level->icon, true),
                'avatar_corner' => Helper::showImage($level->avatar_corner, true),
            ] : null;

            $now = now();

            // FRAMES - item_deliveries + item_gift_transactions
            $deliveryFrameIds = DB::table('item_deliveries')
                ->where('recipient', $profileUserId)
                ->where('type', 'frame')
                ->where(function ($q) use ($now) {
                    $q->whereNull('end_at')
                        ->orWhere('end_at', '>', $now);
                })
                ->pluck('item_id');

            $giftFrameIds = DB::table('item_gift_transactions')
                ->where('receiver_id', $profileUserId)
                ->where('type', 'frame')
                ->where(function ($q) use ($now) {
                    $q->whereNull('end_at')
                        ->orWhere('end_at', '>', $now);
                })
                ->pluck('item_id');

            $frameIds = $deliveryFrameIds
                ->merge($giftFrameIds)
                ->unique()
                ->values();

            $frames = DB::table('frames')
                ->whereIn('id', $frameIds)
                ->get()
                ->map(function ($f) {
                    return [
                        'id' => $f->id,
                        'name' => $f->name,
                        'icon' => Helper::showImage($f->icon, true),
                        'gif' => Helper::showImage($f->gif ?? null, true),
                    ];
                });


            // VEHICLES / CARS - item_deliveries + item_gift_transactions
            $deliveryVehicleIds = DB::table('item_deliveries')
                ->where('recipient', $profileUserId)
                ->where('type', 'entry')
                ->where(function ($q) use ($now) {
                    $q->whereNull('end_at')
                        ->orWhere('end_at', '>', $now);
                })
                ->pluck('item_id');

            $giftVehicleIds = DB::table('item_gift_transactions')
                ->where('receiver_id', $profileUserId)
                ->where('type', 'entry')
                ->where(function ($q) use ($now) {
                    $q->whereNull('end_at')
                        ->orWhere('end_at', '>', $now);
                })
                ->pluck('item_id');

            $vehicleIds = $deliveryVehicleIds
                ->merge($giftVehicleIds)
                ->unique()
                ->values();

            $vehicles = DB::table('cars')
                ->whereIn('id', $vehicleIds)
                ->get()
                ->map(function ($v) {
                    return [
                        'id' => $v->id,
                        'name' => $v->name,
                        'icon' => Helper::showImage($v->icon, true),
                        'gif' => Helper::showImage($v->gif ?? null, true),
                    ];
                });

            // GUARDIANS - top 3 gift senders
            $guardians = DB::table('gift_transactions as gt')
                ->leftJoin('app_users as u', 'u.id', '=', 'gt.sender_id')
                ->select(
                    'gt.sender_id',
                    'u.name',
                    'u.image',
                    DB::raw('SUM(gt.total_value) as total_coins')
                )
                ->where('gt.receiver_id', $profileUserId)
                ->groupBy('gt.sender_id', 'u.name', 'u.image')
                ->orderByDesc('total_coins')
                ->limit(3)
                ->get()
                ->map(function ($g) {
                    return [
                        'id' => $g->sender_id,
                        'name' => $g->name,
                        'image' => Helper::showImage($g->image ?? null, true),
                        'coins' => (int) $g->total_coins,

                        // optional (UI ke liye useful)
                        'coins_text' => number_format($g->total_coins)
                    ];
                });

            // ALBUM
            $album = DB::table('user_albums')
                ->where('app_user_id', $profileUserId)
                ->whereNull('deleted_at')
                ->latest()
                ->limit(6)
                ->get()
                ->map(function ($m) {
                    return [
                        'id' => $m->id,
                        'file' => Helper::showImage($m->file, true),
                        'type' => $m->file_type,
                    ];
                });

            // GIFTS
            $gifts = DB::table('gift_transactions as gt')
                ->join('gifts as g', 'g.id', '=', 'gt.gift_id')
                ->where('gt.receiver_id', $profileUserId)
                ->select(
                    'g.id',
                    'g.name',
                    'g.cover',
                    'g.gif_image',
                    DB::raw('SUM(gt.multiplier) as total_count'),
                    DB::raw('SUM(gt.total_value) as total_coins')
                )
                ->groupBy('g.id', 'g.name', 'g.cover', 'g.gif_image')
                ->orderByDesc('total_count')
                ->limit(30)
                ->get()
                ->map(function ($gift) {
                    return [
                        'id' => $gift->id,
                        'name' => $gift->name,
                        'image' => Helper::showImage($gift->cover, true),
                        'gif' => Helper::showImage($gift->gif_image ?? null, true),
                        'count' => (int) $gift->total_count,
                        'count_text' => 'x' . (int) $gift->total_count,
                        'total_coins' => (int) $gift->total_coins,
                    ];
                });

            return response()->json([
                'status' => true,
                'data' => [
                    'is_own_profile' => $authUser->id == $profileUserId,

                    'user' => [
                        'id' => $user->id,
                        'uid' => $user->uid ?? null,
                        'name' => $user->name ?? null,
                        'image' => Helper::showImage($user->image ?? null, true),
                        'gender' => $user->gender ?? null,
                        'country' => $user->country ?? null,
                        'bio' => $user->bio ?? null,
                    ],

                    'guardian' => $guardians,
                    'album' => $album,
                    'frames' => $frames,
                    'vehicles' => $vehicles,
                    'medal' => $medal,
                    'gifts' => $gifts,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }


    public function getProfile(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

            return response()->json([
                'status' => true,
                'message' => 'Profile fetched successfully',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'gender' => $user->gender,
                    'birthday' => $user->birthdate,
                    'country' => $user->country,
                    'signature' => $user->signature,
                    'image' => $user->image ? \Helper::showImage($user->image, true) : null,
                ]
            ]);
        } catch (\Throwable $e) {
            \Log::error('GET PROFILE API ERROR', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function updateProfile(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $rules = [
                'name' => 'sometimes|required|string|min:2|max:100',
                'gender' => ['sometimes', 'required', Rule::in(['Boy', 'Girl'])],
                'birthdate' => 'sometimes|required|date|before:today',
                'signature' => 'sometimes|nullable|string|max:255',
                'image' => 'sometimes|required|image|mimes:jpg,jpeg,png,webp|max:5120',
            ];

            $messages = [
                'name.required' => 'UserName is required',
                'gender.required' => 'Gender is required',
                'gender.in' => 'Gender must be Boy, Girl',
                'birthdate.required' => 'Birthday is required',
                'birthdate.date' => 'Birthday must be a valid date',
                'birthdate.before' => 'Birthday must be before today',
                'image.image' => 'Avatar must be an image',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors(),
                ], 422);
            }

            $updatedFields = [];

            if ($request->has('name')) {
                $user->name = trim($request->name);
                $updatedFields[] = 'name';
            }

            if ($request->has('gender')) {
                $user->gender = strtolower(trim($request->gender));
                $updatedFields[] = 'gender';
            }

            if ($request->has('birthdate')) {
                $user->birthdate = $request->birthdate;
                $updatedFields[] = 'birthdate';
            }

            if ($request->has('signature')) {
                $user->signature = $request->signature ? trim($request->signature) : null;
                $updatedFields[] = 'signature';
            }

            if ($request->hasFile('image')) {
                if ($user->image && file_exists(storage_path('app/public/' . $user->image))) {
                    @unlink(storage_path('app/public/' . $user->image));
                }

                $user->image = Helper::saveFile($request->file('image'), 'profile_image');
                $updatedFields[] = 'image';
            }

            if (empty($updatedFields)) {
                return response()->json([
                    'status' => false,
                    'message' => 'No field provided for update'
                ], 422);
            }

            $user->save();

            return response()->json([
                'status' => true,
                'message' => 'Profile updated successfully',
            ]);
        } catch (\Throwable $e) {
            \Log::error('PROFILE UPDATE API ERROR', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function getUserRelationships(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'nullable|integer|exists:app_users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        try {
            $authUser = Auth::user();

            if (!$authUser) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $profileUserId = $request->filled('user_id')
                ? (int) $request->user_id
                : (int) $authUser->id;

            $profileUser = DB::table('app_users')
                ->where('id', $profileUserId)
                ->first();

            if (!$profileUser) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found'
                ], 404);
            }

            $relationships = [
                'cp' => [
                    'count' => 0,
                    'data' => null,
                ],
                'brother' => [
                    'count' => 0,
                    'data' => [],
                ],
                'sister' => [
                    'count' => 0,
                    'data' => [],
                ],
                'confidant' => [
                    'count' => 0,
                    'data' => [],
                ],
            ];

            $relations = DB::table('relationship_invitations as ri')
                ->leftJoin('relationship_items as item', 'item.id', '=', 'ri.relationship_item_id')
                ->where('ri.status', 'accept')
                ->where(function ($q) use ($profileUserId) {
                    $q->where('ri.sender_id', $profileUserId)
                        ->orWhere('ri.receiver_id', $profileUserId);
                })
                ->select(
                    'ri.*',
                    'item.name as relationship_name',
                    'item.frame as relationship_frame',
                    'item.background as relationship_background'
                )
                ->orderByDesc('ri.updated_at')
                ->get();

            foreach ($relations as $relation) {
                $otherUserId = ((int) $relation->sender_id === (int) $profileUserId)
                    ? (int) $relation->receiver_id
                    : (int) $relation->sender_id;

                $otherUser = DB::table('app_users')
                    ->where('id', $otherUserId)
                    ->first();

                if (!$otherUser) {
                    continue;
                }

                $type = strtolower(trim($relation->type));

                if (in_array($type, ['cp', 'couple'])) {
                    $typeKey = 'cp';
                } elseif ($type === 'brother') {
                    $typeKey = 'brother';
                } elseif ($type === 'sister') {
                    $typeKey = 'sister';
                } elseif (in_array($type, ['confidant', 'confident', 'confidential'])) {
                    $typeKey = 'confidant';
                } else {
                    continue;
                }

                $days = $relation->updated_at
                    ? Carbon::parse($relation->updated_at)->diffInDays(now())
                    : 0;

                $item = [
                    'invitation_id' => $relation->id,
                    'relationship_item_id' => $relation->relationship_item_id,
                    'relationship_name' => $relation->relationship_name,
                    'relationship_frame' => Helper::showImage($relation->relationship_frame ?? null, true),
                    'relationship_background' => Helper::showImage($relation->relationship_background ?? null, true),
                    'type' => $typeKey,
                    'days' => $days,
                    'days_text' => $days . ' days',
                    'user' => [
                        'id' => $otherUser->id,
                        'uid' => $otherUser->uid ?? null,
                        'name' => $otherUser->name ?? null,
                        'image' => Helper::showImage($otherUser->image ?? null, true),
                        'gender' => $otherUser->gender ?? null,
                        'country' => $otherUser->country ?? null,
                    ],
                ];

                if ($typeKey === 'cp') {
                    // Couple sirf ek hoga, latest accepted relation show hoga
                    if ($relationships['cp']['data'] === null) {
                        $relationships['cp']['data'] = $item;
                        $relationships['cp']['count'] = 1;
                    }
                } else {
                    // Brother, Sister, Confidant multiple ho sakte hain
                    $relationships[$typeKey]['data'][] = $item;
                }
            }

            $relationships['brother']['count'] = count($relationships['brother']['data']);
            $relationships['sister']['count'] = count($relationships['sister']['data']);
            $relationships['confidant']['count'] = count($relationships['confidant']['data']);

            return response()->json([
                'status' => true,
                'message' => 'Relationship details fetched successfully',
                'data' => [
                    'is_own_profile' => (int) $authUser->id === (int) $profileUserId,
                    'user_id' => $profileUserId,
                    'relationships' => $relationships,
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
