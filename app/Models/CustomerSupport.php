<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerSupport extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'region'
    ];

    public function user()
    {
        return $this->belongsTo(AppUser::class, 'user_id');
    }

    public function conversations()
    {
        return $this->hasMany(SupportConversation::class, 'support_id');
    }
}
