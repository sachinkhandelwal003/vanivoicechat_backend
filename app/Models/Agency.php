<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Agency extends Model
{
    use HasFactory;

    protected $table = 'agencies';

    protected $fillable = [
        'user_id',
        'admin_id',
        'is_bd_bound',
        'bd_user_id',
        'country_id',
        'whatsapp_number',
        'briefing',
        'status'
    ];

    protected $casts = [
        'is_bd_bound' => 'boolean',
        'status' => 'boolean',
    ];

    // Agency Owner
    public function user()
    {
        return $this->belongsTo(AppUser::class, 'user_id');
    }

    // BD User (optional)
    public function bdUser()
    {
        return $this->belongsTo(BdUser::class, 'bd_user_id');
    }

    // Country
    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }
}
