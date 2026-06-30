<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Theme extends Model
{
    use HasFactory;

    protected $table = 'themes';
    protected $fillable = ['user_id', 'name', 'validity', 'visibility_type', 'needcoin', 'icon', 'status'];
    protected $casts = [
        'needcoin' => 'array',
        'validity' => 'array',
        'status'   => 'integer',
    ];

    public function givenThemes()
    {
        return $this->hasMany(ThemeGiven::class, 'theme_id');
    }
}
