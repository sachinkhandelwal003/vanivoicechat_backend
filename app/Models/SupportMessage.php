<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SupportMessage extends Model
{
    use HasFactory;

    protected $table = 'support_message';

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'sender_type',
        'message',
        'file',
        'file_type',
        'reply_to',
        'is_read'
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];


    // ------ Relationships --------

    // Message belongs to a user
    public function user()
    {
        return $this->belongsTo(AppUser::class);
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function conversation()
    {
        return $this->belongsTo(SupportConversation::class);
    }

    public function senderUser()
    {
        return $this->belongsTo(AppUser::class, 'sender_id');
    }

    // Parent message (jis message ka reply hai)
    public function replyMessage()
    {
        return $this->belongsTo(SupportMessage::class, 'reply_to');
    }

    // Child replies
    public function replies()
    {
        return $this->hasMany(SupportMessage::class, 'reply_to');
    }
}
