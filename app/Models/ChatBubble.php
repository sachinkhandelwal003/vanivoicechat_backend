<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatBubble extends Model
{
    use HasFactory;

    protected $table = 'chat_bubbles';
    protected $fillable = ['name', 'validity', 'visibility_type', 'needcoin', 'icon', 'status'];


    protected $casts = [
        'needcoin' => 'array',
        'validity' => 'array',
    ];
}
