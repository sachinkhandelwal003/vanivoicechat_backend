<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cars;
use App\Models\Theme;
use App\Models\Frame;
use App\Models\EntryTag;
use App\Models\Voice;
use App\Models\DataCard;
use App\Models\ChatBubble;
use App\Models\StoreUids;
use App\Models\Rank;
use App\Models\Pattern;
use App\Models\PremiumNumber;
use App\Models\VipTransaction;
use App\Models\SvipTransaction;
use App\Models\RelationshipInvitation;
use App\Models\Vip;
use Illuminate\Http\Request;
use App\Helper\Helper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;


class StoreController extends Controller
{
    public function getEntry(Request $request)
    {
        try {
            $tags = Cars::where('visibility_type', 'in_app')->where('status', 1)
                ->latest()
                ->get();

            $data = $tags->map(function ($tag) {
                return [
                    'id' => $tag->id,
                    'name' => $tag->name,
                    'validity' => $tag->validity,
                    'visibility_type' => $tag->visibility_type,
                    'needcoin' => $tag->needcoin,
                    'icon' => Helper::showImage($tag->icon, true),
                    'gif' => Helper::showImage($tag->gif, true),
                    'status' => $tag->status,
                    'created_at' => $tag->created_at,
                ];
            });

            return response()->json([
                'status' => true,
                'message' => 'Entry tags fetched successfully',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function filterOptions(Request $request)
    {
        try {
            $ranks = Rank::latest()->get()->map(function ($rank) {
                return [
                    'id' => $rank->id,
                    'name' => $rank->name,
                ];
            });

            $patterns = Pattern::latest()->get()->map(function ($pattern) {
                return [
                    'id' => $pattern->id,
                    'name' => $pattern->name,
                ];
            });

            return response()->json([
                'status' => true,
                'message' => 'Filter options fetched successfully',
                'data' => [
                    'rank' => $ranks,
                    'pattern' => $patterns,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function getChatBubbles(Request $request)
    {
        try {
            $bubbles = \App\Models\ChatBubble::where('visibility_type', 'in_app')->where('status', 1)
                ->latest()
                ->get();

            $data = $bubbles->map(function ($bubble) {
                return [
                    'id' => $bubble->id,
                    'name' => $bubble->name,
                    'validity' => $bubble->validity,
                    'visibility_type' => $bubble->visibility_type,
                    'needcoin' => $bubble->needcoin,
                    'icon' => \App\Helper\Helper::showImage($bubble->icon, true),
                    'status' => $bubble->status,
                    'created_at' => $bubble->created_at,
                ];
            });

            return response()->json([
                'status' => true,
                'message' => 'Chat bubbles fetched successfully',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function getFrames(Request $request)
    {
        try {
            $user = Auth::user();

            $frames = \App\Models\Frame::where('visibility_type', 'in_app')->where('status', 1)
                ->latest()
                ->get();

            $data = $frames->map(function ($frame) use ($user) {
                return [
                    'id' => $frame->id,
                    'name' => $frame->name,
                    'validity' => $frame->validity,
                    'visibility_type' => $frame->visibility_type,
                    'needcoin' => $frame->needcoin,
                    'icon' => \App\Helper\Helper::showImage($user->image ?? null, true),
                    'gif' => \App\Helper\Helper::showImage($frame->gif, true),
                    'status' => $frame->status,
                    'created_at' => $frame->created_at,
                ];
            });

            return response()->json([
                'status' => true,
                'message' => 'Frames fetched successfully',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getThemes(Request $request)
    {
        try {
            $themes = \App\Models\Theme::whereNull('user_id')->where('visibility_type', 'in_app')->where('status', 1)
                ->latest()
                ->get();

            $data = $themes->map(function ($theme) {
                return [
                    'id' => $theme->id,
                    'name' => $theme->name,
                    'validity' => $theme->validity,
                    'visibility_type' => $theme->visibility_type,
                    'needcoin' => $theme->needcoin,
                    'icon' => \App\Helper\Helper::showImage($theme->icon, true),
                    'status' => $theme->status,
                    'created_at' => $theme->created_at,
                ];
            });

            return response()->json([
                'status' => true,
                'message' => 'Themes fetched successfully',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getStoreUids(Request $request)
    {
        try {

            $blockedUidIds = collect(
                DB::table('item_deliveries')
                    ->where('type', 'id')
                    ->where('end_at', '>', now())
                    ->pluck('item_id')
            )
                ->merge(
                    DB::table('item_gift_transactions')
                        ->where('type', 'id')
                        ->where('end_at', '>', now())
                        ->pluck('item_id')
                )
                ->unique()
                ->toArray();

            $storeUids = \App\Models\StoreUids::with(['rank', 'pattern'])
                ->where('visibility_type', 'in_app')
                ->where('status', 1)
                ->whereNotIn('id', $blockedUidIds)
                ->latest()
                ->get();

            $data = $storeUids->map(function ($item) {
                return [
                    'id' => $item->id,
                    'unique_id' => $item->unique_id,

                    // Rank Data
                    'rank' => [
                        'id' => $item->rank->id ?? null,
                        'name' => $item->rank->name ?? null,
                    ],

                    // Pattern Data
                    'pattern' => [
                        'id' => $item->pattern->id ?? null,
                        'name' => $item->pattern->name ?? null,
                    ],

                    'visibility_type' => $item->visibility_type,
                    'needcoin' => $item->needcoin,
                    'validity' => $item->validity,
                    'badge' => \App\Helper\Helper::showImage($item->badge, true),
                    'rank_badge' => \App\Helper\Helper::showImage($item->rank_badge, true),
                    'status' => $item->status,
                    'created_at' => $item->created_at,
                ];
            });

            return response()->json([
                'status' => true,
                'message' => 'Store UIDs fetched successfully',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function filterStoreUids(Request $request)
    {
        try {
            $query = \App\Models\StoreUids::with(['rank', 'pattern'])
                ->where('visibility_type', 'in_app')
                ->where('status', 1);

            if ($request->filled('rank_id')) {
                if (is_array($request->rank_id)) {
                    $query->whereIn('rank_id', $request->rank_id);
                } else {
                    $query->where('rank_id', $request->rank_id);
                }
            }

            if ($request->filled('pattern_id')) {
                if (is_array($request->pattern_id)) {
                    $query->whereIn('pattern_id', $request->pattern_id);
                } else {
                    $query->where('pattern_id', $request->pattern_id);
                }
            }

            $storeUids = $query->latest()->get();

            $data = $storeUids->map(function ($item) {
                return [
                    'id' => $item->id,
                    'unique_id' => $item->unique_id,

                    'rank' => [
                        'id' => $item->rank->id ?? null,
                        'name' => $item->rank->name ?? null,
                    ],

                    'pattern' => [
                        'id' => $item->pattern->id ?? null,
                        'name' => $item->pattern->name ?? null,
                    ],

                    'visibility_type' => $item->visibility_type,
                    'needcoin' => $item->needcoin,
                    'validity' => $item->validity,
                    'badge' => \App\Helper\Helper::showImage($item->badge, true),
                    'rank_badge' => \App\Helper\Helper::showImage($item->rank_badge, true),
                    'status' => $item->status,
                    'created_at' => $item->created_at,
                ];
            });

            return response()->json([
                'status' => true,
                'message' => 'Filtered Store UIDs fetched successfully',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function getDataCards(Request $request)
    {
        try {
            $user = Auth::user();

            $cards = DataCard::where('visibility_type', 'in_app')->where('status', 1)
                ->latest()
                ->get();

            $data = $cards->map(function ($card) {
                return [
                    'id' => $card->id,
                    'name' => $card->name,
                    'short_tag' => $card->short_tag,
                    'validity' => $card->validity,
                    'visibility_type' => $card->visibility_type,
                    'needcoin' => $card->needcoin,
                    'icon' => Helper::showImage($card->icon, true),
                    'gif' => Helper::showImage($card->gif, true),
                    'status' => $card->status,
                    'created_at' => $card->created_at,
                ];
            });

            return response()->json([
                'status' => true,
                'message' => 'Data cards fetched successfully',
                'user' => [
                    'id' => $user->id ?? null,
                    'name' => $user->name ?? null,
                    'image' => Helper::showImage($user->image ?? null, true),
                ],

                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function getEntryTags(Request $request)
    {
        try {
            $user = Auth::user(); // 👈 login user

            $tags = \App\Models\EntryTag::where('visibility_type', 'in_app')->where('status', 1)
                ->latest()
                ->get();

            $data = $tags->map(function ($tag) {
                return [
                    'id' => $tag->id,
                    'name' => $tag->name,
                    'short_tag' => $tag->short_tag,
                    'validity' => $tag->validity,
                    'visibility_type' => $tag->visibility_type,
                    'needcoin' => $tag->needcoin,
                    'icon' => \App\Helper\Helper::showImage($tag->icon, true),
                    'gif' => \App\Helper\Helper::showImage($tag->gif, true),
                    'status' => $tag->status,
                    'created_at' => $tag->created_at,
                ];
            });

            return response()->json([
                'status' => true,
                'message' => 'Entry tags fetched successfully',

                // user info add kiya
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'image' => \App\Helper\Helper::showImage($user->image, true),
                ],

                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getVoices(Request $request)
    {
        try {
            $user = Auth::user(); // login user

            $voices = \App\Models\Voice::where('visibility_type', 'in_app')->where('status', 1)
                ->latest()
                ->get();

            $data = $voices->map(function ($voice) {
                return [
                    'id' => $voice->id,
                    'name' => $voice->name,
                    'short_tag' => $voice->short_tag,
                    'validity' => $voice->validity,
                    'visibility_type' => $voice->visibility_type,
                    'needcoin' => $voice->needcoin,
                    'icon' => \App\Helper\Helper::showImage($voice->icon, true),
                    'gif' => \App\Helper\Helper::showImage($voice->gif, true),
                    'status' => $voice->status,
                    'created_at' => $voice->created_at,
                ];
            });

            return response()->json([
                'status' => true,
                'message' => 'Voices fetched successfully',

                // user info
                'user' => [
                    'id' => $user->id ?? null,
                    'name' => $user->name ?? null,
                    'image' => \App\Helper\Helper::showImage($user->image ?? null, true),
                ],

                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function sendUserGift(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'receiver_id' => 'required|exists:app_users,id',
            'item_id'     => 'required|integer',
            'type'        => 'required',
            'days'        => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        $sender = Auth::user();
        $receiverId = $request->receiver_id;
        $itemId = $request->item_id;
        $type = strtolower($request->type);
        $days = (int)$request->days;
        $qty = 1;

        DB::beginTransaction();
        try {

            if ($type == 'id') {

                $hasPremiumUid = PremiumNumber::where('user_id', $receiverId)
                    ->where('end_at', '>', now())
                    ->exists();

                if ($hasPremiumUid) {
                    return response()->json([
                        'status' => false,
                        'message' => 'User already has an active Premium UID.'
                    ], 400);
                }

                $alreadyDelivery = DB::table('item_deliveries')
                    ->where('recipient', $receiverId)
                    ->where('type', $type)
                    ->where('item_id', $itemId)
                    ->where('end_at', '>', now())
                    ->exists();

                $alreadyGift = DB::table('item_gift_transactions')
                    ->where('receiver_id', $receiverId)
                    ->where('type', $type)
                    ->where('item_id', $itemId)
                    ->where(function ($q) {
                        $q->whereNull('end_at')
                            ->orWhere('end_at', '>', now());
                    })
                    ->exists();

                if ($alreadyDelivery || $alreadyGift) {
                    return response()->json([
                        'status' => false,
                        'message' => 'User already has this item active'
                    ], 400);
                }

                $uidAlreadyPurchased = DB::table('item_deliveries')
                    ->where('type', 'id')
                    ->where('item_id', $itemId)
                    ->where('end_at', '>', now())
                    ->exists();

                $uidAlreadyGifted = DB::table('item_gift_transactions')
                    ->where('type', 'id')
                    ->where('item_id', $itemId)
                    ->where('end_at', '>', now())
                    ->exists();

                if ($uidAlreadyPurchased || $uidAlreadyGifted) {
                    return response()->json([
                        'status' => false,
                        'message' => 'This UID is already in use.'
                    ], 400);
                }
            }

            switch ($type) {
                case 'theme':
                    $item = \App\Models\Theme::find($itemId);
                    break;
                case 'entry':
                    $item = \App\Models\Cars::find($itemId);
                    break;
                case 'frame':
                    $item = \App\Models\Frame::find($itemId);
                    break;
                case 'entry_tag':
                    $item = \App\Models\EntryTag::find($itemId);
                    break;
                case 'voice':
                    $item = \App\Models\Voice::find($itemId);
                    break;
                case 'profile_card':
                    $item = \App\Models\DataCard::find($itemId);
                    break;
                case 'chat_bubble':
                    $item = \App\Models\ChatBubble::find($itemId);
                    break;
                case 'id':
                    $item = \App\Models\StoreUids::find($itemId);
                    break;
                default:
                    $item = null;
            }

            if (!$item) {
                return response()->json([
                    'status' => false,
                    'message' => 'Item not found'
                ], 404);
            }

            $validity = is_string($item->validity) ? json_decode($item->validity, true) : ($item->validity ?? []);
            $needcoin = is_string($item->needcoin) ? json_decode($item->needcoin, true) : ($item->needcoin ?? []);

            $index = array_search($days, array_map('intval', $validity));

            if ($index === false || !isset($needcoin[$index])) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid duration selected'
                ], 422);
            }

            $price = (int)$needcoin[$index];
            $totalCoins = $price * $qty;

            if ($sender->total_points < $totalCoins) {
                return response()->json([
                    'status' => false,
                    'message' => 'Insufficient balance'
                ], 400);
            }

            $sender->decrement('total_points', $totalCoins);

            $startAt = now();
            $endAt = now()->addDays($days);

            DB::table('item_gift_transactions')->insert([
                'sender_id'   => $sender->id,
                'receiver_id' => $receiverId,
                'item_id'     => $itemId,
                'type'        => $type,
                'quantity'    => $qty,
                'total_coins' => $totalCoins,
                'days'        => $days,
                'start_at'    => $startAt,
                'end_at'      => $endAt,
                'created_at'  => now(),
                'updated_at'  => now()
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Gift sent successfully',
                'data' => [
                    'type' => $type,
                    'days' => $days,
                    'price_per_item' => $price,
                    'quantity' => $qty,
                    'deducted_coins' => $totalCoins,
                    'remaining_coins' => (int)$sender->fresh()->total_points
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

    // public function myItems($type)
    // {
    //     $user = Auth::user();
    //     $type = strtolower(trim($type));

    //     $modelMap = [
    //         'theme' => Theme::class,
    //         'entry' => Cars::class,
    //         'frame' => Frame::class,
    //         'entry_tag' => EntryTag::class,
    //         'voice' => Voice::class,
    //         'profile_card' => DataCard::class,
    //         'chat_bubble' => ChatBubble::class,
    //         'id' => StoreUids::class,
    //     ];

    //     $activeColumnMap = [
    //         'theme' => 'active_theme_id',
    //         'entry' => 'active_car_id',
    //         'frame' => 'active_frame_id',
    //         'entry_tag' => 'active_entry_id',
    //         'voice' => 'active_voice_id',
    //         'profile_card' => 'active_card_id',
    //         'chat_bubble' => 'active_chat_bubble_id',
    //         'id' => 'active_uid_id',
    //     ];

    //     if (!isset($modelMap[$type])) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Invalid type'
    //         ]);
    //     }

    //     // BUY ITEMS (valid time range only)
    //     $deliveryIds = DB::table('item_deliveries')
    //         ->where('recipient', $user->id)
    //         ->where('type', $type)
    //         ->where(function ($q) {
    //             $q->whereNull('start_at')
    //                 ->orWhere('start_at', '<=', now());
    //         })
    //         ->where(function ($q) {
    //             $q->whereNull('end_at')
    //                 ->orWhere('end_at', '>', now());
    //         })
    //         ->pluck('item_id')
    //         ->toArray();

    //     // GIFT ITEMS (valid time range only)
    //     $giftIds = DB::table('item_gift_transactions')
    //         ->where('receiver_id', $user->id)
    //         ->where('type', $type)
    //         ->where(function ($q) {
    //             $q->whereNull('start_at')
    //                 ->orWhere('start_at', '<=', now());
    //         })
    //         ->where(function ($q) {
    //             $q->whereNull('end_at')
    //                 ->orWhere('end_at', '>', now());
    //         })
    //         ->pluck('item_id')
    //         ->toArray();

    //     $ids = collect(array_merge($deliveryIds, $giftIds))
    //         ->unique()
    //         ->values()
    //         ->toArray();

    //     // CUSTOM THEMES
    //     if ($type === 'theme') {
    //         $customThemeIds = Theme::where('user_id', $user->id)
    //             ->where('status', 1)
    //             ->pluck('id')
    //             ->toArray();

    //         $ids = collect(array_merge($ids, $customThemeIds))
    //             ->unique()
    //             ->values()
    //             ->toArray();
    //     }

    //     $items = $modelMap[$type]::whereIn('id', $ids)
    //         ->where('status', 1)
    //         ->get();

    //     $activeId = $user->{$activeColumnMap[$type]} ?? null;

    //     foreach ($items as $item) {
    //         if (isset($item->icon)) {
    //             $item->icon = Helper::showImage($item->icon, true);
    //         }

    //         if (isset($item->gif) && !empty($item->gif)) {
    //             $item->gif = Helper::showImage($item->gif, true);
    //         }

    //         if (isset($item->badge) && !empty($item->badge)) {
    //             $item->badge = Helper::showImage($item->badge, true);
    //         }

    //         $item->in_use = ((int) $item->id === (int) $activeId);
    //     }

    //     return response()->json([
    //         'status' => true,
    //         'user_id' => $user->id,
    //         'type' => $type,
    //         'ids' => $ids,
    //         'data' => $items
    //     ]);
    // }


    public function myItems($type)
    {
        $user = Auth::user();
        $type = strtolower(trim($type));

        $modelMap = [
            'theme' => Theme::class,
            'entry' => Cars::class,
            'frame' => Frame::class,
            'entry_tag' => EntryTag::class,
            'voice' => Voice::class,
            'profile_card' => DataCard::class,
            'chat_bubble' => ChatBubble::class,
            'id' => StoreUids::class,
        ];

        $activeColumnMap = [
            'theme' => 'active_theme_id',
            'entry' => 'active_car_id',
            'frame' => 'active_frame_id',
            'entry_tag' => 'active_entry_id',
            'voice' => 'active_voice_id',
            'profile_card' => 'active_card_id',
            'chat_bubble' => 'active_chat_bubble_id',
            'id' => 'active_uid_id',
        ];

        if (!isset($modelMap[$type])) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid type'
            ]);
        }

        // BUY ITEMS (valid time range only)
        $deliveryIds = DB::table('item_deliveries')
            ->where('recipient', $user->id)
            ->where('type', $type)
            ->where(function ($q) {
                $q->whereNull('start_at')
                    ->orWhere('start_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_at')
                    ->orWhere('end_at', '>', now());
            })
            ->pluck('item_id')
            ->toArray();

        $deliveryItems = DB::table('item_deliveries')
            ->where('recipient', $user->id)
            ->where('type', $type)
            ->where(function ($q) {
                $q->whereNull('start_at')
                    ->orWhere('start_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_at')
                    ->orWhere('end_at', '>', now());
            })
            ->get();

        // GIFT ITEMS (valid time range only)
        $giftIds = DB::table('item_gift_transactions')
            ->where('receiver_id', $user->id)
            ->where('type', $type)
            ->where(function ($q) {
                $q->whereNull('start_at')
                    ->orWhere('start_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_at')
                    ->orWhere('end_at', '>', now());
            })
            ->pluck('item_id')
            ->toArray();

        // $treasuryIds = DB::table('treasure_level_claims')
        //     ->where('user_id', $user->id)
        //     ->where('reward_type', $type)
        //     ->pluck('reward_item_id')
        //     ->toArray();

        $ids = collect(array_merge(
            $deliveryIds,
            $giftIds,
            // $treasuryIds
        ))
            ->unique()
            ->values()
            ->toArray();

        // CUSTOM THEMES
        if ($type === 'theme') {
            $customThemeIds = Theme::where('user_id', $user->id)
                ->where('status', 1)
                ->pluck('id')
                ->toArray();

            $ids = collect(array_merge($ids, $customThemeIds))
                ->unique()
                ->values()
                ->toArray();
        }

        $items = $modelMap[$type]::whereIn('id', $ids)
            ->where('status', 1)
            ->get();

        $vips = VipTransaction::where('user_id', $user->id)
            ->where('end_at', '>', now())
            ->with('vip')
            ->get();
        //  dd($vips);

        // $treasuryVipIds = DB::table('treasure_level_claims')
        //     ->where('user_id', $user->id)
        //     ->where('reward_type', 'vip')
        //     ->pluck('reward_item_id')
        //     ->unique()
        //     ->toArray();

        // $treasuryVips = Vip::whereIn('id', $treasuryVipIds)->get();

        $svips = SvipTransaction::where('user_id', $user->id)
            ->where('end_at', '>', now())
            ->with('svip')
            ->get();

        $relations = RelationshipInvitation::where('status', 'accept')
            ->where(function ($q) use ($user) {
                $q->where('sender_id', $user->id)
                    ->orWhere('receiver_id', $user->id);
            })
            ->whereHas('relationshipItem', function ($q) {
                $q->where('type', 'cp');
            })
            ->with('relationshipItem')
            ->get();

        foreach ($vips as $vipTransaction) {

            if (!$vipTransaction->vip) {
                continue;
            }

            $vip = $vipTransaction->vip;

            $validDays = now()->diffInDays(
                \Carbon\Carbon::parse($vipTransaction->end_at),
                false
            );

            $validDays = max(
                1,
                (int) now()->diffInDays(
                    \Carbon\Carbon::parse($vipTransaction->end_at),
                    false
                )
            );

            // FRAME TAB
            if ($type === 'frame' && !empty($vip->image_frame)) {

                $items->push((object)[
                    'id' => $vip->id,
                    'name' => $vip->name,
                    'validity' => [(string)$validDays],
                    'icon' => asset('storage/' . $vip->image_frame),

                    'gif' => !empty($vip->image_frame_animation)
                        ? asset('storage/' . $vip->image_frame_animation)
                        : null,
                    'status' => 1,
                    'is_vip' => true,
                ]);
            }

            // ENTRY TAG TAB
            if ($type === 'entry_tag' && !empty($vip->entry_tag)) {

                $items->push((object)[
                    'id' => $vip->id,
                    'name' => $vip->name,
                    'validity' => [(string)$validDays],
                    'icon' => asset('storage/' . $vip->entry_tag),
                    'gif' => !empty($vip->entry_tag_animation)
                        ? asset('storage/' . $vip->entry_tag_animation)
                        : null,
                    'status' => 1,
                    'is_vip' => true,
                ]);
            }

            // VOICE TAB
            if ($type === 'entry' && !empty($vip->voice_frame)) {

                $items->push((object)[
                    'id' => $vip->id,
                    'name' => $vip->name,
                    'validity' => [(string)$validDays],
                    'icon' => asset('storage/' . $vip->voice_frame),
                    'gif' => !empty($vip->voice_animation)
                        ? asset('storage/' . $vip->voice_animation)
                        : null,
                    'status' => 1,
                    'is_vip' => true,
                ]);
            }

            // PROFILE CARD TAB
            if ($type === 'profile_card' && !empty($vip->profile_frame)) {

                $items->push((object)[
                    'id' => $vip->id,
                    'name' => $vip->name,
                    'validity' => [(string)$validDays],
                    'icon' => asset('storage/' . $vip->profile_frame),
                    'gif' => !empty($vip->profile_frame_animation)
                        ? asset('storage/' . $vip->profile_frame_animation)
                        : null,
                    'status' => 1,
                    'is_vip' => true,
                ]);
            }

            // CHAT BUBBLE TAB
            if ($type === 'chat_bubble' && !empty($vip->chat_card)) {

                $items->push((object)[
                    'id' => $vip->id,
                    'name' => $vip->name,
                    'validity' => [(string)$validDays],
                    'icon' => asset('storage/' . $vip->chat_card),
                    'status' => 1,
                    'is_vip' => true,
                ]);
            }
        }

        // foreach ($treasuryVips as $vip) {

        //     if ($type === 'frame' && !empty($vip->image_frame)) {

        //         $items->push((object)[
        //             'id' => $vip->id,
        //             'name' => $vip->name,
        //             'validity' => [(string)$vip->days],
        //             'icon' => asset('storage/' . $vip->image_frame),

        //             'gif' => !empty($vip->image_frame_animation)
        //                 ? asset('storage/' . $vip->image_frame_animation)
        //                 : null,
        //             'status' => 1,
        //             'is_vip' => true,
        //         ]);
        //     }

        //     // ENTRY TAG TAB
        //     if ($type === 'entry_tag' && !empty($vip->entry_tag)) {

        //         $items->push((object)[
        //             'id' => $vip->id,
        //             'name' => $vip->name,
        //             'validity' => [(string)$vip->days],
        //             'icon' => asset('storage/' . $vip->entry_tag),
        //             'gif' => !empty($vip->entry_tag_animation)
        //                 ? asset('storage/' . $vip->entry_tag_animation)
        //                 : null,
        //             'status' => 1,
        //             'is_vip' => true,
        //         ]);
        //     }

        //     // VOICE TAB
        //     if ($type === 'entry' && !empty($vip->voice_frame)) {

        //         $items->push((object)[
        //             'id' => $vip->id,
        //             'name' => $vip->name,
        //             'validity' => [(string)$vip->days],
        //             'icon' => asset('storage/' . $vip->voice_frame),
        //             'gif' => !empty($vip->voice_animation)
        //                 ? asset('storage/' . $vip->voice_animation)
        //                 : null,
        //             'status' => 1,
        //             'is_vip' => true,
        //         ]);
        //     }

        //     // PROFILE CARD TAB
        //     if ($type === 'profile_card' && !empty($vip->profile_frame)) {

        //         $items->push((object)[
        //             'id' => $vip->id,
        //             'name' => $vip->name,
        //             'validity' => [(string)$vip->days],
        //             'icon' => asset('storage/' . $vip->profile_frame),
        //             'gif' => !empty($vip->profile_frame_animation)
        //                 ? asset('storage/' . $vip->profile_frame_animation)
        //                 : null,
        //             'status' => 1,
        //             'is_vip' => true,
        //         ]);
        //     }

        //     // CHAT BUBBLE TAB
        //     if ($type === 'chat_bubble' && !empty($vip->chat_card)) {

        //         $items->push((object)[
        //             'id' => $vip->id,
        //             'name' => $vip->name,
        //             'validity' => [(string)$vip->days],
        //             'icon' => asset('storage/' . $vip->chat_card),
        //             'status' => 1,
        //             'is_vip' => true,
        //         ]);
        //     }
        // }



        foreach ($svips as $svipTransaction) {

            if (!$svipTransaction->svip) {
                continue;
            }

            $svip = $svipTransaction->svip;

            // FRAME TAB
            if ($type === 'frame' && !empty($svip->headwear)) {

                $items->push((object)[
                    'id' => $svip->id,
                    'name' => $svip->name,
                    'validity' => [(string)$svip->days],
                    'icon' => asset('storage/' . $svip->headwear),
                    'gif' => !empty($svip->headwear_animation)
                        ? asset('storage/' . $svip->headwear_animation)
                        : null,
                    'status' => 1,
                    'is_svip' => true,
                ]);
            }

            // ENTRY TAB (entrance effect)
            if ($type === 'entry' && !empty($svip->entrance_image)) {

                $items->push((object)[
                    'id' => $svip->id,
                    'name' => $svip->name,
                    'validity' => [(string)$svip->days],
                    'icon' => asset('storage/' . $svip->entrance_image),
                    'gif' => !empty($svip->entrance_animation)
                        ? asset('storage/' . $svip->entrance_animation)
                        : null,
                    'status' => 1,
                    'is_svip' => true,
                ]);
            }

            // ENTRY TAG TAB
            if ($type === 'entry_tag' && !empty($svip->entry)) {

                $items->push((object)[
                    'id' => $svip->id,
                    'name' => $svip->name,
                    'validity' => [(string)$svip->days],
                    'icon' => asset('storage/' . $svip->entry),
                    'gif' => !empty($svip->entry_animation)
                        ? asset('storage/' . $svip->entry_animation)
                        : null,
                    'status' => 1,
                    'is_svip' => true,
                ]);
            }

            // VOICE TAB
            if ($type === 'voice' && !empty($svip->voice_image)) {

                $items->push((object)[
                    'id' => $svip->id,
                    'name' => $svip->name,
                    'validity' => [(string)$svip->days],
                    'icon' => asset('storage/' . $svip->voice_image),
                    'gif' => !empty($svip->voice_animation)
                        ? asset('storage/' . $svip->voice_animation)
                        : null,
                    'status' => 1,
                    'is_svip' => true,
                ]);
            }

            // PROFILE CARD TAB
            if ($type === 'profile_card' && !empty($svip->profile_card)) {

                $items->push((object)[
                    'id' => $svip->id,
                    'name' => $svip->name,
                    'validity' => [(string)$svip->days],
                    'icon' => asset('storage/' . $svip->profile_card),
                    'gif' => !empty($svip->profile_animation)
                        ? asset('storage/' . $svip->profile_animation)
                        : null,
                    'status' => 1,
                    'is_svip' => true,
                ]);
            }

            // CHAT BUBBLE TAB
            if ($type === 'chat_bubble' && !empty($svip->bubble)) {

                $items->push((object)[
                    'id' => $svip->id,
                    'name' => $svip->name,
                    'validity' => [(string)$svip->days],
                    'icon' => asset('storage/' . $svip->bubble),
                    'status' => 1,
                    'is_svip' => true,
                ]);
            }
        }

        // foreach ($relations as $relation) {

        //     if (!$relation->relationshipItem) {
        //         continue;
        //     }

        //     $item = $relation->relationshipItem;

        //     if (
        //         $type === 'frame' &&
        //         !empty($item->frame)
        //     ) {

        //         $items->push((object)[
        //             'id' => $item->id,
        //             'name' => $item->name,
        //             'icon' => asset('storage/' . $item->frame),
        //             'status' => 1,
        //             'is_cp' => true,
        //             'relation_type' => $item->type,
        //         ]);
        //     }
        // }

        foreach ($relations as $relation) {

            $cp = $relation->relationshipItem;

            if ($type === 'frame' && !empty($cp->frame)) {

                $items->push((object)[
                    'id' => $cp->id,
                    'name' => $cp->name,
                    'icon' => asset('storage/' . $cp->frame),
                    'status' => 1,
                    'is_cp' => true,
                ]);
            }
        }
        $activeId = $user->{$activeColumnMap[$type]} ?? null;

        $activeTypeColumnMap = [
            'frame' => 'active_frame_type',
            'entry' => 'active_entry_type',
            'entry_tag' => 'active_entry_tag_type',
            'profile_card' => 'active_profile_card_type',
            'chat_bubble' => 'active_chat_bubble_type',
            'voice' => 'active_voice_type',
        ];

        $activeType = $activeTypeColumnMap[$type]
            ? $user->{$activeTypeColumnMap[$type]} ?? 'store'
            : 'store';

        foreach ($items as $item) {

            if (
                empty($item->is_vip) &&
                empty($item->is_svip) &&
                empty($item->is_cp)
            ) {

                $delivery = $deliveryItems
                    ->where('item_id', $item->id)
                    ->sortByDesc('id')
                    ->first();

                if (
                    $delivery &&
                    !empty($delivery->valid_days)
                ) {
                    $item->validity = [
                        (string)$delivery->valid_days
                    ];
                }

                if (isset($item->icon)) {
                    $item->icon = Helper::showImage($item->icon, true);
                }

                if (isset($item->gif) && !empty($item->gif)) {
                    $item->gif = Helper::showImage($item->gif, true);
                }

                if (isset($item->badge) && !empty($item->badge)) {
                    $item->badge = Helper::showImage($item->badge, true);
                }
            }

            $item->in_use = false;

            if (
                empty($item->is_vip) &&
                empty($item->is_svip) &&
                empty($item->is_cp)
            ) {
                $item->in_use =
                    ((int)$item->id === (int)$activeId)
                    && ($activeType === 'store');
            } elseif (!empty($item->is_vip)) {

                $item->in_use =
                    ((int)$item->id === (int)$activeId)
                    && ($activeType === 'vip');
            } elseif (!empty($item->is_svip)) {

                $item->in_use =
                    ((int)$item->id === (int)$activeId)
                    && ($activeType === 'svip');
            } elseif (!empty($item->is_cp)) {

                $item->in_use =
                    ((int)$item->id === (int)$activeId)
                    && ($activeType === 'cp');
            }
        }

        return response()->json([
            'status' => true,
            'user_id' => $user->id,
            'type' => $type,
            'ids' => $ids,
            'data' => $items
        ]);
    }

    // public function useMyItem(Request $request)
    // {
    //     $validator = \Validator::make($request->all(), [
    //         'type' => 'required|string',
    //         'item_id' => 'nullable|integer',
    //         'action' => 'nullable|in:apply,remove'
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => $validator->errors()
    //         ], 422);
    //     }

    //     $user = Auth::user();

    //     $type = strtolower(trim($request->type));

    //     $itemId = $request->item_id;

    //     $action = strtolower($request->action ?? 'apply');

    //     $columnMap = [
    //         'theme' => 'active_theme_id',
    //         'entry' => 'active_car_id',
    //         'frame' => 'active_frame_id',
    //         'entry_tag' => 'active_entry_id',
    //         'voice' => 'active_voice_id',
    //         'profile_card' => 'active_card_id',
    //         'chat_bubble' => 'active_chat_bubble_id',
    //         'id' => 'active_uid_id',
    //     ];

    //     if (!isset($columnMap[$type])) {

    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Invalid type'
    //         ], 422);
    //     }

    //     $column = $columnMap[$type];

    //     //  REMOVE ITEM

    //     if ($action === 'remove') {

    //         $user->update([
    //             $column => null
    //         ]);

    //         return response()->json([
    //             'status' => true,
    //             'message' => ucfirst($type) . ' removed successfully',
    //             'data' => [
    //                 'type' => $type,
    //                 'item_id' => null,
    //                 'action' => 'remove'
    //             ]
    //         ]);
    //     }

    //     // APPLY ITEM

    //     if (!$itemId) {

    //         return response()->json([
    //             'status' => false,
    //             'message' => 'item_id is required for apply action'
    //         ], 422);
    //     }

    //     $existsInGift = DB::table('item_gift_transactions')
    //         ->where('receiver_id', $user->id)
    //         ->whereRaw('LOWER(type) = ?', [$type])
    //         ->where('item_id', $itemId)
    //         ->exists();

    //     $existsInDelivery = DB::table('item_deliveries')
    //         ->where('recipient', $user->id)
    //         ->whereRaw('LOWER(type) = ?', [$type])
    //         ->where('item_id', $itemId)
    //         ->where(function ($q) {
    //             $q->whereNull('end_at')
    //                 ->orWhere('end_at', '>=', now());
    //         })
    //         ->exists();

    //     $exists = $existsInGift || $existsInDelivery;

    //     if (!$exists) {

    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Item not owned or expired'
    //         ], 403);
    //     }

    //     $user->update([
    //         $column => $itemId
    //     ]);

    //     return response()->json([
    //         'status' => true,
    //         'message' => ucfirst($type) . ' applied successfully',
    //         'data' => [
    //             'type' => $type,
    //             'item_id' => $itemId,
    //             'action' => 'apply'
    //         ]
    //     ]);
    // }



    public function useMyItem(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|string',
            'item_id' => 'nullable|integer',
            // 'source' => 'nullable|in:store,vip,svip,cp',
            'action' => 'nullable|in:apply,remove'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();

        $type = strtolower(trim($request->type));
        $itemId = $request->item_id;
        $source = strtolower($request->source ?? 'store');
        $action = strtolower($request->action ?? 'apply');

        $columnMap = [
            'theme' => 'active_theme_id',
            'entry' => 'active_car_id',
            'frame' => 'active_frame_id',
            'entry_tag' => 'active_entry_id',
            'voice' => 'active_voice_id',
            'profile_card' => 'active_card_id',
            'chat_bubble' => 'active_chat_bubble_id',
            'id' => 'active_uid_id',
        ];

        $typeColumnMap = [
            'frame' => 'active_frame_type',
            'entry' => 'active_entry_type',
            'entry_tag' => 'active_entry_tag_type',
            'voice' => 'active_voice_type',
            'profile_card' => 'active_profile_card_type',
            'chat_bubble' => 'active_chat_bubble_type',
        ];

        if (!isset($columnMap[$type])) {

            return response()->json([
                'status' => false,
                'message' => 'Invalid type'
            ], 422);
        }

        $column = $columnMap[$type];

        /*
    |--------------------------------------------------------------------------
    | REMOVE ITEM
    |--------------------------------------------------------------------------
    */

        if ($action === 'remove') {

            $updateData = [
                $column => null
            ];

            if (isset($typeColumnMap[$type])) {
                $updateData[$typeColumnMap[$type]] = null;
            }

            $user->update($updateData);

            return response()->json([
                'status' => true,
                'message' => ucfirst($type) . ' removed successfully',
                'data' => [
                    'type' => $type,
                    'item_id' => null,
                    'source' => null,
                    'action' => 'remove'
                ]
            ]);
        }

        /*
    |--------------------------------------------------------------------------
    | APPLY ITEM
    |--------------------------------------------------------------------------
    */

        if (!$itemId) {

            return response()->json([
                'status' => false,
                'message' => 'item_id is required for apply action'
            ], 422);
        }

        /*
    |--------------------------------------------------------------------------
    | VIP ITEM
    |--------------------------------------------------------------------------
    */

        if ($source === 'vip') {

            $vipExists = VipTransaction::where('user_id', $user->id)
                ->where('vip_id', $itemId)
                ->where('end_at', '>', now())
                ->exists();

            if (!$vipExists) {

                return response()->json([
                    'status' => false,
                    'message' => 'VIP not owned or expired'
                ], 403);
            }

            $updateData = [
                $column => $itemId
            ];

            if (isset($typeColumnMap[$type])) {
                $updateData[$typeColumnMap[$type]] = 'vip';
            }

            $user->update($updateData);

            return response()->json([
                'status' => true,
                'message' => ucfirst($type) . ' applied successfully',
                'data' => [
                    'type' => $type,
                    'item_id' => $itemId,
                    'source' => 'vip',
                    'action' => 'apply'
                ]
            ]);
        }

        /*
    |--------------------------------------------------------------------------
    | SVIP ITEM
    |--------------------------------------------------------------------------
    */

        if ($source === 'svip') {

            $svipExists = SvipTransaction::where('user_id', $user->id)
                ->where('svip_id', $itemId)
                ->where('end_at', '>', now())
                ->exists();

            if (!$svipExists) {

                return response()->json([
                    'status' => false,
                    'message' => 'SVIP not owned or expired'
                ], 403);
            }

            $updateData = [
                $column => $itemId
            ];

            if (isset($typeColumnMap[$type])) {
                $updateData[$typeColumnMap[$type]] = 'svip';
            }

            $user->update($updateData);

            return response()->json([
                'status' => true,
                'message' => ucfirst($type) . ' applied successfully',
                'data' => [
                    'type' => $type,
                    'item_id' => $itemId,
                    'source' => 'svip',
                    'action' => 'apply'
                ]
            ]);
        }

        /*
    |--------------------------------------------------------------------------
    | CP / RELATIONSHIP FRAME
    |--------------------------------------------------------------------------
    */

        if ($source === 'cp') {

            $relationshipExists = RelationshipInvitation::where(function ($q) use ($user) {
                $q->where('sender_id', $user->id)
                    ->orWhere('receiver_id', $user->id);
            })
                ->where('relationship_item_id', $itemId)
                ->where('status', 'accept')
                ->exists();

            if (!$relationshipExists) {

                return response()->json([
                    'status' => false,
                    'message' => 'Relationship item not owned'
                ], 403);
            }

            $updateData = [
                $column => $itemId
            ];

            if (isset($typeColumnMap[$type])) {
                $updateData[$typeColumnMap[$type]] = 'cp';
            }

            $user->update($updateData);

            return response()->json([
                'status' => true,
                'message' => ucfirst($type) . ' applied successfully',
                'data' => [
                    'type' => $type,
                    'item_id' => $itemId,
                    'source' => 'cp',
                    'action' => 'apply'
                ]
            ]);
        }

        /*
    |--------------------------------------------------------------------------
    | STORE ITEM
    |--------------------------------------------------------------------------
    */

        $existsInGift = DB::table('item_gift_transactions')
            ->where('receiver_id', $user->id)
            ->whereRaw('LOWER(type) = ?', [$type])
            ->where('item_id', $itemId)
            ->where(function ($q) {
                $q->whereNull('end_at')
                    ->orWhere('end_at', '>=', now());
            })
            ->exists();

        $existsInDelivery = DB::table('item_deliveries')
            ->where('recipient', $user->id)
            ->whereRaw('LOWER(type) = ?', [$type])
            ->where('item_id', $itemId)
            ->where(function ($q) {
                $q->whereNull('end_at')
                    ->orWhere('end_at', '>=', now());
            })
            ->exists();

        $exists = $existsInGift || $existsInDelivery;

        if (!$exists) {

            return response()->json([
                'status' => false,
                'message' => 'Item not owned or expired'
            ], 403);
        }

        $updateData = [
            $column => $itemId
        ];

        if (isset($typeColumnMap[$type])) {
            $updateData[$typeColumnMap[$type]] = 'store';
        }

        $user->update($updateData);

        return response()->json([
            'status' => true,
            'message' => ucfirst($type) . ' applied successfully',
            'data' => [
                'type' => $type,
                'item_id' => $itemId,
                'source' => 'store',
                'action' => 'apply'
            ]
        ]);
    }

    public function buyItem(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'item_id' => 'required|integer',
            'type'    => 'required|string',
            'days'    => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();
        $itemId = $request->item_id;
        $type = strtolower($request->type);
        $days = (int) $request->days;

        DB::beginTransaction();
        try {

            $modelMap = [
                'theme' => Theme::class,
                'entry' => Cars::class,
                'frame' => Frame::class,
                'entry_tag' => EntryTag::class,
                'voice' => Voice::class,
                'profile_card' => DataCard::class,
                'chat_bubble' => ChatBubble::class,
                'id' => StoreUids::class,
            ];

            if (!isset($modelMap[$type])) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid type'
                ]);
            }

            $item = $modelMap[$type]::find($itemId);

            if (!$item) {
                return response()->json([
                    'status' => false,
                    'message' => 'Item not found'
                ]);
            }

            if ($type == 'id') {

                $hasActivePremiumUid = PremiumNumber::where('user_id', $user->id)
                    ->where('end_at', '>', now())
                    ->exists();

                if ($hasActivePremiumUid) {
                    return response()->json([
                        'status' => false,
                        'message' => 'You already have an active Premium UID.'
                    ], 400);
                }

                // User already has active Store UID
                $hasStoreUidPurchase = DB::table('item_deliveries')
                    ->where('recipient', $user->id)
                    ->where('type', 'id')
                    ->where('end_at', '>', now())
                    ->exists();

                $hasStoreUidGift = DB::table('item_gift_transactions')
                    ->where('receiver_id', $user->id)
                    ->where('type', 'id')
                    ->where('end_at', '>', now())
                    ->exists();

                if ($hasStoreUidPurchase || $hasStoreUidGift) {
                    return response()->json([
                        'status' => false,
                        'message' => 'You already have an active Store UID.'
                    ], 400);
                }

                $isPurchased = DB::table('item_deliveries')
                    ->where('type', 'id')
                    ->where('item_id', $itemId)
                    ->where('end_at', '>', now())
                    ->exists();

                $isGifted = DB::table('item_gift_transactions')
                    ->where('type', 'id')
                    ->where('item_id', $itemId)
                    ->where('end_at', '>', now())
                    ->exists();

                if ($isPurchased || $isGifted) {
                    return response()->json([
                        'status' => false,
                        'message' => 'This UID is already owned by another user.'
                    ], 400);
                }
            }

            // ALREADY PURCHASED CHECK
            $alreadyDelivery = DB::table('item_deliveries')
                ->where('recipient', $user->id)
                ->where('type', $type)
                ->where('item_id', $itemId)
                ->where('end_at', '>', now())
                ->exists();

            $alreadyGift = DB::table('item_gift_transactions')
                ->where('receiver_id', $user->id)
                ->where('type', $type)
                ->where('item_id', $itemId)
                ->where(function ($q) {
                    $q->whereNull('end_at')
                        ->orWhere('end_at', '>', now());
                })
                ->exists();

            if ($alreadyDelivery || $alreadyGift) {
                return response()->json([
                    'status' => false,
                    'message' => 'Item already purchased'
                ], 400);
            }

            $price = 0;

            if (is_array($item->needcoin)) {
                $validities = array_map('intval', $item->validity ?? []);
                $prices = array_map('intval', $item->needcoin ?? []);

                $index = array_search($days, $validities, true);

                if ($index === false || !isset($prices[$index])) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Invalid duration selected'
                    ]);
                }

                $price = $prices[$index];
            } else {
                $price = (int) $item->needcoin;
            }

            $totalCoins = $price;

            if ($user->total_points < $totalCoins) {
                return response()->json([
                    'status' => false,
                    'message' => 'Insufficient coins'
                ]);
            }

            $user->decrement('total_points', $totalCoins);

            $start = now();
            $end = now()->addDays($days);

            DB::table('item_deliveries')->insert([
                'recipient' => $user->id,
                'type' => $type,
                'item_id' => $itemId,
                'valid_days' => $days,
                'start_at' => $start,
                'end_at' => $end,
                'coins_used' => $totalCoins,
                'source' => 'self',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Item purchased successfully',
                'data' => [
                    'type' => $type,
                    'item_id' => $itemId,
                    'days' => $days,
                    'coins_used' => $totalCoins,
                    'remaining_coins' => (int) $user->fresh()->total_points,
                    'start_at' => $start,
                    'end_at' => $end
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
}
