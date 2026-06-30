<?php

namespace App\Models;

use App\Traits\CustomScopes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CoinPackages extends Model
{
    use HasFactory, CustomScopes;

    protected $table = 'coin_packages';
    protected $fillable = [
        'coins',
        'price',
        'bonus_percent',
        'bonus_coins',
        'total_coins',
        'icon',
        'status'
    ];
}
