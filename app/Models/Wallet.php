<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Wallet extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'wallet';

    protected $fillable = [
        'app_user_id',
        'type',
        'transaction_amount',
        'wallet_balance',
        'wallet_type',
        'remark',
        'operate',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    // Relationship: Wallet belongs to a user
    public function user()
    {
        return $this->belongsTo(AppUser::class, 'app_user_id');
    }
}
