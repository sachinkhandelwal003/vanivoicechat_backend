<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserFan extends Model
{
    use HasFactory;

    protected $table = 'user_fans';
    protected $fillable = ['user_id','fan_user_id','amount', 'status'];
}
