<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'app_user_id',
        'service_pin',
        'shipping_address_id',
        'quantity',
        'total_amount',
        'delivery_charge',
        'handling_charge',
        'late_night_charge',
        'grand_total',
        'payment_method',
        'razorpay_order_id',
        'razorpay_payment_id',
        'razorpay_signature'
    ];

    public function orderItems()
    {
        return $this->hasMany(OrderItems::class, 'order_id', 'id');
    }

    public function shippingAddress()
    {
        return $this->belongsTo(StoreAddress::class, 'shipping_address_id');
    }

    public function appUser()
    {
        return $this->belongsTo(AppUser::class, 'app_user_id');
    }
}
