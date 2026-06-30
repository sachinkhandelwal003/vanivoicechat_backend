<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomAdmin extends Model
{
    use HasFactory;

    protected $table = 'room_admins';

    protected $fillable = [
        'room_id',
        'user_id',
        'created_by',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function user()
    {
        return $this->belongsTo(AppUser::class, 'user_id');
    }

    public function creator()
    {
        return $this->belongsTo(AppUser::class, 'created_by');
    }
}
