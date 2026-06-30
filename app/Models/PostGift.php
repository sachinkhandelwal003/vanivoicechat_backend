<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostGift extends Model
{
    protected $table = 'post_gifts';
    protected $fillable = ['post_id', 'sender_id','receiver_id','gift_id','gift_value','quantity','total_coins'];

}
