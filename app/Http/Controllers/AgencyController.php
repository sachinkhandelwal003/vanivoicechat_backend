<?php

namespace App\Http\Controllers;

use App\Models\Agency;
use App\Models\AppUser;
use App\Models\Country;
use App\Models\Host;
use App\Models\BdUser;
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

class AgencyController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {

            // $query = Agency::with(['user', 'bdUser', 'country'])->latest();
            $query = Agency::with(['user', 'bdUser', 'country'])->withCount('hosts')->latest();

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

                ->addColumn('bd_user', function ($row) {

                    if (!$row->bdUser || !$row->bdUser->user) {
                        return '-';
                    }

                    $bd = $row->bdUser->user;

                    $image = $bd->image
                        ? Helper::showImage($bd->image, true)
                        : asset('assets/img/avatar.png');

                    return '
                        <div class="d-flex align-items-center gap-2 user-profile-trigger"
                             data-user-id="' . $bd->id . '" style="cursor:pointer;">

                            <img src="' . $image . '" width="40" height="40" class="rounded-circle">

                            <div>
                                <div class="fw-bold">' . e($bd->name) . '</div>
                                <small class="text-muted">UID: ' . e($bd->uid) . '</small>
                            </div>

                        </div>
                    ';
                })



    //             ->addColumn('user', function ($row) {

    //                 if (!$row->user) {
    //                     return '-';
    //                 }

    //                 $user = $row->user;

    //                 $image = $user->image
    //                     ? Helper::showImage($user->image, true)
    //                     : asset('assets/img/avatar.png');

    //                 $uidData = Helper::getDisplayUidData($user);

    //                 $badgeHtml = '';

    //                 if (!empty($uidData['badge'])) {
    //                     $badgeHtml = '
    //         <img src="' . $uidData['badge'] . '"
    //              width="16"
    //              height="16"
    //              style="margin-right:4px;vertical-align:middle;">
    //     ';
    //                 }

    //                 $uidColor = $uidData['badge_color'] ?? '#6c757d';

    //                 return '
    //     <div class="d-flex align-items-center gap-2 user-profile-trigger"
    //          data-user-id="' . $user->id . '"
    //          style="cursor:pointer;">

    //         <img src="' . $image . '"
    //              width="40"
    //              height="40"
    //              class="rounded-circle">

    //         <div>
    //             <div class="fw-bold">' . e($user->name) . '</div>
    //             <small class="text-muted">
    //                 UID:
    //                 ' . $badgeHtml . '
    //                 <span style="color:' . $uidColor . ';font-weight:600;">
    //                     ' . e($uidData['uid']) . '
    //                 </span>
    //             </small>
    //         </div>

    //     </div>
    // ';
    //             })

    //             ->addColumn('bd_user', function ($row) {

    //                 if (!$row->bdUser || !$row->bdUser->user) {
    //                     return '-';
    //                 }

    //                 $bd = $row->bdUser->user;

    //                 $image = $bd->image
    //                     ? Helper::showImage($bd->image, true)
    //                     : asset('assets/img/avatar.png');

    //                 $uidData = Helper::getDisplayUidData($bd);

    //                 $badgeHtml = '';

    //                 if (!empty($uidData['badge'])) {
    //                     $badgeHtml = '
    //         <img src="' . $uidData['badge'] . '"
    //              width="16"
    //              height="16"
    //              style="margin-right:4px;vertical-align:middle;">
    //     ';
    //                 }

    //                 $uidColor = $uidData['badge_color'] ?? '#6c757d';

    //                 return '
    //     <div class="d-flex align-items-center gap-2 user-profile-trigger"
    //          data-user-id="' . $bd->id . '"
    //          style="cursor:pointer;">

    //         <img src="' . $image . '"
    //              width="40"
    //              height="40"
    //              class="rounded-circle">

    //         <div>
    //             <div class="fw-bold">' . e($bd->name) . '</div>
    //             <small class="text-muted">
    //                 UID:
    //                 ' . $badgeHtml . '
    //                 <span style="color:' . $uidColor . ';font-weight:600;">
    //                     ' . e($uidData['uid']) . '
    //                 </span>
    //             </small>
    //         </div>

    //     </div>
    // ';
    //             })

                ->addColumn('host_count', function ($row) {

                    return '
                        <div class="d-inline-flex align-items-center px-3 py-2 rounded-pill"
                             style="
                                background:linear-gradient(135deg,#8b5cf6,#6366f1);
                                color:#fff;
                                font-weight:700;
                                box-shadow:0 4px 12px rgba(99,102,241,.25);
                             ">
                            <i class="fas fa-microphone-alt me-2"></i>
                            ' . $row->hosts_count . '
                        </div>
                    ';
                })

                ->addColumn('country', function ($row) {
                    return '<span class="badge bg-light text-dark border">' . $row->country->name . '</span>';
                })

                ->addColumn('created_at', function ($row) {
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
                            <a class="dropdown-item" href="' . route('agency.form', $row->id) . '">
                                <i class="fas fa-edit text-primary"></i> Edit
                            </a>
                            <button class="dropdown-item text-danger delete" data-id="' . $row->id . '">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                    </div>';
                })

                ->rawColumns(['user', 'bd_user', 'host_count', 'country', 'created_at', 'status', 'action'])
                ->make(true);
        }

