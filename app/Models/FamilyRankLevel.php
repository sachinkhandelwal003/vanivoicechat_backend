<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FamilyRankLevel extends Model
{
    use HasFactory;

    protected $table = 'family_rank_levels';
    protected $fillable = ['family_rank_id','level','badge', 'required_points'];

}
