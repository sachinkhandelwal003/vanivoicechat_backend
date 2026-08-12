<?php

namespace App\Http\Controllers;

use App\Models\Host;
use App\Models\AppUser;
use App\Models\Agency;
use App\Models\Country;
use App\Models\BdUser;
use App\Models\AdminAccount;
use App\Models\HostPolicy;
use App\Helper\Helper;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Contracts\View\View;
use App\Exports\HostWorkExport;
use Maatwebsite\Excel\Facades\Excel;

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

            // Get Active Hosts With Agency
            $hosts = Host::with(['user', 'agency.user'])
                ->where('status', 1)
                ->whereNotNull('agency_id')
                ->get();

            // Group Hosts Agency Wise

            $agencyHosts = $hosts->groupBy('agency_id');

            $workData = collect();

            // Filters

            $filterCountry = $request->country;

            $filterCycle = $request->cycle;

            $filterMonth = $request->month;

            //   Generate Agency Wise Cycles

            foreach ($agencyHosts as $agencyId => $agencyHostList) {

                if (!$agencyId) {
                    continue;
                }

                $agency = $agencyHostList->first()->agency;

                if (!$agency) {
                    continue;
                }

                //    Country Filter

                if (!empty($filterCountry)) {

                    $agencyUser = $agency->user ?? null;

                    if (!$agencyUser) {
                        continue;
                    }

                    // Country can be stored either as ID or name

                    $country = Country::find($filterCountry);
                    $countryId = $filterCountry;
                    $countryName = $country->name ?? null;
                    $userCountry = $agencyUser->country ?? null;

                    $countryMatches = (string) $userCountry === (string) $countryId ||
                        ($countryName && strtolower(trim($userCountry)) === strtolower(trim($countryName)));

                    if (!$countryMatches) {
                        continue;
                    }
                }

                //    Earliest Host Creation Date

                $firstHostDate = $agencyHostList->min('created_at');

                if (!$firstHostDate) {
                    continue;
                }

                $firstDate = Carbon::parse($firstHostDate);

                // Starting Month
                $currentMonth = $firstDate->copy()->startOfMonth();
                $today = now();

                // Month Filter
                if (!empty($filterMonth)) {

                    try {
                        $selectedMonth = Carbon::createFromFormat('Y-m', $filterMonth)->startOfMonth();
                    } catch (\Exception $e) {
                        $selectedMonth = null;
                    }

                    // Invalid Month
                    if (!$selectedMonth) {
                        continue;
                    }

                    // Selected Month Before Agency's First Host

                    if ($selectedMonth->lt($currentMonth)) {
                        continue;
                    }

                    // Selected Month After Current Month

                    if ($selectedMonth->gt($today->copy()->startOfMonth())) {
                        continue;
                    }

                    //  When month is selected, ONLY generate that month.
                    $monthsToProcess = collect([$selectedMonth->copy()]);
                } else {

                    // No Month Filter
                    // Generate all available months

                    $monthsToProcess = collect();

                    while ($currentMonth->lte($today->copy()->startOfMonth())) {
                        $monthsToProcess->push($currentMonth->copy());
                        $currentMonth->addMonth();
                    }
                }

                //    Generate Agency Wise Cycles

                foreach ($monthsToProcess as $currentMonth) {

                    // Cycle 1: 1 - 15

                    $cycle1Start = $currentMonth->copy()->startOfMonth();
                    $cycle1End = $currentMonth->copy()->day(15)->endOfDay();

                    $allowCycle1 = empty($filterCycle) || $filterCycle === '1-15';

                    if ($allowCycle1 && $cycle1Start->lte($today)) {

                        $this->createAgencyCycleWork(
                            $workData,
                            $agency,
                            $agencyHostList,
                            $cycle1Start,
                            $cycle1End,
                            '1-15'
                        );
                    }

                    //  Cycle 2: 16 - End
                    $cycle2Start = $currentMonth->copy()->day(16)->startOfDay();
                    $cycle2End = $currentMonth->copy()->endOfMonth()->endOfDay();

                    $allowCycle2 = empty($filterCycle) || $filterCycle === '16-end';


                    if ($allowCycle2 && $cycle2Start->lte($today)) {

                        $this->createAgencyCycleWork(
                            $workData,
                            $agency,
                            $agencyHostList,
                            $cycle2Start,
                            $cycle2End,
                            '16-' . $currentMonth->daysInMonth
                        );
                    }
                }
            }

            // Latest Cycle First
            $workData = $workData->sortByDesc(function ($row) {
                return $row->cycle_start->timestamp;
            })->values();

            //    DataTable

            return DataTables::of($workData)
                ->addIndexColumn()

                ->addColumn('agency', function ($row) {

                    $agencyUser = $row->agency->user ?? null;

                    if (!$agencyUser) {
                        return '-';
                    }

                    $image = $agencyUser->image
                        ? Helper::showImage($agencyUser->image, true)
                        : asset('assets/img/avatar.png');

                    $uidData = Helper::getDisplayUidData($agencyUser);
                    $uidHtml = '';

                    if (!empty($uidData['uid']) && $uidData['uid'] != $uidData['system_uid']) {

                        $uidHtml = '
                        <small class="text-muted">
                            ' . e($uidData['uid']) . ' / ' . e($uidData['system_uid']) . '
                        </small>';
                    } else {
                        $uidHtml = '
                        <small class="text-muted">
                            ' . e($uidData['system_uid'] ?? $agencyUser->uid) . '
                        </small>';
                    }

                    return '
                    <div class="d-flex align-items-center gap-2">
                        <img src="' . $image . '"
                            width="45"
                            height="45"
                            class="rounded-circle">

                        <div>
                            <div class="fw-bold">
                                ' . e($agencyUser->name) . '
                            </div>
                            ' . $uidHtml . '
                        </div>
                    </div>';
                })

                ->addColumn('country', function ($row) {
                    return e($row->country ?? '-');
                })

                ->addColumn('cycle', function ($row) {
                    return '
                    <span class="fw-semibold">
                        ' . e($row->cycle) . '
                    </span>';
                })

                ->addColumn('gift', function ($row) {

                    return '
                    <div>
                        <div>
                            Received: <strong> ' . number_format($row->received_gift) . ' </strong>
                        </div>

                        <div>
                            Sending: <strong>' . number_format($row->sending_gift) . ' </strong>
                        </div>

                        <div class="mt-1">
                            <span class="badge bg-light text-secondary">
                                Lv.' . e($row->level) . '
                            </span>
                        </div>

                    </div>
                ';
                })

                ->addColumn('salary', function ($row) {
                    return '
                    <div>
                        <div>
                            Host: <strong> $' . number_format($row->host_salary, 2) . ' </strong>
                        </div>
                        <div>
                            Agency: <strong> $' . number_format($row->agency_salary, 2) . ' </strong>
                        </div>
                    </div>';
                })

                ->addColumn('status', function ($row) {
                    return '
                    <div>
                        <span class="badge bg-warning text-dark">
                            ' . e($row->settlement_status) . '
                        </span>
                        <br>
                        <span class="badge bg-warning text-dark mt-1">
                            ' . e($row->payment_status) . '
                        </span>
                    </div>';
                })

                ->addColumn('time', function ($row) {
                    return '
                    <div>
                        <div>
                            Created:' . Carbon::parse($row->created_at)->format('d M Y, h:i A') . '
                        </div>
                        <div>
                            Updated:' . Carbon::parse($row->updated_at)->format('d M Y, h:i A') . '
                        </div>
                    </div>';
                })

                // ->addColumn('operate', function ($row) {
                //     return '
                //     <div class="dropdown">

                //         <button
                //             class="btn btn-sm btn-light border rounded-pill"
                //             data-bs-toggle="dropdown">
                //             <i class="fas fa-ellipsis-h"></i>
                //         </button>

                //         <div class="dropdown-menu dropdown-menu-end">
                //             <a href="#"
                //                class="dropdown-item">
                //                 <i class="fas fa-eye text-primary me-2"></i>
                //                 View Details
                //             </a>
                //         </div>
                //     </div>';
                // })


                ->rawColumns(['agency', 'cycle', 'gift', 'salary', 'status', 'time', 'operate'])
                ->make(true);
        }

        $countries = Country::orderBy('name')->get(['id', 'name']);

        return view('host.host_work_index', compact('countries'));
    }

    //    Create Agency Cycle Work

    private function createAgencyCycleWork(&$workData, $agency, $hosts, Carbon $cycleStart, Carbon $cycleEnd, string $cycleName)
    {
        // Host must exist before cycle ends

        $cycleHosts = $hosts->filter(function ($host) use ($cycleEnd) {
            $hostCreatedAt = Carbon::parse($host->created_at);
            return $hostCreatedAt->lte($cycleEnd);
        });

        //  No Host
        if ($cycleHosts->isEmpty()) {
            return;
        }

        // Totals

        $totalReceived = 0;
        $totalSending = 0;
        $totalHostSalary = 0;
        $totalAgencySalary = 0;
        $highestLevel = 0;

        //   Calculate Every Host

        foreach ($cycleHosts as $host) {

            $hostCreatedAt = Carbon::parse($host->created_at);

            // Work Start
            // Host creation ke pehle ka gift count nahi hoga.

            $workStart = $hostCreatedAt->gt($cycleStart) ? $hostCreatedAt->copy() : $cycleStart->copy();

            //   Work End
            $workEnd = $cycleEnd->gt(now()) ? now()->copy() : $cycleEnd->copy();

            // Safety
            if ($workStart->gt($workEnd)) {
                continue;
            }

            //  Received Gifts
            $received = DB::table('gift_transactions')
                ->where('receiver_id', $host->user_id)
                ->whereBetween('created_at', [$workStart, $workEnd])
                ->sum('total_value');

            //Sending Gifts

            $sending = DB::table('gift_transactions')
                ->where('sender_id', $host->user_id)
                ->whereBetween('created_at', [$workStart, $workEnd])
                ->sum('total_value');

            // Add Agency Totals
            $totalReceived += $received;
            $totalSending += $sending;

            // Host Policy
            $policy = HostPolicy::where('status', 1)
                ->where('target_value', '<=', $received)
                ->orderByDesc('target_value')
                ->first();

            if ($policy) {
                $level = (int) $policy->level;
                $hostSalary = (float) $policy->host_salary;
                $agencySalary = (float) $policy->agent_commission;

                // Highest Level
                if ($level > $highestLevel) {
                    $highestLevel = $level;
                }

                //   Salary
                $totalHostSalary += $hostSalary;
                $totalAgencySalary += $agencySalary;
            }
        }

        // Create Agency Cycle Row
        $now = now();
        $workData->push((object) [
            'agency' => $agency,
            'country' => optional($agency->user)->country ?? '-',
            'cycle' => $cycleStart->format('M') . ' ' . $cycleName . ', ' . $cycleStart->year,
            'cycle_start' => $cycleStart->copy(),
            'cycle_end' => $cycleEnd->copy(),
            'received_gift' => $totalReceived,
            'sending_gift' => $totalSending,
            'level' => $highestLevel,
            'host_salary' => $totalHostSalary,
            'agency_salary' => $totalAgencySalary,
            'settlement_status' => 'UNSETTLED',
            'payment_status' => 'UNPAID',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function exportHostWork(Request $request)
    {
        return Excel::download(
            new HostWorkExport(
                $request->country,
                $request->cycle,
                $request->month
            ),
            'host_work_' . now()->format('Y-m-d_H-i-s') . '.xlsx'
        );
    }
}
