<?php

namespace App\Models;

use App\Traits\CustomScopes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class ExchangeHistory extends Model
{
    use HasFactory, CustomScopes;

    protected $table = 'exchange_histories';
    protected $fillable = [
        'user_id',
        'usd_amount',
        'exchange_rate',
        'coins_received',
        'transaction_id',
        'wallet_type',
        'status',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->transaction_id)) {
                $model->transaction_id = 'EXC-' . strtoupper(Str::random(10));
            }
            if (empty($model->status)) {
                $model->status = 'success';
            }
            if (empty($model->wallet_type)) {
                $model->wallet_type = 'main';
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(AppUser::class, 'user_id');
    }
}
