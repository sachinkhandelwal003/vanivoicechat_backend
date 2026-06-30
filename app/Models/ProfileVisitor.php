<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProfileVisitor extends Model
{
    use HasFactory;

    protected $table = 'profile_visitors';
    protected $fillable = [
        'visitor_id',
        'user_id',
    ];

    public function visitor()
    {
        return $this->belongsTo(AppUser::class, 'visitor_id');
    }
}
