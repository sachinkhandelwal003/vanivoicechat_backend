<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WithdrawalRequest extends Model
{
    protected $table = 'withdrawal_requests';

    protected $fillable = [
        'user_id',
        'account_id',
        'method',
        'amount',
        'status',
        'remarks',
        'transaction_id',
        'requested_at',
        'processed_at',
        'processed_by',
    ];

    public function user()
    {
        return $this->belongsTo(AppUser::class, 'user_id');
    }

    public function account()
    {
        return $this->belongsTo(WithdrawalAccount::class, 'account_id');
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
