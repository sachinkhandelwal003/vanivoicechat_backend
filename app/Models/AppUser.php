<?php

namespace App\Models;

// use Laravel\Passport\HasApiTokens;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Models\DataCard;
use App\Models\Vip;
use App\Models\Svip;

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

    public function wcLevels()
    {
        return $this->hasMany(WCLevel::class, 'user_id', 'id');
    }

    public function userMedals()
    {
        return $this->hasMany(UserMedal::class, 'user_id', 'id');
    }

    // public function activeCard()
    // {
    //     return $this->belongsTo(DataCard::class, 'active_card_id');
    // }

    public function getActiveCardAttribute()
    {
        if (!$this->active_card_id) {
            return null;
        }

        switch ($this->active_profile_card_type) {
            case 'vip':
                return Vip::find($this->active_card_id);

            case 'svip':
                return Svip::find($this->active_card_id);

            case 'store':
            default:
                return DataCard::find($this->active_card_id);
        }
    }
    public function activeFrame()
    {
        return $this->belongsTo(Frame::class, 'active_frame_id');
    }

    public function host()
    {
        return $this->hasOne(Host::class, 'user_id');
    }

    public function agency()
    {
        return $this->hasOne(Agency::class, 'user_id');
    }

    public function bdUser()
    {
        return $this->hasOne(BdUser::class, 'user_id');
    }

    public function albums()
    {
        return $this->hasMany(UserAlbum::class, 'app_user_id', 'id');
    }

    public function musics()
    {
        return $this->hasMany(RoomMusicPlaylist::class, 'user_id', 'id');
    }

    public function deliveredItems()
    {
        return $this->hasMany(ItemDelivery::class, 'recipient', 'id')
            ->where('end_at', '>=', now());
    }

    public function giftedItems()
    {
        return $this->hasMany(ItemGiftTransaction::class, 'receiver_id', 'id')
            ->where('end_at', '>=', now());
    }

    public function activeTheme()
    {
        return $this->belongsTo(Theme::class, 'active_theme_id');
    }

    public function room()
    {
        return $this->hasOne(Room::class, 'user_id', 'id');
    }

    public function sentInvites()
    {
        return $this->hasMany(InviteUser::class, 'inviter_id');
    }

    public function receivedInvite()
    {
        return $this->hasOne(InviteUser::class, 'invited_user_id');
    }

    public function inviteRewards()
    {
        return $this->hasMany(InviteRewardHistory::class, 'user_id');
    }


    public function activeSvip()
    {
        return $this->hasOne(SvipTransaction::class, 'user_id')
            ->where('end_at', '>=', now());
    }

    public function coinSeller()
    {
        return $this->hasOne(\App\Models\CoinSeller::class, 'user_id', 'id');
    }
}
