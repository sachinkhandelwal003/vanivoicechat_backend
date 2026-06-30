<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemGiftTransaction extends Model
{
    use HasFactory;

    protected $table = 'item_gift_transactions';
    protected $fillable = ['sender_id', 'receiver_id', 'item_id', 'type', 'quantity', 'total_coins', 'days', 'start_at','end_at'];

    public function sender()
    {
        return $this->belongsTo(AppUser::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(AppUser::class, 'receiver_id');
    }

    public function theme()
    {
        return $this->belongsTo(Theme::class, 'item_id');
    }
}
