<?php

namespace App\Http\Controllers;

use App\Helper\Helper;
use App\Models\AppUser;
use App\Models\Country;
use App\Models\CustomerSupport;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class CustomerSupportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {

            $query = CustomerSupport::with('user')->latest();

            return DataTables::of($query)
                ->addIndexColumn()

                ->editColumn('user_id', function ($row) {

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

                ->editColumn('region', function ($row) {
                    return $row->region ?? '-';
                })
                ->addColumn('shift_time', function ($row) {

                    if (!$row->start_time || !$row->end_time) {
                        return '-';
                    }

                    return Carbon::parse($row->start_time)->format('H:i') .
                        ' - ' .
                        Carbon::parse($row->end_time)->format('H:i');
                })

                // ->addColumn('shift_time', function ($row) {

                //     if (!$row->start_time || !$row->end_time) {
                //         return '-';
                //     }

                //     return Carbon::parse($row->start_time)->format('h:i A') .
                //         ' - ' .
                //         Carbon::parse($row->end_time)->format('h:i A');
                // })

                ->editColumn('created_at', function ($row) {
                    return $row->created_at->format('d M Y');
                })

                ->addColumn('action', function ($row) {
                    $btn = '<div class="dropdown">
                        <button class="btn btn-sm btn-link dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="fas fa-ellipsis-h"></i>
                        </button>
                        <div class="dropdown-menu">';

                    if (Helper::userCan(159, 'can_edit')) {
                        $btn .= '<a class="dropdown-item" href="' . route('customer_support.form', $row->id) . '"><i class="fa-solid fa-pen-to-square me-2 text-primary"></i>Edit</a>';
                    }

                    if (Helper::userCan(159, 'can_delete')) {
                        $btn .= '<button class="dropdown-item text-danger delete" data-id="' . $row->id . '"><i class="fa-solid fa-trash me-2"></i>Delete</button>';
                    }

                    $btn .= '</div></div>';

                    return $btn;
                })

                ->rawColumns(['action', 'user_id', 'shift_time'])
                ->make(true);
        }

        return view('customer_support.index');
    }

    public function form($id = null): View
    {
        $data = null;

        if ($id) {
            $data = CustomerSupport::with('user')->findOrFail($id);
        }

        $users = AppUser::latest()->get();
        $country = Country::orderBy('name', 'ASC')->get();

        return view('customer_support.form', compact('data', 'users', 'country'));
    }


    // Store Function for Add + Update
    // public function store(Request $request): JsonResponse
    // {
    //     $request->validate([
    //         'user' => 'required',
    //         'region'  => 'required|string|max:255',
    //     ]);

    //     $user = AppUser::where('uid', $request->user)->first();

    //     if (!$user) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'User not found with this ID'
    //         ]);
    //     }

    //     CustomerSupport::updateOrCreate(
    //         ['id' => $request->id],
    //         [
    //             'user_id' => $user->id,
    //             'region'  => $request->region,
    //         ]
    //     );

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Customer Support saved successfully'
    //     ]);
    // }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'user'       => 'required',
            'region'     => 'required|string|max:255',
            'start_time' => 'required',
            'end_time'   => 'required',
            'status'     => 'required|boolean',
        ]);

        $user = AppUser::where('uid', $request->user)->first();

        if (!$user) {
            return response()->json([
                'status'  => false,
                'message' => 'User not found with this ID.'
            ]);
        }

        // Same user already assigned
        $userExists = CustomerSupport::where('user_id', $user->id)
            ->when($request->id, function ($q) use ($request) {
                $q->where('id', '!=', $request->id);
            })
            ->exists();

        if ($userExists) {
            return response()->json([
                'status'  => false,
                'message' => 'This user is already assigned as Customer Support.'
            ]);
        }

        $startTime = Carbon::parse($request->start_time)->format('H:i:s');
        $endTime   = Carbon::parse($request->end_time)->format('H:i:s');

        // Check overlap
        $supports = CustomerSupport::where('region', $request->region)
            ->where('status', 1)
            ->when($request->id, function ($q) use ($request) {
                $q->where('id', '!=', $request->id);
            })
            ->get();

        foreach ($supports as $support) {

            if ($this->isTimeOverlap(
                $startTime,
                $endTime,
                $support->start_time,
                $support->end_time
            )) {

                return response()->json([
                    'status'  => false,
                    'message' => 'Another customer support is already assigned for this time slot.'
                ]);
            }
        }

        CustomerSupport::updateOrCreate(
            ['id' => $request->id],
            [
                'user_id'    => $user->id,
                'region'     => $request->region,
                'start_time' => $startTime,
                'end_time'   => $endTime,
                'status'     => $request->status,
            ]
        );

        return response()->json([
            'status'  => true,
            'message' => 'Customer Support saved successfully.'
        ]);
    }

    private function isTimeOverlap($start1, $end1, $start2, $end2): bool
    {
        $ranges1 = $this->expandTimeRange($start1, $end1);
        $ranges2 = $this->expandTimeRange($start2, $end2);

        foreach ($ranges1 as $r1) {
            foreach ($ranges2 as $r2) {

                if ($r1['start'] < $r2['end'] && $r1['end'] > $r2['start']) {
                    return true;
                }
            }
        }

        return false;
    }

    private function expandTimeRange($start, $end): array
    {
        $start = strtotime($start);
        $end   = strtotime($end);

        // Normal Shift
        if ($start < $end) {

            return [[
                'start' => $start,
                'end'   => $end,
            ]];
        }

        // Overnight Shift
        return [

            [
                'start' => $start,
                'end'   => strtotime('23:59:59'),
            ],

            [
                'start' => strtotime('00:00:00'),
                'end'   => $end,
            ],

        ];
    }

    public function delete(Request $request): JsonResponse
    {
        CustomerSupport::findOrFail($request->id)->delete();

        return response()->json([
            'status' => true,
            'message' => 'Deleted successfully'
        ]);
    }
}
