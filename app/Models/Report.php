<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    protected $table = 'reports';

    protected $fillable = [
        'reporter_id',
        'report_type',
        'reported_id',
        'reason',
    ];


    public function reporter()
    {
        return $this->belongsTo(AppUser::class, 'reporter_id');
    }
}
