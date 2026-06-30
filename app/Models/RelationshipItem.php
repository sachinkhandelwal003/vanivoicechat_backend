<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RelationshipItem extends Model
{
    use HasFactory;

    protected $table = 'relationship_items';

    protected $fillable = [
        'name',
        'type',
        'icon',
        'gif',
        'ring',
        'avatar',
        'frame',
        'badge',
        'background',
        'required_coins',
        'status'
    ];
}