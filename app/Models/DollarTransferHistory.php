<?php

namespace App\Models;

use App\Traits\CustomScopes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DollarTransferHistory extends Model
{
    use HasFactory, CustomScopes;

    protected $table = 'dollar_transfer_histories';
    protected $fillable = [
        'sender_id',
        'receiver_id',
        'amount'
    ];

    public function sender()
    {
        return $this->belongsTo(AppUser::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(AppUser::class, 'receiver_id');
    }
}
