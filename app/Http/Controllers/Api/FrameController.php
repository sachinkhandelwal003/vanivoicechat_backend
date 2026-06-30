<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Helper\Helper;
use App\Models\Frame;
use App\Models\FrameBuy;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class FrameController extends Controller
{
  public function frameList()
    {
        try {
            $user = Auth::user(); 

            $frameLists = Frame::where('status', 1)->latest()->get();

            $frameData = $frameLists->map(function ($item) use ($user) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'visibility_type' => $item->visibility_type,
                    'needcoin' => $item->needcoin,
                    'validity' => $item->validity,

                    'icon' => \App\Helper\Helper::showImage($user->image ?? null, true),

                    'gif' => \App\Helper\Helper::showImage($item->gif, true),
                ];
            });

            return response()->json([
                'status' => true,
                'message' => 'Frame Lists Fetched Successfully',
                'data' => $frameData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function buyFrame(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $request->validate([
            'frame_id' => 'required|exists:frames,id',
            'days'     => 'required|integer|min:1',
        ]);

         $days = (int) $request->days;

        $frame = Frame::where('id', $request->frame_id)
            ->where('status', 1)
            ->where('visibility_type', 'in_app')
            ->first();

        if (!$frame) {
            return response()->json([
                'status' => false,
                'message' => 'Frame not available for purchase'
            ], 403);
        }

        $validities = array_map('intval', $frame->validity);
        $prices     = array_map('intval', $frame->needcoin);

        $index = array_search((int) $request->days, $validities, true);

        if ($index === false) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid duration selected'
            ], 422);
        }

        $price = $prices[$index];

        $already = FrameBuy::where('frame_id', $frame->id)
            ->where('user_id', $user->id)
            ->where('end_at', '>', now())
            ->exists();

        if ($already) {
            return response()->json([
                'status' => false,
                'message' => 'Frame already active'
            ], 409);
        }

        if ($user->total_points < $price) {
            return response()->json([
                'status' => false,
                'message' => 'Insufficient coins'
            ], 402);
        }

        DB::transaction(function () use ($user, $frame, $price, $request, $days) {

            $user->decrement('total_points', $price);

            FrameBuy::create([
                'frame_id' => $frame->id,
                'user_id'  => $user->id,
                'start_at' => now(),
                'end_at'   => now()->addDays($days),
                'duration' => $request->days,
            ]);
        });

        return response()->json([
            'status'  => true,
            'message' => 'Frame purchased successfully'
        ]);
    }
}
