<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GiftTransaction extends Model
{
    protected $fillable = [
        'room_id',
        'sender_id',
        'receiver_id',
        'gift_id',
        'coin_value',
        'multiplier',
        'total_value'
    ];

    public function sender()
    {
        return $this->belongsTo(AppUser::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(AppUser::class, 'receiver_id');
    }

    public function gift()
    {
        return $this->belongsTo(Gift::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }
}
