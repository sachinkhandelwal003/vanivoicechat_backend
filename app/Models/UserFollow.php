<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserFollow extends Model
{
    use HasFactory;

    protected $table = 'user_follows';
    protected $fillable = ['follower_id','following_id'];

    public function user()
    {
        return $this->belongsTo(AppUser::class, 'following_id');
    }

     public function fan()
    {
        return $this->belongsTo(AppUser::class, 'follower_id');
    }
}
