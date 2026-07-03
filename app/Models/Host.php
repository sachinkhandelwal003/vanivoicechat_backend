<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Host extends Model
{
    use HasFactory;

    protected $table = 'hosts';

    protected $fillable = [
        'user_id',
        'agency_id',
        'country_id',
        'invite_status',
        'is_dashboard_access',
        'status',
    ];

    // Host User
    public function user()
    {
        return $this->belongsTo(AppUser::class, 'user_id');
    }

    // Agency (Agent)
    public function agency()
    {
        return $this->belongsTo(Agency::class, 'agency_id');
    }

    // Country
    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }


    public function getStatusLabelAttribute()
    {
        return $this->status
            ? '<span class="badge bg-success">Approved</span>'
            : '<span class="badge bg-danger">Pending</span>';
    }

    public function monthlyReports()
    {
        return $this->hasMany(HostMonthlyReport::class);
    }
}
