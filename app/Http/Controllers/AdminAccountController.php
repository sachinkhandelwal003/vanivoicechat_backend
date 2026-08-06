<?php

namespace App\Http\Controllers;

use App\Models\AdminAccount;
use App\Models\AppUser;
use App\Models\Country;
use App\Models\BdUser;
use App\Models\PremiumNumber;
use App\Models\StoreUids;
use App\Helper\Helper;
use Carbon\Carbon;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AdminAccountController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {

            // $query = AdminAccount::with(['user', 'country'])->latest();
            $query = AdminAccount::with(['user', 'country'])->withCount(['bdUsers as bd_count', 'agencies as agency_count'])->latest();

            return DataTables::of($query)
                ->addIndexColumn()

                ->addColumn('user', function ($row) {

                    if (!$row->user) {
                        return '-';
                    }

                    $user = $row->user;

                    $image = $user->image
                        ? Helper::showImage($user->image, true)
                        : asset('assets/img/avatar.png');

                    $uidData = Helper::getDisplayUidData($user);

                    $badgeHtml = '';

                    if (!empty($uidData['badge'])) {
                        $badgeHtml = '
                            <img src="' . $uidData['badge'] . '"
                                width="16"
                                height="16"
                                style="vertical-align:middle;margin-right:4px;">
                        ';
                    }

                    if (!empty($uidData['uid']) && $uidData['uid'] != $uidData['system_uid']) {

                        $uidHtml = '
                            <small class="d-flex align-items-center flex-wrap" style="gap:4px;">
                                ' . $badgeHtml . '
                                <span style="color:' . ($uidData['badge_color'] ?? '#000') . ';font-weight:600;">
                                    ' . e($uidData['uid']) . '
                                </span>
                                <span class="text-muted">/</span>
                                <span class="text-muted">' . e($uidData['system_uid']) . '</span>
                            </small>';
                    } else {

                        $uidHtml = '
                            <small class="text-muted">
                                ' . e($uidData['system_uid'] ?? $user->uid) . '
                            </small>';
                    }

                    return '
                        <div class="d-flex align-items-center gap-2 user-profile-trigger"
                            data-user-id="' . $user->id . '"
                            style="cursor:pointer;">

                            <img src="' . $image . '"
                                width="40"
                                height="40"
                                class="rounded-circle">

                            <div>
                                <div class="fw-bold">' . e($user->name) . '</div>
                                ' . $uidHtml . '
                            </div>

                        </div>
                    ';
                })

                // ->addColumn('user', function ($row) {

                //     if (!$row->user) {
                //         return '-';
                //     }

                //     $user = $row->user;

                //     $image = $user->image
                //         ? Helper::showImage($user->image, true)
                //         : asset('assets/img/avatar.png');

                //     $uidData = Helper::getDisplayUidData($user);

                //     $badgeHtml = '';

                //     if (!empty($uidData['badge'])) {

                //         $badgeHtml = '
                //             <img src="' . $uidData['badge'] . '"
                //                 width="16"
                //                 height="16"
                //                 style="margin-right:4px;vertical-align:middle;">
                //         ';
                //     }

                //     $uidColor = !empty($uidData['badge_color'])
                //         ? $uidData['badge_color']
                //         : '#6c757d';

                //     return '
                //         <div class="d-flex align-items-center gap-2 user-profile-trigger"
                //             data-user-id="' . $user->id . '"
                //             style="cursor:pointer;">

                //             <img src="' . $image . '"
                //                 width="40"
                //                 height="40"
                //                 class="rounded-circle">

                //             <div>
                //                 <div class="fw-bold">' . e($user->name) . '</div>

                //                 <small class="text-muted">
                //                     UID:
                //                     ' . $badgeHtml . '
                //                     <span style="color:' . $uidColor . ';font-weight:600;">
                //                         ' . e($uidData['uid']) . '
                //                     </span>
                //                 </small>
                //             </div>

                //         </div>
                //     ';
                // })

                ->addColumn('country', function ($row) {
                    return $row->country->name ?? '-';
                })

                ->addColumn('bd_count', function ($row) {
                    return '
                        <div style="display:inline-flex; align-items:center; gap:6px; padding:6px 12px; border-radius:30px;
                            background:linear-gradient(135deg,#4f46e5,#7c3aed);
                            color:#fff; font-weight:600; box-shadow:0 4px 12px rgba(79,70,229,.25);
                        ">
                            <i class="fas fa-user-tie"></i>
                            ' . $row->bd_count . '
                        </div>
                    ';
                })

                ->addColumn('agency_count', function ($row) {
                    return '
                        <div style="display:inline-flex; align-items:center; gap:6px; padding:6px 12px; border-radius:30px;
                            background:linear-gradient(135deg,#f59e0b,#f97316); color:#fff; font-weight:600; box-shadow:0 4px 12px rgba(245,158,11,.25);
                        ">
                            <i class="fas fa-building"></i>
                            ' . $row->agency_count . '
                        </div>
                    ';
                })

                ->editColumn('status', function ($row) {
                    return $row->status
                        ? '<span class="badge bg-success">Active</span>'
                        : '<span class="badge bg-danger">Inactive</span>';
                })

                ->addColumn('time', function ($row) {
                    return '
                    <div>
                        <div><strong>Created:</strong> ' . Carbon::parse($row->created_at)->format('Y-m-d H:i:s') . '</div>
                        <div><strong>Updated:</strong> ' . Carbon::parse($row->updated_at)->format('Y-m-d H:i:s') . '</div>
                    </div>';
                })

                ->addColumn('action', function ($row) {

                    if (!Helper::userCan(138, 'can_edit') && !Helper::userCan(138, 'can_delete')) {
                        return '-';
                    }

                    $btn = '
                            <div class="dropdown">
                                <button class="btn btn-sm btn-link dropdown-toggle" data-bs-toggle="dropdown">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>

                                <div class="dropdown-menu">';

                    // Edit Permission
                    if (Helper::userCan(138, 'can_edit')) {
                        $btn .= '
                                <a class="dropdown-item"
                                href="' . route('admin.account.form', $row->id) . '">
                                    <i class="fas fa-edit text-primary me-2"></i> Edit
                                </a>';
                    }

                    // Delete Permission
                    if (Helper::userCan(138, 'can_delete')) {
                        $btn .= '
                                <button class="dropdown-item text-danger delete"
                                        data-id="' . $row->id . '">
                                    <i class="fas fa-trash me-2"></i> Delete
                                </button>';
                    }

                    $btn .= '
                            </div>
                        </div>';

                    return $btn;
                })

                ->rawColumns(['user', 'bd_count', 'agency_count', 'status', 'action', 'time'])
                ->make(true);
        }

        return view('admin_account.index');
    }

    public function form($id = null): View|RedirectResponse
    {
        $admin = null;

        if ($id) {
            $admin = AdminAccount::find($id);

            if (!$admin) {
                return redirect()->route('admin.account')->with('error', 'Admin not found');
            }
        }

        $users = AppUser::select('id', 'name', 'uid')->get();
        $countries = Country::select('id', 'name')->get();

        return view('admin_account.form', compact('admin', 'users', 'countries'));
    }


    public function save(Request $request, $id = null)
    {
        $rules = [
            'user_uid' => 'required',
            'country_id' => 'required|exists:countries,id',
            'whatsapp_number' => 'nullable|string|max:20',
            'status' => 'required|in:0,1',
        ];

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

            //    Find Admin Record

            $admin = $id ? AdminAccount::find($id) : new AdminAccount();

            if ($id && !$admin) {
                return redirect()->back()->with(
                    'error',
                    'Admin not found'
                );
            }

            // Find User (System UID / Premium UID / Store UID)
            $user = Helper::findUserByAnyUid($request->user_uid);

            if (!$user) {
                return redirect()->back()->with('error', 'User not found');
            }

            // Check Existing BD

            $bd = BdUser::where('user_id', $user->id)->first();

            // If BD Already Under Another Admin

            if ($bd && (int) $bd->is_admin_bound === 1 && !empty($bd->admin_id)) {

                return redirect()->back()->with(
                    'error',
                    'This BD is already under another Admin'
                );
            }

            //  Save Admin

            $data = [
                'user_id' => $user->id,
                'country_id' => $request->country_id,
                'whatsapp_number' => $request->whatsapp_number,
                'status' => $request->status
            ];

            $admin->fill($data)->save();

            // Create / Update BD Record
            // Admin user also becomes BD

            BdUser::updateOrCreate(
                [
                    'user_id' => $user->id
                ],

                [
                    'is_admin_bound' => 1,
                    'admin_id' => $admin->id,
                    'country_id' => $request->country_id,
                    'whatsapp_number' => $request->whatsapp_number,
                    'status' => $request->status,
                    'invite_status' => 'accept',
                    'is_dashboard_access' => 0,
                ]
            );

            return redirect()
                ->route('admin.account')
                ->with(
                    'success',
                    $id
                        ? 'Admin updated successfully'
                        : 'Admin added successfully'
                );
        });
    }

    public function delete(Request $request): JsonResponse
    {
        return Helper::deleteRecord(new AdminAccount, $request->id);
    }
}
