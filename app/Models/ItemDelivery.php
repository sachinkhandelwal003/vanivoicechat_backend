<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemDelivery extends Model
{
    use HasFactory;

    protected $table = 'item_deliveries';
    protected $fillable = ['recipient', 'type', 'item_id', 'valid_days', 'start_at', 'end_at', 'coins_used', 'source'];

    public function user()
    {
        return $this->belongsTo(AppUser::class, 'recipient');
    }

    public function frame()
    {
        return $this->belongsTo(Frame::class, 'item_id');
    }

    public function theme()
    {
        return $this->belongsTo(Theme::class, 'item_id');
    }
}
