<?php

namespace App\Http\Controllers;

use App\Models\BdUser;
use App\Models\AppUser;
use App\Models\Country;
use App\Models\Host;
use App\Models\Agency;
use App\Models\AdminAccount;
use App\Helper\Helper;
use Carbon\Carbon;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BdUserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {

            $query = BdUser::with(['user', 'admin.user', 'country'])->withCount(['agencies as agency_count'])->latest();

            return DataTables::of($query)
                ->addIndexColumn()

                ->addColumn('user', function ($row) {

                    if (!$row->user) {
                        return '-';
                    }

                    $image = $row->user->image
                        ? Helper::showImage($row->user->image, true)
                        : asset('assets/img/avatar.png');

                    return '
                        <div class="d-flex align-items-center gap-2 user-profile-trigger"
                             data-user-id="' . $row->user->id . '" style="cursor:pointer;">

                            <img src="' . $image . '" width="40" height="40" class="rounded-circle">

                            <div>
                                <div class="fw-bold">' . e($row->user->name) . '</div>
                                <small class="text-muted">UID: ' . e($row->user->uid) . '</small>
                            </div>

                        </div>
                    ';
                })

                ->addColumn('admin', function ($row) {

                    if (!$row->admin || !$row->admin->user) {
                        return '-';
                    }

                    $adminUser = $row->admin->user;

                    $image = $adminUser->image
                        ? Helper::showImage($adminUser->image, true)
                        : asset('assets/img/avatar.png');

                    return '
                        <div class="d-flex align-items-center gap-2 user-profile-trigger"
                             data-user-id="' . $adminUser->id . '" style="cursor:pointer;">

                            <img src="' . $image . '" width="40" height="40" class="rounded-circle">

                            <div>
                                <div class="fw-bold">' . e($adminUser->name) . '</div>
                                <small class="text-muted">UID: ' . e($adminUser->uid) . '</small>
                            </div>

                        </div>
                    ';
                })

                ->addColumn('country', function ($row) {
                    return '
                        <span class="badge bg-light text-dark border">
                            ' . $row->country->name . '
                        </span>
                    ';
                })

                ->addColumn('agency_count', function ($row) {

                    return '
                        <div style="display:inline-flex; align-items:center; gap:8px; padding:7px 14px; border-radius:30px;
                            background:linear-gradient(135deg,#10b981,#059669);
                            color:#fff; font-weight:700;
                            box-shadow:0 4px 12px rgba(16,185,129,.25);
                        ">
                            <i class="fas fa-building"></i>
                            ' . $row->agency_count . '
                        </div>
                    ';
                })

                ->addColumn('time', function ($row) {
                    return '
                    <div>
                        <div><strong>Created:</strong> ' . Carbon::parse($row->created_at)->format('Y-m-d H:i:s') . '</div>
                        <div><strong>Updated:</strong> ' . Carbon::parse($row->updated_at)->format('Y-m-d H:i:s') . '</div>
                    </div>';
                })

                ->editColumn('status', function ($row) {
                    return $row->status
                        ? '<span class="badge bg-success">Active</span>'
                        : '<span class="badge bg-danger">Inactive</span>';
                })

                ->addColumn('action', function ($row) {
                    return '
                    <div class="dropdown">
                        <button class="btn btn-sm btn-link dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="fas fa-ellipsis-h"></i>
                        </button>
                        <div class="dropdown-menu">
                            <a class="dropdown-item" href="' . route('bd-user.form', $row->id) . '">
                                <i class="fas fa-edit text-primary"></i> Edit
                            </a>
                           <button
                                class="dropdown-item text-success convert-admin"
                                data-id="' . $row->id . '">

                                <i class="fas fa-user-shield"></i>
                                Convert To Admin
                            </button>
                            <button class="dropdown-item text-danger delete" data-id="' . $row->id . '">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                    </div>';
                })

                ->rawColumns(['user', 'admin', 'country', 'agency_count', 'time', 'status', 'action'])
                ->make(true);
        }

        return view('bd.index');
    }

    public function form($id = null): View|RedirectResponse
    {
        $bd = $id ? BdUser::find($id) : null;

        if ($id && !$bd) {
            return redirect()->route('bd-user')->with('error', 'BD not found');
        }

        $countries = Country::all();

        return view('bd.form', compact('bd', 'countries'));
    }

    public function save(Request $request, $id = null)
    {
        $rules = [

            'user_uid' => 'required',

            'country_id' =>
            'required|exists:countries,id',

            'whatsapp_number' =>
            'nullable|string|max:20',

            'status' =>
            'required|in:0,1',
        ];

        /*
        |--------------------------------------------------------------------------
        | If BD Bound To Admin
        |--------------------------------------------------------------------------
        */

        if ($request->is_admin_bound) {

            $rules['admin_uid'] =
                'required|exists:app_users,uid';
        }

        $validator = Validator::make(
            $request->all(),
            $rules
        );

        if ($validator->fails()) {

            return redirect()
                ->back()
                ->withErrors($validator)
                ->withInput();
        }

        return DB::transaction(function () use ($request, $id) {

            /*
        |--------------------------------------------------------------------------
        | Find BD Record
        |--------------------------------------------------------------------------
        */

            $bd = $id
                ? BdUser::find($id)
                : new BdUser();

            /*
        |--------------------------------------------------------------------------
        | Find User
        |--------------------------------------------------------------------------
        */

            $user = AppUser::where(
                'uid',
                $request->user_uid
            )->first();

            if (!$user) {

                return redirect()
                    ->back()
                    ->with(
                        'error',
                        'User not found'
                    );
            }

            $userId = $user->id;

            /*
        |--------------------------------------------------------------------------
        | Check Existing BD
        |--------------------------------------------------------------------------
        */

            $existsInBd = BdUser::where(
                'user_id',
                $userId
            )

                ->when(
                    $id,
                    fn($q) =>
                    $q->where(
                        'id',
                        '!=',
                        $id
                    )
                )

                ->exists();

            if ($existsInBd) {

                return redirect()
                    ->back()
                    ->with(
                        'error',
                        'User already exists as BD'
                    );
            }

            /*
        |--------------------------------------------------------------------------
        | Admin Check
        |--------------------------------------------------------------------------
        */

            $admin = null;

            if ($request->is_admin_bound) {

                $adminUser = AppUser::where(
                    'uid',
                    $request->admin_uid
                )->first();

                if (!$adminUser) {

                    return redirect()
                        ->back()
                        ->with(
                            'error',
                            'Admin user not found'
                        );
                }

                $admin = AdminAccount::where(
                    'user_id',
                    $adminUser->id
                )->first();

                /*
            |--------------------------------------------------------------------------
            | Check Admin Exists
            |--------------------------------------------------------------------------
            */

                if (!$admin) {

                    return redirect()
                        ->back()
                        ->with(
                            'error',
                            'Admin not found'
                        );
                }
            }

            /*
        |--------------------------------------------------------------------------
        | If User Already Admin
        |--------------------------------------------------------------------------
        | Admin users should not become child BD
        |--------------------------------------------------------------------------
        */

            $isAdmin = AdminAccount::where(
                'user_id',
                $userId
            )->exists();

            /*
        |--------------------------------------------------------------------------
        | Save BD
        |--------------------------------------------------------------------------
        */

            $bd->fill([

                'user_id' => $user->id,

                /*
            |--------------------------------------------------------------------------
            | Admin Users Always Independent
            |--------------------------------------------------------------------------
            */

                'is_admin_bound' => $isAdmin
                    ? 0
                    : ($request->is_admin_bound ?? 0),

                'admin_id' => $isAdmin
                    ? null
                    : ($admin->id ?? null),

                'country_id' =>
                $request->country_id,

                'whatsapp_number' =>
                $request->whatsapp_number,

                'briefing' =>
                $request->briefing,

                'status' =>
                $request->status,

                'invite_status' =>
                'accept'
            ])->save();

            return redirect()
                ->route('bd-user')
                ->with(
                    'success',
                    $id
                        ? 'BD updated successfully'
                        : 'BD added successfully'
                );
        });
    }

    public function delete(Request $request): JsonResponse
    {
        return Helper::deleteRecord(new BdUser, $request->id);
    }


    public function transferForm($id)
    {
        $bd = BdUser::with('user')->findOrFail($id);

        return view('bd.transfer', compact('bd'));
    }

    public function transferSave(Request $request, $id)
    {
        $request->validate([
            'admin_uid' => 'required|exists:app_users,uid'
        ]);

        $bd = BdUser::findOrFail($id);

        $adminUser = AppUser::where('uid', $request->admin_uid)->first();

        $admin = AdminAccount::where('user_id', $adminUser->id)->first();

        if (!$admin) {
            return back()->with('error', 'Admin not found');
        }

        $bd->admin_id = $admin->id;
        $bd->is_admin_bound = 1;
        $bd->save();

        return redirect()->route('bd-user')->with('success', 'BD transferred successfully');
    }

    public function convertToAdmin($id)
    {
        DB::beginTransaction();

        try {

            $bd = BdUser::findOrFail($id);

            /*
        |--------------------------------------------------------------------------
        | Already Under Another Admin
        |--------------------------------------------------------------------------
        */

            if ($bd->is_admin_bound == 1) {

                return response()->json([

                    'status' => false,

                    'message' => 'This BD is already under another Admin.'
                ], 422);
            }

            /*
        |--------------------------------------------------------------------------
        | Existing Admin Check
        |--------------------------------------------------------------------------
        */

            $alreadyAdmin = AdminAccount::where(
                'user_id',
                $bd->user_id
            )->exists();

            if ($alreadyAdmin) {

                return response()->json([

                    'status' => false,

                    'message' => 'User is already Admin.'
                ], 422);
            }

            /*
        |--------------------------------------------------------------------------
        | Create Admin
        |--------------------------------------------------------------------------
        */

            $admin = AdminAccount::create([

                'user_id' => $bd->user_id,

                'country_id' => $bd->country_id,
                'whatsapp_number' => $bd->whatsapp_number,

                'status' => 1
            ]);

            // migrate data

            Agency::where(
                'bd_user_id',
                $bd->id
            )->update([

                'admin_id' => $admin->id,

                'is_bd_bound' => 0,

                'bd_user_id' => null
            ]);

            /*
        |--------------------------------------------------------------------------
        | Remove BD Role
        |--------------------------------------------------------------------------
        */

            $bd->delete();

            DB::commit();

            return response()->json([

                'status' => true,

                'message' => 'BD converted to Admin successfully.'
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
