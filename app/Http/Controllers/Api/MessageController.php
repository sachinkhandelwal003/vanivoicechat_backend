<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Helper\Helper;
use App\Models\AppUser;
use App\Models\Friendship;
use App\Models\Message;
use App\Models\SupportMessage;
use App\Models\SupportConversation;
use App\Models\OfficialNotification;
use App\Models\NotificationRead;
use App\Models\ChatReport;
use App\Models\Notification;
use App\Models\UserBlock;
use App\Models\StoreUids;
use App\Models\PremiumNumber;
use App\Events\MessageSent;
use App\Events\MessageDeleted;
use App\Events\SupportMessageSent;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Services\FirebaseService;

class MessageController extends Controller
{

    public function sendRequest(Request $request)
    {
        $sender = Auth::id();
        $receiver = $request->receiver_id;

        if ($sender == $receiver) {
            return response()->json([
                'status' => false,
                'message' => 'You cannot send request to yourself'
            ]);
        }

        $userOne = min($sender, $receiver);
        $userTwo = max($sender, $receiver);

        $friendship  = Friendship::where('user_one', $userOne)
            ->where('user_two', $userTwo)
            ->first();

        if ($friendship) {

            // already friends
            if ($friendship->status == 'accepted') {
                return response()->json([
                    'status' => false,
                    'message' => 'You are already friends'
                ]);
            }

            // request already pending
            if ($friendship->status == 'pending') {
                return response()->json([
                    'status' => false,
                    'message' => 'Friend request already sent'
                ]);
            }

            // rejected → resend request
            if ($friendship->status == 'rejected') {
                $friendship->update([
                    'status' => 'pending',
                    'action_user_id' => $sender
                ]);
            }
        } else {

            Friendship::create([
                'user_one' => $userOne,
                'user_two' => $userTwo,
                'status' => 'pending',
                'action_user_id' => $sender
            ]);
        }

        $receiverUser = AppUser::find($receiver);
        $senderUser = Auth::user();
        $icon = asset('friend-request.png');

        if ($receiverUser && $receiverUser->fcm_token) {
            $firebase = new FirebaseService();

            $firebase->sendNotification(
                $receiverUser->fcm_token,
                "New Friend Request",
                $senderUser->name . " sent you a friend request",
                $icon,

            );
        }

        Notification::create([
            'sender_id' => Auth::id(),
            'receiver_id' => $receiver,
            'title' => 'New Friend Request',
            'message' => $senderUser->name . " sent you a friend request",
            'type' => 'friend request',
            'icon' => 'friend-request.png',
            'country' => auth()->user()->country,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Friend request sent'
        ]);
    }

    public function acceptRequest(Request $request)
    {
        $userId = Auth::id();
        $otherId = $request->request_user_id;

        $userOne = min($userId, $otherId);
        $userTwo = max($userId, $otherId);

        $friendship = Friendship::where('user_one', $userOne)
            ->where('user_two', $userTwo)
            ->where('status', 'pending')
            ->first();

        if (!$friendship) {
            return response()->json([
                'status' => false,
                'message' => 'Request not found'
            ]);
        }

        $friendship->update([
            'status' => 'accepted',
            'action_user_id' => $userId
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Friend request accepted'
        ]);
    }

    public function rejectRequest(Request $request)
    {
        $userId = Auth::id();
        $otherId = $request->request_user_id;

        $userOne = min($userId, $otherId);
        $userTwo = max($userId, $otherId);

        Friendship::where('user_one', $userOne)
            ->where('user_two', $userTwo)
            ->where('status', 'pending')
            ->update([
                'status' => 'rejected',
                'action_user_id' => $userId
            ]);

        return response()->json([
            'status' => true,
            'message' => 'Friend request rejected'
        ]);
    }

