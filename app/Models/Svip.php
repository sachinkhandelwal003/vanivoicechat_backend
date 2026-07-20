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
        'headwear_animation',
        'entry',
        'entry_animation',
        'img_key',
        'text_key',
        'frame_key',
        'entrance_image',
        'entrance_animation',
        'voice_image',
        'voice_animation',
        'profile_card',
        'profile_animation',
        'days',
        'color',
        'admin_limit',
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
