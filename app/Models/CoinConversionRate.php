<?php

namespace App\Models;

use App\Traits\CustomScopes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CoinConversionRate extends Model
{
    use HasFactory, CustomScopes;

    protected $table = 'coin_conversion_rates';
    protected $fillable = [
        'seller_to_user_rate',
        'merchant_to_seller_rate',
        'merchant_to_user_rate',
        'coin_exchange_rate'
    ];
}
