<?php

namespace App\Models;

use App\Traits\CustomScopes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CoinRechargeHistory extends Model
{
    use HasFactory, CustomScopes;

    protected $table = 'coin_recharge_histories';
    protected $fillable = [
        'coin',
        'user_uid',
        'user_id',
        'user_id',
        'seller_id',
        'role',
        'transaction_type',
        'remark',
    ];


    public function user()
    {
        return $this->belongsTo(AppUser::class, 'user_id');
    }
}
