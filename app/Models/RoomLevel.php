<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomLevel extends Model
{
    use HasFactory;

    protected $table = 'room_levels';

    protected $fillable = [
        'level',
        'xp',
        'admins',
        'members',
        'status',
    ];

    protected $casts = [
        'level'   => 'integer',
        'xp'      => 'integer',
        'admins'  => 'integer',
        'members' => 'integer',
        'status'  => 'boolean',
    ];
}
