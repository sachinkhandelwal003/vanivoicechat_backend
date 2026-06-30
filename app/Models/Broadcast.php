<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Broadcast extends Model
{
    use HasFactory;

    protected $table = 'broadcasts';
    protected $fillable = ['user_id', 'message', 'cost', 'region_code'];


    public function user()
    {
        return $this->belongsTo(AppUser::class, 'user_id');
    }
}
