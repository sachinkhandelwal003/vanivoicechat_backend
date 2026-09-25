<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletTabSetting extends Model
{
    use HasFactory;

    protected $table = 'wallet_tab_settings';

    protected $fillable = [
        'tab_key',
        'tab_name',
        'sub_title',
        'icon_class',
        'status',
        'sort_order',
    ];

    protected $casts = [
        'status'     => 'integer',
        'sort_order' => 'integer',
    ];

    /**
     * Check if a specific tab is enabled
     */
    public static function isTabEnabled(string $key): bool
    {
        $tab = static::where('tab_key', $key)->first();
        return $tab ? (bool)$tab->status : true;
    }
}
