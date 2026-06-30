<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SupportConversation extends Model
{
    use HasFactory;

    protected $table = 'support_conversation';

    protected $fillable = [
        'user_id',
        'support_id',
        'status',
        'last_message_at',
    ];

    public function messages()
    {
        return $this->hasMany(SupportMessage::class, 'conversation_id')->latest();
    }

    public function user()
    {
        return $this->belongsTo(AppUser::class, 'user_id');
    }

    public function support()
    {
        return $this->belongsTo(AppUser::class, 'support_id');
    }

    public function latestMessage()
    {
        return $this->hasOne(SupportMessage::class, 'conversation_id')
            ->latestOfMany();
    }
}