        return view('agency.index');
    }

    public function form($id = null): View|RedirectResponse
    {
        $agency = $id ? Agency::find($id) : null;

        if ($id && !$agency) {
            return redirect()->route('agency')->with('error', 'Agency not found');
        }

        $countries = Country::all();

        return view('agency.form', compact('agency', 'countries'));
    }


    public function save(Request $request, $id = null)
    {
        $rules = [
            'user_uid' => 'required',
            'admin_uid' => 'nullable',
            'country_id' => 'required|exists:countries,id',
            'whatsapp_number' => 'nullable|string|max:20',
            'status' => 'required|in:0,1',
        ];

        //    If Agency Bound With BD

        if ($request->is_bd_bound) {
            $rules['bd_user_uid'] = 'required';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator)
                ->withInput();
        }

        return DB::transaction(function () use ($request, $id) {

            //  Find Agency
            $agency = $id ? Agency::find($id) : new Agency();

            if ($id && !$agency) {

                return redirect()->back()->with('error', 'Agency not found');
            }

            $oldUserId = $agency->user_id ?? null;

            //   Find User (System UID / Premium UID / Store UID)

            $user = Helper::findUserByAnyUid($request->user_uid);

            if (!$user) {
                return redirect()->back()->with('error', 'User not found');
            }

            $userId = $user->id;

            //   Check Existing Agency

            $existsInAgency = Agency::where('user_id', $userId)
                ->when($id, fn($q) => $q->where('id', '!=', $id))
                ->exists();

            if ($existsInAgency) {
                return redirect()->back()->with('error', 'User already exists as Agency');
            }

            // Host Restriction
            // cannot directly become agency

            $host = Host::where('user_id', $userId)->where('status', 1)->first();

            if ($host) {
                return redirect()->back()
                    ->with('error', 'Host role must be removed before assigning Agency');
            }

            // BD User (System UID / Premium UID / Store UID)

            $bdUserId = null;

            if ($request->filled('bd_user_uid')) {

                $bdAppUser = Helper::findUserByAnyUid($request->bd_user_uid);

                if (!$bdAppUser) {
                    return redirect()->back()->with('error', 'BD user not found');
                }

                $bd = BdUser::where('user_id', $bdAppUser->id)->first();

                if (!$bd) {
                    return redirect()->back()->with('error', 'BD not found');
                }

                $bdUserId = $bd->id;
            }

            // Admin User (System UID / Premium UID / Store UID)

            $adminId = null;

            if ($request->filled('admin_uid')) {

                $adminUser = Helper::findUserByAnyUid($request->admin_uid);

                if (!$adminUser) {
                    return redirect()->back()->with('error', 'Admin user not found');
                }

                $adminAccount = AdminAccount::where('user_id', $adminUser->id)->first();

                if (!$adminAccount) {
                    return redirect()->back()->with('error', 'Admin center not found');
                }

                $adminId = $adminAccount->id;
            }

            //  Save Agency

            $agency->fill([
                'user_id' => $user->id,
                'admin_id' => $adminId,
                'is_bd_bound' => $request->is_bd_bound ?? 0,
                'bd_user_id' => $bdUserId,
                'country_id' => $request->country_id,
                'whatsapp_number' => $request->whatsapp_number,
                'briefing' => $request->briefing,
                'status' => $request->status,
                'invite_status' => 'accept',
            ])->save();

            //  Agency Includes Host Access

            Host::updateOrCreate(
                [
                    'user_id' => $user->id
                ],
                [
                    'agency_id' => $agency->id,
                    'country_id' => $request->country_id,
                    'is_dashboard_access' => 0,
                    'status' => $request->status,
                    'invite_status' => 'accept',
                ]
            );

            // Remove Old Host

            if ($oldUserId  && $oldUserId != $userId) {

                $oldHost = Host::where('user_id', $oldUserId)
                    ->where('agency_id', $agency->id)
                    ->first();

                if ($oldHost) {
                    $oldHost->delete();
                }
            }

            return redirect()
                ->route('agency')
                ->with(
                    'success',
                    $id
                        ? 'Agency updated successfully'
                        : 'Agency added successfully'
                );
        });
    }

    public function delete(Request $request): JsonResponse
    {
        return Helper::deleteRecord(new Agency, $request->id);
    }
}
