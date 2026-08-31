<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $table = 'rooms';
    protected $fillable = ['user_id', 'room_id', 'room_name', 'room_image', 'country', 'bio', 'total_points', 'room_seat', 'active_theme_id', 'is_locked', 'password', 'status','is_banned','ban_reason','action_by','banned_at','is_pinned','pinned_at','xp','level','admin_limit','member_limit',
   'treasure_banner_1','treasure_banner_2','treasure_banner_3','treasure_banner_4','treasure_banner_5'];


    public function user()
    {
        return $this->belongsTo(AppUser::class, 'user_id');
    }

    public function onlineUsers()
    {
        return $this->hasMany(RoomPresence::class, 'room_id');
    }

    public function theme()
    {
        return $this->belongsTo(Theme::class, 'active_theme_id');
    }

    public function messages()
    {
        return $this->hasMany(RoomMessage::class, 'room_id');
    }

    public function members()
    {
        return $this->hasMany(RoomMember::class, 'room_id');
    }

    public function activeMembers()
    {
        return $this->hasMany(RoomMember::class, 'room_id')
            ->whereNull('left_at');
    }

    public function redEnvelopes()
    {
        return $this->hasMany(RedEnvelope::class, 'room_id', 'id');
    }

    public function redEnvelopeClaims()
    {
        return $this->hasMany(RedEnvelopeClaim::class, 'room_id', 'id');
    }

    public function roomUserRoles()
    {
        return $this->hasMany(RoomUserRole::class, 'room_id');
    }
}
