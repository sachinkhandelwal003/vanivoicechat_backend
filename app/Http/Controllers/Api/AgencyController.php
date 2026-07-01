<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Helper\Helper;
use App\Models\AppUser;
use App\Models\AdminAccount;
use App\Models\Agency;
use App\Models\HostPolicy;
use App\Models\Host;
use App\Models\Notification;
use App\Models\GiftTransaction;
use App\Models\AgencySalarySettlement;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class AgencyController extends Controller
{

    public function agencyDetails()
    {
        $userId = auth()->id();

        $agency = Agency::with([
            'user:id,uid,name,image,country',
            'user.countryData:id,name,iso'
        ])
            ->where('user_id', $userId)
            ->where('status', 1)
            ->first();

        if (!$agency) {
            return response()->json([
                'status' => false,
                'message' => 'Agency User not found'
            ], 404);
        }

        $flag = null;

        if ($agency->user?->countryData?->iso) {
            $flag = 'https://flagcdn.com/w40/' . strtolower($agency->user->countryData->iso) . '.png';
        }

        return response()->json([
            'status' => true,
            'message' => 'Agency details fetched successfully',
            'data' => [
                'id' => $agency->id,
                'user_id' => $agency->user_id,
                'uid' => $agency->user?->uid,
                'name' => $agency->user?->name,
                'image' => !empty($agency->user?->image) ? Helper::showImage($agency->user->image, true) : null,
                'country' => strtolower($agency->user?->country ?? ''),
            ]
        ]);
    }

    public function searchHostUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'search' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        // Agency Check

        $agency = Agency::where('user_id', auth()->id())
            ->where('status', 1)
            ->first();

        if (!$agency) {
            return response()->json([
                'status' => false,
                'message' => 'Agency not found'
            ], 404);
        }

        // Search User Country Wise

        $users = AppUser::whereRaw('LOWER(country) = ?', [strtolower($agency->country->name)])
            ->where(function ($q)
            use ($request) {

                $q->where('uid', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('name', 'LIKE', '%' . $request->search . '%');
            })

            // Remove Existing Hosts
            ->whereDoesntHave('host')
            // Remove Agencies
            ->whereDoesntHave('agency')
            // Remove BD
            ->whereDoesntHave('bdUser')
            ->select('id', 'uid', 'name', 'image')
            ->latest()
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'uid' => $user->uid,
                    'name' => $user->name,
                    'image' => !empty($user->image) ? Helper::showImage($user->image, true) : null,
                ];
            });

        return response()->json([
            'status' => true,
            'message' => 'Users fetched successfully',
            'data' => $users
        ]);
    }

    public function inviteHost(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:app_users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        $authUser = auth()->user();

        $agency = Agency::where('user_id', $authUser->id)
            ->where('status', 1)
            ->first();

        if (!$agency) {
            return response()->json([
                'status' => false,
                'message' => 'Agency not found'
            ], 404);
        }

        $user = AppUser::where('id', $request->user_id)->first();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found'
            ], 404);
        }

        if ($user->id == $authUser->id) {
            return response()->json([
                'status' => false,
                'message' => 'You cannot invite yourself'
            ], 422);
        }

        $existingAgency = Agency::where('user_id', $user->id)->where('status', 1)->exists();

        if ($existingAgency) {
            return response()->json([
                'status' => false,
                'message' => 'Agency already includes Host access'
            ], 422);
        }

        $existingHost = Host::where('user_id', $user->id)->first();

        $pendingInvite = Host::where('user_id', $user->id)
            ->where('invite_status', 'pending')
            ->first();

        if ($pendingInvite) {
            return response()->json([
                'status' => false,
                'message' => 'Host invite already pending'
            ], 422);
        }

        if ($existingHost && !empty($existingHost->agency_id)) {
            return response()->json([
                'status' => false,
                'message' => 'User already exists as Host under another Agency'
            ], 422);
        }

        $host = Host::create([
            'user_id' => $user->id,
            'agency_id' => $agency->id,
            'country_id' => $agency->country_id,
            'invite_status' => 'pending',
            'status' => 1,
        ]);

        Notification::create([
            'user_id' => $user->id,
            'sender_id' => $authUser->id,
            'receiver_id' => $user->id,
            'type' => 'host',
            'title' => 'Host Invitation',
            'message' => $authUser->name . ' invited you for host',
            'country' => strtolower($authUser->country),
            'reference_id' => $host->id,
            'is_read' => 0,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Host invitation sent successfully',
            'data' => [
                'id' => $host->id,
                'user_id' => $user->id,
                'uid' => $user->uid,
                'name' => $user->name,
                'image' => !empty($user->image)
                    ? Helper::showImage($user->image, true)
                    : null,
                'invite_status' => $host->invite_status
            ]
        ]);
    }

    public function hostList()
    {
        $agency = Agency::where('user_id', auth()->id())->where('status', 1)->first();

        if (!$agency) {
            return response()->json([
                'status' => false,
                'message' => 'Agency not found'
            ], 404);
        }

        $agent = [

            'id' => $agency->id,
            'user_id' => $agency->user_id,
            'uid' => $agency->user?->uid,
            'name' => $agency->user?->name,
            'image' => !empty($agency->user?->image)
                ? Helper::showImage(
                    $agency->user->image,
                    true
                )
                : null,

            'member_type' => 'agent',
            'member_badge' => 'Agent',
            'target' => 0,
            'status' => true,
            'created_at' => $agency->created_at
                ? $agency->created_at->format('Y-m-d')
                : null,
        ];

        $hosts = Host::with(['user:id,uid,name,image'])
            ->where('agency_id', $agency->id)
            ->where('invite_status', 'accept')
            ->where('user_id', '!=', $agency->user_id)
            ->latest()
            ->get()
            ->map(function ($item) {

                return [
                    'id' => $item->id,
                    'user_id' => $item->user_id,
                    'uid' => $item->user?->uid,
                    'name' => $item->user?->name,
                    'image' => !empty($item->user?->image)
                        ? Helper::showImage($item->user->image, true) : null,
                    'status' => (bool) $item->status,
                    'created_at' => $item->created_at,
                ];
            });

        $data = collect([$agent])
            ->merge($hosts)
            ->values();

        return response()->json([
            'status' => true,
            'message' => 'Host list fetched successfully',
            'data' => $data
        ]);
    }

    public function removeHost(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'host_id' => 'required|exists:hosts,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        // Agency Check


        $agency = Agency::where(
            'user_id',
            auth()->id()
        )

            ->where(
                'status',
                1
            )

            ->first();

        if (!$agency) {

            return response()->json([

                'status' => false,

                'message' => 'Agency not found'

            ], 404);
        }
        // Host Check


        $host = Host::where(
            'id',
            $request->host_id
        )

            ->where(
                'agency_id',
                $agency->id
            )

            ->first();

        if (!$host) {

            return response()->json([

                'status' => false,

                'message' => 'Host not found under this agency'

            ], 404);
        }

        //   Remove Host


        $host->delete();

        return response()->json([

            'status' => true,

            'message' => 'Host removed successfully'
        ]);
    }

    public function hostApplicationList()
    {
        $agency = Agency::where(
            'user_id',
            auth()->id()
        )->first();

        if (!$agency) {

            return response()->json([

                'status' => false,

                'message' => 'Agency not found'

            ], 404);
        }

        $hosts = Host::with([
            'user:id,uid,name,image'
        ])

            ->where(
                'agency_id',
                $agency->id
            )

            ->where(
                'invite_status',
                'pending'
            )

            ->latest()

            ->get()

            ->map(function ($host) {

                return [

                    'host_id' => $host->id,

                    'user_id' => $host->user?->id,

                    'uid' => $host->user?->uid,

                    'name' => $host->user?->name,

                    'image' => !empty($host->user?->image)

                        ? Helper::showImage(
                            $host->user->image,
                            true
                        )

                        : null,

                    'invite_status' => $host->invite_status,

                    'created_at' => $host->created_at,
                ];
            });

        return response()->json([

            'status' => true,

            'message' => 'Host applications fetched successfully',

            'data' => $hosts
        ]);
    }


    public function hostApplicationAction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'host_id' => 'required|exists:hosts,id',
            'action' => 'required|in:accept,reject'
        ]);

        if ($validator->fails()) {

            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        $agency = Agency::where('user_id', auth()->id())->first();

        if (!$agency) {

            return response()->json([
                'status' => false,
                'message' => 'Agency not found'
            ], 404);
        }

        $host = Host::with('user')
            ->where('id', $request->host_id)
            ->where('agency_id', $agency->id)
            ->where('invite_status', 'pending')
            ->first();

        if (!$host) {

            return response()->json([
                'status' => false,
                'message' => 'Host application not found'
            ], 404);
        }

        // Accept


        if ($request->action == 'accept') {
            $host->update([
                'invite_status' => 'accept',
                'status' => 1
            ]);

            Notification::create([
                'user_id' => $host->user_id,
                'sender_id' => auth()->id(),
                'receiver_id' => $host->user_id,
                'type' => 'host',
                'title' => 'Host Application Approved',
                'message' => auth()->user()->name . ' approved your host application',
                'reference_id' => $host->id,
                'country' => auth()->user()->country,
                'is_read' => 0,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Host application accepted successfully'
            ]);
        }

        //  Reject

        if ($request->action == 'reject') {
            $host->delete();
        };

        return response()->json([
            'status' => true,
            'message' => 'Host application rejected successfully'
        ]);
    }


    public function agencyPolicy(Request $request)
    {
        try {

            $user = auth()->user();

            $agency = Agency::with('country')
                ->where('user_id', $user->id)
                ->where('status', 1)
                ->first();

            if (!$agency) {
                return response()->json([
                    'status' => false,
                    'message' => 'Agency not found'
                ]);
            }

            $policies = HostPolicy::where(
                'country',
                $agency->country->nicename
            )
                ->where('status', 1)
                ->orderBy('level')
                ->get();

            return response()->json([
                'status' => true,
                'message' => 'Agency policy list',
                'data' => $policies->map(function ($policy) {

                    return [
                        'id' => $policy->id,
                        'level' => $policy->level,
                        'target_value' => (int) $policy->target_value,
                        'host_salary' => (float) $policy->host_salary,
                        'agent_commission' => (float) $policy->agent_commission,
                        'total_salary' => (float) $policy->total_salary,
                    ];
                })
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function agencyMyWork()
    {
        try {

            $user = Auth::user();

            $agency = Agency::where('user_id', $user->id)
                ->where('status', 1)
                ->first();

            if (!$agency) {

                return response()->json([
                    'status' => false,
                    'message' => 'Agency not found'
                ]);
            }

            $host = Host::with('country')
                ->where('user_id', $user->id)
                ->first();

            if (!$host) {

                return response()->json([
                    'status' => false,
                    'message' => 'Host record not found'
                ]);
            }

            $today = now();

            $hostCreatedAt = Carbon::parse(
                $host->created_at
            );

            $cycles = [];

            $startMonth = $hostCreatedAt->copy()->startOfMonth();

            while ($startMonth <= $today) {

                $month = $startMonth->format('Y-m');
                // Cycle 1 (1-15)


                $cycle1Start = $startMonth->copy()->startOfMonth();
                $cycle1End   = $startMonth->copy()->day(15);

                if ($cycle1End >= $hostCreatedAt) {

                    $cycles[] = $this->buildAgencyCycleData(
                        $host,
                        $month,
                        '01-15',
                        $cycle1Start,
                        $cycle1End,
                        $hostCreatedAt,
                        $today
                    );
                }

                // Cycle 2 (16-End)


                $cycle2Start = $startMonth->copy()->day(16);
                $cycle2End   = $startMonth->copy()->endOfMonth();

                if (
                    $cycle2Start <= $today &&
                    $cycle2End >= $hostCreatedAt
                ) {

                    $cycles[] = $this->buildAgencyCycleData(
                        $host,
                        $month,
                        '16-End',
                        $cycle2Start,
                        $cycle2End,
                        $hostCreatedAt,
                        $today
                    );
                }

                $startMonth->addMonth();
            }

            $cycles = collect($cycles)
                ->sortByDesc('sort_date')
                ->values();

            return response()->json([
                'status' => true,
                'message' => 'Agency work data fetched successfully',
                'data' => $cycles
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    private function buildAgencyCycleData(
        $host,
        $month,
        $cycleName,
        $startDate,
        $endDate,
        $hostCreatedAt,
        $today
    ) {

        $giftTotal = GiftTransaction::where(
            'receiver_id',
            $host->user_id
        )
            ->whereBetween('created_at', [
                max($startDate, $hostCreatedAt),
                min($endDate, $today)
            ])
            ->sum('total_value');

        $policy = HostPolicy::where(
            'country',
            $host->country->nicename
        )
            ->where('status', 1)
            ->where(
                'target_value',
                '<=',
                $giftTotal
            )
            ->orderByDesc('level')
            ->first();

        $settlement = AgencySalarySettlement::where(
            'agency_id',
            $host->agency_id
        )
            ->where('month', $month)
            ->where('cycle', $cycleName)
            ->first();

        return [

            'month' => $month,

            'cycle' => $cycleName,

            'target' => $giftTotal,

            'target_level' =>
            $policy->level ?? 0,

            'salary' =>
            $policy->host_salary ?? 0,

            'status' =>
            $settlement
                ? 'Settled'
                : 'Unsettled',

            'sort_date' =>
            $endDate->timestamp
        ];
    }

    public function agencyWorkDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [

            'month' => 'required|date_format:Y-m',

            'cycle' => 'required|in:1,2'
        ]);

        if ($validator->fails()) {

            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ]);
        }

        try {

            $user = Auth::user();

            $agency = Agency::where(
                'user_id',
                $user->id
            )
                ->where('status', 1)
                ->first();

            if (!$agency) {

                return response()->json([
                    'status' => false,
                    'message' => 'Agency not found'
                ]);
            }

            $host = Host::with('country')
                ->where('user_id', $user->id)
                ->first();

            if (!$host) {

                return response()->json([
                    'status' => false,
                    'message' => 'Host record not found'
                ]);
            }

            $month = Carbon::parse(
                $request->month . '-01'
            );

            //    Cycle Dates


            if ($request->cycle == 1) {

                $startDate = $month->copy()
                    ->startOfMonth();

                $endDate = $month->copy()
                    ->day(15);
            } else {

                $startDate = $month->copy()
                    ->day(16);

                $endDate = $month->copy()
                    ->endOfMonth();
            }

            //    Daily Gift Details


            $giftDetails = GiftTransaction::selectRaw(
                'DATE(created_at) as date,
             SUM(total_value) as target'
            )
                ->where(
                    'receiver_id',
                    $host->user_id
                )
                ->whereBetween('created_at', [
                    $startDate,
                    $endDate
                ])
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->map(function ($row) {

                    return [

                        'date' => $row->date,

                        'target' => (int) $row->target
                    ];
                });

            //   Total Target


            $totalTarget = GiftTransaction::where(
                'receiver_id',
                $host->user_id
            )
                ->whereBetween('created_at', [
                    $startDate,
                    $endDate
                ])
                ->sum('total_value');
            //  Policy


            $policy = HostPolicy::where(
                'country',
                $host->country->nicename
            )
                ->where('status', 1)
                ->where(
                    'target_value',
                    '<=',
                    $totalTarget
                )
                ->orderByDesc('level')
                ->first();
            // Settlement Status


            $settlement = AgencySalarySettlement::where(
                'agency_id',
                $agency->id
            )
                ->where(
                    'month',
                    $request->month
                )
                ->where(
                    'cycle',
                    $request->cycle
                )
                ->first();

            $status = $settlement
                ? 'Settled'
                : 'Unsettled';

            return response()->json([

                'status' => true,

                'message' =>
                'Agency work details fetched successfully',

                'data' => [

                    'month' =>
                    $request->month,

                    'cycle' =>
                    $request->cycle,

                    'start_date' =>
                    $startDate->format('Y-m-d'),

                    'end_date' =>
                    $endDate->format('Y-m-d'),

                    'target' =>
                    (int) $totalTarget,

                    'target_level' =>
                    $policy->level ?? 0,

                    'salary' =>
                    (float) ($policy->host_salary ?? 0),

                    'status' =>
                    $status,

                    'details' =>
                    $giftDetails
                ]
            ]);
        } catch (\Exception $e) {

            return response()->json([

                'status' => false,

                'message' => $e->getMessage()
            ]);
        }
    }

    public function teamBill()
    {
        try {

            $user = Auth::user();

            $agency = Agency::where('user_id', $user->id)
                ->where('status', 1)
                ->first();

            if (!$agency) {
                return response()->json([
                    'status' => false,
                    'message' => 'Agency not found'
                ]);
            }

            $today = now();

            $joinDate = Carbon::parse(
                $agency->created_at
            )->startOfMonth();

            $currentMonth = now()->startOfMonth();

            $data = [];

            while ($currentMonth->gte($joinDate)) {

                $month = $currentMonth->format('Y-m');

                $cycle1Start = $currentMonth->copy()->startOfMonth();
                $cycle1End = $currentMonth->copy()
                    ->startOfMonth()
                    ->addDays(14)
                    ->endOfDay();

                $cycle2Start = $currentMonth->copy()
                    ->startOfMonth()
                    ->addDays(15);

                $cycle2End = $currentMonth->copy()
                    ->endOfMonth();

                if (
                    $today->gte($cycle2Start)
                ) {

                    $data[] = $this->buildTeamBillData(
                        $agency,
                        $month,
                        '2',
                        $cycle2Start,
                        $cycle2End,
                        $today
                    );
                }

                $data[] = $this->buildTeamBillData(
                    $agency,
                    $month,
                    '1',
                    $cycle1Start,
                    $cycle1End,
                    $today
                );

                $currentMonth->subMonth();
            }

            usort($data, function ($a, $b) {
                return $b['sort_date']
                    <=> $a['sort_date'];
            });

            return response()->json([
                'status' => true,
                'message' => 'Team bill fetched successfully',
                'data' => array_values($data)
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    private function buildTeamBillData(
        $agency,
        $month,
        $cycle,
        $startDate,
        $endDate,
        $today
    ) {

        $hosts = Host::with('country')
            ->where('agency_id', $agency->id)
            ->where('status', 1)
            ->where('invite_status', 'accept')
            ->get();

        $totalTarget = 0;

        $memberSalary = 0;

        $agentSalary = 0;

        foreach ($hosts as $host) {

            $hostCreatedAt = Carbon::parse(
                $host->created_at
            );

            $from = $startDate->copy();

            if ($from->lt($hostCreatedAt)) {
                $from = $hostCreatedAt;
            }

            $giftTotal = GiftTransaction::where(
                'receiver_id',
                $host->user_id
            )
                ->whereBetween('created_at', [
                    $from,
                    min($endDate, $today)
                ])
                ->sum('total_value');

            $policy = HostPolicy::where(
                'country',
                $host->country->nicename
            )
                ->where('status', 1)
                ->where(
                    'target_value',
                    '<=',
                    $giftTotal
                )
                ->orderByDesc('level')
                ->first();

            $totalTarget += $giftTotal;

            $memberSalary +=
                $policy->host_salary ?? 0;

            $agentSalary +=
                $policy->agent_commission ?? 0;
        }

        $settlement = AgencySalarySettlement::where([
            'agency_id' => $agency->id,
            'month' => $month,
            'cycle' => $cycle
        ])->first();

        $isCurrentCycle =
            $today->between(
                $startDate,
                $endDate
            );

        return [

            'month' => $month,

            'cycle' => $cycle,
            'cycle_date' => $startDate->format('m-d')
                . '/'
                . $endDate->format('m-d'),

            'start_date' => $startDate->format('Y-m-d'),

            'end_date' => $endDate->format('Y-m-d'),

            'target' => $totalTarget,

            'member_salary' => round(
                $memberSalary,
                2
            ),

            'agent_salary' => round(
                $agentSalary,
                2
            ),

            'total_salary' => round(
                $memberSalary + $agentSalary,
                2
            ),

            'status' => $settlement
                ? 'Settled'
                : 'Unsettled',

            'is_current' => $isCurrentCycle,

            'sort_date' => $endDate->timestamp
        ];
    }

    public function teamBillDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'month' => 'required',
            'cycle' => 'required|in:1,2',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ]);
        }

        try {

            $agency = Agency::where(
                'user_id',
                auth()->id()
            )
                ->where('status', 1)
                ->first();

            if (!$agency) {
                return response()->json([
                    'status' => false,
                    'message' => 'Agency not found'
                ]);
            }

            $monthDate = Carbon::createFromFormat(
                'Y-m',
                $request->month
            );

            if ($request->cycle == 1) {

                $from = $monthDate
                    ->copy()
                    ->startOfMonth();

                $to = $monthDate
                    ->copy()
                    ->startOfMonth()
                    ->addDays(14)
                    ->endOfDay();
            } else {

                $from = $monthDate
                    ->copy()
                    ->startOfMonth()
                    ->addDays(15);

                $to = $monthDate
                    ->copy()
                    ->endOfMonth();
            }

            $hosts = Host::with([
                'user:id,uid,name,image',
                'country'
            ])
                ->where('agency_id', $agency->id)
                ->where('status', 1)
                ->where('invite_status', 'accept')
                ->get();

            $memberSalary = 0;
            $agentSalary = 0;
            $totalTarget = 0;

            $members = [];

            foreach ($hosts as $host) {

                $hostFrom = $from->copy();

                if ($hostFrom->lt($host->created_at)) {
                    $hostFrom = Carbon::parse(
                        $host->created_at
                    );
                }

                $giftTotal = GiftTransaction::where(
                    'receiver_id',
                    $host->user_id
                )
                    ->whereBetween(
                        'created_at',
                        [$hostFrom, $to]
                    )
                    ->sum('total_value');

                $policy = HostPolicy::where(
                    'country',
                    $host->country->nicename
                )
                    ->where('status', 1)
                    ->where(
                        'target_value',
                        '<=',
                        $giftTotal
                    )
                    ->orderByDesc('level')
                    ->first();

                $hostSalary =
                    $policy->host_salary ?? 0;

                $commission =
                    $policy->agent_commission ?? 0;

                $memberSalary += $hostSalary;

                $agentSalary += $commission;

                $totalTarget += $giftTotal;

                $members[] = [

                    'user_id' =>
                    $host->user_id,

                    'uid' =>
                    $host->user?->uid,

                    'name' =>
                    $host->user?->name,

                    'image' =>
                    !empty($host->user?->image)
                        ? Helper::showImage(
                            $host->user->image,
                            true
                        )
                        : null,

                    'target' =>
                    (float) $giftTotal,

                    'salary' =>
                    (float) $hostSalary,
                ];
            }

            $settlement =
                AgencySalarySettlement::where([
                    'agency_id' => $agency->id,
                    'month' => $request->month,
                    'cycle' => $request->cycle,
                ])->first();

            return response()->json([

                'status' => true,

                'message' =>
                'Team bill details fetched successfully',

                'data' => [

                    'cycle_date' =>
                    $from->format('m-d')
                        . '/'
                        . $to->format('m-d'),

                    'status_text' =>
                    $settlement
                        ? 'Settled'
                        : 'Unsettled',

                    'member_salary' =>
                    round($memberSalary, 2),

                    'agent_salary' =>
                    round($agentSalary, 2),

                    'total_salary' =>
                    round(
                        $memberSalary +
                            $agentSalary,
                        2
                    ),

                    'target' =>
                    (float) $totalTarget,

                    'members' =>
                    $members
                ]
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function runAgencySalarySettlement()
    {
        try {

            \Log::info(
                'MANUAL AGENCY SALARY SETTLEMENT START'
            );

            Artisan::call(
                'agency:settle-salary'
            );

            \Log::info(
                'MANUAL AGENCY SALARY SETTLEMENT END'
            );

            return response()->json([

                'status' => true,

                'message' =>
                'Agency salary settlement executed successfully',

                'output' =>
                Artisan::output()
            ]);
        } catch (\Exception $e) {

            return response()->json([

                'status' => false,

                'message' =>
                $e->getMessage()
            ]);
        }
    }
}
