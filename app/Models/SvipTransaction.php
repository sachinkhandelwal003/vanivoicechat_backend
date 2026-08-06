<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SvipTransaction extends Model
{
    use HasFactory;

    protected $table = 'svip_transactions';
    protected $fillable = [
        'user_id',
        'svip_id',
        'coins_used',
        'start_at',
        'end_at',
    ];

    public function svip()
    {
        return $this->belongsTo(Svip::class);
    }

    public function user()
    {
        return $this->belongsTo(AppUser::class, 'user_id');
    }
}
