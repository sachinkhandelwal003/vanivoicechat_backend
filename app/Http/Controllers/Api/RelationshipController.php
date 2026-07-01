<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RelationshipItem;
use App\Models\RelationshipInvitation;
use App\Models\AppUser;
use App\Models\GiftTransaction;
use App\Models\BdUser;
use App\Models\Agency;
use App\Models\Host;
use App\Models\CoinSeller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Helper\Helper;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


class RelationshipController extends Controller
{
    // public function index(Request $request)
    // {
    //     $query = RelationshipItem::where('status', 1);

    //     if ($request->type) {
    //         $query->where('type', $request->type);
    //     }

    //     $items = $query->get();

    //     $grouped = [];

    //     foreach ($items as $item) {

    //         $grouped[$item->type][] = [
    //             'id' => $item->id,
    //             'name' => $item->name,
    //             'required_coins' => $item->required_coins,

    //             'icon' => Helper::showImage($item->icon, true),
    //             'gif' => Helper::showImage($item->gif, true),
    //             'avatar' => Helper::showImage($item->avatar, true),
    //             'frame' => Helper::showImage($item->frame, true),
    //             'badge' => Helper::showImage($item->badge, true),
    //             'background' => Helper::showImage($item->background, true),
    //         ];
    //     }

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Relationship items fetched successfully',
    //         'data' => $grouped
    //     ]);
    // }


