<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RedEnvelopeClaim extends Model
{
    use HasFactory;

    protected $table = 'red_envelope_claims';

    protected $fillable = [
        'red_envelope_id',
        'user_id',
        'room_id',
        'amount',
        'claimed_at',
    ];

    protected $casts = [
        'claimed_at' => 'datetime'
    ];

    public function redEnvelope()
    {
        return $this->belongsTo(RedEnvelope::class, 'red_envelope_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(AppUser::class, 'user_id', 'id');
    }

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id', 'id');
    }
}
