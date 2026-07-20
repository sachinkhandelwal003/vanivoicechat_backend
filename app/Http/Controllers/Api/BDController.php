<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Helper\Helper;
use App\Models\AppUser;
use App\Models\AdminAccount;
use App\Models\Agency;
use App\Models\BdUser;
use App\Models\Host;
use App\Models\Notification;
use App\Models\AgencySalarySettlement;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class BDController extends Controller
{

    public function bdDetails()
    {
        $userId = auth()->id();

        $bd = BdUser::with([
            'user:id,uid,name,image,country',
            'user.countryData:id,name,iso'
        ])
            ->where('user_id', $userId)
            ->where('status', 1)
            ->first();

        if (!$bd) {
            return response()->json([
                'status' => false,
                'message' => 'BD User not found'
            ], 404);
        }

        $flag = null;

        if ($bd->user?->countryData?->iso) {
            $flag = 'https://flagcdn.com/w40/' . strtolower($bd->user->countryData->iso) . '.png';
        }

        return response()->json([
            'status' => true,
            'message' => 'BD center details fetched successfully',
            'data' => [
                'id' => $bd->id,
                'user_id' => $bd->user_id,
                'uid' => $bd->user?->uid,
                'name' => $bd->user?->name,
                'image' => !empty($bd->user?->image) ? Helper::showImage($bd->user->image, true) : null,
                'country' => strtolower($bd->user?->country ?? ''),
            ]
        ]);
    }

    public function bdAgentList()
    {
        $userId = auth()->id();

        $bd = BdUser::where('user_id', $userId)
            ->where('status', 1)
            ->first();

        if (!$bd) {

            return response()->json([
                'status' => false,
                'message' => 'BD not found'
            ], 404);
        }

        $agents = Agency::with([
            'user:id,uid,name,image,country',
            'user.countryData:id,name,iso'
        ])
            ->withCount('hosts')
            ->where('bd_user_id', $bd->id)
            ->where('invite_status', 'accept')
            ->where('status', 1)
            // ->latest()
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'user_id' => $item->user_id,
                    'uid' => $item->user?->uid,
                    'name' => $item->user?->name,
                    'image' => !empty($item->user?->image) ? Helper::showImage($item->user->image, true) : null,
                    'country' => strtolower($item->user?->country ?? ''),
                    'hosts_count' => $item->hosts_count,
                    'status' => (bool) $item->status,
                    'created_at' => $item->created_at,
                ];
            });

        return response()->json([
            'status' => true,
            'message' => 'BD agent list fetched successfully',
            'data' => $agents
        ]);
    }

    public function inviteAgent11(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:app_users,id',
        ]);

        if ($validator->fails()) {

            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        $authUser = auth()->user();

        $bd = BdUser::where('user_id', $authUser->id)->where('status', 1)->first();

        if (!$bd) {

            return response()->json([
                'status' => false,
                'message' => 'BD not found'
            ], 404);
        }

        $user = AppUser::where('id', $request->user_id)->first();

        if (!$user) {

            return response()->json([
                'status' => false,
                'message' => 'User not found'
            ], 404);
        }

        //   Already exists check

        $exists = Agency::where('user_id', $user->id)->exists();

        if ($exists) {

            return response()->json([
                'status' => false,
                'message' => 'User already agency'
            ], 422);
        }

        // Create Agency Invite

        $agency = Agency::create([

            'user_id' => $user->id,
            'is_bd_bound' => 1,
            'bd_user_id' => $bd->id,
            'country_id' => $bd->country_id,
            'whatsapp_number' => $user->phone,
            'invite_status' => 'pending',
            'status' => 1,
        ]);

        // Notification

        Notification::create([

            'user_id' => $user->id,
            'sender_id' => $authUser->id,
            'receiver_id' => $user->id,
            'type' => 'agency',
            'title' => 'Agency Invitation',
            'message' => auth()->user()->name . ' invited you for agency',
            'country' => auth()->user()->country,
            'reference_id' => $agency->id,
            'is_read' => 0,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Agency invitation sent successfully',
            'data' => $agency

        ]);
    }

    public function inviteAgent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' =>
            'required|exists:app_users,id',
        ]);

        if ($validator->fails()) {

            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        //   Auth User

        $authUser = auth()->user();

        //   BD Check

        $bd = BdUser::where('user_id', $authUser->id)->where('status', 1)->first();

        if (!$bd) {

            return response()->json([
                'status' => false,
                'message' => 'BD not found'
            ], 404);
        }

        //  Find User

        $user = AppUser::where('id', $request->user_id)->first();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found'
            ], 404);
        }

        //   Self Invite Block

        if ($user->id == $authUser->id) {

            return response()->json([
                'status' => false,
                'message' => 'You cannot invite yourself'
            ], 422);
        }

        //  Existing Agency Check

        $existingAgency = Agency::where('user_id', $user->id)->first();

        if ($existingAgency) {

            return response()->json([
                'status' => false,
                'message' =>  'User already exists as Agency'
            ], 422);
        }

        // Host Restriction
        //  Host under another agency cannot directly become agency

        $existingHost = Host::where('user_id', $user->id)->first();

        if ($existingHost && !empty($existingHost->agency_id)) {

            return response()->json([
                'status' => false,
                'message' => 'Host role must be removed before assigning Agency'
            ], 422);
        }

        //   Existing Pending Invite Check

        $pendingInvite = Agency::where('user_id', $user->id)
            ->where('invite_status', 'pending')->first();

        if ($pendingInvite) {

            return response()->json([
                'status' => false,
                'message' => 'Agency invite already pending'
            ], 422);
        }

        //    Create Agency Invite

        $agency = Agency::create([
            'user_id' => $user->id,
            //   Agency Under BD
            'is_bd_bound' => 1,
            'bd_user_id' => $bd->id,
            // If BD belongs under admin
            'admin_id' => $bd->admin_id,
            'country_id' => $bd->country_id,
            'whatsapp_number' => $user->phone,
            'briefing' => null,
            'invite_status' => 'pending',
            'status' => 1,
        ]);

        //  Notification

        Notification::create([

            'user_id' => $user->id,
            'sender_id' => $authUser->id,
            'receiver_id' => $user->id,
            'type' => 'agency',
            'title' => 'Agency Invitation',
            'message' =>  $authUser->name . ' invited you for agency',
            'country' => strtolower($authUser->country),
            'reference_id' => $agency->id,
            'is_read' => 0,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Agency invitation sent successfully',
            'data' => [
                'id' =>  $agency->id,
                'user_id' => $user->id,
                'uid' =>  $user->uid,
                'name' =>  $user->name,
                'image' =>  !empty($user->image)  ? Helper::showImage($user->image,  true) : null,
                'invite_status' => $agency->invite_status
            ]
        ]);
    }

    public function bdDashboardAmount(Request $request)
    {
        $user = auth()->user();

        $bd = BdUser::where(
            'user_id',
            $user->id
        )
            ->where('status', 1)
            ->first();

        if (!$bd) {

            return response()->json([
                'status' => false,
                'message' => 'BD account not found'
            ], 404);
        }

        // Month


        $month = $request->input(
            'month',
            now()->format('Y-m')
        );

        // Agencies Under BD


        $agencyIds = Agency::where(
            'bd_user_id',
            $bd->id
        )
            ->where('status', 1)
            ->pluck('id');

        //  First Cycle


        $firstHalf = AgencySalarySettlement::whereIn(
            'agency_id',
            $agencyIds
        )
            ->where(
                'month',
                $month
            )
            ->where(
                'cycle',
                1
            )
            ->where(
                'status',
                'settled'
            )
            ->sum(
                'total_salary'
            );

        //   Second Cycle


        $secondHalf = AgencySalarySettlement::whereIn(
            'agency_id',
            $agencyIds
        )
            ->where(
                'month',
                $month
            )
            ->where(
                'cycle',
                2
            )
            ->where(
                'status',
                'settled'
            )
            ->sum(
                'total_salary'
            );

        //   Available Months


        $months = AgencySalarySettlement::whereIn(
            'agency_id',
            $agencyIds
        )
            ->select('month')
            ->distinct()
            ->orderByDesc('month')
            ->pluck('month');

        return response()->json([

            'status' => true,

            'message' => 'Dashboard data fetched successfully',

            'data' => [

                'month' => $month,

                'team' => [

                    'agency_count' =>
                    $agencyIds->count(),
                ],

                'salary' => [

                    'first_cycle' => [

                        'cycle' => '1-15',

                        'amount' => round(
                            $firstHalf,
                            2
                        )
                    ],

                    'second_cycle' => [

                        'cycle' => '16-Month End',

                        'amount' => round(
                            $secondHalf,
                            2
                        )
                    ],

                    'total' => round(
                        $firstHalf +
                            $secondHalf,
                        2
                    )
                ]
            ],

            'months' => $months
        ]);
    }
}
