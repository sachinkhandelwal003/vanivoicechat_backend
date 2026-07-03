<?php

namespace App\Models;

use App\Traits\CustomScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotificationRead extends Model
{
    use HasFactory;

    protected $table = 'notification_reads';

    protected $fillable = ['id','user_id','notification_id'];


    public function user()
    {
        return $this->hasOne(AppUser::class,'id','user_id');
    }

   
}
