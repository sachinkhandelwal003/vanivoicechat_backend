<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomPresence extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id',
        'user_id',
        'joined_at',
    ];

    public function user()
    {
        return $this->belongsTo(AppUser::class, 'user_id');
    }
}
