<?php

namespace App\Http\Controllers;

use App\Models\UserBlock;
use App\Models\Room;
use App\Models\AppUser;
use App\Models\User;
use App\Models\Country;
use App\Helper\Helper;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Yajra\DataTables\Facades\DataTables;

class RoomKickLogController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {

            $query = UserBlock::with(['room', 'blockedUser', 'blockerUser', 'blockerAdmin'])->latest();

            // Search keyword: Room Name, Room ID, Kicked User Name, Kicked User UID, Kicker Name, Kicker UID
            if ($request->filled('search_keyword')) {
                $kw = trim($request->search_keyword);
                $query->where(function ($q) use ($kw) {
                    $q->whereHas('room', function ($rq) use ($kw) {
                        $rq->where('room_name', 'like', "%{$kw}%")
                           ->orWhere('room_id', 'like', "%{$kw}%");
                    })
                    ->orWhereHas('blockedUser', function ($uq) use ($kw) {
                        $uq->where('name', 'like', "%{$kw}%")
                           ->orWhere('uid', 'like', "%{$kw}%");
                    })
                    ->orWhereHas('blockerUser', function ($kq) use ($kw) {
                        $kq->where('name', 'like', "%{$kw}%")
                           ->orWhere('uid', 'like', "%{$kw}%");
                    })
                    ->orWhereHas('blockerAdmin', function ($aq) use ($kw) {
                        $aq->where('name', 'like', "%{$kw}%")
                           ->orWhere('email', 'like', "%{$kw}%");
                    });
                });
            }

            // Filter: Country
            if ($request->filled('country_id')) {
                $country = Country::find($request->country_id);
                if ($country) {
                    $countryName = $country->name;
                    $query->where(function ($q) use ($countryName) {
                        $q->whereHas('blockedUser', function ($uq) use ($countryName) {
                            $uq->where('country', 'like', "%{$countryName}%");
                        })
                        ->orWhereHas('room', function ($rq) use ($countryName) {
                            $rq->where('country', 'like', "%{$countryName}%");
                        });
                    });
                }
            }

