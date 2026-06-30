<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\SoftDeletes;

class Address extends Model
{
    use HasFactory;

    protected $fillable = [
        'app_user_id',
        'full_address',
        'house_no',
        'block_no',
        'landmark',
        'receiver_name',
        'receiver_phone',
    ];
}
