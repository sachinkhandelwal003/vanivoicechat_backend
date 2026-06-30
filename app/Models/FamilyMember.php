<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FamilyMember extends Model
{

    protected $table = 'family_members';
    protected $fillable = [
        'family_id',
        'user_id',
        'role',
        'left_at'
    ];

    public function user()
    {
        return $this->belongsTo(AppUser::class);
    }

    public function family()
    {
        return $this->belongsTo(Family::class);
    }
}
