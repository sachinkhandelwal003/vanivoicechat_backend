<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BdUser extends Model
{
    use HasFactory;

    protected $table = 'bd_users';

    protected $fillable = [
        'user_id',
        'is_admin_bound',
        'admin_id',
        'country_id',
        'whatsapp_number',
        'briefing',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(AppUser::class, 'user_id');
    }

    public function admin()
    {
        return $this->belongsTo(AdminAccount::class, 'admin_id');
    }

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }
}