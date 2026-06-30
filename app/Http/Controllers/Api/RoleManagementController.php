<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BdUser;
use App\Models\Agency;
use App\Models\AdminAccount;
use Illuminate\Http\Request;

class RoleManagementController extends Controller
{
    public function bdListByAdmin(Request $request)
    {
        $userId = auth()->id();

        $admin = AdminAccount::where('user_id', $userId)->first();

        if (!$admin) {
            return response()->json([
                'status' => false,
                'message' => 'Admin not found'
            ], 404);
        }

        $bds = BdUser::with(['user', 'country'])
            ->where('is_admin_bound', 1)
            ->where('admin_id', $admin->id)
            ->where('status', 1)
            ->latest()
            ->get();

        $data = $bds->map(function ($bd) {
            return [
                'id' => $bd->id,
                'user_id' => $bd->user_id,
                'name' => optional($bd->user)->name,
                'email' => optional($bd->user)->email,
                'country' => optional($bd->country)->name,
                'whatsapp_number' => $bd->whatsapp_number,
                'briefing' => $bd->briefing,
                'status' => $bd->status,
                'created_at' => $bd->created_at,
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'BD list fetched successfully',
            'data' => $data
        ]);
    }

    public function agencyListByAdmin(Request $request)
    {
        $userId = auth()->id();

        $admin = AdminAccount::where('user_id', $userId)->first();

        if (!$admin) {
            return response()->json([
                'status' => false,
                'message' => 'Admin not found'
            ], 404);
        }

        // Get agencies under this admin
        $agencies = Agency::with(['user', 'country'])
            ->where('admin_id', $admin->id)
            ->where('status', 1)
            ->latest()
            ->get();

        // Format response
        $data = $agencies->map(function ($agency) {
            return [
                'id' => $agency->id,
                'user_id' => $agency->user_id,
                'name' => optional($agency->user)->name,
                'email' => optional($agency->user)->email,
                'country' => optional($agency->country)->name,
                'whatsapp_number' => $agency->whatsapp_number,
                'briefing' => $agency->briefing,
                'status' => $agency->status,
                'created_at' => $agency->created_at,
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'Admin agencies fetched successfully',
            'data' => $data
        ]);
    }



    public function inviteBd(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:app_users,id',
        ]);

        $admin = AdminAccount::where('user_id', auth()->id())->first();

        if (!$admin) {
            return response()->json([
                'status' => false,
                'message' => 'Admin not found'
            ], 404);
        }

        // Already exist check
        if (BdUser::where('user_id', $request->user_id)->exists()) {
            return response()->json([
                'status' => false,
                'message' => 'BD already exists'
            ]);
        }

        $bd = BdUser::create([
            'user_id' => $request->user_id,
            'is_admin_bound' => 1,
            'admin_id' => $admin->id,
            'country_id' => $admin->country_id ?? 1,
            'whatsapp_number' => null,
            'briefing' => null,
            'status' => 0,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'BD invited successfully',
            'data' => $bd
        ]);
    }

    public function inviteAgency(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:app_users,id',
        ]);

        $admin = AdminAccount::where('user_id', auth()->id())->first();

        if (!$admin) {
            return response()->json([
                'status' => false,
                'message' => 'Admin not found'
            ], 404);
        }

        if (Agency::where('user_id', $request->user_id)->exists()) {
            return response()->json([
                'status' => false,
                'message' => 'Agency already exists'
            ]);
        }

        $agency = Agency::create([
            'user_id' => $request->user_id,
            'admin_id' => $admin->id,
            'country_id' => $admin->country_id ?? 1,
            'is_bd_bound' => 0, 
            'bd_user_id' => null,
            'whatsapp_number' => null,
            'briefing' => null,
            'status' => 0,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Agency invited successfully',
            'data' => $agency
        ]);
    }

    public function updateInviteStatus(Request $request)
    {
        $request->validate([
            'type' => 'required|in:bd,agency',
            'status' => 'required|in:1,0',
        ]);

        $userId = auth()->id();

        if ($request->type == 'bd') {
            $record = BdUser::where('user_id', $userId)
                ->where('status', 0)
                ->latest()
                ->first();
        } else {
            $record = Agency::where('user_id', $userId)
                ->where('status', 0)
                ->latest()
                ->first();
        }

        if (!$record) {
            return response()->json([
                'status' => false,
                'message' => 'No pending invite found'
            ], 404);
        }

        if ($request->status == 1) {
            $record->update(['status' => 1]);

            return response()->json([
                'status' => true,
                'message' => ucfirst($request->type) . ' accepted successfully',
                'data' => $record
            ]);
        }

        $record->delete();

        return response()->json([
            'status' => true,
            'message' => ucfirst($request->type) . ' rejected successfully'
        ]);
    }
}
