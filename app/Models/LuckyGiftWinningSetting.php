<?php

namespace App\Models;

use App\Traits\CustomScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LuckyGiftWinningSetting extends Model
{
    use HasFactory;

    protected $table = 'lucky_gift_winning_settings';

    protected $fillable = ['gift_id','quantity','multiple','is_whole_site','probability'];

}
