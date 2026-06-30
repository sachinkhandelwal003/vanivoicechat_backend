<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserLevel extends Model
{
    use HasFactory;

    protected $table = 'user_level';
    protected $fillable = ['grade', 'name', 'experience_cap', 'nickname_color', 'icon', 'avatar_corner','background_image'];

}
