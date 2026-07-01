<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HostPolicy extends Model
{
    use HasFactory;

    protected $table = 'host_policies';

    protected $fillable = [
        'level',
        'time_hours',
        'target_value',
        'host_salary',
        'agent_commission',
        'total_salary',
        'country',
        'sorting',
        'status',
    ];
}