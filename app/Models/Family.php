<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Family extends Model
{
    protected $table = 'families';

    protected $fillable = [
        'name',
        'logo',
        'description',
        'leader_id',
        'level',
        'total_points',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(AppUser::class, 'leader_id');
    }
}
