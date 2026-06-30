<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $table = 'messages';
    protected $fillable = ['sender_id', 'receiver_id', 'message', 'file', 'file_type', 'is_read', 'reply_to'];

    public function sender()
    {
        return $this->belongsTo(AppUser::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(AppUser::class, 'receiver_id');
    }

    public function replyMessage()
    {
        return $this->belongsTo(Message::class, 'reply_to');
    }
}
