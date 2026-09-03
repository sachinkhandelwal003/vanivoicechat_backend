<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RelationshipFeeConfig extends Model
{
    use HasFactory;

    protected $table = 'relationship_fee_configs';

    protected $fillable = [
        'country_id',
        'relationship_type',
        'invite_fee',
        'break_fee',
        'status',
        'created_by',
    ];

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
