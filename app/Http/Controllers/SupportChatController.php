<?php

namespace App\Http\Controllers;

use App\Models\SupportMessage;
use App\Models\SupportConversation;
use App\Events\SupportMessageSent;
use Illuminate\Http\Request;
use App\Helper\Helper;
use App\Http\Controllers\Controller;

class SupportChatController extends Controller
{
    public function index()
    {
        $conversations = SupportConversation::with('user')
            ->withCount(['messages as unread_count' => function ($query) {
                $query->where('sender_type', 'user')
                    ->where('is_read', false);
            }])->latest()->get();

        $totalUnread = SupportMessage::where('sender_type', 'user')
            ->where('is_read', false)
            ->count();
        // dd($conversations); 
        return view('customer_support.index', compact('conversations', 'totalUnread'));
    }

    public function getMessages($conversationId)
    {
        $messages = SupportMessage::where('conversation_id', $conversationId)
            ->orderBy('created_at', 'asc')
            ->get();

        SupportMessage::where('conversation_id', $conversationId)
            ->where('sender_type', 'user')
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json($messages);
    }

    public function conversationRow($id)
    {
        $conversation = SupportConversation::with('user')
            ->withCount(['messages as unread_count' => function ($query) {
                $query->where('sender_type', 'user')
                    ->where('is_read', false);
            }])
            ->findOrFail($id);

        return view('customer_support.partials.conversation_row', compact('conversation'));
    }

    public function send(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required',
            'message' => 'nullable|string',
            'file' => 'nullable|array',
            'file.*' => 'file|mimes:jpg,jpeg,png,mp4,mov,avi|max:20480'
        ]);

        if (!$request->message && !$request->hasFile('file')) {
            return response()->json([
                'status' => false,
                'error' => 'Message or file is required'
            ], 422);
        }

        // If files exist
        if ($request->hasFile('file')) {

            foreach ($request->file('file') as $file) {

                $filePath = Helper::saveFile($file, 'Support_chat');

                $extension = strtolower($file->getClientOriginalExtension());

                if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
                    $fileType = 'image';
                } elseif (in_array($extension, ['mp4', 'mov', 'avi'])) {
                    $fileType = 'video';
                } else {
                    $fileType = 'file';
                }

                $message = SupportMessage::create([
                    'conversation_id' => $request->conversation_id,
                    'sender_id'       => auth()->id(),
                    'sender_type'     => 'admin',
                    'message'         => $request->message,
                    'is_read'         => false,
                    'file'            => $filePath,
                    'file_type'       => $fileType,
                ]);

                broadcast(new SupportMessageSent($message))->toOthers();
            }
        } else {
            // If only text message
            $message = SupportMessage::create([
                'conversation_id' => $request->conversation_id,
                'sender_id'       => auth()->id(),
                'sender_type'     => 'admin',
                'message'         => $request->message,
                'is_read'         => false,
            ]);

            broadcast(new SupportMessageSent($message))->toOthers();
        }

        return response()->json(['status' => true]);
    }
}
