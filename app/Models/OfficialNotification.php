<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfficialNotification extends Model
{
    use HasFactory;

    protected $table = 'official_notifications';

    protected $fillable = [
        'user_id',
        'country',
        'notification',
        'image',
        'url'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    
    // Relationships
    public function user()
    {
        return $this->belongsTo(AppUser::class, 'user_id');
    }
}