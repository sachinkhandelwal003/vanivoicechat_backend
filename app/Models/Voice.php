<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Voice extends Model
{
    use HasFactory;

    protected $table = 'voices';
    protected $fillable = ['name', 'validity', 'short_tag','icon','gif','visibility_type', 'needcoin', 'status'];


    protected $casts = [
        'needcoin' => 'array',
        'validity' => 'array',
    ];
}
