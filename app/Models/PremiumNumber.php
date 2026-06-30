<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PremiumNumber extends Model
{
    use HasFactory;

    protected $table = 'premium_numbers';
    protected $fillable = [
        'user_id',
        'uid',
        'premium_number',
        'valid_days'
    ];

    public function user()
    {
        return $this->belongsTo(AppUser::class, 'user_id');
    }
}
