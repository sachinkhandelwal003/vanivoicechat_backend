<?php

namespace App\Models;

use App\Traits\CustomScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Notification extends Model
{
    use HasFactory;

    protected $table = 'notifications';

    protected $fillable = ['id','user_id','sender_id','receiver_id','type','title','message','icon','image','reference_id','is_read'];


    public function user()
    {
        return $this->hasOne(AppUser::class,'id','user_id');
    }

   
}
