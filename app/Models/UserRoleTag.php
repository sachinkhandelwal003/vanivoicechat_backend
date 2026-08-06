<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserRoleTag extends Model
{
    use HasFactory;

    protected $table = 'user_role_tags';

    protected $fillable = [
        'name',
        'role_type',
        'file_type',
        'file',
        'status',
    ];

}
