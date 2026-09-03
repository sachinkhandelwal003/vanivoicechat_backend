<?php

namespace App\Http\Controllers;

use App\Models\ExchangeHistory;
use App\Models\Country;
use App\Models\Agency;
use App\Models\Host;
use App\Models\CoinSeller;
use App\Helper\Helper;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ExchangeLogController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {

            $query = ExchangeHistory::with(['user.host.country', 'user.agency', 'user.coinSeller'])->latest();

            // Search by User Name/ID or Transaction ID
            if ($request->filled('search_keyword')) {
                $keyword = $request->search_keyword;
                $query->where(function ($q) use ($keyword) {
                    $q->whereHas('user', function ($uq) use ($keyword) {
                        $uq->where('uid', $keyword)
                           ->orWhere('name', 'like', "%{$keyword}%");
                    })->orWhere('transaction_id', 'like', "%{$keyword}%");
                });
            }

            // Role filter
            if ($request->filled('role')) {
                $role = $request->role;
                if ($role === 'host') {
                    $query->whereHas('user.host');
                } elseif ($role === 'agency') {
                    $query->whereHas('user.agency');
                } elseif ($role === 'merchant') {
                    $query->whereHas('user.coinSeller', function ($q) {
                        $q->where('is_merchant', 1);
                    });
                } elseif ($role === 'seller') {
                    $query->whereHas('user.coinSeller', function ($q) {
                        $q->where('is_merchant', 0);
                    });
                } elseif ($role === 'normal') {
                    $query->whereDoesntHave('user.host')
                          ->whereDoesntHave('user.agency')
                          ->whereDoesntHave('user.coinSeller');
                }
            }

            // Country filter
            if ($request->filled('country_id')) {
                $countryId = $request->country_id;
                $query->whereHas('user.host.country', function ($q) use ($countryId) {
                    $q->where('id', $countryId);
                });
            }

            // Status filter
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            // Date range filter
            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // Summary stats
            $totalExchanges   = (clone $query)->count();
            $totalUsd         = (clone $query)->sum('usd_amount');
            $totalCoins       = (clone $query)->sum('coins_received');
            $successCount     = (clone $query)->where('status', 'success')->count();

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('user_info', function ($row) {
                    if (!$row->user) return '-';
                    $user  = $row->user;
                    $image = $user->image
                        ? Helper::showImage($user->image, true)
                        : asset('assets/img/avatar.png');
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
                    return '<div class="d-flex align-items-center gap-2 user-profile-trigger" data-user-id="' . $user->id . '" style="cursor:pointer;">'
                        . '<img src="' . $image . '" width="38" height="38" class="rounded-circle border">'
                        . '<div><div class="fw-bold">' . e($user->name) . '</div>' . $uidHtml . '</div>'
                        . '</div>';
                })
                ->addColumn('role', function ($row) {
                    if (!$row->user) return '-';
                    $user = $row->user;
                    if ($user->coinSeller && $user->coinSeller->is_merchant) {
                        return '<span class="badge bg-dark">Merchant</span>';
                    }
                    if ($user->coinSeller) {
                        return '<span class="badge bg-secondary">Seller</span>';
                    }
                    if ($user->agency) {
                        return '<span class="badge bg-success">Agency</span>';
                    }
                    if ($user->host) {
                        return '<span class="badge bg-warning text-dark">Host</span>';
                    }
                    return '<span class="badge bg-info text-dark">Normal User</span>';
                })
                ->addColumn('country', function ($row) {
                    $country = optional(optional($row->user)->host)->country;
                    return $country ? e($country->name) : (optional($row->user)->country ?: '-');
                })
                ->editColumn('usd_amount', function ($row) {
                    return '<span class="fw-bold text-success">$ ' . number_format($row->usd_amount, 2) . '</span>';
                })
                ->editColumn('exchange_rate', function ($row) {
                    return '<span class="text-primary fw-semibold">1 USD = ' . number_format($row->exchange_rate, 0) . ' coins</span>';
                })
                ->editColumn('coins_received', function ($row) {
                    return '<span class="fw-bold text-warning"><i class="fas fa-coins me-1"></i>' . number_format($row->coins_received, 0) . '</span>';
                })
                ->editColumn('wallet_type', function ($row) {
                    return $row->wallet_type
                        ? '<span class="badge bg-light text-dark border">' . ucfirst($row->wallet_type) . '</span>'
                        : '<span class="badge bg-light text-dark border">Main</span>';
                })
                ->editColumn('transaction_id', function ($row) {
                    return $row->transaction_id
                        ? '<code class="text-muted small">' . e($row->transaction_id) . '</code>'
                        : '-';
                })
                ->editColumn('status', function ($row) {
                    return $row->status === 'success'
                        ? '<span class="badge bg-success"><i class="fas fa-check me-1"></i>Success</span>'
                        : '<span class="badge bg-danger"><i class="fas fa-times me-1"></i>Failed</span>';
                })
                ->editColumn('created_at', function ($row) {
                    return Carbon::parse($row->created_at)->format('d M Y h:i A');
                })
                ->addColumn('action', function ($row) {
                    return '<button class="btn btn-sm btn-outline-primary btn-view-detail"
                        data-id="' . $row->id . '"
                        data-user="' . e(optional($row->user)->name) . '"
                        data-uid="' . e(optional($row->user)->uid) . '"
                        data-usd="' . $row->usd_amount . '"
                        data-rate="' . $row->exchange_rate . '"
                        data-coins="' . $row->coins_received . '"
                        data-txn="' . e($row->transaction_id) . '"
                        data-wallet="' . e($row->wallet_type) . '"
                        data-status="' . $row->status . '"
                        data-date="' . Carbon::parse($row->created_at)->format('d M Y h:i A') . '"
                        data-bs-toggle="modal" data-bs-target="#detailModal">
                        <i class="fas fa-eye"></i> View
                    </button>';
                })
                ->rawColumns(['user_info', 'role', 'usd_amount', 'exchange_rate', 'coins_received', 'wallet_type', 'transaction_id', 'status', 'action'])
                ->with([
                    'summary' => [
                        'total_exchanges'  => $totalExchanges,
                        'total_usd'        => number_format($totalUsd, 2),
                        'total_coins'      => number_format($totalCoins, 0),
                        'success_count'    => $successCount,
                    ]
                ])
                ->make(true);
        }

        $countries = Country::orderBy('name')->get(['id', 'name']);
        return view('exchange_log.index', compact('countries'));
    }
}