    public function friendRequestList()
    {
        $userId = Auth::id();

        $requests = Friendship::where('status', 'pending')
            ->where(function ($query) use ($userId) {
                $query->where('user_one', $userId)
                    ->orWhere('user_two', $userId);
            })
            ->where('action_user_id', '!=', $userId)
            ->with('senderUser:id,name,image')
            ->get()
            ->map(function ($item) {
                return [
                    'request_id' => $item->id,
                    'user_id' => $item->senderUser->id,
                    'name' => $item->senderUser->name,
                    'image' => Helper::showImage($item->senderUser->image, true)
                ];
            });
        //  dd($requests);
        return response()->json([
            'status' => true,
            'count' => $requests->count(),
            'data' => $requests
        ]);
    }

    public function friendList()
    {
        $userId = Auth::id();

        $friends = Friendship::where('status', 'accepted')
            ->where(function ($q) use ($userId) {
                $q->where('user_one', $userId)
                    ->orWhere('user_two', $userId);
            })
            ->get()
            ->map(function ($friendship) use ($userId) {
                return $friendship->user_one == $userId
                    ? $friendship->user_two
                    : $friendship->user_one;
            })
            ->unique()
            ->values();

        $users = AppUser::whereIn('id', $friends)
            ->select('id', 'uid', 'name', 'gender', 'image', 'active_uid_id')
            ->get()
            ->map(function ($item) use ($userId) {

                $blockedByMe = UserBlock::where('blocker_id', $userId)
                    ->where('blocked_user_id', $item->id)
                    ->exists();

                $blockedByUser = UserBlock::where('blocker_id', $item->id)
                    ->where('blocked_user_id', $userId)
                    ->exists();

                $displayUid = $item->uid;
                $uidBadge = null;
                $uidBadgeColor = null;

                // Premium UID
                $premiumUid = PremiumNumber::where('user_id', $item->id)
                    ->where('end_at', '>', now())
                    ->latest()
                    ->first();

                if ($premiumUid) {

                    $displayUid = $premiumUid->premium_number;

                    $uidBadge = asset('storage/1000175794.png');
                    $uidBadgeColor = '#fcd01c';
                } else {

                    // Store UID
                    if ($item->active_uid_id) {

                        $storeUid = StoreUids::find($item->active_uid_id);

                        if ($storeUid) {

                            $hasValidPurchase = DB::table('item_deliveries')
                                ->where('recipient', $item->id)
                                ->where('type', 'id')
                                ->where('item_id', $storeUid->id)
                                ->where('end_at', '>', now())
                                ->exists();

                            $hasValidGift = DB::table('item_gift_transactions')
                                ->where('receiver_id', $item->id)
                                ->where('type', 'id')
                                ->where('item_id', $storeUid->id)
                                ->where('end_at', '>', now())
                                ->exists();

                            if ($hasValidPurchase || $hasValidGift) {

                                $displayUid = $storeUid->unique_id;

                                $uidBadge = !empty($storeUid->rank_badge)
                                    ? Helper::showImage($storeUid->rank_badge, true)
                                    : null;
                                $uidBadgeColor = $storeUid->rank_color ?? null;
                            }
                        }
                    }
                }
                return [
                    'id' => $item->id,
                    // 'uid' => $item->uid,
                    'uid' => $displayUid,
                    'uid_badge' => $uidBadge,
                    'uid_badge_color' => $uidBadgeColor,
                    'name' => $item->name,
                    'gender' => $item->gender,
                    'image' => Helper::showImage($item->image, true),
                    'is_blocked_by_me' => $blockedByMe,
                    'is_blocked_by_user' => $blockedByUser,
                    'is_blocked' => $blockedByMe || $blockedByUser
                ];
            });

        return response()->json([
            'status' => true,
            'data' => $users
        ]);
    }


