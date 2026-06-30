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
use Illuminate\Http\Request;
use App\Helper\Helper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Rank;
use App\Models\Pattern;


class StoreController extends Controller
{
    public function getEntry(Request $request)
    {
        try {
            $tags = Cars::where('status', 1)
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
            $bubbles = \App\Models\ChatBubble::where('status', 1)
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

            $frames = \App\Models\Frame::where('status', 1)
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
            $themes = \App\Models\Theme::whereNull('user_id')->where('status', 1)
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
            $storeUids = \App\Models\StoreUids::with(['rank', 'pattern'])
                ->where('status', 1)
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

            $cards = DataCard::where('status', 1)
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

            $tags = \App\Models\EntryTag::where('status', 1)
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

            $voices = \App\Models\Voice::where('status', 1)
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

    //     // BUY ITEMS (with expiry)
    //     $deliveryIds = DB::table('item_deliveries')
    //         ->where('recipient', $user->id)
    //         ->where('type', $type)
    //         ->where('end_at', '>', now())
    //         ->pluck('item_id')
    //         ->toArray();

    //     // GIFT ITEMS
    //     $giftIds = DB::table('item_gift_transactions')
    //         ->where('receiver_id', $user->id)
    //         ->where('type', $type)
    //         ->pluck('item_id')
    //         ->toArray();

    //     $ids = collect(array_merge($deliveryIds, $giftIds))
    //         ->unique()
    //         ->values()
    //         ->toArray();

    //     $items = $modelMap[$type]::whereIn('id', $ids)->get();

    //     $activeId = $user->{$activeColumnMap[$type]} ?? null;

    //     foreach ($items as $item) {
    //         if (isset($item->icon)) {
    //             $item->icon = Helper::showImage($item->icon, true);
    //         }

    //         $item->in_use = ($item->id == $activeId);
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

    $ids = collect(array_merge($deliveryIds, $giftIds))
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

    $activeId = $user->{$activeColumnMap[$type]} ?? null;

    foreach ($items as $item) {
        if (isset($item->icon)) {
            $item->icon = Helper::showImage($item->icon, true);
        }

        if (isset($item->gif) && !empty($item->gif)) {
            $item->gif = Helper::showImage($item->gif, true);
        }

        if (isset($item->badge) && !empty($item->badge)) {
            $item->badge = Helper::showImage($item->badge, true);
        }

        $item->in_use = ((int) $item->id === (int) $activeId);
    }

    return response()->json([
        'status' => true,
        'user_id' => $user->id,
        'type' => $type,
        'ids' => $ids,
        'data' => $items
    ]);
}
    
    public function useMyItem(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'type' => 'required|string',
            'item_id' => 'required|integer'
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

        $existsInGift = DB::table('item_gift_transactions')
            ->where('receiver_id', $user->id)
            ->whereRaw('LOWER(type) = ?', [$type])
            ->where('item_id', $itemId)
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

        if (!isset($columnMap[$type])) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid type'
            ]);
        }

        $user->update([
            $columnMap[$type] => $itemId
        ]);

        return response()->json([
            'status' => true,
            'message' => ucfirst($type) . ' applied successfully',
            'data' => [
                'type' => $type,
                'item_id' => $itemId
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
