<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Banner extends Model
{
    use HasFactory;

    protected $fillable = [
        'large_banner',
        'small_banner',
        'jump',
        'address',
        'type_address_app',
        'uid',
        'room_id',
        'display',
        'start_time',
        'end_time',
        'region',
        'description'
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time'   => 'datetime',
    ];

    public function country()
    {
        return $this->belongsTo(Country::class, 'region', 'id');
    }
}
