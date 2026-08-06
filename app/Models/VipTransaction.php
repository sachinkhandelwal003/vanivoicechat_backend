<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VipTransaction extends Model
{
    use HasFactory;

    protected $table = 'vip_transactions';
    protected $fillable = [
        'user_id',
        'vip_id',
        'source',
        'sender_id',
        'coins_used',
        'start_at',
        'end_at',
    ];

    public function vip()
    {
        return $this->belongsTo(Vip::class, 'vip_id');
    }
}
