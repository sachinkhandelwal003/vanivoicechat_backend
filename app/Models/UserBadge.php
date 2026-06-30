<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserBadge extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'user_badges';

    protected $fillable = [
        'app_user_id',
        'badge_id',
        'badge_name',
        'badge_resources',
        'usage_status',
    ];

    /**
     * RELATIONSHIPS
     */

    // Each badge belongs to a user
    public function user()
    {
        return $this->belongsTo(AppUser::class, 'app_user_id');
    }
}
