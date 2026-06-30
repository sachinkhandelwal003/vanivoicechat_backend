<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserBackpack extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'user_backpacks';

    protected $fillable = [
        'app_user_id',
        'tool_quantity',
        'tool_name',
        'is_worn',
        'is_giftable',
        'prop_cover',
    ];

    public function user()
    {
        return $this->belongsTo(AppUser::class, 'app_user_id');
    }
}
