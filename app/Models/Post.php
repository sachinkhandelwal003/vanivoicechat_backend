<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $table = 'posts';
    protected $fillable = ['user_id', 'topic_id', 'description', 'country'];

    public function media()
    {
        return $this->hasMany(PostMedia::class, 'post_id');
    }

    public function user()
    {
        return $this->belongsTo(AppUser::class, 'user_id');
    }


    public function comments()
    {
        return $this->hasMany(PostComment::class, 'post_id');
    }

    public function likes()
    {
        return $this->hasMany(PostLike::class, 'post_id');
    }

    public function topic()
    {
        return $this->belongsTo(Topic::class, 'topic_id');
    }

    public function reports()
    {
        return $this->hasMany(PostReport::class);
    }
}
