<?php

namespace App\Models;

use App\Traits\CustomScopes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExchangeHistory extends Model
{
    use HasFactory, CustomScopes;

    protected $table = 'exchange_histories';
    protected $fillable = [
        'user_id',
        'usd_amount',
        'exchange_rate',
        'coins_received'
    ];
}
