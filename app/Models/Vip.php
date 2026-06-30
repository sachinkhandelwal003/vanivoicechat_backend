<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vip extends Model
{
    use HasFactory;

    protected $table = 'vips';
    protected $fillable = ['name', 'badge', 'entry_tag', 'chat_card', 'image_frame', 'username', 'profile_frame', 'days','color', 'needcoins'];
}
