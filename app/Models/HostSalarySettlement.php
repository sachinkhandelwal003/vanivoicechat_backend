<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HostSalarySettlement extends Model
{

    use HasFactory;
    protected $table = 'host_salary_settlements';

    protected $fillable = [

        'host_id',
        'agency_id',
        'user_id',
        'month',
        'cycle',
        'target_value',
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
