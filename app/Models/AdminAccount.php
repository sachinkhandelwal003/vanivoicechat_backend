<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminAccount extends Model
{
    use HasFactory;

    protected $table = 'admin_accounts';

    protected $fillable = [
        'user_id',
        'country_id',
        'whatsapp_number',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(AppUser::class, 'user_id', 'id');
    }

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function adminAccount()
    {
        return $this->belongsTo(AdminAccount::class, 'user_id', 'user_id');
    }

    public function agencies()
    {
        return $this->hasMany(Agency::class, 'admin_id');
    }

    public function bdUsers()
    {
        return $this->hasMany(BdUser::class, 'admin_id', 'id');
    }
}
