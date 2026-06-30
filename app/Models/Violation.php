<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Violation extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'violations';

    protected $fillable = [
        'app_user_id',
        'illegal_content',
        'type',
        'description_of_violation',
        'operator',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    // Optional: relation with app_users
    public function user()
    {
        return $this->belongsTo(AppUser::class, 'app_user_id');
    }
}
