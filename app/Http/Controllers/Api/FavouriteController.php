<?php

namespace App\Http\Controllers\Api;

use App\Helper\Helper;
use App\Http\Controllers\Controller;
use App\Models\Apartment;
use App\Models\Favourite;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class FavouriteController extends Controller
{
    protected $userId;
    protected $user;

    public function __construct()
    {
        $this->middleware(['auth:api']);

        $this->middleware(function ($request, $next) {
            $user = Auth::guard('api')->user();

            if (!$user ) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $this->userId = $user->id;
            $this->user = $user;

            return $next($request);
        });
    }


    public function index(Request $request): JsonResponse
    {

        $favourites = Favourite::with(['property_detail', 'property_detail.apartment', 'property_detail.property'])->where('user_id', $this->userId)
            ->get()
            ->toArray();

        if (empty($favourites)) {
            return response()->json([
                'status' => false,
                'message' => 'No Apartments Found!!!',
                'data' => '',
            ], 404);
        }

        $data = [];
        $status_names = [2 => 'Occupied', 1 => 'Available'];


        foreach ($favourites as $key => $value) {
            $images = explode(',', $value['property_detail']['images']) ?? [];
            $full_paths = [];
            if (!empty($images)) {
                foreach ($images as $key => $path) {
                    $full_paths[] = Helper::showImage(trim($path), 1) ?? null;
                }
            }
            $data[] = [
                'apartment_id'       => $value['property_detail']['apartment']['id'] ?? null,
                'property_id'        => $value['property_detail']['property']['id'] ?? null,
                'unit_id'            => $value['property_detail']['id'] ?? null,
                'apartment_name'     => $value['property_detail']['apartment']['name'] ?? null,
                'property_name'      => $value['property_detail']['property']['name'] ?? null,
                'unit_name'          => $value['property_detail']['name'] ?? null,
                'images'             => $full_paths ?? null,
                'bedrooms'           => $value['property_detail']['bedrooms'] ?? null,
                'bathrooms'          => $value['property_detail']['bathrooms'] ?? null,
                'status'             => $value['property_detail']['status'] ?? null,
                'status_name'        => $status_names[$value['property_detail']['status']] ?? null,
            ];
        }


        return response()->json([
            'status' => true,
            'message' => 'Favourites Found!!!',
            'data' => $data,
        ], 200);
    }


    public function toggleFavourite(Request $request, $id)
    {
        $exists = Favourite::where('user_id', $this->userId)
            ->where('property_detail_id', $id)
            ->exists();

        if ($exists) {
            Favourite::where('user_id', $this->userId)->where('property_detail_id', $id)->delete();
            return response()->json([
                'status' => true,
                'message' => 'Removed from favourites'
            ], 200);
        } else {
            Favourite::insert([
                'user_id' => $this->userId,
                'property_detail_id' => $id,
            ]);
            return response()->json([
                'status' => true,
                'message' => 'Added to favourites'
            ], 200);
        }
    }
}
