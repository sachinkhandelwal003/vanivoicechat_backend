<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SvipPrivilege extends Model
{
    protected $table = 'svip_privileges';

    protected $fillable = [
        'name',
        'icon',
        'sort_order',
        'status'
    ];

    // Relation: kis SVIP me ye privilege hai
    public function svips()
    {
        return $this->belongsToMany(
            Svip::class,
            'svip_level_privileges',
            'privilege_id',
            'svip_id'
        )->withPivot('is_active')->withTimestamps();
    }
}