    public function sendMessage(Request $request)
    {
        // dd($request->all());
        $sender = Auth::id();

        $filePath = null;
        $fileType = null;

        if ($request->hasFile('file')) {

            $file = $request->file('file');

            $filePath = Helper::saveFile($file, 'chat');

            $extension = strtolower($file->getClientOriginalExtension());

            if (in_array($extension, ['jpg', 'jpeg', 'png'])) {

                $fileType = 'image';
            } elseif (in_array($extension, ['mp4', 'mov', 'avi'])) {

                $fileType = 'video';
            } elseif (in_array($extension, ['mp3', 'wav'])) {

                $fileType = 'audio';
            } else {

                $fileType = 'file';
            }
        }

        $message = Message::create([
            'sender_id' => $sender,
            'receiver_id' => $request->receiver_id,
            'message' => $request->message,
            'file' => $filePath,
            'file_type' => $fileType,
            'reply_to' => $request->reply_to
        ]);
        $message->load('replyMessage');
        $message->load('sender');
        $message->receiver_image = Helper::showImage($message->sender->image, true);
        unset($message->sender);

        if ($message->replyMessage) {

            $message->reply = [

                'id' => $message->replyMessage->id,

                'message' => $message->replyMessage->message,

                'file' => $message->replyMessage->file,

                'file_type' => $message->replyMessage->file_type,

                'sender_id' => $message->replyMessage->sender_id,

                'sender_image' => Helper::showImage(
                    $message->replyMessage->sender->image,
                    true
                ),

            ];
        } else {

            $message->reply = null;
        }
        unset($message->replyMessage);
        broadcast(new MessageSent($message))->toOthers();

        return response()->json([
            'status' => true,
            'message' => 'Message sent',
            'data' => $message
        ]);
    }


