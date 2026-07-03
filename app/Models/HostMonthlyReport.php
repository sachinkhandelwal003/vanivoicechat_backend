<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HostMonthlyReport extends Model
{

    use HasFactory;
    protected $table = 'hosts';

    protected $fillable = [

        'host_id',
        'agency_id',
        'user_id',
        'month',
        'gift_total',
        'policy_id',
        'level',
        'host_salary',
        'agency_commission',
        'total_salary',
        'status',
        'settled_at',
    ];

    public function host()
    {
        return $this->belongsTo(Host::class);
    }

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }

    public function policy()
    {
        return $this->belongsTo(HostPolicy::class, 'policy_id');
    }
}
