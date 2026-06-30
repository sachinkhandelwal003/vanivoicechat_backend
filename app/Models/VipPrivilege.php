<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VipPrivilege extends Model
{
    use HasFactory;

    protected $table = 'vip_privileges';
    protected $fillable = ['vip_id', 'name', 'icon', 'status'];
}
