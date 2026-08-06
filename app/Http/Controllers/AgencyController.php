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
use App\Models\HostPolicy;
use App\Models\GiftTransaction;
use App\Models\AgencySalarySettlement;

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
            $query = Agency::with(['user', 'bdUser', 'admin.user', 'country'])->withCount('hosts')->latest();

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
                ->addColumn('admin_user', function ($row) {

                    if (!$row->admin || !$row->admin->user) {
                        return '-';
                    }

                    $user = $row->admin->user;

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

                ->addColumn('bd_user', function ($row) {

                    if (!$row->bdUser || !$row->bdUser->user) {
                        return '-';
                    }

                    $user = $row->bdUser->user;

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

                    if (!Helper::userCan(140, 'can_edit') && !Helper::userCan(140, 'can_delete')) {
                        return '-';
                    }

                    $btn = '
                            <div class="dropdown">
                                <button class="btn btn-sm btn-link dropdown-toggle" data-bs-toggle="dropdown">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>

                                <div class="dropdown-menu">';

                    // Edit Permission (Transfer + Edit)
                    if (Helper::userCan(140, 'can_edit')) {

                        $btn .= '
                                <a href="' . route('agency.transfer', $row->id) . '" class="dropdown-item">
                                    <i class="fas fa-random text-warning me-2"></i> Transfer
                                </a>';

                        $btn .= '
                                <a class="dropdown-item"
                                href="' . route('agency.form', $row->id) . '">
                                    <i class="fas fa-edit text-primary me-2"></i> Edit
                                </a>';
                    }

                    // Delete Permission
                    if (Helper::userCan(140, 'can_delete')) {

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

                ->rawColumns(['user', 'admin_user', 'bd_user', 'host_count', 'country', 'created_at', 'status', 'action'])
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

    public function transfer($id)
    {
        $agency = Agency::with([
            'user',
            'bdUser.user',
            'admin.user',
            'country'
        ])->find($id);

        if (!$agency) {
            return redirect()
                ->route('agency')
                ->withError('Agency not found.');
        }

        return view('agency.transfer', compact('agency'));
    }

    public function transferStore(Request $request)
    {
        $request->validate([
            'agency_id' => 'required|exists:agencies,id',
            'type'      => 'required|in:admin,bd',
            'uid'       => 'required'
        ]);

        DB::beginTransaction();

        try {

            $agency = Agency::findOrFail($request->agency_id);

            $user = AppUser::where('uid', $request->uid)->first();

            if (!$user) {

                return back()->withInput()->withError('Invalid UID.');
            }

            if ($request->type == 'admin') {

                $admin = AdminAccount::where('user_id', $user->id)
                    ->where('status', 1)
                    ->first();

                if (!$admin) {
                    return back()->withInput()->withError('Admin not found.');
                }

                if ($admin->country_id != $agency->country_id) {
                    return back()->withInput()->withError('Admin country does not match agency country.');
                }

                if (
                    $agency->admin_id == $admin->id &&
                    $agency->is_bd_bound == 0
                ) {
                    return back()->withInput()->withError('Agency is already assigned to this Admin.');
                }

                $agency->update([

                    'admin_id'    => $admin->id,
                    'bd_user_id'  => null,
                    'is_bd_bound' => 0,

                ]);
            } else {

                $bd = BdUser::where('user_id', $user->id)
                    ->where('status', 1)
                    ->first();

                if (!$bd) {
                    return back()->withInput()->withError('BD not found.');
                }

                if ($bd->country_id != $agency->country_id) {
                    return back()->withInput()->withError('BD country does not match agency country.');
                }

                if (
                    $agency->bd_user_id == $bd->id &&
                    $agency->is_bd_bound == 1
                ) {
                    return back()->withInput()->withError('Agency is already assigned to this BD.');
                }

                $agency->update([

                    'admin_id'    => $bd->admin_id,
                    'bd_user_id'  => $bd->id,
                    'is_bd_bound' => 1,

                ]);
            }

            DB::commit();

            return redirect()
                ->route('agency')
                ->withSuccess('Agency transferred successfully.');
        } catch (\Exception $e) {

            DB::rollBack();

            return back()
                ->withInput()
                ->withError($e->getMessage());
        }
    }

    public function agencyList(Request $request)
    {
        if ($request->ajax()) {

            $query = Agency::with(['user', 'bdUser'])
                ->latest();

            return DataTables::of($query)

                ->addIndexColumn()

                ->addColumn('agency', function ($row) {

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

                ->addColumn('host_count', function ($row) {
                    return $row->hosts()->count();
                })

                // ->addColumn('bd', function ($row) {

                //     return $row->bdUser?->user?->name ?? '-';
                // })

                ->addColumn('country', function ($row) {

                    return $row->country->name ?? '-';
                })

                ->addColumn('team_work', function ($row) {

                    if (!Helper::userCan(165, 'can_view')) {
                        return '-';
                    }

                    return '
                            <a href="' . route('agency-team-work.show', $row->id) . '"
                                class="btn btn-sm btn-primary">
                                <i class="fas fa-users me-1"></i> Team Work
                            </a>
                        ';
                })

                ->rawColumns(['agency', 'host_count', 'country', 'team_work'])

                ->make(true);
        }

        return view('agency.team-work.index');
    }

    public function teamWork($agencyId)
    {
        $agency = Agency::with(['user', 'country'])->find($agencyId);

        if (!$agency) {
            return redirect()->route('agency-team-work')
                ->withError('Agency not found.');
        }

        $hosts = Host::where('agency_id', $agency->id)
            ->where('status', 1)
            ->orderBy('created_at')
            ->get();

        if ($hosts->isEmpty()) {

            return view('agency.team-work.show', [
                'agency' => $agency,
                'cycles' => []
            ]);
        }

        $today = now();

        // Agency join date se ya first host join date se cycle start kar sakte hain
        $startMonth = Carbon::parse(
            $hosts->min('created_at')
        )->startOfMonth();

        $cycles = [];

        while ($startMonth <= $today) {

            $month = $startMonth->format('Y-m');

            // Cycle 1
            $cycle1Start = $startMonth->copy()->startOfMonth()->startOfDay();
            $cycle1End   = $startMonth->copy()->day(15)->endOfDay();

            $cycles[] = $this->buildAgencyTeamWorkCycle(
                $agency,
                $month,
                '01-15',
                $cycle1Start,
                $cycle1End,
                $today
            );

            // Cycle 2
            $cycle2Start = $startMonth->copy()->day(16)->startOfDay();
            $cycle2End   = $startMonth->copy()->endOfMonth()->endOfDay();

            $cycles[] = $this->buildAgencyTeamWorkCycle(
                $agency,
                $month,
                '16-End',
                $cycle2Start,
                $cycle2End,
                $today
            );

            $startMonth->addMonth();
        }

        $cycles = collect($cycles)
            ->sortByDesc('sort_date')
            ->values();

        return view('agency.team-work.show', compact(
            'agency',
            'cycles'
        ));
    }

    private function buildAgencyTeamWorkCycle($agency, $month, $cycleName, $startDate, $endDate, $today)
    {

        $hosts = Host::with('country')
            ->where('agency_id', $agency->id)
            ->where('status', 1)
            ->get();

        $teamPoints = 0;
        $hostSalary = 0;
        $agencyCommission = 0;
        $hostCount = 0;
        $highestLevel = 0;

        foreach ($hosts as $host) {


            $hostCreatedAt = Carbon::parse($host->created_at);

            $fromDate = max($startDate, $hostCreatedAt);
            $toDate   = min($endDate, $today);

            if ($fromDate > $toDate) {
                continue;
            }


            $giftTotal = GiftTransaction::where(
                'receiver_id',
                $host->user_id
            )
                ->whereBetween('created_at', [
                    $fromDate,
                    $toDate
                ])
                ->sum('total_value');

            $teamPoints += $giftTotal;

            $policy = HostPolicy::where(
                'country',
                $host->country->nicename
            )
                ->where('status', 1)
                ->where('target_value', '<=', $giftTotal)
                ->orderByDesc('level')
                ->first();

            if ($policy) {

                $hostSalary += $policy->host_salary;
                $agencyCommission += $policy->agent_commission;

                if ($policy->level > $highestLevel) {
                    $highestLevel = $policy->level;
                }
            }

            $hostCount++;
        }

        $settlement = AgencySalarySettlement::where(
            'agency_id',
            $agency->id
        )
            ->where('month', $month)
            ->where('cycle', $cycleName)
            ->first();

        return [

            'month' => $month,

            'cycle' => $cycleName,
            'cycle_no' => $cycleName == '01-15' ? 1 : 2,

            'host_count' => $hostCount,

            'team_points' => $teamPoints,

            'target_level' => $highestLevel,

            'host_salary' => $hostSalary,

            'agency_commission' => $agencyCommission,

            'total_salary' => $hostSalary + $agencyCommission,

            'status' => $settlement
                ? 'Settled'
                : 'Unsettled',

            'sort_date' => $endDate->timestamp,
        ];
    }

    public function teamWorkDetails($agencyId, $month, $cycle)
    {
        $agency = Agency::with('user')->findOrFail($agencyId);

        $monthDate = Carbon::parse($month . '-01');

        if ($cycle == 1) {

            $startDate = $monthDate->copy()
                ->startOfMonth()
                ->startOfDay();

            $endDate = $monthDate->copy()
                ->day(15)
                ->endOfDay();
        } else {

            $startDate = $monthDate->copy()
                ->day(16)
                ->startOfDay();

            $endDate = $monthDate->copy()
                ->endOfMonth()
                ->endOfDay();
        }

        $today = now();

        $hosts = Host::with([
            'user',
            'country'
        ])
            ->where('agency_id', $agency->id)
            ->where('status', 1)
            ->get();

        $data = [];

        foreach ($hosts as $host) {

            $hostCreatedAt = Carbon::parse($host->created_at);

            $fromDate = $startDate->copy();

            if ($hostCreatedAt->gt($fromDate)) {
                $fromDate = $hostCreatedAt->copy();
            }

            $toDate = $endDate->copy();

            if ($today->lt($toDate)) {
                $toDate = $today->copy();
            }

            if ($fromDate->gt($toDate)) {
                continue;
            }

            if ($fromDate > $toDate) {
                continue;
            }

            $giftTotal = GiftTransaction::where(
                'receiver_id',
                $host->user_id
            )
                ->whereBetween('created_at', [
                    $fromDate,
                    $toDate
                ])
                ->sum('total_value');

            $policy = HostPolicy::where(
                'country',
                $host->country->nicename
            )
                ->where('status', 1)
                ->where('target_value', '<=', $giftTotal)
                ->orderByDesc('level')
                ->first();

            $data[] = [

                'user' => $host->user,

                'country' => $host->country,

                'target' => $giftTotal,

                'level' => $policy->level ?? 0,

                'host_salary' => $policy->host_salary ?? 0,

                'agency_commission' => $policy->agent_commission ?? 0,

                'total_salary' => ($policy->host_salary ?? 0) + ($policy->agent_commission ?? 0),

            ];
        }

        return view(
            'agency.team-work.details',
            compact(
                'agency',
                'data',
                'month',
                'cycle'
            )
        );
    }
}
