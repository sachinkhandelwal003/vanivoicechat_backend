<?php

namespace App\Http\Controllers;

use App\Models\Agency;
use App\Models\AppUser;
use App\Models\Host;
use App\Models\BdUser;
use App\Models\AdminAccount;
use App\Models\HostSalarySettlement;
use App\Models\AgencySalarySettlement;
use App\Helper\Helper;
use Carbon\Carbon;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Artisan;

class SettlementLogController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {

            $query = HostSalarySettlement::with(['host.user', 'agency.user'])->latest();

            $totalRecords = HostSalarySettlement::count();
            $credited = HostSalarySettlement::where('status', 'settled')->count();
            $failed = HostSalarySettlement::where('status', 'failed')->count();
            $hostSalary = HostSalarySettlement::sum('host_salary');
            $agencyCommission = HostSalarySettlement::sum('agency_commission');

            // Filters

            if ($request->filled('cycle')) {
                $query->where('host_salary_settlements.cycle', $request->cycle);
            }

            if ($request->filled('status')) {
                $query->where('host_salary_settlements.status', $request->status);
            }

            if ($request->filled('agency_id')) {
                $query->where('host_salary_settlements.agency_id', $request->agency_id);
            }

            if ($request->filled('host_uid')) {
                $uid = $request->host_uid;
                $query->whereHas('host.user', function ($q) use ($uid) {
                    $q->where('uid', $uid);
                });
            }

            return DataTables::of($query)

                ->addIndexColumn()

                ->addColumn('host', function ($row) {
                    if (!$row->host || !$row->host->user) {
                        return '-';
                    }

                    $user = $row->host->user;
                    $image = $user->image
                        ? Helper::showImage($user->image, true)
                        : asset('assets/img/avatar.png');

                    return '
                    <div class="d-flex align-items-center gap-2">
                        <img src="' . $image . '"
                             width="42"
                             height="42"
                             class="rounded-circle border">

                        <div>
                            <div class="fw-bold">' . e($user->name) . '</div>
                            <small class="text-muted">UID : ' . e($user->uid) . '</small>
                        </div>
                    </div>
                ';
                })
                ->addColumn('agency', function ($row) {
                    if (!$row->agency || !$row->agency->user) {
                        return '-';
                    }
                    $user = $row->agency->user;
                    return '
                    <div>
                        <div class="fw-bold">' . e($user->name) . '</div>
                        <small class="text-muted">Agency ID :' . $row->agency_id . '</small>
                    </div>
                ';
                })
                ->editColumn('cycle', function ($row) {
                    return '
                    <span class="badge bg-primary">' . $row->month . '(Cycle ' . $row->cycle . ')</span>';
                })
                ->editColumn('target_value', function ($row) {
                    return number_format(
                        $row->target_value
                    );
                })
                ->editColumn('level', function ($row) {
                    return '
                    <span class="badge bg-info">Level ' . $row->level . ' </span>';
                })
                ->editColumn('host_salary', function ($row) {

                    $salary = (float) $row->host_salary;
                    if ($salary > 0) {
                        return '
                            <span class="fw-bold text-success">
                                $ ' . number_format($salary, 2) . '
                                <i class="fas fa-check-circle text-success ms-1"></i>
                            </span>
                        ';
                    }
                    return '$ ' . number_format($salary, 2);
                })
                ->editColumn('agency_salary', function ($row) {

                    $commission = (float) $row->agency_commission;

                    if ($commission <= 0) {
                        return '$ ' . number_format($commission, 2);
                    }

                    $settled = AgencySalarySettlement::where('agency_id', $row->agency_id)
                        ->where('month', $row->month)
                        ->where('cycle', $row->cycle)
                        ->exists();

                    return '
                        <span class="fw-bold text-primary">
                            $ ' . number_format($commission, 2) . '
                            ' . ($settled
                        ? '<i class="fas fa-check-circle text-success ms-1"></i>'
                        : '') . '
                        </span>
                    ';
                })

                ->editColumn('status', function ($row) {

                    switch ($row->status) {

                        case 'settled':
                            return '<span class="badge bg-success">Settled</span>';

                        case 'failed':
                            return '<span class="badge bg-danger">Failed</span>';

                        case 'skipped':
                            return '<span class="badge bg-warning text-dark">Skipped</span>';

                        default:
                            return '<span class="badge bg-secondary">'
                                . ucfirst($row->status)
                                . '</span>';
                    }
                })

                ->addColumn('credited_at', function ($row) {

                    return '
                    <div><strong>' . optional($row->settled_at)->format('d M Y') . '</strong>
                        <br>
                        <small class="text-muted">' . optional($row->settled_at)->format('h:i A') . '</small>
                    </div>
                ';
                })
                ->addColumn('action', function ($row) {

                    return '
                    <div class="dropdown">
                        <button
                            class="btn btn-sm btn-link"
                            data-bs-toggle="dropdown">

                            <i class="fas fa-ellipsis-v"></i>

                        </button>
                        <div class="dropdown-menu">
                            <a
                                href="javascript:void(0)"
                                class="dropdown-item view-settlement"
                                data-id="' . $row->id . '">

                                <i class="fas fa-eye text-primary"></i>

                                View Details
                            </a>
                        </div>
                    </div>
                ';
                })
                ->rawColumns([
                    'host',
                    'agency',
                    'agency_salary',
                    'host_salary',
                    'cycle',
                    'level',
                    'status',
                    'credited_at',
                    'action'
                ])
                ->with([
                    'summary' => [
                        'total_records' => $totalRecords,
                        'credited' => $credited,
                        'failed' => $failed,
                        'host_salary' => number_format($hostSalary, 2, '.', ''),
                        'agency_commission' => number_format($agencyCommission, 2, '.', ''),
                    ]
                ])
                ->make(true);
        }

        // $cycles = HostSalarySettlement::select('month', 'cycle')
        //     ->distinct()
        //     ->orderByDesc('month')
        //     ->orderBy('cycle')
        //     ->get();

        $cycles = [];

        $start = \Carbon\Carbon::create(2026, 1, 1);
        $end = now()->copy()->endOfMonth();

        while ($start <= $end) {

            $month = $start->format('Y-m');

            $cycles[] = [
                'value' => $month . '|1',
                'label' => $start->format('F') . ' 1-15, ' . $start->format('Y'),
            ];

            $cycles[] = [
                'value' => $month . '|2',
                'label' => $start->format('F') . ' 16-' . $start->copy()->endOfMonth()->day . ', ' . $start->format('Y'),
            ];

            $start->addMonth();
        }

        return view('settlement.index', compact('cycles'));
    }

    public function runHostSalary(Request $request)
    {
        $request->validate([
            'cycle' => 'required'
        ]);

        [$month, $cycle] = explode('|', $request->cycle);

        DB::beginTransaction();

        try {

            // Host Salary Settlement
            Artisan::call('host:settle-salary', [
                'month' => $month,
                'cycle' => $cycle
            ]);

            // Agency Salary Settlement
            Artisan::call('agency:settle-salary', [
                'month' => $month,
                'cycle' => $cycle
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Host & Agency salary settled successfully.'
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

}
