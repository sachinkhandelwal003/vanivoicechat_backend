<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Medal extends Model
{
    use HasFactory;

    protected $table = 'medals';

    protected $fillable = [
        'title',
        'type',
        'level',
        'target_value',
        'icon',
        'sort',
        'status'
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    // Scope for active medals
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}