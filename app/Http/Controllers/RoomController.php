<?php

namespace App\Http\Controllers;

use App\Helper\Helper;
use App\Models\User;
use App\Models\AppUser;
use App\Models\RoomMusicPlaylist;
use App\Models\Room;
use App\Models\RoomMember;
use App\Models\RoomUserRole;
use App\Models\Theme;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class RoomController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {

            $query = Room::with('user')->withCount([
                'members as members_count' => function ($q) {
                    $q->whereNull('left_at');
                },
                'roomUserRoles as admins_count' => function ($q) {
                    $q->where('role', 'admin');
                }
            ])->latest();

            return DataTables::of($query)
                ->addIndexColumn()

                ->addColumn('room_owner', function ($row) {

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
                                class="rounded-circle"
                                width="40"
                                height="40">

                            <div>
                                <div class="fw-bold">' . e($user->name) . '</div>
                                ' . $uidHtml . '
                            </div>

                        </div>
                    ';
                })

                ->addColumn('room_info', function ($row) {
                    $image = $row->room_image
                        ? Helper::showImage($row->room_image, true)
                        : asset('assets/img/avatar.png');
                    return '
                        <div class="d-flex align-items-center gap-2"
                            data-user-id="' . $row->id . '"
                            style="cursor:pointer;">

                            <img src="' . $image . '"
                                 class="rounded-circle"
                                 width="40"
                                 height="40">
                            <div>
                                <div class="fw-bold">' . e($row->room_name ?? 'Room') . '</div>
                                <small class="text-muted">' . e($row->room_id) . '</small>
                            </div>
                        </div>
                ';
                })

                ->editColumn('room_number', function ($row) {
                    return '<span class="badge rounded-pill bg-secondary">' . ($row->room_seat ?? '-') . '</span>';
                })

                ->addColumn('room_members', function ($row) {
                    return '<span class="badge rounded-pill bg-info">' . $row->members_count . '</span>';
                })
                ->addColumn('admins', function ($row) {
                    return '<span class="badge rounded-pill bg-warning">'
                        . $row->admins_count .
                        '</span>';
                })

                // ->editColumn('status', function ($row) {
                //     return $row->status == 1
                //         ? '<span class="badge rounded-pill bg-success">Enable</span>'
                //         : '<span class="badge rounded-pill bg-danger">Disable</span>';
                // })

                ->addColumn('time', function ($row) {
                    return '
                        <div>
                            <div class="text-muted small">Creation time:</div>
                            <div>' . Carbon::parse($row->created_at)->format('Y-m-d') . '</div>
                            <div>' . Carbon::parse($row->created_at)->format('H:i:s') . '</div>
                        </div>
                    ';
                })

                ->editColumn('ban_status', function ($row) {

                    if ($row->is_banned == 1) {
                        return '<span class="badge rounded-pill bg-danger">
                            <i class="fas fa-ban me-1"></i> Banned
                        </span>';
                    }
                    if ($row->status == 1) {
                        return '<span class="badge rounded-pill bg-success">
                            <i class="fas fa-check-circle me-1"></i> Active
                        </span>';
                    }
                    return '<span class="badge rounded-pill bg-secondary">
                        <i class="fas fa-times-circle me-1"></i> Disabled
                    </span>';
                })
                ->addColumn('pin_status', function ($row) {

                    if ($row->is_pinned) {
                        return '<span class="badge bg-warning">
                                    <i class="fas fa-thumbtack me-1"></i> Pinned
                                </span>';
                    }

                    return '<span class="badge bg-light text-dark">
                                Not Pinned
                            </span>';
                })
                ->addColumn('action', function ($row) {

                    $btn = '<div class="dropdown">
                    <button class="btn btn-sm btn-link dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-ellipsis-h"></i>
                    </button>
                    <div class="dropdown-menu">';
                    if (Helper::userCan(129, 'can_edit')) {
                        $btn .= '
                            <button class="dropdown-item updateAdminLimit"
                                data-id="' . $row->id . '"
                                data-limit="' . $row->admin_limit . '">
                                <i class="fas fa-user-shield text-primary me-2"></i>
                                Update Admin Limit
                            </button>';
                    }
                    if (Helper::userCan(129, 'can_edit')) {
                        $btn .= '
                        <button class="dropdown-item updateMemberLimit"
                            data-id="' . $row->id . '"
                            data-limit="' . $row->member_limit . '">
                            <i class="fas fa-users text-success me-2"></i>
                            Update Member Limit
                        </button>';
                    }
                    if (Helper::userCan(129, 'can_edit')) {
                        if ($row->is_pinned) {

                            $btn .= '
                            <button class="dropdown-item text-success unpinRoom"
                                data-id="' . $row->id . '">
                                <i class="fas fa-thumbtack me-2"></i>
                                Unpin Room
                            </button>';
                        } else {

                            $btn .= '
                        <button class="dropdown-item text-warning pinRoom"
                            data-id="' . $row->id . '">
                            <i class="fas fa-thumbtack me-2"></i>
                            Pin Room
                        </button>';
                        }
                    }
                    if (Helper::userCan(129, 'can_edit')) {
                        if ($row->is_banned) {

                            $btn .= '
                            <button class="dropdown-item text-success unbanRoom"
                                data-id="' . $row->id . '">
                                <i class="fas fa-unlock me-2"></i>
                                Unban Room
                            </button>';
                        } else {

                            $btn .= '
                            <button class="dropdown-item text-warning banRoom"
                                data-id="' . $row->id . '">
                                <i class="fas fa-ban me-2"></i>
                                Ban Room
                            </button>';
                        }
                    }
                    if (Helper::userCan(129, 'can_view')) {
                        if ($row->is_banned) {
                            $btn .= '
                            <button class="dropdown-item accountProcessing"
                                data-id="' . $row->id . '">
                                <i class="fas fa-info-circle text-primary me-2"></i>
                                Account Processing
                            </button>';
                        }
                    }
                    if (Helper::userCan(129, 'can_edit')) {
                        $btn .= '
                            <button class="dropdown-item editRoomName"
                                data-id="' . $row->id . '"
                                data-name="' . e($row->room_name) . '">

                                <i class="fas fa-edit text-primary me-2"></i>
                                Edit Room Name

                            </button>
                        ';
                    }
                    if (Helper::userCan(129, 'can_delete')) {
                        $btn .= '
                        <button class="dropdown-item text-warning deleteRoomImage"
                            data-id="' . $row->id . '">
                            <i class="fas fa-image me-2"></i>
                            Delete Room Image
                        </button>';
                    }
                    if (Helper::userCan(129, 'can_view')) {
                        $btn .= '<a class="dropdown-item" href="' . route('room.view', $row->id) . '">
                    <i class="fas fa-eye text-info me-2"></i> View
                    </a>';
                    }
                    if (Helper::userCan(129, 'can_view')) {
                        $btn .= '<a class="dropdown-item" href="' . route('room.members', $row->id) . '">
                        <i class="fas fa-users text-success me-2"></i> Members
                    </a>';
                    }
                    if (Helper::userCan(129, 'can_delete')) {
                        $btn .= '<button class="dropdown-item text-danger delete" data-id="' . $row->id . '">
                        <i class="fas fa-trash me-2"></i> Delete
                        </button>';
                    }

                    $btn .= '</div></div>';

                    return $btn;
                })

                ->rawColumns(['ban_status', 'pin_status', 'room_owner', 'room_number', 'room_members', 'admins', 'room_info', 'time', 'status', 'action'])
                ->make(true);
        }

        $pinnedRooms = Room::with('user')
            ->where('is_pinned', 1)
            ->latest()
            ->take(3)
            ->get();

        return view('room.index', compact('pinnedRooms'));
    }

    public function delete(Request $request): JsonResponse
    {
        return Helper::deleteRecord(new Frame, $request->id);
    }




    public function members($room_id)
    {
        $room = Room::findOrFail($room_id);

        $members = RoomMember::with('user')
            ->where('room_id', $room_id)
            ->whereNull('left_at')
            ->latest()
            ->get();

        foreach ($members as $member) {

            if ($member->user_id == $room->user_id) {
                $member->role = 'Owner';
            } else {
                $role = \App\Models\RoomUserRole::where('room_id', $room_id)
                    ->where('user_id', $member->user_id)
                    ->value('role');

                $member->role = $role ? ucfirst($role) : 'Member';
            }
        }

        return view('room.members', compact('room', 'members'));
    }

    public function membersAjax(Request $request, $room_id)
    {
        $room = Room::findOrFail($room_id);

        $query = RoomMember::with('user')
            ->where('room_id', $room_id)
            ->whereNull('left_at')
            ->whereHas('user');

        if ($request->search) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('uid', 'like', '%' . $request->search . '%');
            });
        }

        $members = $query->paginate(10);

        $data = [];

        foreach ($members as $member) {

            if (!$member->user) {
                continue;
            }

            // Role logic
            if ($member->user_id == $room->user_id) {
                $role = 'Owner';
            } else {
                $roleData = RoomUserRole::where('room_id', $room_id)
                    ->where('user_id', $member->user_id)
                    ->value('role');

                $role = $roleData ? ucfirst($roleData) : 'Member';
            }

            $roleClass = $role == 'Owner' ? 'badge bg-danger' : ($role == 'Admin' ? 'badge bg-warning text-dark' : 'badge bg-secondary');

            $data[] = [
                'name' => $member->user->name,
                'uid' => $member->user->uid,
                'image' => $member->user->image
                    ? Helper::showImage($member->user->image, true)
                    : asset('assets/img/avatar.png'),
                'role' => $role,
                'role_class' => $roleClass,
                'joined_at' => Carbon::parse($member->joined_at)->format('d M Y, h:i A'),
            ];
        }

        return response()->json([
            'data' => $data,
            'pagination' => $members->links()->render(),
            'total' => $members->total(),
            'from' => $members->firstItem(),
            'to' => $members->lastItem(),
        ]);
    }

    public function view($id)
    {
        $room = Room::with('user')->findOrFail($id);

        return view('room.view', compact('room'));
    }


    public function userMusics(Request $request)
    {
        if ($request->ajax()) {

            $query = AppUser::with('musics')->has('musics')->latest();

            return DataTables::of($query)

                ->addColumn('user', function ($row) {

                    $image = $row->image
                        ? Helper::showImage($row->image, true)
                        : asset('assets/img/avatar.png');

                    $uidData = Helper::getDisplayUidData($row);

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
                                ' . e($uidData['system_uid'] ?? $row->uid) . '
                            </small>';
                    }

                    return '
                        <div class="user-box user-profile-trigger"
                            data-user-id="' . $row->id . '"
                            style="cursor:pointer;">

                            <img src="' . $image . '">

                            <div class="user-info">
                                <div class="name">' . e($row->name) . '</div>
                                ' . $uidHtml . '
                            </div>

                        </div>
                    ';
                })

                ->addColumn('total_music', function ($row) {
                    return '
                        <span class="music-badge">
                            🎵 ' . $row->musics->count() . ' Songs
                        </span>
                    ';
                })

                ->addColumn('musics', function ($row) {

                    if (!Helper::userCan(129, 'can_view')) {
                        return '-';
                    }

                    return '
                            <button class="btn-playlist view-musics"
                                    data-user="' . $row->id . '">
                                <i class="fas fa-music me-1"></i>
                                View Playlist
                            </button>
                        ';
                })

                ->rawColumns(['user', 'total_music', 'musics'])

                ->filterColumn('user', function ($query, $keyword) {
                    $query->where(function ($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%")->orWhere('uid', 'like', "%{$keyword}%");
                    });
                })

                ->make(true);
        }

        return view('room.user_music');
    }

    public function getUserMusicList($id)
    {
        $musics = RoomMusicPlaylist::where('user_id', $id)
            ->select('title', 'artist', 'audio_url')
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'data' => $musics
        ]);
    }

    public function userThemes(Request $request)
    {
        if ($request->ajax()) {

            $query = Room::with(['user'])->whereNotNull('active_theme_id')->latest();

            return DataTables::of($query)

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
                        <div class="d-flex align-items-center user-profile-trigger"
                            data-user-id="' . $user->id . '"
                            style="cursor:pointer;">

                            <img src="' . $image . '"
                                width="50"
                                height="50"
                                class="rounded-circle me-2">

                            <div>
                                <div class="fw-bold">' . e($user->name) . '</div>
                                ' . $uidHtml . '
                            </div>

                        </div>
                    ';
                })

                ->addColumn('theme', function ($row) {

                    if (!$row->active_theme_id) {
                        return '-';
                    }

                    $theme = Theme::find($row->active_theme_id);

                    if (!$theme) {
                        return '-';
                    }

                    return '
                        <div class="d-flex align-items-center">
                            <img src="' . Helper::showImage($theme->icon, true) . '"
                                 width="60"
                                 height="60"
                                 class="rounded me-2">

                            <div>
                                <div class="fw-bold">' . $theme->name . '</div>
                            </div>
                        </div>
                    ';
                })

                ->addColumn('room', function ($row) {

                    return '
                        <div>
                            <div class="fw-bold">' . $row->room_name . '</div>
                            <small>' . $row->room_id . '</small>
                        </div>
                    ';
                })

                ->filterColumn('user', function ($query, $keyword) {

                    $query->where(function ($q) use ($keyword) {

                        $q->where('name', 'like', "%{$keyword}%")
                            ->orWhere('uid', 'like', "%{$keyword}%");
                    });
                })

                ->addColumn('activated_at', function ($row) {
                    return Carbon::parse($row->updated_at)->timezone('Asia/Kolkata')->format('d M Y h:i A');
                })

                ->rawColumns(['user', 'theme', 'room', 'activated_at'])
                ->make(true);
        }

        return view('room.user_theme');
    }

    public function banRoom(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $room = Room::findOrFail($id);

        $room->update([
            'is_banned' => 1,
            'ban_reason' => $request->reason,
            'action_by' => auth()->id(),
            'banned_at' => now(),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Room banned successfully.',
        ]);
    }

    public function unbanRoom($id)
    {
        $room = Room::findOrFail($id);

        $room->update([
            'is_banned' => 0,
            'ban_reason' => null,
            'action_by' => null,
            'banned_at' => null,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Room unbanned successfully.',
        ]);
    }

    public function accountProcessing($id)
    {
        $room = Room::findOrFail($id);

        $admin = User::find($room->action_by);

        return response()->json([
            'status' => true,
            'data' => [
                'room_name'   => $room->room_name,
                'room_id'     => $room->room_id,
                'status'      => $room->is_banned ? 'Banned' : 'Active',
                'reason'      => $room->ban_reason,
                'action_by'   => $admin->name ?? '-',
                'action_date' => $room->banned_at
                    ? Carbon::parse($room->banned_at)->format('d M Y h:i A')
                    : '-',
            ]
        ]);
    }

    public function deleteRoomImage($id)
    {
        $room = Room::find($id);

        if (!$room) {
            return response()->json([
                'status' => false,
                'message' => 'Room not found.'
            ], 404);
        }

        if ($room->room_image) {

            $path = storage_path('app/public/' . $room->room_image);

            if (file_exists($path)) {
                @unlink($path);
            }

            $publicPath = public_path('storage/' . $room->room_image);

            if (file_exists($publicPath)) {
                @unlink($publicPath);
            }

            $room->update([
                'room_image' => null
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Room image removed successfully.'
        ]);
    }

    public function pinRoom($id)
    {
        $room = Room::findOrFail($id);

        if ($room->is_pinned) {
            return response()->json([
                'status' => false,
                'message' => 'Room is already pinned.'
            ], 422);
        }

        $pinnedCount = Room::where('is_pinned', 1)->count();

        if ($pinnedCount >= 3) {
            return response()->json([
                'status' => false,
                'message' => 'Only 3 rooms can be pinned. Please unpin one room first.'
            ], 422);
        }

        $room->update([
            'is_pinned' => 1,
            'pinned_at' => now(),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Room pinned successfully.'
        ]);
    }

    public function unpinRoom($id)
    {
        $room = Room::findOrFail($id);

        $room->update([
            'is_pinned' => 0,
            'pinned_at' => null,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Room unpinned successfully.'
        ]);
    }

    public function customThemeRequests(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {

            $query = Theme::with('user')
                ->whereNotNull('user_id')
                ->latest();

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
                                style="vertical-align:middle;margin-right:4px;">';
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
                                ' . e($uidData['system_uid']) . '
                            </small>';
                    }

                    return '
                        <div class="d-flex align-items-center gap-2 user-profile-trigger"
                            data-user-id="' . $user->id . '"
                            style="cursor:pointer;">

                            <img src="' . $image . '"
                                width="45"
                                height="45"
                                class="rounded-circle">

                            <div>
                                <div class="fw-bold">' . e($user->name) . '</div>
                                ' . $uidHtml . '
                            </div>

                        </div>';
                })

                ->addColumn('theme', function ($row) {

                    $image = $row->icon
                        ? Helper::showImage($row->icon, true)
                        : asset('assets/img/no-image.png');

                    return '
                    <div class="d-flex align-items-center gap-2">

                        <img src="' . $image . '"
                            width="45"
                            height="45"
                            class="rounded">

                        <div>
                            <div class="fw-bold">' . e($row->name) . '</div>
                        </div>

                    </div>
                ';
                })

                ->editColumn('status', function ($row) {
                    if ($row->status == 1) {
                        return '<span class="badge bg-success"> Approved </span>';
                    }
                    if ($row->status == 2) {
                        return '<span class="badge bg-danger"> Rejected </span>';
                    }
                    return '<span class="badge bg-warning"> Pending </span>';
                })

                ->addColumn('time', function ($row) {

                    return '
                    <div>
                        <div>' . optional($row->created_at)->format('d M Y') . '</div>
                        <small class="text-muted">'
                        . optional($row->created_at)->format('h:i A') .
                        '</small>
                    </div>
                ';
                })

                ->addColumn('action', function ($row) {

                    if (
                        !Helper::userCan(134, 'can_edit') &&
                        !Helper::userCan(134, 'can_delete')
                    ) {
                        return '-';
                    }

                    $btn = '
                            <div class="dropdown">

                                <button class="btn btn-sm btn-link text-dark p-0"
                                    type="button"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false">

                                    <i class="fas fa-ellipsis-v"></i>

                                </button>

                                <div class="dropdown-menu dropdown-menu-end">';

                    // Pending
                    if ($row->status == 0) {

                        if (Helper::userCan(134, 'can_edit')) {
                            $btn .= '
                                    <button class="dropdown-item approveTheme"
                                        data-id="' . $row->id . '">
                                        <i class="fas fa-check text-success me-2"></i>
                                        Approve
                                    </button>

                                    <button class="dropdown-item rejectTheme"
                                        data-id="' . $row->id . '">
                                        <i class="fas fa-times text-danger me-2"></i>
                                        Reject
                                    </button>';
                        }
                    }

                    // Approved
                    elseif ($row->status == 1) {

                        if (Helper::userCan(134, 'can_edit')) {
                            $btn .= '
                                    <button class="dropdown-item rejectTheme"
                                        data-id="' . $row->id . '">
                                        <i class="fas fa-times text-danger me-2"></i>
                                        Reject
                                    </button>';
                        }
                    }

                    // Rejected
                    elseif ($row->status == 2) {

                        if (Helper::userCan(134, 'can_edit')) {
                            $btn .= '
                                    <button class="dropdown-item approveTheme"
                                        data-id="' . $row->id . '">
                                        <i class="fas fa-check text-success me-2"></i>
                                        Approve
                                    </button>';
                        }
                    }

                    // Delete
                    if (Helper::userCan(134, 'can_delete')) {
                        $btn .= '
                                <div class="dropdown-divider"></div>

                                <button class="dropdown-item text-danger delete"
                                    data-id="' . $row->id . '">
                                    <i class="fas fa-trash text-danger me-2"></i>
                                    Delete
                                </button>';
                    }

                    $btn .= '
                            </div>
                        </div>';

                    return $btn;
                })

                ->rawColumns([
                    'user',
                    'theme',
                    'status',
                    'action',
                    'time'
                ])

                ->make(true);
        }

        return view('room.custom_theme_list');
    }

    public function approveTheme($id)
    {
        $theme = Theme::find($id);

        if (!$theme) {
            return response()->json([
                'status' => false,
                'message' => 'Theme not found.'
            ], 404);
        }

        $theme->update([
            'status' => 1,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Theme approved successfully.'
        ]);
    }

    public function rejectTheme($id)
    {
        $theme = Theme::find($id);

        if (!$theme) {
            return response()->json([
                'status' => false,
                'message' => 'Theme not found.'
            ], 404);
        }

        $theme->update([
            'status' => 2,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Theme rejected successfully.'
        ]);
    }

    public function deleteTheme(Request $request): JsonResponse
    {
        return Helper::deleteRecord(new Theme, $request->id);
    }

    public function updateAdminLimit(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:rooms,id',
            'admin_limit' => 'required|integer|min:0',
        ]);

        $room = Room::find($request->id);

        $room->update([
            'admin_limit' => $request->admin_limit,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Admin limit updated successfully.'
        ]);
    }

    public function updateMemberLimit(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:rooms,id',
            'member_limit' => 'required|integer|min:0',
        ]);

        $room = Room::find($request->id);

        $room->update([
            'member_limit' => $request->member_limit,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Member limit updated successfully.'
        ]);
    }

    public function updateRoomName(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:rooms,id',
            'room_name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $room = Room::find($request->id);

        if (!$room) {
            return response()->json([
                'status' => false,
                'message' => 'Room not found.',
            ], 404);
        }

        $room->room_name = trim($request->room_name);
        $room->save();

        return response()->json([
            'status' => true,
            'message' => 'Room name updated successfully.',
            'room_name' => $room->room_name,
        ]);
    }
}
