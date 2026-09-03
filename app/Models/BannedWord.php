<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BannedWord extends Model
{
    use HasFactory;

    protected $table = 'banned_words';

    protected $fillable = ['word', 'category', 'status', 'created_by'];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all active banned words for the API (used by mobile app).
     * Returns lowercase trimmed array of words.
     */
    public static function getActiveWords(): array
    {
        return static::where('status', 1)
            ->pluck('word')
            ->map(fn($w) => strtolower(trim($w)))
            ->toArray();
    }
}
