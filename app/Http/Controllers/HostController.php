<?php

namespace App\Http\Controllers;

use App\Models\Host;
use App\Models\AppUser;
use App\Models\Agency;
use App\Models\Country;
use App\Models\BdUser;
use App\Models\AdminAccount;
use App\Helper\Helper;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Contracts\View\View;

class HostController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {

            $query = Host::with(['user', 'agency.user', 'country'])->latest();

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

                ->addColumn('agency', function ($row) {

                    if (!$row->agency || !$row->agency->user) {
                        return '-';
                    }

                    $user = $row->agency->user;

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


                ->addColumn('country', function ($row) {
                    return '<span class="badge bg-light text-dark border">' . $row->country->name . '</span>';
                })

                ->editColumn('status', function ($row) {
                    return $row->status
                        ? '<span class="badge bg-success">Approved</span>'
                        : '<span class="badge bg-danger">Pending</span>';
                })

                ->addColumn('created_at', function ($row) {
                    return '
                    <div>
                        <div><strong>Created:</strong> ' . Carbon::parse($row->created_at)->format('Y-m-d H:i:s') . '</div>
                        <div><strong>Updated:</strong> ' . Carbon::parse($row->updated_at)->format('Y-m-d H:i:s') . '</div>
                    </div>';
                })

                ->addColumn('action', function ($row) {

                    if (!Helper::userCan(141, 'can_edit') && !Helper::userCan(141, 'can_delete')) {
                        return '-';
                    }

                    $btn = '
                            <div class="dropdown">
                                <button class="btn btn-sm btn-link dropdown-toggle" data-bs-toggle="dropdown">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>

                                <div class="dropdown-menu">';

                    // Edit Permission (Edit + Transfer Host)
                    if (Helper::userCan(141, 'can_edit')) {

                        $btn .= '
                                <a class="dropdown-item"
                                href="' . route('host.form', $row->id) . '">
                                    <i class="fas fa-edit text-primary me-2"></i> Edit
                                </a>';

                        $btn .= '
                                <a class="dropdown-item"
                                href="' . route('host.transfer.form', $row->id) . '">
                                    <i class="fas fa-exchange-alt text-warning me-2"></i> Transfer Host
                                </a>';
                    }

                    // Delete Permission
                    if (Helper::userCan(141, 'can_delete')) {

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

                ->rawColumns(['user', 'agency', 'country', 'created_at', 'status', 'action'])
                ->make(true);
        }

        return view('host.index');
    }

    public function form($id = null)
    {
        $host = $id ? Host::find($id) : null;

        if ($id && !$host) {
            return redirect()->route('host')->with('error', 'Host not found');
        }

        $countries = Country::all();

        return view('host.form', compact('host', 'countries'));
    }

    public function save(Request $request, $id = null)
    {
        $rules = [
            'user_uid' => 'required',
            'country_id' => 'required|exists:countries,id',
            'status' =>
            'required|in:0,1',
        ];

        $validator = Validator::make(
            $request->all(),
            $rules
        );

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        return DB::transaction(function () use ($request, $id) {

            //   Find Host
            $host = $id ? Host::find($id) : new Host();

            // Find User (System UID / Premium UID / Store UID)
            $user = Helper::findUserByAnyUid($request->user_uid);

            if (!$user) {
                return back()->with('error', 'User not found');
            }

            $userId = $user->id;

            //  Existing Host Check
            $existsInHost = Host::where('user_id', $userId)
                ->when($id, fn($q) => $q->where('id', '!=', $id))
                ->exists();

            if ($existsInHost) {
                return back()->with('error', 'User already exists as Host');
            }

            //   Agency Check
            // Agency already includes Host permissions

            $existsInAgency = Agency::where('user_id', $userId)->exists();

            if ($existsInAgency) {
                return back()->with(
                    'error',
                    'User already has Agency role. Agency users cannot be assigned Host role.'
                );
            }

            // Agency UID

            $agency = null;

            if ($request->filled('agency_uid')) {

                $agencyUser = Helper::findUserByAnyUid($request->agency_uid);

                if (!$agencyUser) {
                    return back()->with('error', 'Agency user not found');
                }

                $agency = Agency::where('user_id', $agencyUser->id)->first();

                if (!$agency) {
                    return back()->with('error', 'Agency not found');
                }
            }

            //  Save Host

            $host->fill([
                'user_id' => $user->id,
                'agency_id' => $agency->id ?? null,
                'country_id' => $request->country_id,
                'status' => $request->status,
                'invite_status' => 'accept',
            ])->save();

            return redirect()->route('host')
                ->with(
                    'success',
                    $id
                        ? 'Host updated successfully'
                        : 'Host added successfully'
                );
        });
    }

    public function delete(Request $request)
    {
        return Helper::deleteRecord(new Host, $request->id);
    }



    public function transferForm($id)
    {
        $host = Host::with(['user', 'agency'])->findOrFail($id);

        return view('host.transfer', compact('host'));
    }

    public function transferSave(Request $request, $id)
    {
        $request->validate([
            'agency_uid' => 'required'
        ]);

        $host = Host::findOrFail($id);

        //   Find Agency User (System UID / Premium UID / Store UID)

        $agencyUser = Helper::findUserByAnyUid($request->agency_uid);

        if (!$agencyUser) {
            return back()->with('error', 'Agency user not found');
        }

        $agency = Agency::where('user_id', $agencyUser->id)->first();

        if (!$agency) {
            return back()->with('error', 'Agency not found');
        }

        if ($agency->country_id != $host->country_id) {
            return back()->with('error', 'Agency country must match host country');
        }

        $host->agency_id = $agency->id;
        $host->save();

        return redirect()->route('host')->with('success', 'Host transferred successfully');
    }


    public function hostWorkindex(Request $request)
    {
        if ($request->ajax()) {

            $hosts = Host::with(['user', 'agency'])
                ->get()
                ->map(function ($host) {

                    $host->gift_value = \DB::table('gift_transactions')
                        ->where('receiver_id', $host->user_id)
                        ->where('created_at', '>=', $host->created_at)
                        ->sum('total_value');

                    return $host;
                });

            return DataTables::of($hosts)

                ->addIndexColumn()

                ->addColumn('host', function ($row) {

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

                ->addColumn('agency', function ($row) {
                    return $row->agency->name ?? '-';
                })

                ->addColumn('gift_value', function ($row) {
                    return number_format($row->gift_value);
                })

                ->addColumn('host_since', function ($row) {
                    return $row->created_at->format('d M Y h:i A');
                })

                ->rawColumns(['host'])

                ->make(true);
        }

        return view('host.host_work_index');
    }
}
