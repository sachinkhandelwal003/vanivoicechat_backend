<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Svip extends Model
{
    protected $table = 'svips';

    protected $fillable = [
        'name',
        'need_coins',
        'medal',
        'medal_gif',
        'title',
        'bubble',
        'headwear',
        'entry',
        'days',
        'color', 
        'status'
    ];

    // Relation: All privileges of this SVIP
    public function privileges()
    {
        return $this->belongsToMany(
            SvipPrivilege::class,
            'svip_level_privileges',
            'svip_id',
            'privilege_id'
        )->withPivot('is_active')->withTimestamps();
    }

    // Only active privileges
    public function activePrivileges()
    {
        return $this->belongsToMany(
            SvipPrivilege::class,
            'svip_level_privileges',
            'svip_id',
            'privilege_id'
        )->wherePivot('is_active', 1);
    }
}
