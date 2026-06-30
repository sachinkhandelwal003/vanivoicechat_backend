<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EntryTag extends Model
{
    use HasFactory;

    protected $table = 'entry_tags';
    protected $fillable = ['name', 'short_tag', 'validity', 'visibility_type', 'needcoin', 'icon', 'gif', 'img_key', 'text_key', 'frame_key', 'status'];

    protected $casts = [
        'needcoin' => 'array',
        'validity' => 'array',
    ];
}
