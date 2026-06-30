<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Medal;
use App\Helper\Helper;
use Illuminate\Http\Request;

class MedalController extends Controller
{
    public function index(Request $request)
    {
        $query = Medal::where('status', 1);

        if ($request->has('type') && $request->type != '') {
            $query->where('type', $request->type);
        }

        $medals = $query->latest()->get();

        $data = $medals->map(function ($item) {
            return [
                'id' => $item->id,
                'title' => $item->title,
                'type' => $item->type,
                'image' => Helper::showImage($item->icon, true),
                'status' => $item->status,
                'created_at' => $item->created_at,
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'Medal list fetched successfully',
            'data' => $data
        ]);
    }
}
