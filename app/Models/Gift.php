<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gift extends Model
{
    use HasFactory;

    protected $table = 'gifts';
    protected $fillable = ['gift_type','cover_type','name','cover', 'price','animation_type','gif_image','file_path','animation_duration', 'status'];


}
