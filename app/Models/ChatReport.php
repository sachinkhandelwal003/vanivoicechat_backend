<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatReport extends Model
{
    use HasFactory;

    protected $table = 'chat_reports';
    protected $fillable = [
        'reporter_id',
        'reported_user_id',
        'message_id',
        'reason',
        'description',
        'status'
    ];

    public function reporter()
    {
        return $this->belongsTo(AppUser::class, 'reporter_id');
    }

    public function reportedUser()
    {
        return $this->belongsTo(AppUser::class, 'reported_user_id');
    }
}
