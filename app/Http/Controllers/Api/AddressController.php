<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Address;
use Illuminate\Support\Facades\Auth;

class AddressController extends Controller
{
    public function store(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'full_address' => 'required|string|max:500',
            'house_no'     => 'nullable|string|max:100',
            'block_no'     => 'nullable|string|max:100',
            'landmark'     => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $appUser = Auth::guard('api')->user();

        if (!$appUser) {
            return response()->json([
                'status'  => false,
                'message' => 'Unauthorized user',
            ], 401);
        }

        $validated = $validator->validated();

        // Auto-fill receiver data
        $validated['receiver_name']  = $appUser->name;
        $validated['receiver_phone'] = $appUser->mobile;

        // Always create new row
        $address = Address::create(array_merge($validated, [
            'app_user_id' => $appUser->id,
        ]));

        return response()->json([
            'status'  => true,
            'message' => 'New address added successfully',
            'data'    => $address,
        ], 201);
    }


    public function index()
    {
        $appUser = Auth::guard('api')->user();

        if (!$appUser) {
            return response()->json([
                'status'  => false,
                'message' => 'Unauthorized user',
            ], 401);
        }

        $addresses = Address::where('app_user_id', $appUser->id)->get();

        $addressData = $addresses->map(function ($item) {
            return [
                'address_id'     => $item->id,
                'full_address'   => $item->full_address, 
            ];
        });

        return response()->json([
            'status'  => true,
            'message' => 'Addresses fetched successfully',
            'data'    => $addressData,
        ]);
    }


    public function destroy($id)
    {
        $appUser = Auth::guard('api')->user();

        if (!$appUser) {
            return response()->json([
                'status'  => false,
                'message' => 'Unauthorized user',
            ], 401);
        }

        $address = Address::where('app_user_id', $appUser->id)
            ->where('id', $id)
            ->first();

        if (!$address) {
            return response()->json([
                'status'  => false,
                'message' => 'Address not found',
            ], 404);
        }

        $address->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Address deleted successfully',
        ], 200);
    }

    public function address(Request $request)
    {
        $request->validate([
            'full_address' => 'required|string|max:255',
        ]);

        $user = Auth::guard('api')->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $address = Address::updateOrCreate(
            ['app_user_id' => $user->id], 
            ['full_address' => $request->full_address]
        );

        return response()->json([
            'status' => true,
            'message' => 'Address saved successfully',
            'data' => $address,
        ], 200);
    }
}
