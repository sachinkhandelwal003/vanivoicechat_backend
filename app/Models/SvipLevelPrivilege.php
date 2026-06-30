<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SvipLevelPrivilege extends Model
{
    protected $table = 'svip_level_privileges';

    protected $fillable = [
        'svip_id',
        'privilege_id',
        'is_active'
    ];

    // SVIP relation
    public function svip()
    {
        return $this->belongsTo(Svip::class, 'svip_id');
    }

    // Privilege relation
    public function privilege()
    {
        return $this->belongsTo(SvipPrivilege::class, 'privilege_id');
    }
}
