<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgencySalarySettlement extends Model
{

    protected $table = 'agency_salary_settlements';
    protected $fillable = [

        'agency_id',
        'user_id',
        'month',
        'cycle',
        'target_value',
        'policy_id',
        'level',
        'agent_salary',
        'total_salary',
        'status',
        'settled_at',
    ];


    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }

    public function policy()
    {
        return $this->belongsTo(AgencyPolicy::class, 'policy_id');
    }
}
