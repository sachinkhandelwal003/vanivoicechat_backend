<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserAlbum extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'user_albums';

    protected $fillable = [
        'app_user_id',
        'file',
        'file_path'
    ];

    // Relationship: Album belongs to a User
    public function user()
    {
        return $this->belongsTo(AppUser::class, 'app_user_id');
    }
}
