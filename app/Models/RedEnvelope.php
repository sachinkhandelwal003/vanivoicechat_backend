<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RedEnvelope extends Model
{
    use HasFactory;

    protected $table = 'red_envelopes';

    protected $fillable = [
        'room_id',
        'sender_user_id',
        'country',
        'type',
        'total_amount',
        'total_users',
        'claimed_amount',
        'claimed_users',
        'remaining_amount',
        'remaining_users',
        'status',
        'expires_at',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id', 'id');
    }

    public function sender()
    {
        return $this->belongsTo(AppUser::class, 'sender_user_id', 'id');
    }

    public function claims()
    {
        return $this->hasMany(RedEnvelopeClaim::class, 'red_envelope_id', 'id');
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    public function isActive(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    public function isCompleted(): bool
    {
        return $this->remaining_users <= 0 || $this->remaining_amount <= 0;
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}