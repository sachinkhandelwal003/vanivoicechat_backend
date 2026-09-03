<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ManualCoinTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'admin_id', 'transaction_id', 'action', 'coins', 'before_coins', 'after_coins', 'reason'
    ];

    public function user()
    {
        return $this->belongsTo(AppUser::class, 'user_id');
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
