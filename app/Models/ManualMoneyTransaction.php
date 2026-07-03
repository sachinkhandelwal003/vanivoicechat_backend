<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManualMoneyTransaction extends Model
{
    use HasFactory;

    protected $table = 'manual_money_transactions';

    protected $fillable = [
        'user_id',
        'admin_id',
        'type',
        'amount',
        'before_balance',
        'after_balance',
        'reason',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'before_balance' => 'decimal:2',
        'after_balance' => 'decimal:2',
    ];


    public function user()
    {
        return $this->belongsTo(AppUser::class, 'user_id');
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function adminProfile()
    {
        return $this->belongsTo(AdminAccount::class, 'user_id', 'user_id');
    }

    public function bd()
    {
        return $this->belongsTo(BdUser::class, 'user_id', 'user_id');
    }

    public function agency()
    {
        return $this->belongsTo(Agency::class, 'user_id', 'user_id');
    }

    public function host()
    {
        return $this->belongsTo(Host::class, 'user_id', 'user_id');
    }

    public function coinSeller()
    {
        return $this->belongsTo(CoinSeller::class, 'user_id', 'user_id');
    }

    public function getTypeBadgeAttribute()
    {
        return $this->type === 'credit'
            ? '<span class="badge bg-success">Credit</span>'
            : '<span class="badge bg-danger">Deduct</span>';
    }
}
