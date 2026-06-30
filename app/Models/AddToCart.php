<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AddToCart extends Model
{
    use HasFactory;

    protected $table = 'add_to_carts';

    protected $fillable = [
        'app_user_id',
        'product_id',
        'quantity',
        'item_total',
        'delivery_charge',
        'handling_charge',
        'late_night_charge',
        'grand_total',
    ];

    /**
     * Relationship: Each cart item belongs to a product.
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
