<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Helper\Helper;
use App\Models\AppUser;
use App\Models\GiftTransaction;
use App\Models\Agency;
use App\Models\CoinConversionRate;
use App\Models\HostPolicy;
use App\Models\Host;
use App\Models\Notification;
use App\Models\ExchangeHistory;
use App\Models\DollarTransferHistory;
use App\Models\WithdrawalAccount;
use App\Models\WithdrawalRequest;
use App\Models\AdminAccount;
use App\Models\BdUser;
use App\Models\CoinSeller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use App\Models\HostSalarySettlement;
use App\Services\FirebaseService;

class HostCenterController extends Controller
{


    public function hostDetails()
    {
        $userId = auth()->id();

        $host = Host::with([
            'user:id,uid,name,image,country',
            'user.countryData:id,name,iso'
        ])
            ->where('user_id', $userId)
            ->where('status', 1)
            ->where('invite_status', 'accept')
            ->first();

        if (!$host) {
            return response()->json([
                'status' => false,
                'message' => 'Host User not found'
            ], 404);
        }

        $flag = null;

        if ($host->user?->countryData?->iso) {
            $flag = 'https://flagcdn.com/w40/' .
                strtolower($host->user->countryData->iso) . '.png';
        }

        return response()->json([
            'status' => true,
            'message' => 'Host details fetched successfully',
            'data' => [
                'id' => $host->id,
                'user_id' => $host->user_id,
                'agency_id' => $host->agency_id,
                'uid' => $host->user?->uid,
                'name' => $host->user?->name,
                'image' => !empty($host->user?->image)
                    ? Helper::showImage($host->user->image, true)
                    : null,
                'country' => strtolower($host->user?->country ?? ''),
                'flag' => $flag,
                'is_dashboard_access' => (bool) $host->is_dashboard_access,
            ]
        ]);
    }
    public function applyForHost(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'agency_uid' => 'required'
        ]);

        if ($validator->fails()) {

            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        $user = auth()->user();

        // User already Agency?

        $agencyExists = Agency::where('user_id', $user->id)
            ->where('invite_status', 'accept')
            ->exists();

        if ($agencyExists) {

            return response()->json([
                'status' => false,
                'message' => 'Agency user cannot apply for host'
            ], 422);
        }


        $host = Host::where('user_id', auth()->id())
            ->where('invite_status', 'accept')
            ->first();

        if ($host) {
            return response()->json([
                'status' => false,
                'message' => 'You are already attached to an agency'
            ], 422);
        }

        // Already Host?

        $hostExists = Host::where('user_id', $user->id)
            ->where('invite_status', 'accept')
            ->exists();

        if ($hostExists) {

            return response()->json([
                'status' => false,
                'message' => 'You are already a host'
            ], 422);
        }

        // Find Agency By UID

        $agencyUser = AppUser::where('uid', $request->agency_uid)->first();

        if (!$agencyUser) {

            return response()->json([
                'status' => false,
                'message' => 'Agent not found'
            ], 404);
        }

        $agency = Agency::where('user_id', $agencyUser->id)
            ->where('status', 1)
            ->first();

        if (!$agency) {

            return response()->json([
                'status' => false,
                'message' => 'Agent not found'
            ], 404);
        }

        //   Pending Request Check

        $pendingRequest = Host::where('user_id', $user->id)
            ->where('invite_status', 'pending')
            ->exists();

        if ($pendingRequest) {

            return response()->json([
                'status' => false,
                'message' => 'Host application already pending'
            ], 422);
        }

        // Create Host Request

        $host = Host::create([
            'user_id' => $user->id,
            'agency_id' => $agency->id,
            'country_id' => $agency->country_id,
            'invite_status' => 'pending',
            'status' => 1,
        ]);

        //    Notification To Agent

        Notification::create([
            'user_id' => $agencyUser->id,
            'sender_id' => $user->id,
            'receiver_id' => $agencyUser->id,
            'type' => 'host_apply',
            'title' => 'Host Application',
            'message' => $user->name . ' applied for host',
            'reference_id' => $host->id,
            'country' => $user->country,
            'is_read' => 0,
        ]);

        if ($agencyUser->fcm_token) {

            app(FirebaseService::class)->sendNotification(
                $agencyUser->fcm_token,
                'New Host Application',
                $user->name . ' applied for host',
                [
                    'type' => 'host_apply',
                    'host_id' => $host->id,
                    'user_id' => $user->id,
                ]
            );
        }

        return response()->json([

            'status' => true,
            'message' => 'Host application submitted successfully',

            'data' => [
                'host_id' => $host->id,
                'agency_id' => $agency->id,
                'invite_status' => $host->invite_status
            ]
        ]);
    }

    public function hostPolicy(Request $request)
    {
        try {

            $user = auth()->user();

            $host = Host::with('country')->where('user_id', $user->id)
                ->where('status', 1)
                ->first();

            if (!$host) {
                return response()->json([
                    'status' => false,
                    'message' => 'Host not found'
                ]);
            }

            $policies = HostPolicy::where('country', $host->country->nicename)
                ->where('status', 1)
                ->orderBy('level', 'asc')
                ->get();

            return response()->json([
                'status' => true,
                'message' => 'Host policy list',
                'data' => $policies->map(function ($policy) {
                    return [
                        'id' => $policy->id,
                        'level' => $policy->level,
                        'target_value' => (int) $policy->target_value,
                        'host_salary' => (float) $policy->host_salary,
                    ];
                })
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function myWork()
    {
        try {

            $user = Auth::user();

            $host = Host::with('country')
                ->where('user_id', $user->id)
                ->where('status', 1)
                ->first();

            if (!$host) {
                return response()->json([
                    'status' => false,
                    'message' => 'Host not found'
                ]);
            }

            $hostCreatedAt = Carbon::parse($host->created_at);
            $today = now();

            $cycles = [];

            $current = $hostCreatedAt->copy()->startOfMonth();

            while ($current <= $today) {

                $month = $current->format('Y-m');

                //    Cycle 1 : 1-15

                $cycle1Start = $current->copy()->startOfMonth();
                $cycle1End = $current->copy()->startOfMonth()->addDays(14)->endOfDay();

                if ($cycle1End >= $hostCreatedAt) {

                    $cycles[] = $this->buildCycleData(
                        $host,
                        $month,
                        '01-15',
                        $cycle1Start,
                        $cycle1End,
                        $hostCreatedAt,
                        $today
                    );
                }

                //  Cycle 2 : 16-End

                $cycle2Start = $current->copy()->startOfMonth()->addDays(15);
                $cycle2End = $current->copy()->endOfMonth();

                // Current month me agar abhi 16 nahi ayi to second cycle mat dikhao


                if (
                    $today >= $cycle2Start &&
                    $cycle2End >= $hostCreatedAt
                ) {

                    $cycles[] = $this->buildCycleData(
                        $host,
                        $month,
                        '16-End',
                        $cycle2Start,
                        $cycle2End,
                        $hostCreatedAt,
                        $today
                    );
                }

                $current->addMonth()->startOfMonth();
            }

            //  Latest Cycle Top par


            $cycles = collect($cycles)->sortByDesc('sort_date')->values();

            return response()->json([
                'status' => true,
                'message' => 'My Work Data',
                'data' => $cycles
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
    }



    private function buildCycleData(
        $host,
        $month,
        $cycleName,
        $startDate,
        $endDate,
        $hostCreatedAt,
        $today
    ) {

        $giftTotal = GiftTransaction::where('receiver_id', $host->user_id)
            ->whereBetween('created_at', [max($startDate, $hostCreatedAt), min($endDate, $today)])
            ->sum('total_value');

        $policy = HostPolicy::where('country', $host->country->nicename)
            ->where('status', 1)
            ->where('target_value', '<=', $giftTotal)
            ->orderByDesc('level')
            ->first();

        $isCurrentCycle = $today->between($startDate, $endDate);

        $cycleNumber = $cycleName == '01-15' ? 1 : 2;

        //    Default Live Values

        $level = $policy->level ?? 0;
        $salary = $policy->host_salary ?? 0;
        $status = 'Pending';

        //   Current Cycle


        if ($isCurrentCycle) {
            $status = 'Unsettled';
        } else {

            //    Past Cycle Settlement Check

            $settlement = HostSalarySettlement::where([
                'host_id' => $host->id,
                'month'   => $month,
                'cycle'   => $cycleNumber
            ])->first();

            if ($settlement) {
                $status = 'Settled';

                // Freeze values from settlement
                $level = $settlement->level;
                $salary = $settlement->host_salary;
            } else {
                $status = 'Pending';
            }
        }

        return [
            'month' => $month,
            'cycle' => $cycleName,
            'target' => $giftTotal,
            'target_level' => $level,
            'salary' => $salary,
            'status' => $status,
            'is_current' => $isCurrentCycle,
            'sort_date' => $endDate->timestamp
        ];
    }

    public function myWorkDetails(Request $request)
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

            $user = Auth::user();

            $host = Host::with('country')
                ->where('user_id', $user->id)
                ->where('status', 1)
                ->first();

            if (!$host) {
                return response()->json([
                    'status' => false,
                    'message' => 'Host not found'
                ]);
            }

            $monthDate = Carbon::createFromFormat('Y-m', $request->month);

            if ($request->cycle == 1) {

                $from = $monthDate->copy()->startOfMonth();

                $to = $monthDate->copy()->startOfMonth()->addDays(14)->endOfDay();
            } else {

                $from = $monthDate->copy()->startOfMonth()->addDays(15);

                $to = $monthDate->copy()->endOfMonth();
            }

            // Host banne se pehle ka data count nahi hoga

            if ($from < $host->created_at) {
                $from = Carbon::parse($host->created_at);
            }

            //    Daily Details

            $details = GiftTransaction::selectRaw(" DATE(created_at) as date,SUM(total_value) as target")
                ->where('receiver_id', $host->user_id)
                ->whereBetween('created_at', [$from, $to])
                ->groupByRaw('DATE(created_at)')
                ->orderBy('date')
                ->get()
                ->map(function ($row) {
                    return [
                        'date' => $row->date,
                        'target' => (int) $row->target
                    ];
                });

            //    Total Target

            $totalTarget = GiftTransaction::where('receiver_id', $host->user_id)
                ->whereBetween('created_at', [$from, $to])
                ->sum('total_value');

            //   Policy

            $policy = HostPolicy::where('country', $host->country->nicename)
                ->where('status', 1)
                ->where('target_value', '<=', $totalTarget)
                ->orderByDesc('level')
                ->first();

            //   Settlement Status

            $settlement = HostSalarySettlement::where('host_id', $host->id)
                ->where('month', $request->month)
                ->where('cycle', $request->cycle)
                ->first();

            $status = $settlement ? 'Settled' : 'Unsettled';

            return response()->json([
                'status' => true,
                'message' => 'Work details fetched successfully',

                'data' => [
                    'month' => $request->month,
                    'cycle' => $request->cycle,
                    'start_date' => $from->format('Y-m-d'),
                    'end_date' => $to->format('Y-m-d'),
                    'target' => (int) $totalTarget,
                    'target_level' => $policy->level ?? 0,
                    'salary' => (float) ($policy->host_salary ?? 0),
                    'status_text' => $status,
                    'details' => $details
                ]
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function exchangeSalaryToCoins(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ]);
        }

        try {

            $user = Auth::user();

            if ($user->balance < $request->amount) {

                return response()->json([
                    'status' => false,
                    'message' => 'Insufficient balance'
                ]);
            }

            $rate = CoinConversionRate::first();

            if (!$rate) {

                return response()->json([
                    'status' => false,
                    'message' => 'Conversion rate not found'
                ]);
            }

            $coins = round($request->amount * $rate->coin_exchange_rate);

            DB::transaction(function () use (
                $user,
                $request,
                $coins,
                $rate
            ) {
                $user->balance -=  $request->amount;
                $user->total_points += $coins;
                $user->save();

                ExchangeHistory::create([
                    'user_id' =>  $user->id,
                    'usd_amount' => $request->amount,
                    'exchange_rate' => $rate->coin_exchange_rate,
                    'coins_received' => $coins
                ]);
            });

            return response()->json([
                'status' => true,
                'message' => 'Coins exchanged successfully',
                'data' => [
                    'usd_amount' => (float) $request->amount,
                    'coins_received' => $coins,
                    'remaining_balance' => $user->fresh()->balance
                ]
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function exchangeHistory(Request $request)
    {
        try {

            $user = Auth::user();

            $query = ExchangeHistory::where('user_id', $user->id);

            //  Date Filter

            if (
                $request->filled('start_date') &&
                $request->filled('end_date')
            ) {

                $query->whereBetween(
                    'created_at',
                    [
                        Carbon::parse($request->start_date)
                            ->startOfDay(),

                        Carbon::parse($request->end_date)
                            ->endOfDay()
                    ]
                );
            }

            //   UID Filter

            if ($request->filled('uid')) {

                if ($user->uid != $request->uid) {
                    return response()->json([
                        'status' => true,
                        'summary' => [
                            'records' => 0,
                            'net_amount' => '0.00'
                        ],
                        'data' => []
                    ]);
                }
            }

            $histories = $query->latest()->get();
            $records = $histories->count();
            $netAmount = $histories->sum('coins_received');

            $data = $histories->map(
                function ($row) use ($user) {

                    return [
                        'id' => $row->id,
                        'type' => 'Exchange (Money To Coin)',
                        'uid' => $user->uid,
                        'name' => $user->name,
                        'amount' => '-' . number_format($row->coins_received, 2, '.', ''),
                        'usd_amount' => (float) $row->usd_amount,
                        'exchange_rate' => (int) $row->exchange_rate,
                        'coins_received' => (int) $row->coins_received,
                        'date_time' => $row->created_at->format('Y-m-d H:i:s'),
                    ];
                }
            );

            return response()->json([

                'status' => true,
                'message' => 'Exchange history fetched successfully',
                'summary' => [
                    'records' => $records,
                    'net_amount' => '-' . number_format($netAmount, 2, '.', '')
                ],
                'data' => $data

            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
    }


    public function searchTransferUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'uid' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ]);
        }

        try {

            $authUser = Auth::user();

            $user = AppUser::where('uid', $request->uid)
                ->first();

            if (!$user) {

                return response()->json([
                    'status' => false,
                    'message' => 'User not found'
                ]);
            }

            if ($user->id == $authUser->id) {

                return response()->json([
                    'status' => false,
                    'message' => 'You cannot transfer dollars to yourself'
                ]);
            }

            $isAdmin = AdminAccount::where(
                'user_id',
                $user->id
            )
                ->where('status', 1)
                ->exists();

            $isBd = BdUser::where(
                'user_id',
                $user->id
            )
                ->where('status', 1)
                ->where('is_dashboard_access', 1)
                ->exists();

            $isAgency = Agency::where(
                'user_id',
                $user->id
            )
                ->where('status', 1)
                ->where('invite_status', 'accept')
                ->exists();

            $isHost = Host::where(
                'user_id',
                $user->id
            )
                ->where('status', 1)
                ->where('is_dashboard_access', 1)
                ->exists();

            $isMerchant = CoinSeller::where(
                'user_id',
                $user->id
            )
                ->where('status', 1)
                ->where('is_merchant', 1)
                ->exists();

            $isCoinSeller = CoinSeller::where(
                'user_id',
                $user->id
            )
                ->where('status', 1)
                ->where('is_merchant', 0)
                ->exists();

            //    Normal User Not Allowed


            if (
                !$isAdmin &&
                !$isBd &&
                !$isAgency &&
                !$isHost &&
                !$isMerchant &&
                !$isCoinSeller
            ) {

                return response()->json([
                    'status' => false,
                    'message' => 'User not found'
                ]);
            }

            //   Role


            $role = null;

            if ($isAdmin) {

                $role = 'admin';
            } elseif ($isBd) {

                $role = 'bd';
            } elseif ($isAgency) {

                $role = 'agency';
            } elseif ($isHost) {

                $role = 'host';
            } elseif ($isMerchant) {

                $role = 'merchant';
            } elseif ($isCoinSeller) {

                $role = 'coinseller';
            }

            return response()->json([
                'status' => true,
                'message' => 'User found',
                'data' => [

                    'user_id' => $user->id,

                    'uid' => $user->uid,

                    'name' => $user->name,

                    'role' => $role,

                    'image' => $user->image
                        ? Helper::showImage(
                            $user->image,
                            true
                        )
                        : null,
                ]
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
    }


    public function transferDollar(Request $request)
    {
        $validator = Validator::make($request->all(), [

            'uid' => 'required|exists:app_users,uid',

            'amount' => 'required|numeric|min:0.01',
        ]);

        if ($validator->fails()) {

            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ]);
        }

        try {

            $sender = Auth::user();

            $receiver = AppUser::where(
                'uid',
                $request->uid
            )->first();

            if (!$receiver) {

                return response()->json([
                    'status' => false,
                    'message' => 'Receiver not found'
                ]);
            }

            if ($sender->id == $receiver->id) {

                return response()->json([
                    'status' => false,
                    'message' => 'You cannot transfer dollars to yourself'
                ]);
            }

            if ($sender->balance < $request->amount) {

                return response()->json([
                    'status' => false,
                    'message' => 'Insufficient balance'
                ]);
            }

            DB::transaction(function () use (
                $sender,
                $receiver,
                $request
            ) {

                $sender->balance -=
                    $request->amount;

                $sender->save();

                $receiver->balance =
                    ($receiver->balance ?? 0)
                    + $request->amount;

                $receiver->save();

                DollarTransferHistory::create([

                    'sender_id' =>
                    $sender->id,

                    'receiver_id' =>
                    $receiver->id,

                    'amount' =>
                    $request->amount
                ]);
            });

            return response()->json([
                'status' => true,
                'message' => 'Dollar transferred successfully'
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function transferHistory(Request $request)
    {
        try {

            $user = Auth::user();

            $query = DollarTransferHistory::with([
                'sender:id,uid,name,image',
                'receiver:id,uid,name,image'
            ])
                ->where(function ($q) use ($user) {

                    $q->where('sender_id', $user->id)
                        ->orWhere('receiver_id', $user->id);
                });

            //   Start Date


            if ($request->filled('start_date')) {

                $query->whereDate(
                    'created_at',
                    '>=',
                    $request->start_date
                );
            }

            //   End Date


            if ($request->filled('end_date')) {

                $query->whereDate(
                    'created_at',
                    '<=',
                    $request->end_date
                );
            }

            // UID Filter


            if ($request->filled('uid')) {

                $query->where(function ($q) use ($request) {

                    $q->whereHas('sender', function ($sub) use ($request) {

                        $sub->where(
                            'uid',
                            $request->uid
                        );
                    })->orWhereHas(
                        'receiver',
                        function ($sub) use ($request) {

                            $sub->where(
                                'uid',
                                $request->uid
                            );
                        }
                    );
                });
            }

            $histories = $query
                ->latest()
                ->get();

            $records = $histories->count();

            $netAmount = 0;

            $data = $histories->map(function ($row) use ($user, &$netAmount) {

                $isSender =
                    $row->sender_id == $user->id;

                $otherUser = $isSender
                    ? $row->receiver
                    : $row->sender;

                if ($isSender) {

                    $netAmount -= $row->amount;
                } else {

                    $netAmount += $row->amount;
                }

                return [

                    'id' => $row->id,

                    'type' => $isSender
                        ? 'sent'
                        : 'received',

                    'title' => $isSender
                        ? 'Transfer to ' . ($otherUser->name ?? '')
                        : 'Received from ' . ($otherUser->name ?? ''),

                    'uid' =>
                    $otherUser->uid ?? '',

                    'name' =>
                    $otherUser->name ?? '',

                    'image' =>
                    !empty($otherUser->image)
                        ? Helper::showImage(
                            $otherUser->image,
                            true
                        )
                        : null,

                    'amount' => ($isSender ? '-' : '+')
                        . number_format(
                            $row->amount,
                            2,
                            '.',
                            ''
                        ),

                    'date_time' =>
                    $row->created_at
                        ->format('Y-m-d H:i:s')
                ];
            });

            return response()->json([

                'status' => true,

                'message' =>
                'Transfer history fetched successfully',

                'summary' => [

                    'records' =>
                    $records,

                    'net_amount' =>
                    number_format(
                        $netAmount,
                        2,
                        '.',
                        ''
                    )
                ],

                'data' => $data
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function walletBalance()
    {
        try {
            $user = Auth::user();

            return response()->json([

                'status' => true,
                'message' => 'Wallet balance fetched successfully',
                'data' => [
                    'balance' => (float) $user->balance,
                    'formatted_balance' => '$' . number_format($user->balance, 2),
                ]
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
    }


    public function runHostSettlement()
    {
        try {
            \Log::info('HOST SETTLEMENT START');

            Artisan::call('host:settle-salary');

            \Log::info('HOST SETTLEMENT END');

            return response()->json([
                'status' => true,
                'message' => 'Host settlement executed successfully',
                'output' => Artisan::output()
            ]);
        } catch (\Exception $e) {

            \Log::error($e->getMessage());

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function submitWithdrawal(Request $request)
    {
        $rules = [
            'method' => 'required|in:bank,usdt',
            'amount' => 'required|numeric|min:1',
        ];

        if ($request->method == 'bank') {

            $rules['account_holder_name'] = 'required';
            $rules['bank_name'] = 'required';
            $rules['account_number'] = 'required';
            $rules['ifsc_code'] = 'required';
        }

        if ($request->method == 'usdt') {

            $rules['channel_name'] = 'required';
            $rules['usdt_address'] = 'required';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {

            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ]);
        }

        try {

            $user = Auth::user();

            if ($user->balance < $request->amount) {

                return response()->json([
                    'status' => false,
                    'message' => 'Insufficient balance'
                ]);
            }

            DB::transaction(function () use (
                $request,
                $user,
                &$withdrawal
            ) {

                //  Save / Update Account

                $account = WithdrawalAccount::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'type' => $request->method
                    ],
                    [
                        'account_holder_name' => $request->account_holder_name,
                        'bank_name' => $request->bank_name,
                        'account_number' => $request->account_number,
                        'ifsc_code' => $request->ifsc_code,
                        'channel_name' => $request->channel_name,
                        'usdt_address' => $request->usdt_address,
                        'status' => 1
                    ]
                );

                // Deduct Balance

                $user->decrement('balance', $request->amount);

                //  Create Withdrawal Request

                $withdrawal =
                    WithdrawalRequest::create([
                        'user_id' => $user->id,
                        'account_id' => $account->id,
                        'method' => $request->method,
                        'amount' => $request->amount,
                        'status' => 'pending',
                        'requested_at' => now()
                    ]);
            });

            return response()->json([
                'status' => true,
                'message' => 'Withdrawal request submitted successfully',
                'data' => [
                    'withdrawal_id' => $withdrawal->id,
                    'method' =>  $withdrawal->method,
                    'amount' => (float) $withdrawal->amount,
                    'status' => $withdrawal->status,
                ]
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
    }


    public function withdrawalHistory(Request $request)
    {
        try {

            $user = Auth::user();

            $query = WithdrawalRequest::where('user_id', $user->id);

            //   Start Date

            if ($request->filled('start_date')) {
                $query->whereDate('created_at', '>=', $request->start_date);
            }

            //  End Date

            if ($request->filled('end_date')) {
                $query->whereDate('created_at', '<=', $request->end_date);
            }

            // Method Filter

            if ($request->filled('method')) {
                $query->where('method',  $request->method);
            }

            //  Status Filter

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            $withdrawals = $query->latest()->get();

            $totalAmount = $withdrawals->sum('amount');

            $data = $withdrawals->map(function ($row) {

                return [
                    'id' => $row->id,
                    'type' => 'Withdrawal',
                    'method' => ucfirst($row->method),
                    'amount' => '-' . number_format($row->amount, 2, '.', ''),
                    'status' => ucfirst($row->status),
                    'remarks' => $row->remarks,
                    'date' => $row->created_at->format('Y-m-d'),
                    'time' => $row->created_at->format('h:i A'),
                    'date_time' => $row->created_at->format('Y-m-d H:i:s'),
                ];
            });

            return response()->json([
                'status' => true,
                'message' => 'Withdrawal history fetched successfully',
                'summary' => [
                    'records' => $withdrawals->count(),
                    'total_withdrawal' => '-' . number_format($totalAmount, 2, '.', ''),
                ],
                'data' => $data
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}
