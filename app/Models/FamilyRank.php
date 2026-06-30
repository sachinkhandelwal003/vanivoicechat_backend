<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FamilyRank extends Model
{
    use HasFactory;

    protected $table = 'family_ranks';
    protected $fillable = ['name','sort'];

}
