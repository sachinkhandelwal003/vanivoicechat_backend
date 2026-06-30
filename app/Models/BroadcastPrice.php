<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BroadcastPrice extends Model
{
    use HasFactory;

    protected $table = 'broadcast_prices';
    protected $fillable = ['user_id', 'region_code', 'price', 'status'];


    public function user()
    {
        return $this->belongsTo(AppUser::class, 'user_id');
    }

}
