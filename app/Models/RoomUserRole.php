<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomUserRole extends Model
{
    use HasFactory;

    protected $table = 'room_user_roles';

    protected $fillable = [
        'room_id',
        'user_id',
        'role',
        'assigned_by',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function user()
    {
        return $this->belongsTo(AppUser::class, 'user_id');
    }

    public function assignedBy()
    {
        return $this->belongsTo(AppUser::class, 'assigned_by');
    }
}
