<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Coupon;

class CouponController extends Controller
{
    public function coupon(Request $request)
    {
        // Get cart total (you can also calculate on server)
        $cartTotal = (float) $request->input('cart_total', 0);

        // Fetch active and valid coupons
        $coupons = Coupon::where('status', 'active')
            ->whereDate('valid_from', '<=', now())
            ->whereDate('valid_until', '>=', now())
            ->get();

        $response = $coupons->map(function ($coupon) use ($cartTotal) {
            $minOrder = (float) $coupon->min_order_amount;
            $discountValue = (float) $coupon->value;
            $code = strtoupper($coupon->coupon_code);

            // Check if coupon can be applied
            $canApply = $cartTotal >= $minOrder;

            // Prepare offer message
            $offerMessage = $canApply
                ? "Use code {$code} to get ₹{$discountValue} off"
                : "Add ₹" . number_format($minOrder - $cartTotal, 2) . " more to avail this offer";

            return [
                'id' => $coupon->id,
                'coupon_code' => $code,
                'discount_text' => "Get flat ₹{$discountValue} discount using coupon code {$code}",
                'min_order_amount' => $minOrder,
                'offer_message' => $offerMessage,
                'apply_enabled' => $canApply, // true only if cart total >= min order
                'type' => $coupon->type,
                'valid_from' => $coupon->valid_from,
                'valid_until' => $coupon->valid_until,
            ];
        });

        return response()->json([
            'status' => true,
            'data' => $response
        ]);
    }
}