            // Filter: Date Range
            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            $totalKicks  = (clone $query)->count();
            $todayKicks  = (clone $query)->whereDate('created_at', Carbon::today())->count();
            $monthKicks  = (clone $query)->whereMonth('created_at', Carbon::now()->month)->count();

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('room_info', function ($row) {
                    if (!$row->room) {
                        return '<span class="text-muted">Room #' . e($row->room_id) . '</span>';
                    }
                    $image = $row->room->room_image
                        ? Helper::showImage($row->room->room_image, true)
                        : asset('assets/img/avatar.png');
                    return '<div class="d-flex align-items-center gap-2">
                        <img src="' . $image . '" width="38" height="38" class="rounded-circle border">
                        <div>
                            <div class="fw-bold text-dark">' . e($row->room->room_name ?? 'Room') . '</div>
                            <small class="text-muted"><i class="fas fa-door-open me-1"></i>ID: ' . e($row->room->room_id) . '</small>
                        </div>
                    </div>';
                })
                ->addColumn('kicked_user', function ($row) {
                    if (!$row->blockedUser) {
                        return '<span class="text-muted">User #' . e($row->blocked_user_id) . '</span>';
                    }
                    $user = $row->blockedUser;
                    $image = $user->image ? Helper::showImage($user->image, true) : asset('assets/img/avatar.png');
                    $uidData = Helper::getDisplayUidData($user);
                    $badgeHtml = '';
                    if (!empty($uidData['badge'])) {
                        $badgeHtml = '<img src="' . $uidData['badge'] . '" width="16" height="16" style="vertical-align:middle;margin-right:3px;">';
                    }
                    $uidText = !empty($uidData['uid']) ? $uidData['uid'] : ($user->uid ?? '-');
                    return '<div class="d-flex align-items-center gap-2">
                        <img src="' . $image . '" width="38" height="38" class="rounded-circle border">
                        <div>
                            <div class="fw-bold text-dark">' . e($user->name) . '</div>
                            <small class="text-muted">' . $badgeHtml . 'ID: ' . e($uidText) . '</small>
                        </div>
                    </div>';
                })
                ->addColumn('kicked_by', function ($row) {
                    if ($row->blockerUser) {
                        $kicker = $row->blockerUser;
                        $image = $kicker->image ? Helper::showImage($kicker->image, true) : asset('assets/img/avatar.png');
                        return '<div class="d-flex align-items-center gap-2">
                            <img src="' . $image . '" width="36" height="36" class="rounded-circle border">
                            <div>
                                <div class="fw-bold text-dark">' . e($kicker->name) . '</div>
                                <small class="text-muted">ID: ' . e($kicker->uid ?? $kicker->id) . '</small>
                            </div>
                        </div>';
                    } elseif ($row->blockerAdmin) {
                        $admin = $row->blockerAdmin;
                        return '<div class="d-flex align-items-center gap-2">
                            <span class="badge bg-primary-soft text-primary p-2 rounded-circle"><i class="fas fa-user-shield"></i></span>
                            <div>
                                <div class="fw-bold text-primary">' . e($admin->name) . '</div>
                                <small class="text-muted"><i class="fas fa-shield-alt me-1"></i>Admin</small>
                            </div>
                        </div>';
                    }
                    return '<span class="text-muted">User #' . e($row->blocker_id) . '</span>';
                })
                ->addColumn('country', function ($row) {
                    $c = $row->blockedUser->country ?? ($row->room->country ?? '-');
                    return '<span class="fw-semibold text-dark"><i class="fas fa-globe me-1 text-muted"></i>' . e($c) . '</span>';
                })
                ->editColumn('reason', function ($row) {
                    if ($row->expires_at) {
                        $exp = Carbon::parse($row->expires_at);
                        if ($exp->isPast()) {
                            return '<span class="badge bg-secondary">Expired (' . $exp->format('d-m-Y') . ')</span>';
                        } else {
                            return '<span class="badge bg-danger">Blocked till ' . $exp->format('d-m-Y H:i') . '</span>';
                        }
                    }
                    return '<span class="badge bg-warning text-dark"><i class="fas fa-ban me-1"></i>Room Kick / Ban</span>';
                })
                ->editColumn('created_at', function ($row) {
                    return '<div>
                        <div class="fw-semibold text-dark">' . Carbon::parse($row->created_at)->format('d-m-Y') . '</div>
                        <small class="text-muted">' . Carbon::parse($row->created_at)->format('h:i A') . '</small>
                    </div>';
                })
                ->addColumn('action', function ($row) {
                    $reasonText = 'Room Kick / Ban';
                    if ($row->expires_at) {
                        $reasonText .= ' (Expires: ' . Carbon::parse($row->expires_at)->format('d-m-Y H:i') . ')';
                    }

                    $jsonRow = htmlspecialchars(json_encode([
                        'id'              => $row->id,
                        'room_name'       => $row->room->room_name ?? ('Room #' . $row->room_id),
                        'room_id'         => $row->room->room_id ?? $row->room_id,
                        'user_name'       => $row->blockedUser->name ?? ('User #' . $row->blocked_user_id),
                        'user_uid'        => $row->blockedUser->uid ?? $row->blocked_user_id,
                        'user_country'    => $row->blockedUser->country ?? 'N/A',
                        'kicker_name'     => $row->blockerUser->name ?? ($row->blockerAdmin->name ?? ('User #' . $row->blocker_id)),
                        'kicker_uid'      => $row->blockerUser->uid ?? ($row->blockerAdmin->email ?? $row->blocker_id),
                        'reason'          => $reasonText,
                        'created_at'      => Carbon::parse($row->created_at)->format('d-m-Y, h:i A'),
                    ]), ENT_QUOTES, 'UTF-8');

                    return '<div class="d-flex gap-1 justify-content-center">
                        <button class="btn btn-sm btn-outline-info btn-view-detail" data-info=\'' . $jsonRow . '\' title="View Details">
                            <i class="fas fa-eye"></i> Details
                        </button>
                        <button class="btn btn-sm btn-outline-danger btn-delete-log" data-id="' . $row->id . '" title="Remove Kick Log">
                            <i class="fas fa-trash-alt"></i> Remove
                        </button>
                    </div>';
                })
                ->rawColumns(['room_info', 'kicked_user', 'kicked_by', 'country', 'reason', 'created_at', 'action'])
                ->with([
                    'summary' => [
                        'total_kicks' => $totalKicks,
                        'today_kicks' => $todayKicks,
                        'month_kicks' => $monthKicks,
                    ]
                ])
                ->make(true);
        }

        $countries = Country::orderBy('name')->get(['id', 'name']);
        return view('room_kick_log.index', compact('countries'));
    }

    public function show($id): JsonResponse
    {
        $log = UserBlock::with(['room', 'blockedUser', 'blockerUser', 'blockerAdmin'])->find($id);

        if (!$log) {
            return response()->json(['status' => false, 'message' => 'Kick log entry not found.']);
        }

        $reasonText = 'Room Kick / Ban';
        if ($log->expires_at) {
            $reasonText .= ' (Expires: ' . Carbon::parse($log->expires_at)->format('d-m-Y H:i') . ')';
        }

        return response()->json([
            'status' => true,
            'data'   => [
                'id'           => $log->id,
                'room_name'    => $log->room->room_name ?? ('Room #' . $log->room_id),
                'room_id'      => $log->room->room_id ?? $log->room_id,
                'user_name'    => $log->blockedUser->name ?? ('User #' . $log->blocked_user_id),
                'user_uid'     => $log->blockedUser->uid ?? $log->blocked_user_id,
                'user_country' => $log->blockedUser->country ?? 'N/A',
                'kicker_name'  => $log->blockerUser->name ?? ($log->blockerAdmin->name ?? ('User #' . $log->blocker_id)),
                'kicker_uid'   => $log->blockerUser->uid ?? ($log->blockerAdmin->email ?? $log->blocker_id),
                'reason'       => $reasonText,
                'created_at'   => Carbon::parse($log->created_at)->format('d-m-Y, h:i A'),
            ]
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $log = UserBlock::find($id);

        if (!$log) {
            return response()->json(['status' => false, 'message' => 'Kick log entry not found.']);
        }

        $log->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Kick log entry removed successfully.'
        ]);
    }
}
