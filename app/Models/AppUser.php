<?php

namespace App\Models;

// use Laravel\Passport\HasApiTokens;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class AppUser extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $table = 'app_users';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    // protected $hidden = ['password', 'mpin'];

    public function getCountryAttribute($value)
    {
        return $value ? strtoupper($value) : null;
    }

    public function countryData()
    {
        return $this->belongsTo(Country::class, 'country', 'name');
    }

    public function topicLikes()
    {
        return $this->hasMany(TopicLike::class);
    }

    public function postLikes()
    {
        return $this->hasMany(PostLike::class);
    }

    public function postReports()
    {
        return $this->hasMany(PostReport::class);
    }

    public function premium()
    {
        return $this->hasOne(PremiumNumber::class, 'user_id', 'id');
    }

    public function levelInfo()
    {
        return $this->belongsTo(UserLevel::class, 'user_level', 'grade');
    }

    public function conversations()
    {
        return $this->hasMany(SupportConversation::class, 'user_id');
    }

    public function supportConversations()
    {
        return $this->hasMany(SupportConversation::class, 'support_id');
    }

    public function roomMessages()
    {
        return $this->hasMany(RoomMessage::class, 'user_id');
    }

    public function roomMemberships()
    {
        return $this->hasMany(RoomMember::class, 'user_id');
    }

    public function sentRedEnvelopes()
    {
        return $this->hasMany(RedEnvelope::class, 'sender_user_id', 'id');
    }

    public function redEnvelopeClaims()
    {
        return $this->hasMany(RedEnvelopeClaim::class, 'user_id', 'id');
    }
}
