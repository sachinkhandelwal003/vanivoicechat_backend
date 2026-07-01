<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Medal;
use App\Models\UserMedal;
use App\Helper\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;


class MedalController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = Medal::where('status', 1)->orderBy('sort', 'asc');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $medals = $query->get();

        $userMedals = UserMedal::where('user_id', $user->id)->get()->keyBy('medal_id');
        $userMedalIds = UserMedal::where('user_id', $user->id)->pluck('medal_id')->toArray();

        $data = $medals->map(function ($item) use ($userMedals) {
            $userMedal = $userMedals->get($item->id);
            return [
                'id' => $item->id,
                'title' => $item->title,
                'type' => $item->type,
                'image' => Helper::showImage($item->icon, true),
                'is_unlock' => !is_null($userMedal),
                'is_equipped' => $userMedal ? (bool) $userMedal->is_equipped : false,
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

    public function myMedals()
    {
        $userId = auth()->id();

        $medals = UserMedal::with('medal')
            ->where('user_id', $userId)
            ->get();

        $data = $medals->map(function ($item) {

            return [
                'id' => $item->medal->id,
                'title' => $item->medal->title,
                'type' => $item->medal->type,
                'image' => Helper::showImage($item->medal->icon, true),
                'is_equipped' => (bool) $item->is_equipped,
                'slot_no' => $item->slot_no
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'My medals fetched successfully',
            'data' => $data
        ]);
    }

    public function toggleEquipMedal(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'medal_id' => 'required|exists:medals,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        $userId = auth()->id();

        DB::beginTransaction();

        try {

            $userMedal = UserMedal::where('user_id', $userId)
                ->where('medal_id', $request->medal_id)
                ->first();

            if (!$userMedal) {

                DB::rollBack();

                return response()->json([
                    'status' => false,
                    'message' => 'Medal not unlocked'
                ], 422);
            }



            /*
        |--------------------------------------------------------------------------
        | Unequip
        |--------------------------------------------------------------------------
        */
            if ($userMedal->is_equipped) {

                $removedSlot = $userMedal->slot_no;

                $userMedal->update([
                    'is_equipped' => 0,
                    'slot_no' => null
                ]);


                UserMedal::where('user_id', $userId)
                    ->whereNotNull('slot_no')
                    ->where('slot_no', '>', $removedSlot)
                    ->decrement('slot_no');


                DB::commit();

                return response()->json([
                    'status' => true,
                    'action' => 'unequipped',
                    'message' => 'Medal unequipped successfully'
                ]);
            }



            // Equip

            $equippedCount = UserMedal::where('user_id', $userId)
                ->where('is_equipped', 1)
                ->count();


            if ($equippedCount >= 10) {

                DB::rollBack();

                return response()->json([
                    'status' => false,
                    'message' => 'Maximum 10 medals allowed'
                ], 422);
            }



            $userMedal->update([
                'is_equipped' => 1,
                'slot_no' => $equippedCount + 1
            ]);


            DB::commit();

            return response()->json([
                'status' => true,
                'action' => 'equipped',
                'message' => 'Medal equipped successfully'
            ]);
        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
