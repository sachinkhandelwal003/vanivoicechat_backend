<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssetsUser extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'assets_user';

    protected $fillable = [
        'wallet_id',
        'app_user_id',
    ];

    /**
     * RELATIONSHIPS
     */

    // Each assetsUser belongs to one wallet
    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    // Each assetsUser belongs to one app user
    public function user()
    {
        return $this->belongsTo(AppUser::class, 'app_user_id');
    }
}
