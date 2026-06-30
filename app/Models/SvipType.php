<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SvipType extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'svip_type';

    protected $fillable = [
        'name',
        'gif',
        'image',
        'description',
        'coin',
        'status',
    ];

    protected $dates = [
        'deleted_at',
    ];
}