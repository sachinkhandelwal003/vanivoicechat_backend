<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WcLevelSetting extends Model
{
    use HasFactory;

    protected $table = 'wc_level_settings';

    protected $fillable = [
        'type',
        'description',
    ];

    /**
     * Scope for type (wealth/charm)
     */
    public function scopeType($query, $type)
    {
        return $query->where('type', $type);
    }
}