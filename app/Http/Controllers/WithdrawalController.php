<?php

namespace App\Http\Controllers;

use App\Models\AppUser;
use App\Models\Country;
use App\Models\WithdrawalRequest;
use App\Models\ManualMoneyTransaction;
use App\Helper\Helper;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class WithdrawalController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {

            $query = WithdrawalRequest::with(['user.host.country', 'account', 'processedBy'])->latest();

            if ($request->filled('search_keyword')) {
                $keyword = $request->search_keyword;
                $query->where(function ($q) use ($keyword) {
                    $q->whereHas('user', function ($uq) use ($keyword) {
                        $uq->where('uid', $keyword);
                    })->orWhere('transaction_id', 'like', "%{$keyword}%");
                });
            }

            if ($request->filled('method')) {
                $query->where('method', $request->method);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('date_from')) {
                $query->whereDate('requested_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('requested_at', '<=', $request->date_to);
            }

            if ($request->filled('country_id')) {
                $countryId = $request->country_id;
                $query->whereHas('user.host.country', function ($q) use ($countryId) {
                    $q->where('id', $countryId);
                });
            }

            // Summaries
            $pendingCount = WithdrawalRequest::where('status', 'pending')->count();
            $approvedCount = WithdrawalRequest::where('status', 'approved')->count();
            $rejectedCount = WithdrawalRequest::where('status', 'rejected')->count();
            $totalAmountApproved = WithdrawalRequest::where('status', 'approved')->sum('amount');

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('user', function ($row) {
                    if (!$row->user) {
                        return '-';
                    }
                    $user = $row->user;
                    $image = $user->image ? Helper::showImage($user->image, true) : asset('assets/img/avatar.png');
                    $uidData = Helper::getDisplayUidData($user);

                    $badgeHtml = '';
                    if (!empty($uidData['badge'])) {
                        $badgeHtml = '<img src="' . $uidData['badge'] . '" width="16" height="16" style="vertical-align:middle;margin-right:4px;">';
                    }

                    if (!empty($uidData['uid']) && $uidData['uid'] != $uidData['system_uid']) {
                        $uidHtml = '<small class="d-flex align-items-center flex-wrap" style="gap:4px;">' . $badgeHtml . '<span style="color:' . ($uidData['badge_color'] ?? '#000') . ';font-weight:600;">' . e($uidData['uid']) . '</span><span class="text-muted">/</span><span class="text-muted">' . e($uidData['system_uid']) . '</span></small>';
                    } else {
                        $uidHtml = '<small class="text-muted">' . e($uidData['system_uid'] ?? $user->uid) . '</small>';
                    }

                    return '<div class="d-flex align-items-center gap-2 user-profile-trigger" data-user-id="' . $user->id . '" style="cursor:pointer;"><img src="' . $image . '" width="40" height="40" class="rounded-circle border"><div><div class="fw-bold">' . e($user->name) . '</div>' . $uidHtml . '</div></div>';
                })
                ->addColumn('country', function ($row) {
                    $country = optional(optional($row->user)->host)->country;
                    return $country ? e($country->name) : (optional($row->user)->country ?: '-');
                })
                ->addColumn('method', function ($row) {
                    if ($row->method == 'bank') {
                        return '<span class="badge bg-primary">Bank</span>';
                    }
                    return '<span class="badge bg-warning text-dark">USDT</span>';
                })
                ->editColumn('amount', function ($row) {
                    return '<span class="fw-bold text-success">$ ' . number_format($row->amount, 2) . '</span>';
                })
                ->addColumn('payment_details', function ($row) {
                    if (!$row->account) {
                        return '-';
                    }
                    $html = '';
                    if ($row->method == 'bank') {
                        $html .= '<div><small class="text-muted d-block">Bank:</small> <strong>' . e($row->account->bank_name) . '</strong></div>';
                        $html .= '<div><small class="text-muted d-block">A/C Name:</small> <strong>' . e($row->account->account_holder_name) . '</strong></div>';
                        $html .= '<div><small class="text-muted d-block">A/C No:</small> <strong>' . e($row->account->account_number) . '</strong></div>';
                        $html .= '<div><small class="text-muted d-block">IFSC:</small> <strong>' . e($row->account->ifsc_code) . '</strong></div>';
                    } else {
                        $html .= '<div><small class="text-muted d-block">Channel:</small> <strong>' . e($row->account->channel_name) . '</strong></div>';
                        $html .= '<div><small class="text-muted d-block">Address:</small> <strong>' . e($row->account->usdt_address) . '</strong></div>';
                    }
                    return $html;
                })
                ->editColumn('status', function ($row) {
                    if ($row->status == 'pending') {
                        return '<span class="badge bg-warning text-dark">Pending</span>';
                    } elseif ($row->status == 'approved') {
                        return '<span class="badge bg-success">Approved</span>';
                    } else {
                        return '<span class="badge bg-danger">Rejected</span>';
                    }
                })
                ->editColumn('requested_at', function ($row) {
                    return $row->requested_at ? Carbon::parse($row->requested_at)->format('d M Y h:i A') : '-';
                })
                ->addColumn('admin_log', function ($row) {
                    if ($row->status == 'pending') {
                        return '-';
                    }
                    $adminName = $row->processedBy ? e($row->processedBy->name) : 'Unknown';
                    $date = $row->processed_at ? Carbon::parse($row->processed_at)->format('d M Y h:i A') : '-';
                    $action = $row->status == 'approved' ? '<span class="text-success">Approved</span>' : '<span class="text-danger">Rejected</span>';
                    return '<div><small>By: <strong>'.$adminName.'</strong></small></div><div><small>Action: '.$action.'</small></div><div><small class="text-muted">'.$date.'</small></div>';
                })
                ->addColumn('action', function ($row) {
                    if ($row->status == 'pending') {
                        return '
                        <div class="dropdown">
                            <button class="btn btn-sm btn-link" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v"></i></button>
                            <div class="dropdown-menu">
                                <a href="javascript:void(0)" class="dropdown-item text-success action-btn" data-id="' . $row->id . '" data-type="approve" data-user="' . e($row->user->name ?? '-') . '" data-amount="' . $row->amount . '" data-method="' . $row->method . '" data-bs-toggle="modal" data-bs-target="#actionModal">
                                    <i class="fas fa-check-circle me-1"></i> Approve
                                </a>
                                <a href="javascript:void(0)" class="dropdown-item text-danger action-btn" data-id="' . $row->id . '" data-type="reject" data-user="' . e($row->user->name ?? '-') . '" data-amount="' . $row->amount . '" data-method="' . $row->method . '" data-bs-toggle="modal" data-bs-target="#actionModal">
                                    <i class="fas fa-times-circle me-1"></i> Reject
                                </a>
                            </div>
                        </div>';
                    } else {
                        return $row->status == 'approved' ? '<span class="badge bg-success">Approved</span>' : '<span class="badge bg-danger">Rejected</span>';
                    }
                })
                ->rawColumns(['user', 'method', 'amount', 'payment_details', 'status', 'admin_log', 'action'])
                ->with([
                    'summary' => [
                        'pending_count' => $pendingCount,
                        'approved_count' => $approvedCount,
                        'rejected_count' => $rejectedCount,
                        'total_approved_usd' => number_format($totalAmountApproved, 2, '.', '')
                    ]
                ])
                ->make(true);
        }

        $countries = Country::orderBy('name')->get(['id', 'name']);
        return view('withdrawal.index', compact('countries'));
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'action' => 'required|in:approve,reject',
            'transaction_id' => 'required_if:action,approve',
            'remarks' => 'required_if:action,reject'
        ]);

        DB::beginTransaction();
        try {
            $withdrawal = WithdrawalRequest::findOrFail($id);

            if ($withdrawal->status != 'pending') {
                return response()->json(['status' => false, 'message' => 'Request is already processed.']);
            }

            if ($request->action == 'approve') {
                $withdrawal->status = 'approved';
                $withdrawal->transaction_id = $request->transaction_id;
            } else {
                $withdrawal->status = 'rejected';
                $withdrawal->remarks = $request->remarks;

                // Restore user balance
                $user = AppUser::find($withdrawal->user_id);
                if ($user) {
                    $beforeBalance = $user->balance ?? 0;
                    $afterBalance = $beforeBalance + $withdrawal->amount;
                    $user->balance = $afterBalance;
                    $user->save();

                    ManualMoneyTransaction::create([
                        'user_id' => $user->id,
                        'admin_id' => Auth::id(),
                        'type' => 'credit',
                        'amount' => $withdrawal->amount,
                        'before_balance' => $beforeBalance,
                        'after_balance' => $afterBalance,
                        'reason' => 'Withdrawal Rejected Refund: ' . $request->remarks,
                    ]);
                }
            }

            $withdrawal->processed_at = now();
            $withdrawal->processed_by = Auth::id();
            $withdrawal->save();

            DB::commit();
            return response()->json(['status' => true, 'message' => 'Withdrawal request ' . $request->action . 'd successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }
}