    public function recentChats()
    {
        $userId = Auth::id();

        $systemNotificationCount = Notification::where('type', 'post')
            ->whereDoesntHave('reads', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->count();

        $chats = Message::select(
            DB::raw('
                CASE 
                    WHEN sender_id = ' . $userId . ' 
                    THEN receiver_id 
                    ELSE sender_id 
                END as friend_id
            '),
            DB::raw('MAX(id) as last_message_id')
        )
            ->where(function ($query) use ($userId) {
                $query->where('sender_id', $userId)
                    ->orWhere('receiver_id', $userId);
            })
            ->groupBy('friend_id')
            ->orderByDesc('last_message_id')
            ->get();

        $chatList = $chats->map(function ($chat) use ($userId) {

            $lastMessage = Message::find($chat->last_message_id);

            $friend = AppUser::select('id', 'uid', 'name', 'image', 'gender')
                ->find($chat->friend_id);

            $unread = Message::where('sender_id', $chat->friend_id)
                ->where('receiver_id', $userId)
                ->where('is_read', 0)
                ->count();

            return [
                'id' => $friend->id,
                'uid' => $friend->uid,
                'name' => $friend->name,
                'image' => Helper::showImage($friend->image, true),
                'last_message' => $lastMessage->message,
                'file' => $lastMessage->file
                    ? Helper::showImage($lastMessage->file, true)
                    : null,

                'file_type' => $lastMessage->file_type,
                'last_message_time' => $lastMessage->created_at
                    ->diffForHumans(),
                'unread_count' => $unread,
            ];
        });

        return response()->json([
            'status' => true,
            'system_notification_count' => $systemNotificationCount,
            'data' => $chatList
        ]);
    }

    public function chatMessages($friendId)
    {
        $userId = Auth::id();

        $blockedByMe = UserBlock::where('blocker_id', $userId)
            ->where('blocked_user_id', $friendId)
            ->exists();

        $blockedByFriend = UserBlock::where('blocker_id', $friendId)
            ->where('blocked_user_id', $userId)
            ->exists();

        $blockMessage = null;

        if ($blockedByMe) {
            $blockMessage = "You have blocked this user";
        }

        if ($blockedByFriend) {
            $blockMessage = "You are blocked by this user";
        }

        $messages = Message::with(['sender', 'receiver', 'replyMessage.sender'])->where(function ($query) use ($userId, $friendId) {
            $query->where('sender_id', $userId)
                ->where('receiver_id', $friendId);
        })
            ->orWhere(function ($query) use ($userId, $friendId) {

                $query->where('sender_id', $friendId)
                    ->where('receiver_id', $userId);
            })
            ->orderBy('created_at', 'asc')
            ->paginate(20);

        Message::where('sender_id', $friendId)
            ->where('receiver_id', $userId)
            ->where('is_read', 0)
            ->update([
                'is_read' => 1
            ]);

        $messages->getCollection()->transform(function ($message) use ($userId) {
            $senderImage = Helper::showImage($message->sender->image, true);

            $replyData = null;
            if ($message->replyMessage) {
                $replyData = [
                    'id' => $message->replyMessage->id,
                    'message' => $message->replyMessage->message,
                    'file' => $message->replyMessage->file
                        ? Helper::showImage($message->replyMessage->file, true)
                        : null,
                    'file_type' => $message->replyMessage->file_type,
                    'sender_id' => $message->replyMessage->sender_id,
                    'sender_image' => Helper::showImage($message->replyMessage->sender->image, true)
                ];
            }
            return [
                'id' => $message->id,
                'sender_id' => $message->sender->id,
                'sender_image' => $senderImage,
                'message' => $message->message,
                'type' => $message->sender_id == $userId
                    ? 'sent'
                    : 'received',
                'file' => $message->file
                    ? Helper::showImage($message->file, true)
                    : null,
                'file_type' => $message->file_type,
                'reply' => $replyData,
                'time' => $message->created_at->format('h:i A'),
                'full_time' => $message->created_at,
                'is_read' => $message->is_read
            ];
        });


        return response()->json([
            'status' => true,
            'message' => 'Message Fetched Successfully',
            'is_blocked_by_me' => $blockedByMe,
            'is_blocked_by_user' => $blockedByFriend,
            'block_message' => $blockMessage,

            // Important for mobile app
            'can_send_message' => !($blockedByMe || $blockedByFriend),
            'data' => $messages
        ]);
    }

    public function officialNotification(Request $request)
    {
        $user = Auth::user();

        $data = OfficialNotification::where(function ($query) use ($user) {

            $query->where('user_id', $user->id)
                ->orWhere(function ($q) use ($user) {
                    $q->whereNull('user_id')
                        ->where('country', $user->country);
                })
                ->orWhere(function ($q) {
                    $q->whereNull('user_id')
                        ->whereNull('country');
                });
        })
            ->latest()
            ->get();

        $data->transform(function ($item) {

            if ($item->image) {
                $item->image = Helper::showImage($item->image, true);
            }

            $item->formatted_time = \Carbon\Carbon::parse($item->created_at)->format('m/d H:i');

            return $item;
        });
        return response()->json([
            'status' => true,
            'message' => 'Official Notification Fetched Successfully',
            'data' => $data
        ]);
    }

    public function deleteMessage($id)
    {
        $userId = Auth::id();

        $message = Message::where('id', $id)
            ->where(function ($query) use ($userId) {
                $query->where('sender_id', $userId)
                    ->orWhere('receiver_id', $userId);
            })
            ->first();

        if (!$message) {
            return response()->json([
                'status' => false,
                'message' => 'Message not found'
            ], 404);
        }

        $senderId = $message->sender_id;
        $receiverId = $message->receiver_id;

        $message->delete();

        broadcast(new MessageDeleted($id, $senderId, $receiverId))->toOthers();

        return response()->json([
            'status' => true,
            'message' => 'Message deleted successfully'
        ]);
    }

    public function sendSupportMessage(Request $request)
    {
        $request->validate([
            'message' => 'nullable|string'
        ]);

        $userId = Auth::id();

        DB::beginTransaction();

        try {

            // check existing open conversation

            $conversation = SupportConversation::where('user_id', $userId)
                ->where('status', 'open')
                ->first();

            // if not exist create new

            if (!$conversation) {
                $conversation = SupportConversation::create([
                    'user_id' => $userId,
                    'status' => 'open',
                    'last_message_at' => now()
                ]);
            }

            $filePath = null;
            $fileType = null;

            if ($request->hasFile('file')) {

                $file = $request->file('file');

                $filePath = Helper::saveFile($file, 'Support_chat');

                $extension = strtolower($file->getClientOriginalExtension());

                if (in_array($extension, ['jpg', 'jpeg', 'png'])) {

                    $fileType = 'image';
                } elseif (in_array($extension, ['mp4', 'mov', 'avi'])) {

                    $fileType = 'video';
                } elseif (in_array($extension, ['mp3', 'wav'])) {

                    $fileType = 'audio';
                } else {

                    $fileType = 'file';
                }
            }

            $message = SupportMessage::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $userId,
                'sender_type' => 'user',
                'message' => $request->message,
                'is_read' => false,
                'file' => $filePath,
                'file_type' => $fileType,
            ]);

            broadcast(new SupportMessageSent($message))->toOthers();

            // update last message time
            $conversation->update([
                'last_message_at' => now()
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Message sent successfully',
                'conversation_id' => $conversation->id
            ]);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function reportUser(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'reported_user_id' => 'required|exists:app_users,id',
            'message_id' => 'nullable|exists:messages,id',
            'reason' => 'required',
            'description' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ]);
        }

