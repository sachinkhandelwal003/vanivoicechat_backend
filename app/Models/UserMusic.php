<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserMusic extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'user_music';

    protected $fillable = [
        'app_user_id',
        'amount',
        'review_status',
        'music_content',
    ];

    public function user()
    {
        return $this->belongsTo(AppUser::class, 'app_user_id');
    }
}
