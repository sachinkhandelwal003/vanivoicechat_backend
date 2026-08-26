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
use App\Models\UserMedal;
use App\Models\WCLevel;
use App\Models\Room;
use App\Models\RelationshipInvitation;
use App\Models\StoreUids;
use App\Models\PremiumNumber;
use App\Models\Frame;
use App\Models\Vip;
use App\Models\Svip;
use App\Models\RelationshipItem;
use App\Models\InviteUser;
use App\Models\InviteRewardHistory;
use App\Models\RewardInviting;
use App\Models\Notification;
use App\Models\SvipTransaction;
use App\Models\VipTransaction;
use App\Models\UserRoleTag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Services\FirebaseService;

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
            'invite_code' => 'nullable|string',
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

        if ($request->filled('invite_code')) {

            // User can use invite code only once
            if (!empty($user->referred_by)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invite code already used'
                ], 422);
            }

            // Find inviter
            $referrer = AppUser::where('invite_code', $request->invite_code)
                ->where('id', '!=', $user->id)
                ->first();

            if (!$referrer) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid invite code'
                ], 422);
            }

            // Prevent duplicate referral record
            $alreadyExists = InviteUser::where('invited_user_id', $user->id)->exists();

            if ($alreadyExists) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invite code already applied.'
                ], 422);
            }

            // Save referral
            InviteUser::create([
                'inviter_id'      => $referrer->id,
                'invited_user_id' => $user->id,
                'invite_code'     => $request->invite_code,
                'is_completed'    => 1,
                'completed_at'    => now(),
            ]);

            // Save referrer on user table
            $data['referred_by'] = $referrer->id;

            $this->checkInviteReward($referrer->id);
        }

        $user->update($data);

        return response()->json([
            'status' => true,
            'message' => 'User Details Saved Successfully',
        ]);
    }

    private function checkInviteReward($userId)
    {
        $completedInvites = InviteUser::where('inviter_id', $userId)
            ->where('is_completed', 1)
            ->count();

        $rewards = RewardInviting::orderBy('target_person')->get();

        foreach ($rewards as $reward) {

            if ($completedInvites >= $reward->target_person) {

                $alreadyRewarded = InviteRewardHistory::where('user_id', $userId)
                    ->where('reward_inviting_id', $reward->id)
                    ->exists();

                if ($alreadyRewarded) {
                    continue;
                }

                $user = AppUser::find($userId);

                $user->increment('total_points', $reward->reward_coin);

                InviteRewardHistory::create([
                    'user_id'            => $userId,
                    'reward_inviting_id' => $reward->id,
                    'target_person'      => $reward->target_person,
                    'reward_coin'        => $reward->reward_coin,
                ]);

                Notification::create([
                    'sender_id'   => null,
                    'receiver_id' => $user->id,
                    'type'        => 'invite_reward',
                    'title'       => 'Invite Reward',
                    'message'     => "Congratulations! You have received {$reward->reward_coin} coins for successfully inviting {$reward->target_person} users.",
                    'image'       => null,
                    'country'     => $user->country,
                ]);

                if (!empty($user->fcm_token)) {

                    $firebase = new FirebaseService();

                    try {

                        $firebase->sendNotification(
                            $user->fcm_token,
                            'Invite Reward',
                            "Congratulations! You have received {$reward->reward_coin} coins for successfully inviting {$reward->target_person} users.",
                            null
                        );
                    } catch (\Exception $e) {
                        \Log::error('Invite Reward FCM Error: ' . $e->getMessage());
                    }
                }
            }
        }
    }

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

            // $user = AppUser::find($userId);

            // CURRENT ACTIVE ROOM
            // $roomId  = Room::where('user_id', $userId)->first();
            $hideCurrentRoom = false;

            // VIP - Forbidden To Follow

            $hasVipForbiddenToFollow = VipTransaction::where('user_id', $userId)
                ->where('start_at', '<=', now())
                ->where(function ($q) {
                    $q->whereNull('end_at')
                        ->orWhere('end_at', '>=', now());
                })
                ->whereHas('vip.privileges', function ($q) {
                    $q->where('slug', 'forbidden_to_follow')
                        ->where('status', 1);
                })
                ->exists();

            //  SVIP - Avoid Following

            $hasSvipAvoidFollowing = SvipTransaction::where('user_id', $userId)
                ->where('start_at', '<=', now())
                ->where(function ($q) {
                    $q->whereNull('end_at')
                        ->orWhere('end_at', '>=', now());
                })
                ->whereHas('svip.privileges', function ($q) {
                    $q->where('slug', 'avoid_following')
                        ->where('svip_level_privileges.is_active', 1);
                })
                ->exists();

            // IF ANY ROOM-HIDING PRIVILEGE EXISTS

            if ($hasVipForbiddenToFollow || $hasSvipAvoidFollowing) {
                $hideCurrentRoom = true;
            }

            // GET CURRENT ROOM

            $currentRoom = null;

            if (!$hideCurrentRoom) {

                $currentRoom = DB::table('room_presences')
                    ->where('user_id', $userId)
                    ->orderByDesc('last_ping_at')
                    ->first();
            }

            $user = AppUser::with([
                'countryData:id,name,iso',
                'activeCard:id,name,icon,gif',
                'activeFrame:id,name,icon,gif'
            ])->find($userId);

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
            $flag = null;

            if ($user->countryData && $user->countryData->iso) {
                $flag = 'https://flagcdn.com/w40/' . strtolower($user->countryData->iso) . '.png';
            }

            $roles = [];

            if (BdUser::where('user_id', $user->id)->where('invite_status', 'accept')->where('status', 1)->where('is_dashboard_access', 1)->exists()) {
                $roles = ['bd'];
            } else {
                if (AdminAccount::where('user_id', $user->id)->where('status', 1)->exists()) {
                    $roles[] = 'admin';
                }

                if (Agency::where('user_id', $user->id)->where('invite_status', 'accept')->where('status', 1)->exists()) {
                    $roles[] = 'agency';
                }

                if (Host::where('user_id', $user->id)->where('invite_status', 'accept')->where('status', 1)->where('is_dashboard_access', 1)->exists()) {
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

            $isOnline = false;

            if (!empty($user->user_last_seen)) {
                $isOnline = \Carbon\Carbon::parse(
                    $user->user_last_seen
                )->gt(now()->subMinutes(1));
            }

            $hideOnline = SvipTransaction::where(
                'user_id',
                $user->id
            )
                ->where(function ($q) {
                    $q->whereNull('end_at')
                        ->orWhere('end_at', '>=', now());
                })
                ->whereHas('svip.privileges', function ($q) {
                    $q->where(
                        'svip_privileges.slug',
                        'online_user'
                    )
                        ->where(
                            'svip_level_privileges.is_active',
                            1
                        );
                })
                ->exists();

            if ($hideOnline && !$isOwnProfile) {
                $isOnline = false;
            }

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

            $wealthLevel = WCLevel::with([
                'levelData' => function ($q) {
                    $q->where('type', 'wealth');
                }
            ])
                ->where('user_id', $userId)
                ->where('type', 'wealth')
                ->first();



            $charmLevel = WCLevel::with([
                'levelData' => function ($q) {
                    $q->where('type', 'charm');
                }
            ])
                ->where('user_id', $userId)
                ->where('type', 'charm')
                ->first();



            $equippedMedals = UserMedal::with('medal')
                ->where('user_id', $userId)
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

            $cpRelation = RelationshipInvitation::with([
                'sender:id,uid,name,image,active_frame_id',
                'receiver:id,uid,name,image,active_frame_id',

                'sender.activeFrame:id,name,icon,gif',
                'receiver.activeFrame:id,name,icon,gif',
            ])

                ->where('status', 'accept')
                ->whereRaw('LOWER(type)=?', ['cp'])
                ->where(function ($q) use ($userId) {
                    $q->where('sender_id', $userId)
                        ->orWhere('receiver_id', $userId);
                })
                ->latest()->first();


            $cpData = null;

            if ($cpRelation) {

                $partner = (int) $cpRelation->sender_id === (int) $userId ? $cpRelation->receiver : $cpRelation->sender;

                $partnerFrame = null;

                if ($partner && $partner->active_frame_id) {

                    if ($partner->active_frame_type === 'vip') {

                        $vip = Vip::find($partner->active_frame_id);

                        if ($vip) {
                            $partnerFrame = [
                                'id' => $vip->id,
                                'name' => $vip->name,
                                'icon' => asset('storage/' . $vip->image_frame),
                                'svga' => !empty($vip->image_frame_animation)
                                    ? asset('storage/' . $vip->image_frame_animation)
                                    : null,
                            ];
                        }
                    } elseif ($partner->active_frame_type === 'svip') {

                        $svip = Svip::find($partner->active_frame_id);

                        if ($svip) {
                            $partnerFrame = [
                                'id' => $svip->id,
                                'name' => $svip->name,
                                'icon' => !empty($svip->headwear)
                                    ? asset('storage/' . $svip->headwear)
                                    : null,
                                'svga' => !empty($svip->headwear_animation)
                                    ? asset('storage/' . $svip->headwear_animation)
                                    : null,
                            ];
                        }
                    } elseif (in_array($partner->active_frame_type, [
                        'cp',
                        'brother',
                        'sister',
                        'confident'
                    ])) {

                        $relationItem = RelationshipItem::find($partner->active_frame_id);

                        if ($relationItem) {
                            $partnerFrame = [
                                'id' => $relationItem->id,
                                'name' => $relationItem->name,
                                'icon' => !empty($relationItem->frame)
                                    ? Helper::showImage($relationItem->frame, true)
                                    : null,
                                'svga' => !empty($relationItem->frame_animation)
                                    ? Helper::showImage($relationItem->frame_animation, true)
                                    : null,
                            ];
                        }
                    } else {

                        $frame = Frame::find($partner->active_frame_id);

                        if ($frame) {
                            $partnerFrame = [
                                'id' => $frame->id,
                                'name' => $frame->name,
                                'icon' => !empty($frame->icon)
                                    ? Helper::showImage($frame->icon, true)
                                    : null,
                                'svga' => !empty($frame->gif)
                                    ? Helper::showImage($frame->gif, true)
                                    : null,
                            ];
                        }
                    }
                }
                $cpData = [
                    'id' => $partner?->id,
                    'uid' => $partner?->uid,
                    'name' => $partner?->name,
                    'image' => !empty($partner?->image) ? Helper::showImage($partner->image, true) : null,
                    'days' => (int) \Carbon\Carbon::parse($cpRelation->created_at)->diffInDays(now()),
                    'cp-heart' => asset('storage/cp-heart.png'),

                    // 'frame' => $partner?->activeFrame ? [
                    //     'id' => $partner->activeFrame->id,
                    //     'name' => $partner->activeFrame->name,
                    //     'icon' => !empty($partner->activeFrame->icon)
                    //         ? Helper::showImage($partner->activeFrame->icon, true) : null,

                    //     'svga' => !empty($partner->activeFrame->gif)
                    //         ? Helper::showImage($partner->activeFrame->gif, true) : null,
                    // ] : null,

                    'frame' => $partnerFrame,
                ];
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

                            $uidBadge = !empty($storeUid->rank_badge)
                                ? Helper::showImage($storeUid->rank_badge, true)
                                : null;
                            $uidBadgeColor = $storeUid->rank_badge_color ?? null;
                        }
                    }
                }
            }


            $frameData = null;

            if ($user->active_frame_id) {

                // STORE FRAME

                if ($user->active_frame_type === 'store' || empty($user->active_frame_type)) {

                    $frame = Frame::find($user->active_frame_id);

                    if ($frame) {
                        $frameData = [
                            'id' => $frame->id,
                            'name' => $frame->name,
                            'icon' => !empty($frame->icon)
                                ? Helper::showImage($frame->icon, true)
                                : null,

                            'svga' => !empty($frame->gif)
                                ? Helper::showImage($frame->gif, true)
                                : null,
                        ];
                    }
                }

                //    VIP FRAME
                elseif ($user->active_frame_type === 'vip') {

                    $vip = Vip::find($user->active_frame_id);

                    if ($vip) {
                        $frameData = [
                            'id' => $vip->id,
                            'name' => $vip->name,
                            'icon' => !empty($vip->image_frame)
                                ? asset('storage/' . $vip->image_frame)
                                : null,

                            'svga' => !empty($vip->image_frame_animation)
                                ? asset('storage/' . $vip->image_frame_animation)
                                : null,
                        ];
                    }
                }

                // SVIP FRAME
                elseif ($user->active_frame_type === 'svip') {

                    $svip = Svip::find($user->active_frame_id);

                    if ($svip) {
                        $frameData = [
                            'id' => $svip->id,
                            'name' => $svip->name,
                            'icon' => !empty($svip->headwear)
                                ? asset('storage/' . $svip->headwear)
                                : null,

                            'svga' => !empty($svip->headwear_animation)
                                ? asset('storage/' . $svip->headwear_animation)
                                : null,
                        ];
                    }
                }

                //   CP FRAME

                elseif ($user->active_frame_type === 'cp') {

                    $relationItem = RelationshipItem::find($user->active_frame_id);

                    if ($relationItem) {
                        $frameData = [
                            'id' => $relationItem->id,
                            'name' => $relationItem->name,
                            'icon' => !empty($relationItem->frame)
                                ? Helper::showImage($relationItem->frame, true)
                                : null,

                            'svga' => !empty($relationItem->frame_animation)
                                ? Helper::showImage($relationItem->frame_animation, true)
                                : null,
                        ];
                    }
                }
            }

            $nicknameMeta = Helper::getNicknameMeta($user->id);
            $membershipBadges = Helper::getUserMembershipBadges($user->id);
            $authHasAnyPrivateMessage = Helper::hasVipPrivilege($authUser->id, 'any_private_message');
            return response()->json([
                'status' => true,
                'message' => 'User Details fethed Successfuly',
                'data' =>  [
                    'is_own_profile' => $isOwnProfile,
                    'id' => $user->id,
                    // 'room_id' => $roomId ? $roomId->id : null,
                    'room_id' => $currentRoom?->room_id,
                    // 'uid' => $user->uid,
                    'uid' => $displayUid,
                    'uid_badge' => $uidBadge,
                    'uid_badge_color' => $uidBadgeColor,
                    'name' => $user->name,
                    'nickname_meta' => $nicknameMeta,
                    // 'nickname_color' => $nicknameMeta['color'],
                    // 'nickname_effect' => $nicknameMeta['effect'],
                    // 'has_animated_nickname' => $nicknameMeta['animated'],
                    'email' => $user->email,
                    'gender' => $user->gender,
                    'image' => $image,
                    'flag' => $flag,
                    'signature' => $user->signature,

                    'is_online' => $isOnline,
                    'last_seen' => $user->user_last_seen,
                    'hide_online_status' => $hideOnline,

                    'cp_relation' => $cpData,

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
                    'auth_has_any_private_message' => $authHasAnyPrivateMessage,
                    'role_badges' => Helper::getUserRoleBadges($user->id),
                    'membership_badges' => $membershipBadges,
                    'wealth_level' => [
                        'level' => $wealthLevel?->level ?? 1,
                        'icon' => $wealthLevel?->levelData?->icon
                            ? Helper::showImage($wealthLevel->levelData->icon, true) : null
                    ],

                    'charm_level' => [
                        'level' => $charmLevel?->level ?? 1,
                        'icon' => $charmLevel?->levelData?->icon
                            ? Helper::showImage($charmLevel->levelData->icon, true)
                            : null
                    ],

                    'medals' => $equippedMedals,

                    'profile_card' => $user->activeCard ? [
                        'id' => $user->activeCard->id,
                        'name' => $user->activeCard->name,
                        'icon' => !empty($user->activeCard->icon) ? Helper::showImage($user->activeCard->icon, true) : null,
                        'svga' => !empty($user->activeCard->gif) ? Helper::showImage($user->activeCard->gif, true) : null,
                    ] : null,

                    // 'frame' => $user->activeFrame ? [
                    //     'id' => $user->activeFrame->id,
                    //     'name' => $user->activeFrame->name,
                    //     'icon' => !empty($user->activeFrame->icon) ? Helper::showImage($user->activeFrame->icon, true) : null,
                    //     'svga' => !empty($user->activeFrame->gif) ? Helper::showImage($user->activeFrame->gif, true) : null,
                    // ] : null,

                    'frame' => $frameData,
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

        // Check Mysterious Visitor privilege
        $isMysteriousVisitor = SvipTransaction::where('user_id', $visitorId)
            ->where(function ($q) {
                $q->whereNull('end_at')
                    ->orWhere('end_at', '>=', now());
            })
            ->whereHas('svip.privileges', function ($q) {
                $q->where('svip_privileges.slug', 'mysterious_visitor') // Mysterious Visitor
                    ->where('svip_level_privileges.is_active', 1);
            })
            ->exists();

        if (!$isMysteriousVisitor) {
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
        }

        return response()->json([
            'status' => true,
            'message' => 'Profile visit recorded'
        ]);
    }

    public function profileVisitors()
    {
        $userId = Auth::id();

        $currentVip = VipTransaction::with('vip.privileges')
            ->where('user_id', $userId)
            ->where('start_at', '<=', now())
            ->where(function ($q) {
                $q->whereNull('end_at')
                    ->orWhere('end_at', '>=', now());
            })
            ->first();

        $hasVipVisitorPrivilege = false;

        if ($currentVip && $currentVip->vip) {

            $hasVipVisitorPrivilege =
                $currentVip->vip->privileges
                ->where('slug', 'view_visitors')
                ->where('status', 1)
                ->isNotEmpty();
        }

        $currentSvip = SvipTransaction::with('svip.privileges')
            ->where('user_id', $userId)
            ->where('end_at', '>=', now())
            ->first();

        $hasVisitorPrivilege = false;

        if ($currentSvip) {

            $hasVisitorPrivilege = $currentSvip->svip->privileges
                ->where('slug', 'visiting_traces') // Visiting Traces privilege slug
                ->where('pivot.is_active', 1)
                ->isNotEmpty();
        }

        $visitors = ProfileVisitor::with('visitor')
            ->where('user_id', $userId)
            ->orderByDesc('updated_at')
            ->get()
            ->map(function ($visit) {
                if (!$visit->visitor) {
                    return null;
                }

                $visitor = $visit->visitor;
                $nicknameMeta = Helper::getNicknameMeta($visit->visitor->id);
                return [
                    'id'   => $visit->visitor->id,
                    'uid'  => $visit->visitor->uid ?? null,
                    'name' => $visit->visitor->name,
                    'nickname_meta' => $nicknameMeta,
                    'gender' => $visit->visitor->gender,
                    'image' => $visit->visitor->image
                        ? Helper::showImage($visit->visitor->image, true)
                        : null,
                ];
            })->filter()
            ->values();

        return response()->json([
            'status' => true,
            'has_vip_visitor_privilege' => $hasVipVisitorPrivilege,
            'has_visitor_trace_privilege' => $hasVisitorPrivilege,
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

            $userId = $request->filled('user_id')
                ? $request->user_id
                : $authUser->id;

            $posts = Post::where('user_id', $userId)
                ->with([
                    'topic',
                    'media',
                    'user',
                ])
                ->withCount([
                    'comments',
                    'likes'
                ])
                ->latest()
                ->get()
                ->map(function ($post) use ($authUser) {

                    return [
                        'id' => $post->id,
                        'topic_name' => $post->topic->name ?? null,
                        'description' => $post->description,
                        'comments_count' => $post->comments_count,
                        'likes_count' => $post->likes_count,

                        'time_ago' => Carbon::parse(
                            $post->created_at
                        )->diffForHumans(),

                        'is_liked' => $post->likes()
                            ->where('user_id', $authUser->id)
                            ->exists(),

                        'user' => [
                            'id' => $post->user->id,
                            'name' => $post->user->name,
                            'gender' => $post->user->gender,
                            'image' => Helper::showImage(
                                $post->user->image,
                                true
                            ),
                        ],

                        'media' => $post->media->map(function ($media) {

                            return [
                                'file_type' => $media->file_type,
                                'file_url' => Helper::showImage(
                                    $media->file_path,
                                    true
                                ),
                            ];
                        })->values(),
                    ];
                });

            return response()->json([
                'status' => true,
                'message' => 'Posts fetched successfully',
                'data' => $posts
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
            'file' => 'required|file|mimes:jpg,jpeg,png,jfif,gif,mp4|max:20240'
        ]);

        $user = Auth::user();
        // Album Upload Ban Check
        if ((int)$user->is_album_banned === 1) {
            return response()->json([
                'status' => false,
                'message' => 'Your album upload permission has been disabled by the administrator.'
            ], 403);
        }

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
            // $level = DB::table('user_level')
            //     ->where('grade', $user->user_level)
            //     ->first();

            // $medal = $level ? [
            //     'id' => $level->id,
            //     'name' => $level->name,
            //     'icon' => Helper::showImage($level->icon, true),
            //     'avatar_corner' => Helper::showImage($level->avatar_corner, true),
            // ] : null;

            $medal = UserMedal::with('medal')
                ->where('user_id', $user->id)
                ->where('is_equipped', 1)
                ->orderBy('slot_no')
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->medal->id,
                        'name' => $item->medal->title,
                        'icon' => Helper::showImage(
                            $item->medal->icon,
                            true
                        ),
                    ];
                })
                ->values();

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

            $isOwnProfile = $authUser->id == $profileUserId;

            $hideGiftRecord = false;

            if (!$isOwnProfile) {

                $hideGiftRecord = SvipTransaction::where('user_id', $profileUserId)
                    ->where('end_at', '>=', now())
                    ->whereHas('svip.privileges', function ($q) {
                        $q->where('svip_privileges.slug', 'hide_gift_record') // Hide Gift Record
                            ->where('svip_level_privileges.is_active', 1);
                    })
                    ->exists();
            }
            if ($hideGiftRecord) {

                $gifts = collect([]);
            } else {
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
            }

            return response()->json([
                'status' => true,
                'data' => [
                    'is_own_profile' => $authUser->id == $profileUserId,
                    'hide_gift_record' => $hideGiftRecord,
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
            Log::error('GET PROFILE API ERROR', [
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
                'image' => 'sometimes|required|file|max:5120',
            ];

            $messages = [
                'name.required' => 'UserName is required',
                'gender.required' => 'Gender is required',
                'gender.in' => 'Gender must be Boy, Girl',
                'birthdate.required' => 'Birthday is required',
                'birthdate.date' => 'Birthday must be a valid date',
                'birthdate.before' => 'Birthday must be before today',
                'image.file' => 'Avatar must be a file',
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

            // if ($request->hasFile('image')) {
            //     if ($user->image && file_exists(storage_path('app/public/' . $user->image))) {
            //         @unlink(storage_path('app/public/' . $user->image));
            //     }

            //     $user->image = Helper::saveFile($request->file('image'), 'profile_image');
            //     $updatedFields[] = 'image';
            // }

            if ($request->hasFile('image')) {

                $extension = strtolower($request->file('image')->getClientOriginalExtension());

                $normalExtensions = ['jpg', 'jpeg', 'png', 'webp'];
                $animatedExtensions = ['gif', 'svga'];

                // Invalid file format
                if (!in_array($extension, array_merge($normalExtensions, $animatedExtensions))) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Invalid image format.'
                    ], 422);
                }

                // Animated profile privilege check
                if (in_array($extension, $animatedExtensions)) {

                    $hasVipGifPrivilege = Helper::hasVipPrivilege($user->id, 'gif_avatar');

                    $hasSvipGifPrivilege = SvipTransaction::where('user_id', $user->id)
                        ->where('end_at', '>=', now())
                        ->whereHas('svip.privileges', function ($q) {
                            $q->where('svip_privileges.slug', 'dynamic_avatar')
                                ->where('svip_level_privileges.is_active', 1);
                        })
                        ->exists();

                    if (!$hasVipGifPrivilege && !$hasSvipGifPrivilege) {
                        return response()->json([
                            'status' => false,
                            'message' => 'GIF avatar privilege is required.'
                        ], 403);
                    }
                }

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
            Log::error('PROFILE UPDATE API ERROR', [
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

            // Profile User

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

            //  Default CP Item

            $defaultCp = DB::table('relationship_items')
                ->where('status', 1)
                ->whereRaw('LOWER(type)=?', ['cp'])
                ->orderBy('required_coins')
                ->first();

            // Default Response

            $relationships = [
                'cp' => [
                    'count' => 0,
                    'data' => [
                        'invitation_id' => null,
                        'type' => 'cp',
                        'days' => 0,
                        'days_text' => '0 days',
                        'gift_coins' => 0,

                        'current_level' => [
                            'id' => $defaultCp->id ?? null,
                            'name' => $defaultCp->name ?? null,
                            'required_coins' => $defaultCp->required_coins ?? 0,
                            'frame' => Helper::showImage($defaultCp->frame ?? null, true),
                            'background' => Helper::showImage($defaultCp->background ?? null, true),
                        ],

                        'owner_user' => [
                            'id' => $profileUser->id,
                            'uid' => $profileUser->uid ?? null,
                            'name' => $profileUser->name ?? null,
                            'image' => Helper::showImage($profileUser->image ?? null, true),
                            'gender' => $profileUser->gender ?? null,
                            'country' => $profileUser->country ?? null,
                        ],

                        'related_user' => null,
                    ],
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

            // Accepted Relations

            $relations = DB::table('relationship_invitations as ri')
                ->where('ri.status', 'accept')
                ->where(function ($q)
                use ($profileUserId) {
                    $q->where('ri.sender_id', $profileUserId)
                        ->orWhere('ri.receiver_id', $profileUserId);
                })
                ->orderByDesc('ri.updated_at')->get();

            foreach ($relations as $relation) {

                // Related User

                $otherUserId = ((int) $relation->sender_id === (int) $profileUserId)
                    ? (int) $relation->receiver_id : (int) $relation->sender_id;

                $otherUser = DB::table('app_users')->where('id', $otherUserId)->first();

                if (!$otherUser) {
                    continue;
                }

                // Type Mapping

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

                // Pair Gift Coins

                $giftCoins = DB::table('gift_transactions')
                    ->where(function ($q)
                    use ($profileUserId, $otherUserId) {
                        $q->where(function ($sub)
                        use ($profileUserId, $otherUserId) {
                            $sub->where('sender_id', $profileUserId)
                                ->where('receiver_id', $otherUserId);
                        })->orWhere(function ($sub)
                        use ($profileUserId, $otherUserId) {
                            $sub->where('sender_id', $otherUserId)
                                ->where('receiver_id', $profileUserId);
                        });
                    })
                    ->sum('total_value');

                // Current Level

                $currentLevel = DB::table('relationship_items')->where('status', 1)
                    ->whereRaw('LOWER(type)=?', [$typeKey])
                    ->where('required_coins', '<=', $giftCoins)
                    ->orderByDesc('required_coins')
                    ->first();

                if (!$currentLevel) {

                    $currentLevel = DB::table('relationship_items')
                        ->where('status', 1)
                        ->whereRaw('LOWER(type)=?', [$typeKey])
                        ->orderBy('required_coins')
                        ->first();
                }

                // Accepted Days

                $days = max(
                    1,
                    (int) Carbon::parse($relation->updated_at)
                        ->startOfDay()
                        ->diffInDays(now()->startOfDay())
                );

                // Response Item

                $item = [
                    'invitation_id' => $relation->id,
                    'type' => $typeKey,
                    'days' => $days,
                    'days_text' => $days . ' days',
                    'gift_coins' => (int) $giftCoins,

                    'current_level' => [
                        'id' => $currentLevel->id ?? null,
                        'name' => $currentLevel->name ?? null,
                        'required_coins' => $currentLevel->required_coins ?? 0,
                        'frame' => Helper::showImage($currentLevel->frame ?? null, true),
                        'background' => Helper::showImage($currentLevel->background ?? null, true),
                    ],

                    // 'owner_user' => [
                    //     'id' => $profileUser->id,
                    //     'uid' => $profileUser->uid ?? null,
                    //     'name' => $profileUser->name ?? null,
                    //     'image' => Helper::showImage($profileUser->image ?? null,true),
                    //     'gender' => $profileUser->gender ?? null,
                    //     'country' => $profileUser->country ?? null,
                    // ],

                    'related_user' => [
                        'id' => $otherUser->id,
                        'uid' => $otherUser->uid ?? null,
                        'name' => $otherUser->name ?? null,
                        'image' => Helper::showImage($otherUser->image ?? null, true),
                        'gender' => $otherUser->gender ?? null,
                        'country' => $otherUser->country ?? null,
                    ],
                ];

                // CP only one

                if ($typeKey === 'cp') {
                    $relationships['cp']['data'] = $item;
                    $relationships['cp']['count'] = 1;
                } else {
                    $relationships[$typeKey]['data'][] = $item;
                }
            }

            // Counts

            $relationships['brother']['count'] = count($relationships['brother']['data']);

            $relationships['sister']['count'] = count($relationships['sister']['data']);

            $relationships['confidant']['count'] = count($relationships['confidant']['data']);

            return response()->json([
                'status' => true,
                'message' =>
                'Relationship details fetched successfully',
                'data' => [
                    'is_own_profile' => (int) $authUser->id === (int) $profileUserId,
                    'user_id' => $profileUserId,
                    'relationships' => $relationships,
                ]
            ], 200);
        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'message' =>
                $e->getMessage()
            ], 500);
        }
    }

    public function getFollowingUsers(Request $request)
    {
        $authUser = Auth::user();

        if (!$authUser) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated',
                'data' => [],
            ], 401);
        }

        try {

            //  USERS FOLLOWED BY AUTH USER

            $followingIds = DB::table('user_follows')
                ->where('follower_id', $authUser->id)
                ->pluck('following_id')
                ->unique()
                ->values();

            if ($followingIds->isEmpty()) {
                return response()->json([
                    'status' => true,
                    'message' => 'Following users fetched successfully',
                    'data' => [],
                ]);
            }

            $users = AppUser::with([
                'countryData:id,name,iso',
                'wcLevels.levelData',
                'userMedals.medal',
            ])
                ->whereIn('id', $followingIds)
                ->get();


            $data = $users->map(function ($user) {

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
                                    ? Helper::showImage($storeUid->rank_badge, true) : null;
                                $uidBadgeColor = $storeUid->rank_badge_color ?? null;
                            }
                        }
                    }
                }

                $nicknameMeta = Helper::getNicknameMeta($user->id);

                $membershipBadges = Helper::getUserMembershipBadges($user->id);

                $roleBadges = Helper::getUserRoleBadges($user->id);

                $medals = $user->userMedals
                    ->where('is_equipped', 1)
                    ->sortBy('slot_no')
                    ->map(function ($item) {

                        if (!$item->medal) {
                            return null;
                        }

                        return [
                            'id' => $item->medal->id,
                            'name' => $item->medal->title,
                            'icon' => !empty($item->medal->icon)
                                ? Helper::showImage($item->medal->icon, true) : null,
                        ];
                    })
                    ->filter()
                    ->values();

                $flag = null;

                if ($user->countryData?->iso) {
                    $flag = 'https://flagcdn.com/w40/' . strtolower($user->countryData->iso) . '.png';
                }

                $wealthLevel = $user->wcLevels->where('type', 'wealth')->first();
                $charmLevel = $user->wcLevels->where('type', 'charm')->first();

                $image = null;

                if (!empty($user->image)) {

                    if (\Illuminate\Support\Str::startsWith($user->image, ['http://', 'https://'])) {
                        $image = $user->image;
                    } else {
                        $image = Helper::showImage($user->image, true);
                    }
                }

                return [

                    'id' => $user->id,
                    'uid' => $displayUid,
                    'uid_badge' => $uidBadge,
                    'uid_badge_color' => $uidBadgeColor,
                    'name' => $user->name,
                    'nickname_meta' => $nicknameMeta,
                    'gender' => $user->gender,
                    'image' => $image,
                    'flag' => $flag,
                    'role_badges' => $roleBadges,
                    'membership_badges' => $membershipBadges,
                    'wealth_level' => [
                        'level' => $wealthLevel?->level ?? 1,
                        'icon' => $wealthLevel?->levelData?->icon
                            ? Helper::showImage(
                                $wealthLevel->levelData->icon,
                                true
                            )
                            : null,
                    ],
                    'charm_level' => [
                        'level' => $charmLevel?->level ?? 1,
                        'icon' => $charmLevel?->levelData?->icon
                            ? Helper::showImage(
                                $charmLevel->levelData->icon,
                                true
                            )
                            : null,
                    ],
                    'medals' => $medals,
                ];
            })->values();

            return response()->json([
                'status' => true,
                'message' => 'Following users fetched successfully',
                'data' => $data,
            ], 200);
        } catch (\Throwable $e) {

            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
                'data' => [],
            ], 500);
        }
    }

    public function getFanUsers(Request $request)
    {
        $authUser = Auth::user();

        if (!$authUser) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated',
                'data' => [],
            ], 401);
        }

        try {
            $fanIds = DB::table('user_follows')
                ->where('following_id', $authUser->id)
                ->pluck('follower_id')
                ->unique()
                ->values();

            if ($fanIds->isEmpty()) {
                return response()->json([
                    'status' => true,
                    'message' => 'Fan users fetched successfully',
                    'data' => [],
                ], 200);
            }

            $users = AppUser::with([
                'countryData:id,name,iso',
                'wcLevels.levelData',
                'userMedals.medal',
            ])
                ->whereIn('id', $fanIds)
                ->get();

            $data = $users->map(function ($user) {

                $displayUid = $user->uid;
                $uidBadge = null;
                $uidBadgeColor = null;

                $premiumUid = PremiumNumber::where('user_id', $user->id)
                    ->where('end_at', '>', now())
                    ->latest()
                    ->first();

                if ($premiumUid) {
                    $displayUid = $premiumUid->premium_number;
                    $uidBadge = asset('storage/1000175794.png');
                    $uidBadgeColor = '#fcd01c';
                } else {
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
                                    ? Helper::showImage($storeUid->rank_badge, true) : null;
                                $uidBadgeColor = $storeUid->rank_badge_color ?? null;
                            }
                        }
                    }
                }

                $nicknameMeta = Helper::getNicknameMeta($user->id);

                $membershipBadges = Helper::getUserMembershipBadges($user->id);

                $roleBadges = Helper::getUserRoleBadges($user->id);

                $medals = $user->userMedals
                    ->where('is_equipped', 1)
                    ->sortBy('slot_no')
                    ->map(function ($item) {

                        if (!$item->medal) {
                            return null;
                        }

                        return [
                            'id' => $item->medal->id,
                            'name' => $item->medal->title,
                            'icon' => !empty($item->medal->icon)
                                ? Helper::showImage($item->medal->icon, true) : null,
                        ];
                    })
                    ->filter()
                    ->values();

                $flag = null;

                if ($user->countryData?->iso) {

                    $flag = 'https://flagcdn.com/w40/' . strtolower($user->countryData->iso) . '.png';
                }

                $wealthLevel = $user->wcLevels->where('type', 'wealth')->first();
                $charmLevel = $user->wcLevels->where('type', 'charm')->first();

                $image = null;

                if (!empty($user->image)) {

                    if (\Illuminate\Support\Str::startsWith($user->image, ['http://', 'https://'])) {
                        $image = $user->image;
                    } else {
                        $image = Helper::showImage($user->image, true);
                    }
                }

                return [

                    'id' => $user->id,
                    'uid' => $displayUid,
                    'uid_badge' => $uidBadge,
                    'uid_badge_color' => $uidBadgeColor,
                    'name' => $user->name,
                    'nickname_meta' => $nicknameMeta,
                    'gender' => $user->gender,
                    'image' => $image,
                    'flag' => $flag,
                    'role_badges' => $roleBadges,
                    'membership_badges' => $membershipBadges,
                    'wealth_level' => [
                        'level' => $wealthLevel?->level ?? 1,
                        'icon' => $wealthLevel?->levelData?->icon
                            ? Helper::showImage($wealthLevel->levelData->icon, true) : null,
                    ],
                    'charm_level' => [
                        'level' => $charmLevel?->level ?? 1,
                        'icon' => $charmLevel?->levelData?->icon
                            ? Helper::showImage($charmLevel->levelData->icon, true) : null,
                    ],
                    'medals' => $medals,

                ];
            })->values();

            return response()->json([
                'status' => true,
                'message' => 'Fan users fetched successfully',
                'data' => $data,
            ], 200);
        } catch (\Throwable $e) {

            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
                'data' => [],
            ], 500);
        }
    }
}
