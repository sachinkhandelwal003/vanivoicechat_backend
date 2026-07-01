<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WithdrawalAccount extends Model
{
    protected $table = 'withdrawal_accounts';

    protected $fillable = [
        'user_id',
        'type',
        'account_holder_name',
        'bank_name',
        'account_number',
        'ifsc_code',
        'usdt_address',
        'channel_name',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(AppUser::class, 'user_id');
    }
}
