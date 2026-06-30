<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RelationshipInvitation extends Model
{
    use HasFactory;

    protected $table = 'relationship_invitations';

    protected $fillable = [
        'sender_id',
        'receiver_id',
        'relationship_item_id',
        'type',
        'status',
    ];

    public function sender()
    {
        return $this->belongsTo(AppUser::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(AppUser::class, 'receiver_id');
    }

    public function relationshipItem()
    {
        return $this->belongsTo(RelationshipItem::class, 'relationship_item_id');
    }
}