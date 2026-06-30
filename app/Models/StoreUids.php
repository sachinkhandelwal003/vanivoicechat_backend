<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreUids extends Model
{
    use HasFactory;

    protected $table = 'store_uids';
    protected $fillable = ['rank_id', 'pattern_id', 'visibility_type', 'unique_id', 'needcoin', 'validity', 'badge', 'rank_badge','status'];
    protected $casts = [
        'needcoin' => 'array',
        'validity' => 'array',
    ];

    public function rank()
    {
        return $this->belongsTo(Rank::class, 'rank_id');
    }

    public function pattern()
    {
        return $this->belongsTo(Pattern::class, 'pattern_id');
    }
}