        $report = ChatReport::create([
            'reporter_id' => $user->id,
            'reported_user_id' => $request->reported_user_id,
            'message_id' => $request->message_id,
            'reason' => $request->reason,
            'description' => $request->description
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Report submitted successfully',
            'data' => $report
        ]);
    }

    public function blockUser(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'blocked_user_id' => 'required|exists:app_users,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ]);
        }

        if ($user->id == $request->blocked_user_id) {
            return response()->json([
                'status' => false,
                'message' => 'You cannot block yourself'
            ]);
        }

        $exists = UserBlock::where('blocker_id', $user->id)
            ->where('blocked_user_id', $request->blocked_user_id)
            ->exists();

        if ($exists) {
            return response()->json([
                'status' => false,
                'message' => 'User already blocked'
            ]);
        }

        UserBlock::create([
            'blocker_id' => $user->id,
            'blocked_user_id' => $request->blocked_user_id
        ]);

        return response()->json([
            'status' => true,
            'message' => 'User blocked successfully'
        ]);
    }

    public function unblockUser(Request $request)
    {
        $user = Auth::user();

        $block = UserBlock::where('blocker_id', $user->id)
            ->where('blocked_user_id', $request->blocked_user_id)
            ->first();

        if (!$block) {
            return response()->json([
                'status' => false,
                'message' => 'User not blocked'
            ]);
        }

        $block->delete();

        return response()->json([
            'status' => true,
            'message' => 'User unblocked successfully'
        ]);
    }

    public function blockedUsersList()
    {
        $user = Auth::user();

        $blockedUsers = UserBlock::with('blockedUser:id,name,image')
            ->where('blocker_id', $user->id)
            ->latest()
            ->get();

        $data = $blockedUsers->map(function ($block) {
            return [
                'user_id' => $block->blockedUser->id,
                'name' => $block->blockedUser->name,
                'image' => Helper::showImage($block->blockedUser->image, true),
                'blocked_at' => $block->created_at->diffForHumans(),
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'Blocked users list',
            'data' => $data
        ]);
    }

    public function markSystentNotificationRead()
    {
        $userId = Auth::id();

        $notifications = Notification::where('type', 'post')
            ->pluck('id');

        foreach ($notifications as $notificationId) {

            NotificationRead::firstOrCreate([
                'notification_id' => $notificationId,
                'user_id' => $userId
            ]);
        }

        return response()->json([
            'status' => true
        ]);
    }
}
