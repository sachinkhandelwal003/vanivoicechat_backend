<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CustomerSupport;
use App\Models\SupportConversation;
use App\Models\SupportMessage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Helper\Helper;
use App\Events\SupportMessageSent;
use App\Events\SupportMessageDeleted;
use Carbon\Carbon;

class CustomerSupportController extends Controller
{
    public function getSupportUser()
    {
        $user = Auth::user();

        $support = DB::table('customer_supports')
            ->join('app_users', 'app_users.id', '=', 'customer_supports.user_id')
            ->where('customer_supports.region', $user->country)
            ->select(
                'app_users.id as support_user_id',
                'app_users.name',
                'app_users.image'
            )
            ->first();

        if (!$support) {

            return response()->json([
                'status' => false,
                'message' => 'Support not available in your country'
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Data Fetch Successfully',
            'data' => [
                'support_user_id' => $support->support_user_id,
                'name' => $support->name,
                'image' => Helper::showImage($support->image, true)
            ]
        ]);
    }
    public function startChat(Request $request)
    {
        $user = Auth::user();

        $support = CustomerSupport::where('region', $user->country)->first();

        if (!$support) {
            return response()->json([
                'status' => false,
                'message' => 'No support available in your region'
            ]);
        }

        $conversation = SupportConversation::firstOrCreate(
            [
                'user_id' => $user->id,
                'support_id' => $support->user_id,
            ],
            [
                'region' => $user->country,
                'status' => 1
            ]
        );

        return response()->json([
            'status' => true,
            'conversation' => $conversation
        ]);
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required|exists:support_conversation,id',
            'message' => 'nullable|string',
            'file' => 'nullable|file'
        ]);

        $conversation = SupportConversation::findOrFail($request->conversation_id);

        $authUser = Auth::user();

        $isSupport = CustomerSupport::where('user_id', $authUser->id)->exists();

        if ($isSupport) {
            $senderType = 'support';
        } else {
            $senderType = 'user';
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
            'sender_id' => Auth::id(),
            'sender_type' => $senderType,
            'message' => $request->message,
            'file' => $filePath,
            'file_type' => $fileType,
            'reply_to' => $request->reply_to,
        ]);
        $message->load(['replyMessage.senderUser']);
        $message->receiver_image = Helper::showImage($message->senderUser->image, true);
        if ($message->replyMessage) {
            $reply = $message->replyMessage;
            $message->reply = [
                'id' => $reply->id,
                'message' => $reply->message,
                'file' => $reply->file
                    ? Helper::showImage($reply->file, true)
                    : null,
                'file_type' => $reply->file_type,
                'sender_id' => $reply->sender_id,
                'sender_name' => $reply->senderUser->name ?? null,
                'sender_image' => Helper::showImage($reply->senderUser->image, true),
            ];
        } else {
            $message->reply = null;
        }

        unset($message->replyMessage);
        unset($message->senderUser);

        broadcast(new SupportMessageSent($message))->toOthers();

        $conversation->update([
            'last_message_at' => now()
        ]);

        return response()->json([
            'status' => true,
            'message' => $message
        ]);
    }

    public function getMessages($conversationId)
    {
        $authUser = Auth::user();

        $conversation = SupportConversation::findOrFail($conversationId);

        if (
            $conversation->user_id != $authUser->id &&
            $conversation->support_id != $authUser->id
        ) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        SupportMessage::where('conversation_id', $conversationId)
            ->where('sender_id', '!=', $authUser->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $messages = SupportMessage::with(['senderUser', 'replyMessage.senderUser'])
            ->where('conversation_id', $conversationId)
            ->orderBy('created_at', 'asc')
            ->paginate(20);

        $messages->getCollection()->transform(function ($item) use ($authUser) {
            $reply = null;

            if ($item->replyMessage) {

                $reply = [
                    'id' => $item->replyMessage->id,
                    'message' => $item->replyMessage->message,
                    'file' => $item->replyMessage->file
                        ? Helper::showImage($item->replyMessage->file, true)
                        : null,
                    'file_type' => $item->replyMessage->file_type,
                    'sender_id' => $item->replyMessage->sender_id,
                    'sender_name' => $item->replyMessage->senderUser->name ?? null,
                    'sender_image' => Helper::showImage($item->replyMessage->senderUser->image, true)
                ];
            }
            return [
                'id' => $item->id,
                'conversation_id' => $item->conversation_id,
                'sender_id' => $item->sender_id,
                'sender_type' => $item->sender_type,
                'type' => $item->sender_id == $authUser->id ? 'sender' : 'receiver',
                'message' => $item->message,
                'file' => $item->file ? Helper::showImage($item->file, true) : null,
                'file_type' => $item->file_type,
                'is_read' => $item->is_read,
                'created_at' => $item->created_at,

                'sender' => [
                    'id' => $item->senderUser->id ?? null,
                    'name' => $item->senderUser->name ?? null,
                    'image' => Helper::showImage($item->senderUser->image, true),
                ],
                'reply' => $reply
            ];
        });

        return response()->json([
            'status' => true,
            'messages' => $messages
        ]);
    }

    public function supportConversations()
    {
        $authId = Auth::id();
        $isCustomerSupport = CustomerSupport::where('user_id', $authId)->exists();
        $conversations = SupportConversation::where(function ($query) use ($authId) {
            $query->where('support_id', $authId)
                ->orWhere('user_id', $authId);
        })
            ->with(['user', 'support'])
            ->orderBy('last_message_at', 'desc')
            ->get()
            ->map(function ($conversation) use ($authId) {

                $unread = SupportMessage::where('conversation_id', $conversation->id)
                    ->where('sender_id', '!=', $authId)
                    ->where('is_read', 0)
                    ->count();

                // If logged user is support
                if ($conversation->support_id == $authId) {
                    $otherUser = $conversation->user;
                } else {
                    $otherUser = $conversation->support;
                }

                return [
                    'conversation_id' => $conversation->id,
                    'id' => $otherUser->id,
                    'uid' => $otherUser->uid,
                    'name' => $otherUser->name ?? null,
                    'image' => Helper::showImage($otherUser->image, true),
                    'last_message' => $conversation->latestMessage?->message,
                    'file' => $conversation->latestMessage?->file
                        ? Helper::showImage($conversation->latestMessage->file, true)
                        : null,

                    'file_type' => $conversation->latestMessage?->file_type,
                    'last_message_at' => Carbon::parse($conversation->last_message_at)->diffForHumans(),
                    'unread_count' => $unread,
                ];
            });

        return response()->json([
            'status' => true,
            'is_customer_support' => $isCustomerSupport,
            'conversations' => $conversations
        ]);
    }

    public function deleteMessage($id)
    {
        $authId = Auth::id();

        $message = SupportMessage::find($id);

        if (!$message) {
            return response()->json([
                'status' => false,
                'message' => 'Message not found'
            ], 404);
        }

        if ($message->sender_id != $authId) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $conversationId = $message->conversation_id;

        $message->delete();

        broadcast(new SupportMessageDeleted($conversationId, $id))->toOthers();

        return response()->json([
            'status' => true,
            'message' => 'Message deleted successfully',
            'message_id' => $id
        ]);
    }
}
