<?php

namespace App\Models;

use App\Traits\CustomScopes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CoinTransaction extends Model
{
    use HasFactory, CustomScopes;

    protected $table = 'coin_transactions';
    protected $fillable = [
        'user_id',
        'package_id',
        'coins',
        'bonus_coins',
        'total_coins',
        'amount',
        'type'
    ];

    public function user()
    {
        return $this->belongsTo(AppUser::class, 'user_id');
    }

    public function package()
    {
        return $this->belongsTo(CoinPackages::class, 'package_id');
    }
}