    public function index(Request $request)
    {
        $profileUserId = $request->user_id ?? auth()->id();

        $itemQuery = RelationshipItem::where('status', 1);

        if ($request->type) {

            $itemQuery->whereRaw(
                'LOWER(type)=?',
                [strtolower($request->type)]
            );
        }

        $items = $itemQuery->get();

        $grouped = [];
        $globalFriendList = [];

        //    Type based relations

        $relationQuery = RelationshipInvitation::with([
            'sender:id,name,image',
            'receiver:id,name,image',
            'relationshipItem:id,name,type,required_coins'
        ])
            ->where('status', 'accept')
            ->where(function ($q) use ($profileUserId) {

                $q->where('sender_id', $profileUserId)
                    ->orWhere('receiver_id', $profileUserId);
            });


        if ($request->type) {

            $relationQuery->whereRaw(
                'LOWER(type)=?',
                [strtolower($request->type)]
            );
        }


        $relations = $relationQuery->get();

        // All relations for friend list

        $allRelations = RelationshipInvitation::with([
            'sender:id,name,image',
            'receiver:id,name,image',
            'relationshipItem:id,name,type,required_coins'
        ])
            ->where('status', 'accept')
            ->where(function ($q) use ($profileUserId) {

                $q->where('sender_id', $profileUserId)
                    ->orWhere('receiver_id', $profileUserId);
            })
            ->get();

        //    Gift coin map

        $giftCoinMap = [];

        foreach ($allRelations as $relation) {

            $friend = $relation->sender_id == $profileUserId
                ? $relation->receiver
                : $relation->sender;


            // Lifetime coins

            $totalGiftCoins = GiftTransaction::where(
                function ($q)
                use ($profileUserId, $friend) {

                    $q->where(function ($sub)
                    use ($profileUserId, $friend) {

                        $sub->where(
                            'sender_id',
                            $profileUserId
                        )
                            ->where(
                                'receiver_id',
                                $friend->id
                            );
                    })->orWhere(function ($sub)
                    use ($profileUserId, $friend) {

                        $sub->where(
                            'sender_id',
                            $friend->id
                        )
                            ->where(
                                'receiver_id',
                                $profileUserId
                            );
                    });
                }
            )->sum('total_value');

            // All levels

            $levels = RelationshipItem::whereRaw(
                'LOWER(type)=?',
                [strtolower($relation->type)]
            )
                ->orderBy(
                    'required_coins'
                )
                ->get();


            $currentLevelItem =
                $levels->first();

            $previousCoins = 0;

            foreach ($levels as $index => $level) {

                if (
                    $totalGiftCoins >=
                    $level->required_coins
                ) {

                    if (
                        isset(
                            $levels[$index + 1]
                        )
                    ) {

                        $previousCoins =
                            $level->required_coins;

                        $currentLevelItem =
                            $levels[$index + 1];
                    }
                } else {

                    break;
                }
            }

            // Current progress

            $currentLevelCoins =
                max(
                    0,
                    $totalGiftCoins
                        - $previousCoins
                );


            $giftCoinMap[$relation->id] = [

                'total' =>
                (int) $totalGiftCoins,

                'current' =>
                (int) $currentLevelCoins,

                'level_item' =>
                $currentLevelItem
            ];
        }

        // Items

        foreach ($items as $item) {

            $type = strtolower(
                $item->type
            );


            if (!isset($grouped[$type])) {

                $grouped[$type] = [

                    'items' => [],

                    'relations' => []
                ];
            }



            $grouped[$type]['items'][] = [

                'id' =>
                $item->id,

                'name' =>
                $item->name,

                'required_coins' =>
                $item->required_coins,

                'icon' =>
                Helper::showImage(
                    $item->icon,
                    true
                ),

                'gif' =>
                Helper::showImage(
                    $item->gif,
                    true
                ),

                'avatar' =>
                Helper::showImage(
                    $item->avatar,
                    true
                ),

                'frame' =>
                Helper::showImage(
                    $item->frame,
                    true
                ),

                'badge' =>
                Helper::showImage(
                    $item->badge,
                    true
                ),

                'background' =>
                Helper::showImage(
                    $item->background,
                    true
                ),
            ];
        }

        // Type based relations only

        foreach ($relations as $relation) {

            if (
                $request->type &&
                strtolower($relation->type)
                != strtolower($request->type)
            ) {
                continue;
            }

            $type = strtolower(
                $relation->type
            );

            $friend = $relation->sender_id == $profileUserId
                ? $relation->receiver
                : $relation->sender;

            $giftData =
                $giftCoinMap[$relation->id];

            $grouped[$type]['relations'][] = [

                'invitation_id' =>
                $relation->id,

                'user_id' =>
                $friend->id,

                'name' =>
                $friend->name,

                'image' =>
                Helper::showImage(
                    $friend->image,
                    true
                ),

                'days' => max(
                    1,
                    (int) $relation->updated_at
                        ->startOfDay()
                        ->diffInDays(
                            now()->startOfDay()
                        )
                ),

                'total_gift_coins' =>
                $giftData['total'],

                'gift_coins' =>
                $giftData['current'],

                'relationship_item' => [

                    'id' =>
                    $giftData['level_item']
                        ->id,

                    'name' =>
                    $giftData['level_item']
                        ->name,

                    'required_coins' =>
                    $giftData['level_item']
                        ->required_coins
                ]
            ];
        }

        // Friend list always all types

        foreach ($allRelations as $relation) {

            $type = strtolower(
                $relation->type
            );

            $friend = $relation->sender_id == $profileUserId
                ? $relation->receiver
                : $relation->sender;

            $giftData =
                $giftCoinMap[$relation->id];

            $globalFriendList[] = [

                'user_id' =>
                $friend->id,

                'name' =>
                $friend->name,

                'image' =>
                Helper::showImage(
                    $friend->image,
                    true
                ),

                'gift_coins' =>
                $giftData['total'],

                'level_name' =>
                $giftData['level_item']
                    ->name,

                'type' =>
                $type
            ];
        }

        usort(
            $globalFriendList,
            function ($a, $b) {

                return $b['gift_coins']
                    <=> $a['gift_coins'];
            }
        );

        return response()->json([

            'status' => true,

            'message' =>
            'Relationship fetched successfully',

            'data' => [

                'relationship_data' =>
                $grouped,

                'friend_list' =>
                $globalFriendList
            ]
        ]);
    }

