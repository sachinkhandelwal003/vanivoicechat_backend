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
        'requested_at',
        'processed_at',
    ];

    public function user()
    {
        return $this->belongsTo(AppUser::class, 'user_id');
    }
}
