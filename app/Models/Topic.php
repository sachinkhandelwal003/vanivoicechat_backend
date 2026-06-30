<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Topic extends Model
{
    use HasFactory;

    protected $table = 'topics';
    protected $fillable = ['category', 'name', 'description', 'icon', 'status'];

    public function topicCat()
    {
        return $this->belongsTo(TopicCategory::class, 'category');
    }

    public function likes()
    {
        return $this->hasMany(TopicLike::class, 'topic_id');
    }
}
