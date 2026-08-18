<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppRule extends Model
{
    use HasFactory;

    protected $table = 'app_rules';

    protected $fillable = [
        'heading',
        'type',
        'rule',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function scopeType($query, $type)
    {
        return $query->where('type', $type);
    }

}
