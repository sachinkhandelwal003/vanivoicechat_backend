<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FamilyJoinRequest extends Model
{
    use HasFactory;

    protected $table = 'family_join_requests';
    protected $fillable = ['family_id', 'user_id', 'status'];

    public function user()
    {
        return $this->belongsTo(AppUser::class);
    }
}
