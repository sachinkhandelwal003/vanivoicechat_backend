<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use HasFactory;

    protected $table = 'system_settings';

    protected $fillable = [
        'type',
        'setting_value',
    ];

    protected $casts = [
        'setting_value' => 'integer',
    ];

    /**
     * Get setting value by type
     */
    public static function getValue(string $type, $default = null)
    {
        return static::where('type', $type)->value('setting_value') ?? $default;
    }

    /**
     * Create or update setting
     */
    public static function setValue(string $type, $value): void
    {
        static::updateOrCreate(
            ['type' => $type],
            ['setting_value' => $value]
        );
    }
}
