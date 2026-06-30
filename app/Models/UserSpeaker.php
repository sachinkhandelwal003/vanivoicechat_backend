<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserSpeaker extends Model
{
    use SoftDeletes;

    protected $table = 'user_speaker';

    protected $fillable = [
        'app_user_id',
        'content',
    ];

    public function user()
    {
        return $this->belongsTo(AppUser::class, 'app_user_id');
    }
}
