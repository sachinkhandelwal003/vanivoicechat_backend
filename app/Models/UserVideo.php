<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserVideo extends Model
{
    use SoftDeletes;

    protected $table = "user_videos";

    protected $fillable = [
        'app_user_id',
        'amount',
        'review_status',
        'video_content',
    ];

    public function user()
    {
        return $this->belongsTo(AppUser::class, 'app_user_id');
    }
}
