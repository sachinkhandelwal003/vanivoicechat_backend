<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataCard extends Model
{
    use HasFactory;

    protected $table = 'data_cards';
    protected $fillable = ['name', 'validity', 'short_tag','visibility_type', 'needcoin', 'icon', 'gif', 'status'];


    protected $casts = [
        'needcoin' => 'array',
        'validity' => 'array',
    ];
}
