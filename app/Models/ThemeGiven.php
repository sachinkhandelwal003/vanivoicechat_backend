<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ThemeGiven extends Model
{
    use HasFactory;

    protected $table = 'theme_given';
    protected $fillable = ['theme_id','user_id','source','duration','start_at','end_at'];

      public function theme()
    {
        return $this->belongsTo(Theme::class, 'theme_id');
    }

}
