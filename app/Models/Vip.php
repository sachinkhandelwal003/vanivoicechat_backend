<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vip extends Model
{
    use HasFactory;

    protected $table = 'vips';
    protected $fillable = [
        'name',
        'title_tag',
        'badge',
        'entry_tag',
        'entry_tag_animation',
        'img_key',
        'text_key',
        'frame_key',
        'chat_card',
        'image_frame',
        'image_frame_animation',
        'username',
        'profile_frame',
        'profile_frame_animation',
        'days',
        'color',
        'voice_frame',
        'voice_animation',
        'needcoins'
    ];

    public function privileges()
    {
        return $this->hasMany(VipPrivilege::class, 'vip_id');
    }
}