    public function sendInvite(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'receiver_id' => 'required|exists:app_users,id',
            'relationship_item_id' => 'required|exists:relationship_items,id',
            'type' => 'required',
            'coin' => 'required|integer|min:1',
        ]);

        if ($validation->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validation->errors()
            ], 422);
        }

        $senderId = auth()->id();

        if (!$senderId) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        // Self invite block
        if ((int)$senderId === (int)$request->receiver_id) {
            return response()->json([
                'status' => false,
                'message' => 'You cannot invite yourself'
            ], 422);
        }

        DB::beginTransaction();

        try {
            $sender = AppUser::where('id', $senderId)
                ->lockForUpdate()
                ->first();

            if (!$sender) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Sender not found'
                ], 404);
            }

            $alreadyConnected = RelationshipInvitation::where(
                function ($q) use ($senderId, $request) {

                    $q->where(function ($sub)
                    use ($senderId, $request) {

                        $sub->where('sender_id', $senderId)
                            ->where('receiver_id', $request->receiver_id);
                    })->orWhere(function ($sub)
                    use ($senderId, $request) {

                        $sub->where(
                            'sender_id',
                            $request->receiver_id
                        )
                            ->where(
                                'receiver_id',
                                $senderId
                            );
                    });
                }
            )
                ->where('status', 'accept')
                ->lockForUpdate()
                ->first();

            if ($alreadyConnected) {

                DB::rollBack();

                return response()->json([
                    'status' => false,
                    'message' =>
                    'Already in ' .
                        strtoupper($alreadyConnected->type) .
                        ' relationship'
                ], 422);
            }

            if (strtolower($request->type) == 'cp') {


                //    Sender CP check

                $senderAlreadyInCp = RelationshipInvitation::where(
                    'status',
                    'accept'
                )
                    ->whereRaw(
                        'LOWER(type)=?',
                        ['cp']
                    )
                    ->where(function ($q) use ($senderId) {

                        $q->where(
                            'sender_id',
                            $senderId
                        )
                            ->orWhere(
                                'receiver_id',
                                $senderId
                            );
                    })
                    ->lockForUpdate()
                    ->exists();



                if ($senderAlreadyInCp) {

                    DB::rollBack();

                    return response()->json([
                        'status' => false,
                        'message' =>
                        'You are already in CP relationship'
                    ], 422);
                }

                // Receiver CP check

                $receiverAlreadyInCp = RelationshipInvitation::where(
                    'status',
                    'accept'
                )
                    ->whereRaw(
                        'LOWER(type)=?',
                        ['cp']
                    )
                    ->where(function ($q) use ($request) {

                        $q->where(
                            'sender_id',
                            $request->receiver_id
                        )
                            ->orWhere(
                                'receiver_id',
                                $request->receiver_id
                            );
                    })
                    ->lockForUpdate()
                    ->exists();



                if ($receiverAlreadyInCp) {

                    DB::rollBack();

                    return response()->json([
                        'status' => false,
                        'message' =>
                        'This user is already in CP relationship'
                    ], 422);
                }
            }

            // Duplicate invite check
            $pendingInvite = RelationshipInvitation::where(
                function ($q) use ($senderId, $request) {

                    $q->where(function ($sub)
                    use ($senderId, $request) {

                        $sub->where(
                            'sender_id',
                            $senderId
                        )
                            ->where(
                                'receiver_id',
                                $request->receiver_id
                            );
                    })->orWhere(function ($sub)
                    use ($senderId, $request) {

                        $sub->where(
                            'sender_id',
                            $request->receiver_id
                        )
                            ->where(
                                'receiver_id',
                                $senderId
                            );
                    });
                }
            )
                ->whereRaw(
                    'LOWER(type)=?',
                    [strtolower($request->type)]
                )
                ->where('status', 'pending')
                ->lockForUpdate()
                ->exists();

            if ($pendingInvite) {

                DB::rollBack();

                return response()->json([
                    'status' => false,
                    'message' => 'Invite already pending'
                ], 422);
            }

            $coin = (int) $request->coin;

            if ((int) $sender->total_points < $coin) {

                DB::rollBack();

                return response()->json([
                    'status' => false,
                    'message' => 'Insufficient coins'
                ], 400);
            }

            // Coin deduct
            $sender->total_points = (int)$sender->total_points - $coin;
            $sender->save();
            $receiver = AppUser::find(
                $request->receiver_id
            );
            // Save invite
            $invite = RelationshipInvitation::create([
                'sender_id' => $senderId,
                'receiver_id' => $request->receiver_id,
                'relationship_item_id' => $request->relationship_item_id,
                'type' => $request->type,
                'coin' => $coin,
            ]);

            Notification::create([

                'user_id' => $receiver->id,
                'sender_id' => auth()->id(),
                'receiver_id' => $receiver->id,
                'type' => $request->type,
                'title' => ucfirst($request->type) . ' Invitation',
                'message' => auth()->user()->name . ' invited you for ' . $request->type,
                'reference_id' => $invite->id,
                'country' => auth()->user()->country,
                'is_read' => 0,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Invitation sent successfully'
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();

            \Log::error('SEND INVITE ERROR', [
                'sender_id' => $senderId,
                'receiver_id' => $request->receiver_id,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong'
            ], 500);
        }
    }

    // public function respondInvite(Request $request)
    // {
    //     $request->validate([
    //         'invitation_id' => 'required|exists:relationship_invitations,id',
    //         'action' => 'required|in:accept,reject'
    //     ]);

    //     $invite = RelationshipInvitation::find($request->invitation_id);

    //     // Only receiver can respond
    //     if ($invite->receiver_id != auth()->id()) {
    //         return response()->json(['status' => false, 'message' => 'Unauthorized']);
    //     }

    //     $invite->status = $request->action;
    //     $invite->save();

    //     if ($request->action == 'accept') {
    //         // optional future table
    //     }

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Invitation ' . $request->action
    //     ]);
    // }

    // public function getInvitations()
    // {
    //     $userId = auth()->id();

    //     $data = RelationshipInvitation::with(['sender:id,name,uid,image', 'relationshipItem'])
    //         ->where('receiver_id', $userId)
    //         ->where('status', 'pending')
    //         ->latest()
    //         ->get();

    //     $response = [];

    //     foreach ($data as $item) {

    //         $response[] = [
    //             'id' => $item->id,
    //             'sender_id' => $item->sender_id,
    //             'receiver_id' => $item->receiver_id,
    //             'type' => strtolower($item->type),

    //             'status' => $item->status,
    //             'created_at' => $item->created_at,

    //             'sender' => $item->sender ? [
    //                 'id' => $item->sender->id,
    //                 'name' => $item->sender->name,
    //                 'uid' => $item->sender->uid,
    //                 'image' => !empty($item->sender->image)
    //                     ? Helper::showImage($item->sender->image, true)
    //                     : null,
    //             ] : null,

    //             'relationship_item' => [
    //                 'id' => $item->relationshipItem->id,
    //                 'name' => $item->relationshipItem->name,
    //                 'icon' => Helper::showImage($item->relationshipItem->icon, true),
    //                 'gif' => Helper::showImage($item->relationshipItem->gif, true),
    //                 'avatar' => Helper::showImage($item->relationshipItem->avatar, true),
    //                 'frame' => Helper::showImage($item->relationshipItem->frame, true),
    //                 'badge' => Helper::showImage($item->relationshipItem->badge, true),
    //                 'background' => Helper::showImage($item->relationshipItem->background, true),
    //                 'required_coins' => $item->relationshipItem->required_coins,
    //             ]
    //         ];
    //     }

    //     return response()->json([
    //         'status' => true,
    //         'data' => $response
    //     ]);
    // }

    public function relationInvitePreview(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'invite_user_id' => 'required|integer|exists:app_users,id',
            'type' => 'required|in:cp,brother,sister,confident',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => $validator->errors(),
            ], 422);
        }

        try {
            $authUser = Auth::user();

            if (!$authUser) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Unauthenticated',
                ], 401);
            }

            if ((int)$authUser->id === (int)$request->invite_user_id) {
                return response()->json([
                    'status'  => false,
                    'message' => 'You cannot invite yourself',
                ], 422);
            }

            $inviteUser = AppUser::select('id', 'name', 'uid', 'image')
                ->find($request->invite_user_id);

            if (!$inviteUser) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Invite user not found',
                ], 404);
            }

            $type = strtolower($request->type);

            // Invite charge alag hoga
            $inviteCharges = [
                'cp' => 4999,
                'brother' => 9999,
                'sister' => 8999,
                'confident' => 8999,
            ];

            $inviteCoins = $inviteCharges[$type] ?? 0;

            // Preview ke liye har type ka first/default item
            $relationshipItem = RelationshipItem::where('status', 1)
                ->whereRaw('LOWER(type) = ?', [$type])
                ->orderBy('id', 'asc')
                ->first();

            if (!$relationshipItem) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Relationship item not found for selected type',
                ], 404);
            }

            return response()->json([
                'status'  => true,
                'message' => 'Relationship invite preview fetched successfully',
                'data'    => [
                    'type' => $type,
                    'invite_coins' => (int) $inviteCoins,

                    'sender' => [
                        'id'    => $authUser->id,
                        'name'  => $authUser->name,
                        'uid'   => $authUser->uid,
                        'image' => $authUser->image ? \Helper::showImage($authUser->image, true) : null,
                    ],

                    'receiver' => [
                        'id'    => $inviteUser->id,
                        'name'  => $inviteUser->name,
                        'uid'   => $inviteUser->uid,
                        'image' => $inviteUser->image ? \Helper::showImage($inviteUser->image, true) : null,
                    ],

                    'relationship_item' => [
                        'id'    => $relationshipItem->id,
                        'name'  => $relationshipItem->name,
                        'type'  => strtolower($relationshipItem->type),

                        'gif'   => $relationshipItem->gif ? \Helper::showImage($relationshipItem->gif, true) : null,
                        'ring'  => $relationshipItem->ring ? \Helper::showImage($relationshipItem->ring, true) : null,
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            \Log::error('RELATIONSHIP INVITE PREVIEW API ERROR', [
                'error' => $e->getMessage(),
                'line'  => $e->getLine(),
                'file'  => $e->getFile(),
            ]);

            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }


    public function breakupDetails()
    {
        try {
            $userId = auth()->id();

            if (!$userId) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $cpRelation = RelationshipInvitation::with(['sender:id,name,image', 'receiver:id,name,image'])
                ->where('status', 'accept')
                ->whereRaw('LOWER(type)=?', ['cp'])
                ->where(function ($q) use ($userId) {
                    $q->where('sender_id', $userId)
                        ->orWhere('receiver_id', $userId);
                })
                ->latest()->first();

            if (!$cpRelation) {
                return response()->json([
                    'status' => false,
                    'message' => 'No CP relationship found'
                ], 404);
            }

            $partner = $cpRelation->sender_id == $userId ? $cpRelation->receiver : $cpRelation->sender;

            return response()->json([

                'status' => true,
                'message' => 'CP breakup details fetched successfully',
                'data' => [
                    'relationship_id' => $cpRelation->id,
                    'card_image' =>  asset('storage/breakup_card.png'),
                    'coin' => 50000,
                    'notes' => [
                        'To cancel the CP relationship, you need to buy a breakup card first, and use it when canceling the CP relationship.',
                        'After the cancellation is successful, the other party will receive 80% of the value of the breakup card as consolation coins.'
                    ],
                    // 'partner' => [
                    //     'id' => $partner->id,
                    //     'name' => $partner->name,
                    //     'image' => Helper::showImage($partner->image, true)
                    // ]
                ]
            ]);
        } catch (\Throwable $e) {

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }


    public function myRelationshipList()
    {
        try {
            $userId = auth()->id();

            if (!$userId) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

            //    Static remove charges

            $removeCharges = [
                'cp' => 50000,
                'brother' => 0,
                'sister' => 0,
                'confidant' => 0,
            ];

            $relations = RelationshipInvitation::with([
                'sender:id,uid,name,image',
                'receiver:id,uid,name,image',
                'relationshipItem:id,name,type'
            ])
                ->where('status', 'accept')
                ->where(function ($q) use ($userId) {
                    $q->where('sender_id', $userId)
                        ->orWhere('receiver_id', $userId);
                })
                ->latest()
                ->get();

            $data = [];

            foreach ($relations as $relation) {
                $type = strtolower($relation->type);
                $friend = $relation->sender_id == $userId ? $relation->receiver : $relation->sender;

                $data[] = [
                    'relationship_id' =>  $relation->id,
                    'type' => $type,
                    'level_name' => $relation->relationshipItem->name,
                    'coin' => $removeCharges[$type] ?? 0,

                    'user' => [
                        'id' => $friend->id,
                        'uid' => $friend->uid,
                        'name' => $friend->name,
                        'image' => Helper::showImage($friend->image, true)
                    ]
                ];
            }

            return response()->json([
                'status' => true,
                'message' =>
                'Relationship list fetched successfully',
                'data' => $data
            ]);
        } catch (\Throwable $e) {

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function removeRelationship(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'relationship_id' => 'required|exists:relationship_invitations,id'
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $userId = auth()->id();

            if (!$userId) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $relation = RelationshipInvitation::lockForUpdate()
                ->where('id', $request->relationship_id)
                ->where('status', 'accept')
                ->where(function ($q) use ($userId) {
                    $q->where('sender_id', $userId)
                        ->orWhere('receiver_id', $userId);
                })
                ->first();

            if (!$relation) {

                DB::rollBack();

                return response()->json([
                    'status' => false,
                    'message' =>
                    'Relationship not found'
                ], 404);
            }

            $type = strtolower(
                $relation->type
            );

            // Static charges

            $charges = [
                'cp' => 50000,
                'brother' => 0,
                'sister' => 0,
                'confidant' => 0
            ];

            $removeCoin = $charges[$type] ?? 0;

            $authUser = AppUser::lockForUpdate()->find($userId);

            if (
                (int) $authUser->total_points < $removeCoin
            ) {
                DB::rollBack();

                return response()->json([
                    'status' => false,
                    'message' =>
                    'Insufficient coins'
                ], 422);
            }

            // Deduct remove charges

            $authUser->total_points = (int) $authUser->total_points - $removeCoin;
            $authUser->save();

            // CP breakup compensation

            if ($type == 'cp') {

                $partnerId = $relation->sender_id == $userId ? $relation->receiver_id : $relation->sender_id;
                $partner = AppUser::lockForUpdate()->find($partnerId);

                $compensation = (int) ($removeCoin * 0.80);

                $partner->total_points = (int) $partner->total_points + $compensation;

                $partner->save();
            }

            // Delete relation

            $relation->delete();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => ucfirst($type) . ' relationship removed successfully'
            ]);
        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }


    public function getInvitations()
    {
        $userId = auth()->id();

        $response = [];

        // Relationship Invitations

        $relations = RelationshipInvitation::with(
            'sender:id,name,uid,image'
        )
            ->where('receiver_id', $userId)
            ->where('status', 'pending')
            ->get();

        foreach ($relations as $item) {

            $response[] = [

                'id' => $item->id,
                'type' => 'relationship',
                'sub_type' => strtolower($item->type),
                // 'message' => $item->sender->name . ' invited you for ' . strtolower($item->type),
                'message' => 'invited you for ' . strtolower($item->type),
                'created_at' => $item->created_at,
                'sender' => [
                    'id' => $item->sender->id,
                    'uid' => $item->sender->uid,
                    'name' => $item->sender->name,
                    'image' => !empty($item->sender->image)
                        ? Helper::showImage(
                            $item->sender->image,
                            true
                        )
                        : null,
                ]
            ];
        }

        //    Agency Invitations

        $agencies = Agency::with('admin.user:id,name,uid,image')
            ->where('user_id', $userId)
            ->where('invite_status', 'pending')
            ->get();

        foreach ($agencies as $item) {

            $sender = $item->admin?->user;

            $response[] = [

                'id' => $item->id,
                'type' => 'agency',
                'sub_type' => 'agency',
                // 'message' => $sender->name . ' invited you for agent',
                'message' => 'invited you for agent',
                'created_at' => $item->created_at,
                'sender' => [
                    'id' => $sender->id,
                    'uid' => $sender->uid,
                    'name' => $sender->name,
                    'image' => !empty($sender->image) ? Helper::showImage($sender->image, true) : null,
                ]
            ];
        }

        //   BD Invitations

        $bds = BdUser::with('admin.user:id,name,uid,image')
            ->where('user_id', $userId)
            ->where('invite_status', 'pending')
            ->get();

        foreach ($bds as $item) {

            $sender = $item->admin?->user;

            $response[] = [

                'id' => $item->id,
                'type' => 'bd',
                'sub_type' => 'bd',
                // 'message' => $sender->name . ' invited you for bd',
                'message' => 'invited you for bd',
                'created_at' => $item->created_at,
                'sender' => [
                    'id' => $sender->id,
                    'uid' => $sender->uid,
                    'name' => $sender->name,
                    'image' => !empty($sender->image) ? Helper::showImage($sender->image, true) : null,
                ]
            ];
        }

        //Host Invitations
        $hosts = Host::with('agency.user:id,name,uid,image')
            ->where('user_id', $userId)
            ->where('invite_status', 'pending')
            ->get();

        foreach ($hosts as $item) {

            $sender = $item->agency?->user;

            $response[] = [

                'id' => $item->id,
                'type' => 'host',
                'sub_type' => 'host',
                // 'message' => $sender->name . ' invited you for bd',
                'message' => 'invited you for Host',
                'created_at' => $item->created_at,
                'sender' => [
                    'id' => $sender->id,
                    'uid' => $sender->uid,
                    'name' => $sender->name,
                    'image' => !empty($sender->image) ? Helper::showImage($sender->image, true) : null,
                ]
            ];
        }

        collect($response)
            ->sortByDesc('created_at')
            ->values();

        return response()->json([
            'status' => true,
            'data' => $response
        ]);
    }


    public function respondInvite(Request $request)
    {
        $request->validate([
            'type' => 'required',
            'invitation_id' => 'required|integer',
            'action' => 'required|in:accept,reject'
        ]);

        $userId = auth()->id();

        //    Relationship

        if (in_array($request->type, ['cp', 'brother', 'sister', 'confident'])) {

            $invite = RelationshipInvitation::find($request->invitation_id);

            if (!$invite || $invite->receiver_id != $userId) {

                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized'
                ]);
            }

            $invite->status = $request->action;
            $invite->save();
        }

        // Agency
        elseif ($request->type == 'agency') {

            $invite = Agency::find($request->invitation_id);

            if (!$invite || $invite->user_id != $userId) {

                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized'
                ]);
            }

            $invite->invite_status = $request->action;
            $invite->save();

            if ($request->action == 'accept') {

                Host::firstOrCreate(

                    [
                        'user_id' => $invite->user_id
                    ],

                    [

                        'agency_id' => $invite->id,

                        'country_id' => $invite->country_id,

                        'invite_status' => 'accept',

                        'is_dashboard_access' => 0,

                        'status' => 1
                    ]
                );
            }
        }

        // BD
        elseif ($request->type == 'bd') {

            $invite = BdUser::find($request->invitation_id);

            if (!$invite || $invite->user_id != $userId) {

                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized'
                ]);
            }

            $invite->invite_status = $request->action;
            $invite->save();
        }

        //  Host
        elseif ($request->type == 'host') {

            $invite = Host::find($request->invitation_id);

            if (!$invite || $invite->user_id != $userId) {

                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized'
                ]);
            }

            $invite->invite_status = $request->action;
            $invite->save();
        }

        //   Merchant / Coinseller
        elseif (in_array($request->type, ['merchant', 'coinseller'])) {

            $invite = CoinSeller::find(
                $request->invitation_id
            );

            if (!$invite || $invite->user_id != $userId) {

                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized'
                ]);
            }

            $invite->invite_status = $request->action;
            $invite->save();
        }

        return response()->json([
            'status' => true,
            'message' => ucfirst($request->type) . ' invitation ' . $request->action . 'ed successfully'
        ]);
    }
}
