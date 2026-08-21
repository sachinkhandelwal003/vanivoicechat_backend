<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Game extends Model
{
    use HasFactory;

    protected $table = 'games';

    protected $fillable = [
        'name',
        'slug',
        'sud_game_id',
        'sud_game_type',
        'description',
        'icon',
        'banner',
        'entry_coins',
        'min_coins',
        'max_coins',
        'status',
        'is_featured',
        'sort_order',
    ];

    protected $casts = [
        'entry_coins' => 'integer',
        'min_coins' => 'integer',
        'max_coins' => 'integer',
        'status' => 'boolean',
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function sessions()
    {
        return $this->hasMany(GameSession::class, 'game_id');
    }
}
