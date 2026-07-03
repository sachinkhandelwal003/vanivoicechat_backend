<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Helper\Helper;
use App\Models\AppUser;
use App\Models\AdminAccount;
use App\Models\Agency;
use App\Models\BdUser;
use App\Models\Notification;
use App\Models\Host;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class AdminCenterController extends Controller
{
    public function adminCenterDetails()
    {
        $userId = auth()->id();

        $admin = AdminAccount::with([
            'user:id,uid,name,image,country',
            'user.countryData:id,name,iso'
        ])
            ->where('user_id', $userId)
            ->where('status', 1)
            ->first();

        if (!$admin) {
            return response()->json([
                'status' => false,
                'message' => 'Admin not found'
            ], 404);
        }

        $flag = null;

        if ($admin->user?->countryData?->iso) {
            $flag = 'https://flagcdn.com/w40/' . strtolower($admin->user->countryData->iso) . '.png';
        }

        return response()->json([
            'status' => true,
            'message' => 'Admin center details fetched successfully',
            'data' => [
                'id' => $admin->id,
                'user_id' => $admin->user_id,
                'uid' => $admin->user?->uid,
                'name' => $admin->user?->name,
                'image' => !empty($admin->user?->image) ? Helper::showImage($admin->user->image, true) : null,
                'country' => strtolower($admin->user?->country ?? ''),
            ]
        ]);
    }

    public function agentList()
    {
        $admin = AdminAccount::where('user_id', auth()->id())->first();

        if (!$admin) {

            return response()->json([
                'status' => false,
                'message' => 'Admin not found'
            ], 404);
        }

        $agencies = Agency::with(
            'user:id,uid,name,image'
        )
            ->where('admin_id', $admin->id)
            ->latest()
            ->get()
            ->map(function ($item) {

                $hostCount = Host::where('agency_id', $item->id)
                    ->where('status', 1)->count();

                return [
                    'id' => $item->id,
                    'user_id' => $item->user_id,
                    'uid' => $item->user->uid ?? null,
                    'name' => $item->user->name ?? null,
                    'image' => !empty($item->user?->image) ? Helper::showImage($item->user->image, true) : null,
                    'role' => 'Agent',
                    'host_count' => $hostCount,
                    'whatsapp_number' => $item->whatsapp_number,
                    'status' => (bool) $item->status
                ];
            });

        return response()->json([
            'status' => true,
            'message' => 'Agent list fetched successfully',
            'data' => $agencies
        ]);
    }

    public function bdList()
    {
        $admin = AdminAccount::where('user_id', auth()->id())->first();

        if (!$admin) {
            return response()->json([
                'status' => false,
                'message' => 'Admin not found'
            ], 404);
        }

        $data = BdUser::with(['user:id,uid,name,image'])
            ->where('admin_id', $admin->id)
            // ->latest()
            ->get()
            ->map(function ($item) {

                $agentCount = Agency::where('bd_user_id', $item->id)
                    ->where('invite_status', 'accept')
                    ->where('status', 1)
                    ->count();

                return [
                    'id' => $item->id,
                    'user_id' => $item->user_id,
                    'uid' => $item->user?->uid,
                    'name' => $item->user?->name,
                    'image' => !empty($item->user?->image) ? Helper::showImage($item->user->image, true) : null,
                    'country_id' => $item->country_id,
                    'whatsapp_number' => $item->whatsapp_number,
                    'briefing' => $item->briefing,
                    'agent_count' => $agentCount,
                    'is_admin_bound' => (bool) $item->is_admin_bound,
                    'status' => (bool) $item->status,
                    'created_at' => $item->created_at
                ];
            });

        return response()->json([
            'status' => true,
            'message' => 'BD list fetched successfully',
            'data' => $data
        ]);
    }

    public function bdAgentListById($bdId)
    {
        $admin = AdminAccount::where('user_id', auth()->id())->first();

        if (!$admin) {

            return response()->json([
                'status' => false,
                'message' => 'Admin not found'
            ], 404);
        }

        $bd = BdUser::with(['user:id,uid,name,image'])
            ->where('id', $bdId)
            ->where('admin_id', $admin->id)
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
            ->where('bd_user_id', $bd->id)
            ->where('invite_status', 'accept')
            ->where('status', 1)
            ->latest()
            ->get()
            ->map(function ($item) {

                $flag = null;

                if ($item->user?->countryData?->iso) {

                    $flag = 'https://flagcdn.com/w40/' .
                        strtolower($item->user->countryData->iso) . '.png';
                }

                $hostCount = Host::where('agency_id', $item->id)
                    ->where('status', 1)
                    ->count();

                return [
                    'id' => $item->id,
                    'user_id' => $item->user_id,
                    'uid' => $item->user?->uid,
                    'name' => $item->user?->name,
                    'image' => !empty($item->user?->image) ? Helper::showImage($item->user->image, true) : null,
                    'host_count' => $hostCount,
                    'flag' => $flag,
                    'country' => strtolower($item->user?->country ?? ''),
                    'role_badges' => Helper::getUserRoleBadges($item->user_id),
                    'whatsapp_number' => $item->whatsapp_number,
                    'briefing' =>  $item->briefing,
                    'invite_status' => $item->invite_status,
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

    public function agentHostList($agencyId)
    {
        $agency = Agency::with(['user:id,uid,name,image'])
            ->where('id', $agencyId)
            ->first();

        if (!$agency) {

            return response()->json([
                'status' => false,
                'message' => 'Agency not found'
            ], 404);
        }

        $hosts = Host::with(['user:id,uid,name,image,country',])
            ->where('agency_id', $agency->id)
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
                    'role_badges' => Helper::getUserRoleBadges($item->user_id),
                    'status' => (bool) $item->status,
                    'created_at' => $item->created_at,
                ];
            });

        return response()->json([
            'status' => true,
            'message' => 'Agent host list fetched successfully',
            'data' => $hosts
        ]);
    }

    public function sendAgentInvite(Request $request)
    {
        $validator = Validator::make($request->all(), [

            'user_id' =>
            'required|exists:app_users,id'
        ]);

        if ($validator->fails()) {

            return response()->json([

                'status' => false,

                'message' =>
                $validator->errors()

            ], 422);
        }

        /*
    |--------------------------------------------------------------------------
    | Auth Admin
    |--------------------------------------------------------------------------
    */

        $admin = AdminAccount::where(
            'user_id',
            auth()->id()
        )->first();

        if (!$admin) {

            return response()->json([

                'status' => false,

                'message' => 'Admin not found'

            ], 404);
        }

        /*
    |--------------------------------------------------------------------------
    | Find User
    |--------------------------------------------------------------------------
    */

        $user = AppUser::where(
            'id',
            $request->user_id
        )->first();

        if (!$user) {

            return response()->json([

                'status' => false,

                'message' => 'User not found'

            ], 404);
        }

        /*
    |--------------------------------------------------------------------------
    | Self Invite Block
    |--------------------------------------------------------------------------
    */

        if ($user->id == auth()->id()) {

            return response()->json([

                'status' => false,

                'message' =>
                'You cannot invite yourself'

            ], 422);
        }

        /*
    |--------------------------------------------------------------------------
    | Existing Agency Check
    |--------------------------------------------------------------------------
    */

        $existingAgency = Agency::where(
            'user_id',
            $user->id
        )->first();

        if ($existingAgency) {

            return response()->json([

                'status' => false,

                'message' =>
                'User already exists as Agency'

            ], 422);
        }

        /*
    |--------------------------------------------------------------------------
    | Host Restriction
    |--------------------------------------------------------------------------
    | Host under another agency
    | cannot directly become agency
    |--------------------------------------------------------------------------
    */

        $existingHost = Host::where(
            'user_id',
            $user->id
        )->first();

        if (
            $existingHost
            &&
            !empty($existingHost->agency_id)
        ) {

            return response()->json([

                'status' => false,

                'message' =>
                'Host role must be removed before assigning Agency'

            ], 422);
        }

        /*
    |--------------------------------------------------------------------------
    | Existing Pending Invite Check
    |--------------------------------------------------------------------------
    */

        $pendingInvite = Agency::where(
            'user_id',
            $user->id
        )

            ->where(
                'invite_status',
                'pending'
            )

            ->first();

        if ($pendingInvite) {

            return response()->json([

                'status' => false,

                'message' =>
                'Agency invite already pending'

            ], 422);
        }

        /*
    |--------------------------------------------------------------------------
    | Create Agency Invite
    |--------------------------------------------------------------------------
    */

        $agency = Agency::create([

            'user_id' =>
            $user->id,

            'admin_id' =>
            $admin->id,

            /*
        |--------------------------------------------------------------------------
        | Direct Admin Invite
        |--------------------------------------------------------------------------
        */

            'is_bd_bound' => 0,

            'bd_user_id' => null,

            'country_id' =>
            $admin->country_id,

            'whatsapp_number' =>
            $user->phone,

            'briefing' => null,

            'invite_status' => 'pending',

            'status' => 1
        ]);

        /*
    |--------------------------------------------------------------------------
    | Notification
    |--------------------------------------------------------------------------
    */

        Notification::create([

            'user_id' =>
            $user->id,

            'sender_id' =>
            auth()->id(),

            'receiver_id' =>
            $user->id,

            'type' => 'agency',

            'title' =>
            'Agency Invitation',

            'message' =>
            auth()->user()->name .
                ' invited you for agency',

            'reference_id' =>
            $agency->id,

            'country' =>
            strtolower(
                auth()->user()->country
            ),

            'is_read' => 0,
        ]);

        return response()->json([

            'status' => true,

            'message' =>
            'Agent invite sent successfully',

            'data' => [

                'id' =>
                $agency->id,

                'user_id' =>
                $user->id,

                'uid' =>
                $user->uid,

                'name' =>
                $user->name,

                'image' =>
                !empty($user->image)
                    ? Helper::showImage(
                        $user->image,
                        true
                    )
                    : null,

                'invite_status' =>
                $agency->invite_status
            ]
        ]);
    }

    public function sendBdInvite(Request $request)
    {
        $validator = Validator::make($request->all(), [

            'user_id' =>
            'required|exists:app_users,id'
        ]);

        if ($validator->fails()) {

            return response()->json([

                'status' => false,

                'message' =>
                $validator->errors()

            ], 422);
        }

        /*
    |--------------------------------------------------------------------------
    | Auth Admin
    |--------------------------------------------------------------------------
    */

        $admin = AdminAccount::where(
            'user_id',
            auth()->id()
        )->first();

        if (!$admin) {

            return response()->json([

                'status' => false,

                'message' => 'Admin not found'

            ], 404);
        }

        /*
    |--------------------------------------------------------------------------
    | Find User
    |--------------------------------------------------------------------------
    */

        $user = AppUser::where(
            'id',
            $request->user_id
        )->first();

        if (!$user) {

            return response()->json([

                'status' => false,

                'message' => 'User not found'

            ], 404);
        }

        /*
    |--------------------------------------------------------------------------
    | Self Invite Block
    |--------------------------------------------------------------------------
    */

        if ($user->id == auth()->id()) {

            return response()->json([

                'status' => false,

                'message' =>
                'You cannot invite yourself'

            ], 422);
        }

        /*
    |--------------------------------------------------------------------------
    | Existing BD Check
    |--------------------------------------------------------------------------
    */

        $existingBd = BdUser::where(
            'user_id',
            $user->id
        )->first();

        /*
    |--------------------------------------------------------------------------
    | If Already BD Under Another Admin
    |--------------------------------------------------------------------------
    */

        if (
            $existingBd
            &&
            (int) $existingBd->is_admin_bound === 1
            &&
            !empty($existingBd->admin_id)
        ) {

            return response()->json([

                'status' => false,

                'message' =>
                'This BD is already under another Admin'

            ], 422);
        }

        /*
    |--------------------------------------------------------------------------
    | If Already Independent Admin/BD
    |--------------------------------------------------------------------------
    */

        if (
            $existingBd
            &&
            (
                (int) $existingBd->is_admin_bound === 0
                ||
                empty($existingBd->admin_id)
            )
        ) {

            return response()->json([

                'status' => false,

                'message' =>
                'User already exists as independent BD/Admin'

            ], 422);
        }

        /*
    |--------------------------------------------------------------------------
    | Check Existing Pending Invite
    |--------------------------------------------------------------------------
    */

        $pendingInvite = BdUser::where(
            'user_id',
            $user->id
        )

            ->where(
                'invite_status',
                'pending'
            )

            ->first();

        if ($pendingInvite) {

            return response()->json([

                'status' => false,

                'message' =>
                'BD invite already pending'

            ], 422);
        }

        /*
    |--------------------------------------------------------------------------
    | Create BD Invite
    |--------------------------------------------------------------------------
    */

        $bd = BdUser::create([

            'user_id' =>
            $user->id,

            /*
        |--------------------------------------------------------------------------
        | This BD belongs under Admin
        |--------------------------------------------------------------------------
        */

            'is_admin_bound' => 1,

            'admin_id' =>
            $admin->id,

            'country_id' =>
            $admin->country_id,

            'whatsapp_number' =>
            $user->phone,

            'briefing' => null,

            'invite_status' => 'pending',

            'status' => 1,
        ]);

        /*
    |--------------------------------------------------------------------------
    | Notification
    |--------------------------------------------------------------------------
    */

        Notification::create([

            'user_id' =>
            $user->id,

            'sender_id' =>
            auth()->id(),

            'receiver_id' =>
            $user->id,

            'type' => 'bd',

            'title' =>
            'BD Invitation',

            'message' =>
            auth()->user()->name .
                ' invited you for BD',

            'reference_id' =>
            $bd->id,

            'country' =>
            strtolower(
                auth()->user()->country
            ),

            'is_read' => 0,
        ]);

        return response()->json([

            'status' => true,

            'message' =>
            'BD invite sent successfully',

            'data' => [

                'id' => $bd->id,

                'user_id' =>
                $user->id,

                'uid' =>
                $user->uid,

                'name' =>
                $user->name,

                'image' =>
                !empty($user->image)
                    ? Helper::showImage(
                        $user->image,
                        true
                    )
                    : null,

                'invite_status' =>
                $bd->invite_status
            ]
        ]);
    }
}
