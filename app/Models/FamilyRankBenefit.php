<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FamilyRankBenefit extends Model
{
    use HasFactory;

    protected $table = 'family_rank_benefits';
    protected $fillable = ['family_level_id','level_badge', 'level_frame','members','admin'];

}
