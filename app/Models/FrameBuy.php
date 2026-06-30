<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FrameBuy extends Model
{
    use HasFactory;

    protected $table = 'frame_buy';
    protected $fillable = ['frame_id','user_id','duration','start_at','end_at'];

      public function frame()
    {
        return $this->belongsTo(Frame::class);
    }

}
