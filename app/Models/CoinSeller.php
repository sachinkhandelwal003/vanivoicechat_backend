<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoinSeller extends Model
{
    use HasFactory;

    protected $table = 'coin_sellers';

    protected $fillable = [
        'user_id',
        'country_id',
        'whatsapp_number',
        'sold',
        'is_merchant',
        'status',
    ];

    protected $casts = [
        'is_merchant' => 'boolean',
        'status' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(AppUser::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function scopeMerchant($query)
    {
        return $query->where('is_merchant', 1);
    }

    
    // Scope for normal sellers
    public function scopeNormalSeller($query)
    {
        return $query->where('is_merchant', 0);
    }

    // Active sellers
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}