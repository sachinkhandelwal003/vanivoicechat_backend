<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoinSellerTransaction extends Model
{
    use HasFactory;

    protected $table = 'coin_seller_transactions';

    protected $fillable = [
        'sender_id',
        'sender_type',
        'receiver_id',
        'receiver_type',
        'coins',
        'balance_before',
        'balance_after',
        'transaction_type',
        'reference_id',
        'remark',
    ];

    // Sender User
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    // Receiver User
    public function receiver()
    {
        return $this->belongsTo(AppUser::class, 'receiver_id');
    }
}
