<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Frame extends Model
{
    use HasFactory;

    protected $table = 'frames';
    protected $fillable = ['name', 'validity', 'visibility_type', 'needcoin', 'icon', 'gif', 'status'];


    protected $casts = [
        'needcoin' => 'array',
        'validity' => 'array',
    ];
}
