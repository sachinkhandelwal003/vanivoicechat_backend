<?php

namespace App\Http\Controllers\Api;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\ChatMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ChatController extends Controller
{
    public function sendMessage(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'receiver_id' => 'required|exists:app_users,id',
            'message' => 'required|string',
        ]);

        if ($validated->fails()) {
            return response()->json([
                "status" => false,
                "message" => "Validation Error.",
                'errors' => $validated->errors()
            ], 422);
        }

        $data = [...$validated->validated()];

        $old_master = ChatMaster::where('sender_id', auth('api')->id())->where('receiver_id', $data['receiver_id'])->first();

        if ($old_master) {
            $data['chat_master_id'] = $old_master->id;
        } else {
            $data['chat_master_id'] = ChatMaster::create([
                'sender_id' => auth('api')->id(),
                'receiver_id' => $data['receiver_id'],
            ])->id;
        }


        $message = Chat::create([
            'chat_master_id' => $data['chat_master_id'],
            'sender_id' => auth('api')->id(),
            'receiver_id' => $data['receiver_id'],
            'message' => $data['message'],
        ]);

        broadcast(new MessageSent($message));

        return response()->json([
            'status' => true,
            'message' => 'message sent',
            'data' => $message
        ], 200);
    }

    public function getMessages(Request $request, $receiver_id)
    {
        $messages = Chat::where(function ($query) use ($receiver_id) {
            $query->where('sender_id', auth()->id())
                ->where('receiver_id', $receiver_id);
        })->orWhere(function ($query) use ($receiver_id) {
            $query->where('sender_id', $receiver_id)
                ->where('receiver_id', auth()->id());
        })->get();

        return response()->json([
            'status' => true,
            'message' => 'message sent',
            'data' => $messages
        ], 200);
    }
}
 