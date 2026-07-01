<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Helper\Helper;
use App\Models\Room;
use App\Models\Theme;
use App\Models\ThemeGiven;
use App\Models\ItemDelivery;
use App\Models\ItemGiftTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Events\RoomThemeUpdated;

class ThemeController extends Controller
{
    public function themeList()
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

    public function buyTheme(Request $request)
    {
        $user = Auth::user();

        $validate = Validator::make($request->all(), [
            'theme_id' => 'required|exists:themes,id',
            'days'     => 'required|integer|min:1',
        ]);

        if ($validate->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validate->errors()
            ]);
        }

        $theme = Theme::where('id', $request->theme_id)
            ->where('status', 1)
            ->where('visibility_type', 'in_app')
            ->firstOrFail();

        $daysRequested = (int) $request->days;

        $validities = array_map('intval', $theme->validity);
        $prices     = array_map('intval', $theme->needcoin);

        $index = array_search($daysRequested, $validities, true);


        if ($index === false || !isset($prices[$index])) {
            return response()->json([
                'status'  => false,
                'message' => 'Invalid duration selected'
            ]);
        }

        $needCoin = (int) $prices[$index];

        $already = ThemeGiven::where('theme_id', $theme->id)
            ->where('user_id', $user->id)
            ->where('end_at', '>', now())
            ->exists();

        if ($already) {
            return response()->json([
                'status'  => false,
                'message' => 'Theme already active'
            ]);
        }

        if ($user->total_points < $needCoin) {
            return response()->json([
                'status'  => false,
                'message' => 'Insufficient coins'
            ]);
        }

        DB::transaction(function () use ($user, $theme, $needCoin, $daysRequested) {

            $user->decrement('total_points', $needCoin);

            ThemeGiven::create([
                'theme_id' => $theme->id,
                'user_id'  => $user->id,
                'source'   => 'buy',
                'duration' => $daysRequested,
                'start_at' => now(),
                'end_at'   => now()->addDays($daysRequested),
            ]);
        });

        return response()->json([
            'status'  => true,
            'message' => 'Theme purchased successfully'
        ]);
    }

    public function getOwnTheme()
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthenticated',
                ], 401);
            }

            $now = Carbon::now();

            $customThemes = Theme::where('user_id', $user->id)
                ->where('status', 1)
                ->get()
                ->map(function ($theme) {
                    return [
                        'ownership_type' => 'custom',
                        "delivery_id"    => null,
                        'item_id'        => $theme->id,
                        'type'           => 'theme',
                        'start_at'       => null,
                        'end_at'         => null,
                        "valid_days"     => null,
                        "coins_used"     => null,
                        "source"         => "custom",
                        'item'           => [
                            'id'    => $theme->id,
                            'name'  => $theme->name ?? null,
                            'image' => !empty($theme->icon)
                                ? \Helper::showImage($theme->icon, true)
                                : null,
                        ],
                    ];
                });

            // Self purchased themes from item_deliveries

            $purchasedThemes = ItemDelivery::with(['theme'])
                ->where('recipient', $user->id)
                ->where('type', 'theme')
                ->where(function ($q) use ($now) {
                    $q->whereNull('start_at')
                        ->orWhere('start_at', '<=', $now);
                })
                ->where(function ($q) use ($now) {
                    $q->whereNull('end_at')
                        ->orWhere('end_at', '>=', $now);
                })
                ->get()
                ->map(function ($row) {
                    return [
                        'ownership_type' => 'self_purchase',
                        'delivery_id'    => $row->id,
                        'item_id'        => $row->item_id,
                        'type'           => $row->type,
                        'start_at'       => $row->start_at,
                        'end_at'         => $row->end_at,
                        'valid_days'     => $row->valid_days,
                        'coins_used'     => $row->coins_used,
                        'source'         => $row->source,
                        'item'           => $row->theme ? [
                            'id'          => $row->theme->id,
                            'name'        => $row->theme->name ?? null,
                            'image'       => !empty($row->theme->icon)
                                ? \Helper::showImage($row->theme->icon, true)
                                : null
                        ] : null,
                    ];
                });

            // Gifted themes from item_gift_transactions

            $giftedThemes = ItemGiftTransaction::with(['theme', 'sender:id,name,image'])
                ->where('receiver_id', $user->id)
                ->where('type', 'theme')
                ->where(function ($q) use ($now) {
                    $q->whereNull('start_at')
                        ->orWhere('start_at', '<=', $now);
                })
                ->where(function ($q) use ($now) {
                    $q->whereNull('end_at')
                        ->orWhere('end_at', '>=', $now);
                })
                ->get()
                ->map(function ($row) {
                    return [
                        'ownership_type' => 'gifted',
                        'gift_id'        => $row->id,
                        'item_id'        => $row->item_id,
                        'type'           => $row->type,
                        'quantity'       => $row->quantity,
                        'days'           => $row->days,
                        'total_coins'    => $row->total_coins,
                        'start_at'       => $row->start_at,
                        'end_at'         => $row->end_at,
                        'sender'         => $row->sender ? [
                            'id'    => $row->sender->id,
                            'name'  => $row->sender->name,
                            'image' => !empty($row->sender->image)
                                ? \Helper::showImage($row->sender->image, true)
                                : null,
                        ] : null,
                        'item'           => $row->theme ? [
                            'id'          => $row->theme->id,
                            'name'        => $row->theme->name ?? null,
                            'image'       => !empty($row->theme->icon)
                                ? \Helper::showImage($row->theme->icon, true)
                                : null,
                        ] : null,
                    ];
                });

            // Merge both collections

            $themes = $customThemes
                ->concat($purchasedThemes)
                ->concat($giftedThemes)
                ->sortByDesc(function ($item) {
                    return $item['end_at'] ?? now()->addYears(10);
                })
                ->values();

            return response()->json([
                'status' => true,
                'message' => 'My active themes fetched successfully',
                'data' => $themes,
            ]);
        } catch (\Throwable $e) {
            \Log::error('MY THEMES API ERROR', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function useTheme(Request $request): JsonResponse
    {
        $validate = Validator::make($request->all(), [
            'theme_id' => 'required|integer|exists:themes,id',
            'room_id'  => 'required|integer|exists:rooms,id',
        ]);

        if ($validate->fails()) {
            return response()->json([
                'status'  => false,
                'message' => $validate->errors(),
            ], 422);
        }

        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'status'  => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        DB::beginTransaction();

        try {
            $now = now();

            $theme = Theme::where('id', $request->theme_id)
                ->where('status', 1)
                ->lockForUpdate()
                ->first();

            if (!$theme) {
                DB::rollBack();
                return response()->json([
                    'status'  => false,
                    'message' => 'Theme not found or inactive',
                ], 404);
            }

            $hasAccess = false;
            $accessType = null;

            if ($theme->user_id !== null) {
                if ((int) $theme->user_id === (int) $user->id) {
                    $hasAccess = true;
                    $accessType = 'custom';
                }
            } else {
                $selfPurchased = ItemDelivery::where('recipient', $user->id)
                    ->where('item_id', $theme->id)
                    ->where('type', 'theme')
                    ->where(function ($q) use ($now) {
                        $q->whereNull('start_at')
                            ->orWhere('start_at', '<=', $now);
                    })
                    ->where(function ($q) use ($now) {
                        $q->whereNull('end_at')
                            ->orWhere('end_at', '>=', $now);
                    })
                    ->exists();

                $gifted = ItemGiftTransaction::where('receiver_id', $user->id)
                    ->where('item_id', $theme->id)
                    ->where('type', 'theme')
                    ->where(function ($q) use ($now) {
                        $q->whereNull('start_at')
                            ->orWhere('start_at', '<=', $now);
                    })
                    ->where(function ($q) use ($now) {
                        $q->whereNull('end_at')
                            ->orWhere('end_at', '>=', $now);
                    })
                    ->exists();

                if ($selfPurchased) {
                    $hasAccess = true;
                    $accessType = 'self_purchase';
                } elseif ($gifted) {
                    $hasAccess = true;
                    $accessType = 'gifted';
                }
            }

            if (!$hasAccess) {
                DB::rollBack();
                return response()->json([
                    'status'  => false,
                    'message' => 'You do not have access to this theme or it has expired',
                ], 403);
            }

            $room = Room::where('id', $request->room_id)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if (!$room) {
                DB::rollBack();
                return response()->json([
                    'status'  => false,
                    'message' => 'Room not found or you are not the owner',
                ], 404);
            }

            if ((int) $room->active_theme_id === (int) $theme->id) {
                DB::commit();

                $themeData = [
                    'id'        => $theme->id,
                    'name'      => $theme->name ?? null,
                    'image'     => !empty($theme->icon) ? \Helper::showImage($theme->icon, true) : null,
                    'status'    => $theme->status,
                ];

                return response()->json([
                    'status'  => true,
                    'message' => 'Theme already applied',
                    'data'    => [
                        'room_id'         => $room->id,
                        'active_theme_id' => $room->active_theme_id,
                        'access_type'     => $accessType,
                        'theme'           => $themeData,
                    ],
                ]);
            }

            $room->update([
                'active_theme_id' => $theme->id,
            ]);

            $room->refresh();

            $themeData = [
                'id'        => $theme->id,
                'name'      => $theme->name ?? null,
                'image'     => !empty($theme->icon) ? \Helper::showImage($theme->icon, true) : null,
                'status'    => $theme->status,
            ];

            event(new RoomThemeUpdated($room->id, [
                'room_id'         => $room->id,
                'active_theme_id' => $theme->id,
                'access_type'     => $accessType,
                'theme'           => $themeData,
                'message'         => 'Room theme updated successfully',
            ]));

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => 'Theme applied successfully',
                'data'    => [
                    'room_id'         => $room->id,
                    'active_theme_id' => $room->active_theme_id,
                    'access_type'     => $accessType,
                    'theme'           => $themeData,
                ],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('USE THEME API ERROR', [
                'message'  => $e->getMessage(),
                'line'     => $e->getLine(),
                'file'     => $e->getFile(),
                'user_id'  => $user?->id,
                'room_id'  => $request->room_id,
                'theme_id' => $request->theme_id,
            ]);

            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }


    public function uploadUserTheme(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'  => 'required|string|max:100',
            'icon'  => 'required|file|mimes:jpg,jpeg,png,gif,webp|max:5120',
            'coins' => 'required|integer|min:1',
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

        $uploadedPath = null;

        DB::beginTransaction();

        try {
            $user = \App\Models\AppUser::where('id', $authUser->id)
                ->lockForUpdate()
                ->first();

            if (!$user) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'User not found',
                ], 404);
            }

            $coins = (int) $request->coins;

            if ((int) $user->total_points < $coins) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Insufficient coins',
                ], 400);
            }

            if ($request->hasFile('icon')) {
                $uploadedPath = Helper::saveFile($request->file('icon'), 'theme_images');
            }

            $user->decrement('total_points', $coins);

            $theme = Theme::create([
                'name'            => trim($request->name),
                'icon'            => $uploadedPath,
                'user_id'         => $user->id,
                'visibility_type' => 'user',
                'status'          => 1,
            ]);

            DB::commit();

            $user->refresh();

            return response()->json([
                'status'  => true,
                'message' => 'Theme uploaded successfully',
                'data'    => [
                    'id'              => $theme->id,
                    'name'            => $theme->name,
                    'icon'            => $theme->icon ? Helper::showImage($theme->icon, true) : null,
                    'remaining_coins' => (int) $user->total_points,
                ]
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            if ($uploadedPath && file_exists(storage_path('app/public/' . $uploadedPath))) {
                @unlink(storage_path('app/public/' . $uploadedPath));
            }

            \Log::error('UPLOAD USER THEME API ERROR', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile(),
                'user_id' => $authUser?->id,
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
