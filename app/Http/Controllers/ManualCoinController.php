<?php

namespace App\Http\Controllers;

use App\Models\AppUser;
use App\Models\Country;
use App\Models\ManualCoinTransaction;
use App\Helper\Helper;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class ManualCoinController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {

            $query = ManualCoinTransaction::with(['user', 'admin'])->latest();

            // Search by user name / uid / txn id
            if ($request->filled('search_keyword')) {
                $kw = $request->search_keyword;
                $query->where(function ($q) use ($kw) {
                    $q->whereHas('user', function ($uq) use ($kw) {
                        $uq->where('uid', $kw)->orWhere('name', 'like', "%{$kw}%");
                    })->orWhere('transaction_id', 'like', "%{$kw}%");
                });
            }

            if ($request->filled('action')) {
                $query->where('action', $request->action);
            }

            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            $totalSent   = (clone $query)->where('action', 'send')->sum('coins');
            $totalDeduct = (clone $query)->where('action', 'deduct')->sum('coins');
            $totalTxns   = (clone $query)->count();

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('user_info', function ($row) {
                    if (!$row->user) return '-';
                    $user  = $row->user;
                    $image = $user->image ? Helper::showImage($user->image, true) : asset('assets/img/avatar.png');
                    $uidData   = Helper::getDisplayUidData($user);
                    $badgeHtml = '';
                    if (!empty($uidData['badge'])) {
                        $badgeHtml = '<img src="' . $uidData['badge'] . '" width="16" height="16" style="vertical-align:middle;margin-right:3px;">';
                    }
                    if (!empty($uidData['uid']) && $uidData['uid'] != $uidData['system_uid']) {
                        $uidHtml = '<small class="d-flex align-items-center flex-wrap" style="gap:3px;">'
                            . $badgeHtml
                            . '<span style="color:' . ($uidData['badge_color'] ?? '#000') . ';font-weight:600;">' . e($uidData['uid']) . '</span>'
                            . '<span class="text-muted">/</span>'
                            . '<span class="text-muted">' . e($uidData['system_uid']) . '</span>'
                            . '</small>';
                    } else {
                        $uidHtml = '<small class="text-muted">' . e($uidData['system_uid'] ?? $user->uid) . '</small>';
                    }
                    return '<div class="d-flex align-items-center gap-2"><img src="' . $image . '" width="38" height="38" class="rounded-circle border"><div><div class="fw-bold">' . e($user->name) . '</div>' . $uidHtml . '</div></div>';
                })
                ->addColumn('action_badge', function ($row) {
                    return $row->action === 'send'
                        ? '<span class="badge bg-success"><i class="fas fa-plus me-1"></i>Send</span>'
                        : '<span class="badge bg-danger"><i class="fas fa-minus me-1"></i>Deduct</span>';
                })
                ->addColumn('coins_formatted', function ($row) {
                    $icon  = $row->action === 'send' ? 'text-success' : 'text-danger';
                    $prefix = $row->action === 'send' ? '+' : '-';
                    return '<span class="fw-bold ' . $icon . '"><i class="fas fa-coins me-1"></i>' . $prefix . number_format($row->coins) . '</span>';
                })
                ->addColumn('balance_change', function ($row) {
                    return '<small class="text-muted">' . number_format($row->before_coins) . ' → <strong>' . number_format($row->after_coins) . '</strong></small>';
                })
                ->addColumn('admin_name', function ($row) {
                    return $row->admin ? '<span class="fw-semibold text-primary">' . e($row->admin->name) . '</span>' : '-';
                })
                ->editColumn('created_at', function ($row) {
                    return Carbon::parse($row->created_at)->format('d M Y h:i A');
                })
                ->rawColumns(['user_info', 'action_badge', 'coins_formatted', 'balance_change', 'admin_name'])
                ->with([
                    'summary' => [
                        'total_sent'   => number_format($totalSent),
                        'total_deduct' => number_format($totalDeduct),
                        'total_txns'   => $totalTxns,
                    ]
                ])
                ->make(true);
        }

        return view('manual_coins.index');
    }

    /**
     * Search user by UID – returns user info for lookup modal
     */
    public function searchUser(Request $request)
    {
        $request->validate(['uid' => 'required']);

        $user = AppUser::where('uid', $request->uid)
            ->with('coinSeller', 'agency', 'host')
            ->first();

        if (!$user) {
            return response()->json(['status' => false, 'message' => 'User not found.']);
        }

        // Only Normal Users allowed
        if ($user->coinSeller || $user->agency) {
            return response()->json(['status' => false, 'message' => 'Only Normal Users are allowed. Sellers, Merchants and Agencies cannot be selected.']);
        }

        return response()->json([
            'status' => true,
            'user'   => [
                'id'           => $user->id,
                'name'         => $user->name,
                'uid'          => $user->uid,
                'total_points' => (int) $user->total_points,
                'image'        => $user->image ? Helper::showImage($user->image, true) : asset('assets/img/avatar.png'),
            ]
        ]);
    }

    /**
     * Process send or deduct
     */
    public function process(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:app_users,id',
            'action'  => 'required|in:send,deduct',
            'coins'   => 'required|integer|min:1',
            'reason'  => 'required|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            $user = AppUser::lockForUpdate()->find($request->user_id);

            if (!$user) {
                return response()->json(['status' => false, 'message' => 'User not found.']);
            }

            // Validate normal user again server-side
            if ($user->coinSeller || $user->agency) {
                return response()->json(['status' => false, 'message' => 'Only Normal Users are allowed.']);
            }

            $before = (int) $user->total_points;

            if ($request->action === 'deduct') {
                if ($request->coins > $before) {
                    return response()->json(['status' => false, 'message' => 'Deduct amount exceeds user coin balance (' . number_format($before) . ' coins).']);
                }
                $after = $before - $request->coins;
            } else {
                $after = $before + $request->coins;
            }

            $user->total_points = $after;
            $user->save();

            ManualCoinTransaction::create([
                'user_id'        => $user->id,
                'admin_id'       => Auth::id(),
                'transaction_id' => 'MCT-' . strtoupper(Str::random(10)),
                'action'         => $request->action,
                'coins'          => $request->coins,
                'before_coins'   => $before,
                'after_coins'    => $after,
                'reason'         => $request->reason,
            ]);

            DB::commit();

            $actionWord = $request->action === 'send' ? 'sent' : 'deducted';
            return response()->json([
                'status'       => true,
                'message'      => number_format($request->coins) . ' coins ' . $actionWord . ' successfully.',
                'after_coins'  => $after,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }
}
