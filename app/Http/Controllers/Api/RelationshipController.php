<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RelationshipItem;
use App\Models\RelationshipInvitation;
use App\Models\AppUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Helper\Helper;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


class RelationshipController extends Controller
{
    public function index(Request $request)
    {
        $query = RelationshipItem::where('status', 1);

        if ($request->type) {
            $query->where('type', $request->type);
        }

        $items = $query->get();

        $grouped = [];

        foreach ($items as $item) {

            $grouped[$item->type][] = [
                'id' => $item->id,
                'name' => $item->name,
                'required_coins' => $item->required_coins,

                'icon' => Helper::showImage($item->icon, true),
                'gif' => Helper::showImage($item->gif, true),
                'avatar' => Helper::showImage($item->avatar, true),
                'frame' => Helper::showImage($item->frame, true),
                'badge' => Helper::showImage($item->badge, true),
                'background' => Helper::showImage($item->background, true),
            ];
        }

        return response()->json([
            'status' => true,
            'message' => 'Relationship items fetched successfully',
            'data' => $grouped
        ]);
    }

    // public function sendInvite(Request $request)
    // {
    //     $validation = Validator::make($request->all(), [
    //         'receiver_id' => 'required|exists:app_users,id',
    //         'relationship_item_id' => 'required|exists:relationship_items,id',
    //         'type' => 'required',
    //         'required|integer|min:1',
    //     ]);

    //     if ($validation->fails()) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => $validation->errors()
    //         ]);
    //     }

    //     $senderId = auth()->id();

    //     // Self invite block
    //     if ($senderId == $request->receiver_id) {
    //         return response()->json(['status' => false, 'message' => 'You cannot invite yourself']);
    //     }

    //     // Duplicate invite check
    //     $exists = RelationshipInvitation::where('sender_id', $senderId)
    //         ->where('receiver_id', $request->receiver_id)
    //         ->where('type', $request->type)
    //         ->where('status', 'pending')
    //         ->exists();

    //     if ($exists) {
    //         return response()->json(['status' => false, 'message' => 'Invite already sent']);
    //     }

    //     // Save invite
    //     RelationshipInvitation::create([
    //         'sender_id' => $senderId,
    //         'receiver_id' => $request->receiver_id,
    //         'relationship_item_id' => $request->relationship_item_id,
    //         'type' => $request->type,
    //         'message' => $request->message
    //     ]);

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Invitation sent successfully'
    //     ]);
    // }


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

            // Duplicate invite check
            $exists = RelationshipInvitation::where('sender_id', $senderId)
                ->where('receiver_id', $request->receiver_id)
                ->where('type', $request->type)
                ->where('status', 'pending')
                ->lockForUpdate()
                ->exists();

            if ($exists) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Invite already sent'
                ], 422);
            }

            $coin = (int) $request->coin;

            if ((int)$sender->total_points < $coin) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Insufficient coins'
                ], 400);
            }

            // Coin deduct
            $sender->total_points = (int)$sender->total_points - $coin;
            $sender->save();

            // Save invite
            RelationshipInvitation::create([
                'sender_id' => $senderId,
                'receiver_id' => $request->receiver_id,
                'relationship_item_id' => $request->relationship_item_id,
                'type' => $request->type,
                'coin' => $coin,
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

    public function respondInvite(Request $request)
    {
        $request->validate([
            'invitation_id' => 'required|exists:relationship_invitations,id',
            'action' => 'required|in:accept,reject'
        ]);

        $invite = RelationshipInvitation::find($request->invitation_id);

        // Only receiver can respond
        if ($invite->receiver_id != auth()->id()) {
            return response()->json(['status' => false, 'message' => 'Unauthorized']);
        }

        $invite->status = $request->action;
        $invite->save();

        if ($request->action == 'accept') {
            // optional future table
        }

        return response()->json([
            'status' => true,
            'message' => 'Invitation ' . $request->action
        ]);
    }

    public function getInvitations()
    {
        $userId = auth()->id();

        $data = RelationshipInvitation::with(['sender:id,name,uid,image', 'relationshipItem'])
            ->where('receiver_id', $userId)
            ->where('status', 'pending')
            ->latest()
            ->get();

        $response = [];

        foreach ($data as $item) {

            $response[] = [
                'id' => $item->id,
                'sender_id' => $item->sender_id,
                'receiver_id' => $item->receiver_id,
                'type' => strtolower($item->type),

                'status' => $item->status,
                'created_at' => $item->created_at,

                'sender' => $item->sender ? [
                    'id' => $item->sender->id,
                    'name' => $item->sender->name,
                    'uid' => $item->sender->uid,
                    'image' => !empty($item->sender->image)
                        ? Helper::showImage($item->sender->image, true)
                        : null,
                ] : null,

                'relationship_item' => [
                    'id' => $item->relationshipItem->id,
                    'name' => $item->relationshipItem->name,
                    'icon' => Helper::showImage($item->relationshipItem->icon, true),
                    'gif' => Helper::showImage($item->relationshipItem->gif, true),
                    'avatar' => Helper::showImage($item->relationshipItem->avatar, true),
                    'frame' => Helper::showImage($item->relationshipItem->frame, true),
                    'badge' => Helper::showImage($item->relationshipItem->badge, true),
                    'background' => Helper::showImage($item->relationshipItem->background, true),

                    'required_coins' => $item->relationshipItem->required_coins,
                ]
            ];
        }

        return response()->json([
            'status' => true,
            'data' => $response
        ]);
    }


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
}
