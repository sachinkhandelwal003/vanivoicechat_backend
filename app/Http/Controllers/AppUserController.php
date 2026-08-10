<?php

namespace App\Http\Controllers;

use App\Models\AppUser;
use App\Models\Theme;
use App\Models\Frame;
use App\Models\EntryTag;
use App\Models\Voice;
use App\Models\DataCard;
use App\Models\ChatBubble;
use App\Models\Cars;
use App\Models\Gift;
use App\Models\Vip;
use App\Models\StoreUids;
use App\Models\PostReport;
use App\Models\Country;
use App\Models\User;
use App\Models\WcLevel;
use App\Models\UserAlbum;
use App\Models\ItemDelivery;
use App\Models\ItemGiftTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Carbon\Carbon;
use App\Helper\Helper;
use Illuminate\Support\Facades\Auth;

class AppUserController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {

            $users = AppUser::query()
                ->latest()
                ->whereNull('app_users.deleted_at')

                ->leftJoin('wc_levels as wealth_level', function ($join) {
                    $join->on('wealth_level.user_id', '=', 'app_users.id')
                        ->where('wealth_level.type', 'wealth');
                })

                ->leftJoin('wc_levels as charm_level', function ($join) {
                    $join->on('charm_level.user_id', '=', 'app_users.id')
                        ->where('charm_level.type', 'charm');
                })

                ->select(
                    'app_users.*',
                    'wealth_level.level as wealth_level',
                    'charm_level.level as charm_level'
                );

            if ($request->uid != '') {
                $users->where('uid', $request->uid);
            }

            if ($request->username != '') {
                $users->where('name', 'LIKE', "%{$request->username}%");
            }

            if ($request->equipment != '') {
                $users->where('equipment_number', $request->equipment);
            }

            if ($request->region != '') {
                $users->where('region', $request->region);
            }

            return DataTables::of($users)
                ->addIndexColumn()
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

                    // Premium/Store UID + System UID
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

                        // Only System UID
                        $uidHtml = '
                                    <small class="text-muted">
                                        ' . e($uidData['system_uid'] ?? $row->uid) . '
                                    </small>';
                    }
                    return '
                            <div class="d-flex align-items-center gap-2 user-profile-trigger"
                                data-user-id="' . $row->id . '"
                                style="cursor:pointer;">

                                <img src="' . $image . '"
                                    width="45"
                                    height="45"
                                    class="rounded-circle">

                                <div>
                                    <div class="fw-bold">' . e($row->name) . '</div>
                                    ' . $uidHtml . '
                                </div>

                            </div>';
                })

                ->editColumn('disable_status', function ($row) {

                    if ($row->is_blacklisted) {
                        return '<span class="badge bg-dark">
                        <i class="fa fa-ban"></i> Blacklisted
                    </span>';
                    }

                    if ($row->is_disabled) {
                        $until = $row->disabled_until
                            ? \Carbon\Carbon::parse($row->disabled_until)->format('Y-m-d')
                            : 'Permanent';

                        return '<span class="badge bg-danger">
                            <i class="fa fa-clock"></i> Disabled <br>
                            <small>Until: ' . $until . '</small>
                        </span>';
                    }

                    return '<span class="badge bg-success">Active</span>';
                })
                ->addColumn('wealth_level', function ($row) {
                    return $row->wealth_level ?? 0;
                })
                ->addColumn('charm_level', function ($row) {
                    return $row->charm_level ?? 0;
                })
                ->addColumn('balance_info', function ($row) {
                    return '
                        <a href="javascript:void(0)"
                        class="showBalance text-primary fw-bold">
                        Balance
                        </a>

                        <div class="balanceDetails mt-2 d-none">
                            <div class="border rounded p-2 bg-light">

                                <div class="d-flex justify-content-between">
                                    <span>Balance</span>
                                    <strong>$ ' . number_format($row->balance, 2) . '</strong>
                                </div>

                                <hr class="my-1">

                                <div class="d-flex justify-content-between">
                                    <span>Total Points</span>
                                    <strong><i class="fas fa-coins text-warning"></i> ' . number_format($row->total_points) . '</strong>
                                </div>

                            </div>
                        </div>';
                })
                ->editColumn(
                    'created_at',
                    fn($row) =>
                    $row->created_at ? $row->created_at->format('Y-m-d H:i:s') : ''
                )
                ->addColumn('operate', function ($row) {
                    $btn = '<div class="dropdown">
                    <button class="btn btn-sm btn-link dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-ellipsis-h"></i>
                    </button>
                    <div class="dropdown-menu">';
                    if (Helper::userCan(104, 'can_edit')) {
                        $btn .= '<button class="dropdown-item wealthLevelBtn"
                                data-id="' . $row->id . '"
                                data-name="' . e($row->name) . '">
                                <i class="fas fa-gem me-2 text-warning"></i> Wealth Level
                            </button>';
                    }
                    if (Helper::userCan(104, 'can_edit')) {
                        $btn .= '<button class="dropdown-item charmLevelBtn"
                                data-id="' . $row->id . '"
                                data-name="' . e($row->name) . '">
                                <i class="fas fa-heart me-2 text-danger"></i> Charm Level
                            </button>';
                    }
                    if (Helper::userCan(104, 'can_view')) {
                        $btn .= '<a class="dropdown-item" href="' . route('user-details', $row->id) . '"><i class="fas fa-eye me-2"></i> User Details</a>';
                    }
                    if (Helper::userCan(104, 'can_edit')) {
                        if ($row->is_disabled) {
                            $btn .= '<button class="dropdown-item text-success activateUserBtn"
                                data-id="' . $row->id . '"
                                data-name="' . e($row->name) . '">
                                <i class="fas fa-unlock me-2"></i> Activate User
                            </button>';
                        } else {
                            $btn .= '<button class="dropdown-item text-danger disableUserBtn"
                                data-id="' . $row->id . '"
                                data-name="' . e($row->name) . '">
                            <i class="fas fa-user-clock me-2"></i> Disable User
                            </button>';
                        }
                    }
                    if (Helper::userCan(104, 'can_edit')) {
                        if ($row->is_blacklisted) {
                            $btn .= '<span class="dropdown-item text-danger disabled">
                                <i class="fas fa-user-slash me-2"></i> Blacklisted
                            </span>';
                        } else {
                            $btn .= '<button class="dropdown-item text-dark blacklistUserBtn"
                                data-id="' . $row->id . '"
                                data-name="' . e($row->name) . '">
                                <i class="fas fa-user-slash me-2"></i> Blacklist User
                            </button>';
                        }
                    }
                    if (Helper::userCan(104, 'can_delete')) {
                        $btn .= '<button class="dropdown-item text-warning deleteProfileBtn"
                            data-id="' . $row->id . '"
                            data-name="' . e($row->name) . '">
                            <i class="fas fa-user-times me-2"></i> Delete Profile
                        </button>';
                    }
                    if (Helper::userCan(104, 'can_edit')) {
                        if ($row->is_disabled || $row->is_blacklisted) {
                            $btn .= '<button class="dropdown-item accountProcessingBtn"
                                data-id="' . $row->id . '">
                                <i class="fas fa-user-cog me-2 text-info"></i> Account Processing
                            </button>';
                        }
                    }
                    if (Helper::userCan(104, 'can_edit')) {
                        $btn .= '<a class="dropdown-item" href="' . route('user.edit', $row->id) . '">
                            <i class="fas fa-edit text-primary me-2"></i> Edit
                        </a>';
                    }
                    if (Helper::userCan(104, 'can_delete')) {
                        $btn .= '<button class="dropdown-item text-danger delete"
                            data-id="' . $row->id . '"
                            data-name="' . e($row->name) . '">
                            <i class="fas fa-trash me-2"></i> Delete
                        </button>';
                    }
                    $btn .= '</div></div>';

                    return $btn;
                })

                ->rawColumns(['user', 'uid', 'disable_status', 'wealth_level', 'charm_level', 'balance_info', 'operate'])
                ->make(true);
        }

        return view('app_users.index');
    }

    public function userDetails($id)
    {
        $user = AppUser::find($id);

        return view('app_users.view', compact('user'));
    }

    public function disable(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:app_users,id',
            'reason' => 'required|string|max:255',
            'disabled_until' => 'nullable|date'
        ]);

        $authUser = Auth::user();
        $user = AppUser::findOrFail($request->user_id);

        $user->is_disabled = 1;
        $user->disabled_reason = $request->reason;
        $user->disabled_until = $request->disabled_until;
        $user->action_by = $authUser->id;
        $user->save();

        return response()->json(['status' => true]);
    }

    public function activate(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:app_users,id'
        ]);

        $user = AppUser::findOrFail($request->user_id);

        $user->is_disabled = 0;
        $user->disabled_reason = null;
        $user->disabled_until = null;
        $user->save();

        return response()->json(['status' => true]);
    }

    public function blacklist(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:app_users,id',
            'reason' => 'required|string'
        ]);
        $authUser = Auth::user();
        $user = AppUser::findOrFail($request->user_id);

        $user->is_blacklisted = true;
        $user->blacklist_reason = $request->reason;
        $user->blacklisted_at = now();
        $user->action_by = $authUser->id;
        $user->save();

        return response()->json(['status' => true]);
    }

    public function edit($id = null)
    {
        $user = $id ? AppUser::findOrFail($id) : null;
        $countries = Country::select('id', 'name', 'nicename')->get();
        return view('app_users.form', compact('user', 'countries'));
    }

    public function save(Request $request, $id = null)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'uid' => 'required|unique:app_users,uid,' . $id,
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:20',
        ]);

        $user = $id ? AppUser::findOrFail($id) : new AppUser();

        $user->name = $request->name;
        $user->uid = $request->uid;
        $user->email = $request->email;
        $user->phone = $request->phone;
        $user->gender = $request->gender;
        $user->country = $request->country;
        $user->region = $request->region;
        $user->birthdate = $request->birthdate;

        // IMAGE UPLOAD
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('users', 'public');
            $user->image = $path;
        }

        $user->save();

        return redirect()
            ->route('app-users')->with('success', $id ? 'User updated successfully' : 'User added successfully');
    }

    public function delete(Request $request)
    {
        return Helper::deleteRecord(new AppUser, $request->id);
    }


    public function userAlbums(Request $request)
    {
        if ($request->ajax()) {

            $query = AppUser::with('albums')
                ->has('albums')
                ->latest();

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

                    // Premium/Store UID + System UID
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

                        // Only System UID
                        $uidHtml = '
                                    <small class="text-muted">
                                        ' . e($uidData['system_uid'] ?? $row->uid) . '
                                    </small>';
                    }
                    return '
                            <div class="d-flex align-items-center gap-2 user-profile-trigger"
                                data-user-id="' . $row->id . '"
                                style="cursor:pointer;">

                                <img src="' . $image . '"
                                    width="45"
                                    height="45"
                                    class="rounded-circle">

                                <div>
                                    <div class="fw-bold">' . e($row->name) . '</div>
                                    ' . $uidHtml . '
                                </div>

                            </div>';
                })

                ->addColumn('total_albums', function ($row) {
                    return $row->albums->count();
                })

                // ->addColumn('albums', function ($row) {

                //     $html = '<div class="d-flex flex-wrap gap-2">';

                //     foreach ($row->albums as $album) {

                //         $file = asset('storage/' . $album->file);

                //         if (str_contains($album->file_type, 'video')) {

                //             $html .= '
                //                 <div class="position-relative">

                //                     <video class="album-video"
                //                         data-video="' . $file . '"
                //                         width="60"
                //                         height="60"
                //                         style="cursor:pointer;border-radius:8px;object-fit:cover;">
                //                         <source src="' . $file . '" type="' . $album->file_type . '">
                //                     </video>

                //                     <button
                //                         class="btn btn-danger btn-sm deleteAlbumBtn"
                //                         data-id="' . $album->id . '"
                //                         style="
                //                             position:absolute;
                //                             top:-6px;
                //                             right:-6px;
                //                             width:22px;
                //                             height:22px;
                //                             border-radius:50%;
                //                             padding:0;
                //                             line-height:18px;">
                //                         <i class="fas fa-times"></i>
                //                     </button>

                //                 </div>
                //                 ';
                //         } else {

                //             $html .= '
                //                 <div class="position-relative">

                //                     <img src="' . $file . '"
                //                         class="album-thumb"
                //                         data-image="' . $file . '"
                //                         width="60"
                //                         height="60"
                //                         style="cursor:pointer;border-radius:8px;object-fit:cover;">

                //                     <button
                //                         class="btn btn-danger btn-sm deleteAlbumBtn"
                //                         data-id="' . $album->id . '"
                //                         style="
                //                             position:absolute;
                //                             top:-6px;
                //                             right:-6px;
                //                             width:22px;
                //                             height:22px;
                //                             border-radius:50%;
                //                             padding:0;
                //                             line-height:18px;">
                //                         <i class="fas fa-times"></i>
                //                     </button>

                //                 </div>
                //                 ';
                //         }
                //     }

                //     $html .= '</div>';

                //     return $html;
                // })

                ->addColumn('albums', function ($row) {

                    $html = '<div class="d-flex flex-wrap gap-2">';

                    foreach ($row->albums as $album) {

                        $file = asset('storage/' . $album->file);

                        // Delete button only if user has delete permission
                        $deleteBtn = '';

                        if (Helper::userCan(105, 'can_delete')) {
                            $deleteBtn = '
                                <button
                                    class="btn btn-danger btn-sm deleteAlbumBtn"
                                    data-id="' . $album->id . '"
                                    style="
                                        position:absolute;
                                        top:-6px;
                                        right:-6px;
                                        width:22px;
                                        height:22px;
                                        border-radius:50%;
                                        padding:0;
                                        line-height:18px;">
                                    <i class="fas fa-times"></i>
                                </button>
                            ';
                        }

                        if (str_contains($album->file_type, 'video')) {

                            $html .= '
                                    <div class="position-relative">
                                        <video class="album-video"
                                            data-video="' . $file . '"
                                            width="60"
                                            height="60"
                                            style="cursor:pointer;border-radius:8px;object-fit:cover;">
                                            <source src="' . $file . '" type="' . $album->file_type . '">
                                        </video>
                                        ' . $deleteBtn . '
                                    </div>';
                        } else {

                            $html .= '
                                    <div class="position-relative">
                                        <img src="' . $file . '"
                                        class="album-thumb"
                                        data-image="' . $file . '"
                                        width="60"
                                        height="60"
                                        style="cursor:pointer;border-radius:8px;object-fit:cover;">
                                        ' . $deleteBtn . '
                                    </div>';
                        }
                    }

                    $html .= '</div>';

                    return $html;
                })
                ->addColumn('status', function ($row) {

                    if ((int)$row->is_album_banned === 1) {
                        return '<span class="badge bg-danger">
                            <i class="fas fa-ban me-1"></i> Banned
                        </span>';
                    }

                    return '<span class="badge bg-success">
                        <i class="fas fa-check-circle me-1"></i> Active
                    </span>';
                })
                ->addColumn('action', function ($row) {

                    $btn = '<div class="dropdown">
                        <button class="btn btn-sm btn-link dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="fas fa-ellipsis-h"></i>
                        </button>
                        <div class="dropdown-menu">';
                    if (Helper::userCan(105, 'can_edit')) {
                        if ($row->is_album_banned) {
                            $btn .= '<a href="javascript:void(0)"
                                class="dropdown-item unbanAlbumBtn"
                                data-id="' . $row->id . '">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                Unban Album Upload
                            </a>';
                        } else {
                            $btn .= '<a href="javascript:void(0)"
                                class="dropdown-item banAlbumBtn"
                                data-id="' . $row->id . '">
                                <i class="fas fa-camera-slash text-danger me-2"></i>
                                Ban Album Upload
                            </a>';
                        }
                    }
                    $btn .= '</div></div>';

                    return $btn;
                })
                ->rawColumns(['action', 'user', 'albums', 'status'])
                ->make(true);
        }

        return view('app_users.user_albums');
    }

    public function deleteAlbum($id)
    {
        $album = UserAlbum::find($id);

        if (!$album) {
            return response()->json([
                'status' => false,
                'message' => 'Album not found.'
            ]);
        }

        // Storage File Delete
        if ($album->file) {

            $storageFile = storage_path('app/public/' . $album->file);

            if (file_exists($storageFile)) {
                @unlink($storageFile);
            }

            $publicFile = public_path('storage/' . $album->file);

            if (file_exists($publicFile)) {
                @unlink($publicFile);
            }
        }

        $album->delete();

        return response()->json([
            'status' => true,
            'message' => 'Album deleted successfully.'
        ]);
    }


    public function userItems(Request $request)
    {
        if ($request->ajax()) {

            $query = AppUser::with(['deliveredItems', 'giftedItems'])->where(function ($q) {
                $q->has('deliveredItems')->orHas('giftedItems');
            })->latest();

            return DataTables::of($query)

                // ->addColumn('user', function ($row) {

                //     $image = $row->image
                //         ? Helper::showImage($row->image, true)
                //         : asset('assets/img/avatar.png');

                //     return '
                //     <div class="d-flex align-items-center gap-2 user-profile-trigger"
                //          data-user-id="' . $row->id . '"
                //          style="cursor:pointer;">

                //         <img src="' . $image . '"
                //              width="40"
                //              height="40"
                //              class="rounded-circle">

                //         <div>
                //             <div class="fw-bold">' . $row->name . '</div>
                //             <small class="text-muted">' . $row->uid . '</small>
                //         </div>

                //     </div>';
                // })


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

                    // Premium/Store UID + System UID
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

                        // Only System UID
                        $uidHtml = '
                                    <small class="text-muted">
                                        ' . e($uidData['system_uid'] ?? $row->uid) . '
                                    </small>';
                    }
                    return '
                            <div class="d-flex align-items-center gap-2 user-profile-trigger"
                                data-user-id="' . $row->id . '"
                                style="cursor:pointer;">

                                <img src="' . $image . '"
                                    width="45"
                                    height="45"
                                    class="rounded-circle">

                                <div>
                                    <div class="fw-bold">' . e($row->name) . '</div>
                                    ' . $uidHtml . '
                                </div>

                            </div>';
                })

                ->addColumn('total_items', function ($row) {

                    $today = now();

                    $delivered = collect(
                        $row->deliveredItems
                            ->where('end_at', '>=', $today)
                            ->map(function ($item) {
                                return $item->type . '_' . $item->item_id;
                            })
                            ->values()
                    );

                    $gifted = collect(
                        $row->giftedItems
                            ->where('end_at', '>=', $today)
                            ->map(function ($item) {
                                return $item->type . '_' . $item->item_id;
                            })
                            ->values()
                    );

                    $allItems = $delivered
                        ->merge($gifted)
                        ->unique();

                    return $allItems->count();
                })

                ->addColumn('items', function ($row) {

                    if (!Helper::userCan(106, 'can_view')) {
                        return '-';
                    }

                    return '
                        <button class="btn btn-sm btn-primary view-items"
                                data-user="' . $row->id . '">
                            View Items
                        </button>
                    ';
                })

                ->filterColumn('user', function ($query, $keyword) {
                    $query->where(function ($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%")->orWhere('uid', 'like', "%{$keyword}%");
                    });
                })

                ->rawColumns(['user', 'items'])

                ->make(true);
        }

        return view('app_users.user_items');
    }

    public function getUserItems($id)
    {
        $user = AppUser::with(['deliveredItems', 'giftedItems'])->findOrFail($id);

        $today = now();

        $allItems = $user->deliveredItems
            ->filter(function ($item) use ($today) {
                return !empty($item->end_at) && $item->end_at >= $today;
            })
            ->map(function ($item) {
                return [
                    'transaction_id' => $item->id,
                    'source' => 'delivery',
                    'type' => $item->type,
                    'item_id' => $item->item_id
                ];
            })
            ->merge(
                $user->giftedItems
                    ->filter(function ($item) use ($today) {
                        return !empty($item->end_at) && $item->end_at >= $today;
                    })
                    ->map(function ($item) {
                        return [
                            'transaction_id' => $item->id,
                            'source' => 'gift',
                            'type' => $item->type,
                            'item_id' => $item->item_id
                        ];
                    })
            );

        // $allItems = $allItems->groupBy(function ($item) {
        //     return $item['type'] . '_' . $item['item_id'];
        // });

        $items = [];

        foreach ($allItems as $item) {

            // \Log::info([
            //     'type' => $item['type'],
            //     'item_id' => $item['item_id']
            // ]);

            $image = null;

            switch ($item['type']) {

                case 'theme':

                    $theme = Theme::find($item['item_id']);

                    if ($theme && !empty($theme->icon)) {
                        $image = Helper::showImage($theme->icon, true);
                    }

                    break;

                case 'entry':

                    $car = Cars::find($item['item_id']);

                    if ($car && !empty($car->icon)) {
                        $image = Helper::showImage($car->icon, true);
                    }

                    break;

                case 'entry_tag':

                    $entry = EntryTag::find($item['item_id']);

                    if ($entry && !empty($entry->icon)) {
                        $image = Helper::showImage($entry->icon, true);
                    }

                    break;

                case 'avatar_frame':
                case 'frame':

                    $frame = Frame::find($item['item_id']);

                    if ($frame && !empty($frame->icon)) {
                        $image = Helper::showImage($frame->icon, true);
                    }

                    break;

                case 'voice':

                    $voice = Voice::find($item['item_id']);

                    if ($voice && !empty($voice->icon)) {
                        $image = Helper::showImage($voice->icon, true);
                    }

                    break;

                case 'vip':

                    $vip = Vip::find($item['item_id']);

                    if ($vip && !empty($vip->badge)) {
                        $image = Helper::showImage($vip->badge, true);
                    }

                    break;

                case 'id':

                    $uid = StoreUids::find($item['item_id']);

                    if ($uid && !empty($uid->badge)) {
                        $image = Helper::showImage($uid->badge, true);
                    }

                    break;

                case 'profile_card':

                    $card = DataCard::find($item['item_id']);

                    if ($card && !empty($card->icon)) {
                        $image = Helper::showImage($card->icon, true);
                    }

                    break;

                case 'chat_bubble':

                    $bubble = ChatBubble::find($item['item_id']);

                    if ($bubble && !empty($bubble->icon)) {
                        $image = Helper::showImage($bubble->icon, true);
                    }

                    break;

                case 'car':

                    $car = Cars::find($item['item_id']);

                    if ($car && !empty($car->icon)) {
                        $image = Helper::showImage($car->icon, true);
                    }

                    break;

                case 'gift':

                    $gift = Gift::find($item['item_id']);

                    if ($gift && !empty($gift->icon)) {
                        $image = Helper::showImage($gift->icon, true);
                    }

                    break;
            }

            if (!$image) {
                continue;
            }

            $items[] = [
                'transaction_id' => $item['transaction_id'],
                'source' => $item['source'],
                'type' => ucfirst(str_replace('_', ' ', $item['type'])),
                'image' => $image,
            ];
        }

        return response()->json([
            'status' => true,
            'data'   => $items
        ]);
    }

    public function deleteUserItem(Request $request)
    {
        $request->validate([
            'id'     => 'required|integer',
            'source' => 'required|in:delivery,gift',
        ]);

        if ($request->source == 'delivery') {

            $record = ItemDelivery::find($request->id);
        } else {

            $record = ItemGiftTransaction::find($request->id);
        }

        if (!$record) {
            return response()->json([
                'status' => false,
                'message' => 'Item not found.'
            ], 404);
        }

        $user = AppUser::find($request->source == 'delivery'
            ? $record->recipient
            : $record->receiver_id);

        if ($user) {

            switch ($record->type) {

                case 'theme':
                    if ($user->active_theme_id == $record->item_id) {
                        $user->active_theme_id = null;
                    }
                    break;

                case 'frame':
                case 'avatar_frame':
                    if ($user->active_frame_id == $record->item_id) {
                        $user->active_frame_id = null;
                    }
                    break;

                case 'entry':
                    if ($user->active_entry_id == $record->item_id) {
                        $user->active_entry_id = null;
                    }
                    break;

                case 'entry_tag':
                    if ($user->active_entry_tag_id == $record->item_id) {
                        $user->active_entry_tag_id = null;
                    }
                    break;

                case 'voice':
                    if ($user->active_voice_id == $record->item_id) {
                        $user->active_voice_id = null;
                    }
                    break;

                case 'profile_card':
                    if ($user->active_card_id == $record->item_id) {
                        $user->active_card_id = null;
                    }
                    break;

                case 'chat_bubble':
                    if ($user->active_chat_bubble_id == $record->item_id) {
                        $user->active_chat_bubble_id = null;
                    }
                    break;

                case 'car':
                    if ($user->active_car_id == $record->item_id) {
                        $user->active_car_id = null;
                    }
                    break;

                case 'id':
                    if ($user->active_uid_id == $record->item_id) {
                        $user->active_uid_id = null;
                    }
                    break;
            }

            $user->save();
        }

        $record->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Item deleted successfully.'
        ]);
    }

    public function postReportList(Request $request)
    {
        if ($request->ajax()) {

            $query = PostReport::with(['user', 'post.user', 'post.media'])->latest();

            return DataTables::of($query)

                ->addIndexColumn()
                ->addColumn('reporter', function ($row) {

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
                                width="45"
                                height="45"
                                class="rounded-circle">

                            <div>
                                <div class="fw-bold">' . e($user->name) . '</div>
                                ' . $uidHtml . '
                            </div>

                        </div>
                    ';
                })

                ->addColumn('post_owner', function ($row) {

                    if (!$row->post) {
                        return '<span class="badge bg-danger">Post Deleted</span>';
                    }

                    $user = $row->post->user;

                    if (!$user) {
                        return '<span class="badge bg-warning">User Deleted</span>';
                    }

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
                                width="45"
                                height="45"
                                class="rounded-circle">

                            <div>
                                <div class="fw-bold text-danger">' . e($user->name) . '</div>
                                ' . $uidHtml . '
                            </div>

                        </div>
                    ';
                })

                ->addColumn('post', function ($row) {

                    if (!$row->post) {
                        return '<span class="badge bg-danger">Post Deleted (#' . $row->post_id . ')</span>';
                    }

                    $media = $row->post->media->first();

                    if (!$media) {
                        return '<span class="text-muted">No Media</span>';
                    }

                    $url = Helper::showImage($media->file_path, true);

                    $extension = strtolower(pathinfo($media->file_path, PATHINFO_EXTENSION));

                    // IMAGE
                    if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {

                        return '
                            <a href="' . $url . '" target="_blank">
                                <img src="' . $url . '"width="90" height="90"
                                    style="object-fit:cover; border-radius:10px; border:1px solid #ddd; box-shadow:0 2px 8px rgba(0,0,0,.15);">
                            </a>
                        ';
                    }

                    // VIDEO
                    if (
                        in_array($extension, ['mp4', 'mov', 'avi', 'mkv', 'webm']) || str_contains($media->file_type, 'video') ||
                        $media->file_type == 'application/octet-stream'
                    ) {

                        return '
                            <video width="120" height="90" controls preload="metadata"
                                   style="border-radius:10px; border:1px solid #ddd; background:#000;">
                                <source src="' . $url . '" type="video/mp4">
                            </video>
                        ';
                    }

                    return '
                        <span class="badge bg-secondary">
                            ' . $media->file_type . '
                        </span>
                    ';
                })

                ->addColumn('reason', function ($row) {

                    return '
                        <span class="badge bg-danger">
                            ' . $row->reason . '
                        </span>
                    ';
                })

                ->addColumn('reported_at', function ($row) {
                    return Carbon::parse($row->created_at)->timezone('Asia/Kolkata')->format('d M Y Y h:i A');
                })

                ->rawColumns(['reporter', 'post_owner', 'post', 'reason', 'reported_at',])
                ->make(true);
        }

        return view('app_users.post_reports');
    }

    // User detail side modal
    public function userProfile($id)
    {
        $user = AppUser::with([
            'premium',
            'host',
            'agency',
            'activeFrame',
            'activeCard',
            'activeTheme',
            'deliveredItems',
            'giftedItems'
        ])->findOrFail($id);

        $items = $this->getUserItemsData($user);

        return response()->json([
            'status' => true,
            'html' => view('components.user_profile', compact('user', 'items'))->render()
        ]);
    }

    private function getUserItemsData($user)
    {
        $today = now();

        $delivered = $user->deliveredItems
            ->filter(fn($item) => !empty($item->end_at) && $item->end_at >= $today)
            ->map(fn($item) => [
                'type' => $item->type,
                'item_id' => $item->item_id
            ])
            ->toBase();

        $gifted = $user->giftedItems
            ->filter(fn($item) => !empty($item->end_at) && $item->end_at >= $today)
            ->map(fn($item) => [
                'type' => $item->type,
                'item_id' => $item->item_id
            ])
            ->toBase();

        $allItems = $delivered->merge($gifted);

        $items = [];

        foreach ($allItems as $item) {

            $image = null;

            switch ($item['type']) {

                case 'theme':
                    $model = Theme::find($item['item_id']);
                    $image = $model?->icon;
                    break;

                case 'frame':
                case 'avatar_frame':
                    $model = Frame::find($item['item_id']);
                    $image = $model?->icon;
                    break;

                case 'profile_card':
                    $model = DataCard::find($item['item_id']);
                    $image = $model?->icon;
                    break;

                case 'chat_bubble':
                    $model = ChatBubble::find($item['item_id']);
                    $image = $model?->icon;
                    break;

                case 'voice':
                    $model = Voice::find($item['item_id']);
                    $image = $model?->icon;
                    break;

                case 'vip':
                    $model = Vip::find($item['item_id']);
                    $image = $model?->badge;
                    break;

                case 'entry':
                case 'car':
                    $model = Cars::find($item['item_id']);
                    $image = $model?->icon;
                    break;

                case 'entry_tag':
                    $model = EntryTag::find($item['item_id']);
                    $image = $model?->icon;
                    break;

                case 'gift':
                    $model = Gift::find($item['item_id']);
                    $image = $model?->icon;
                    break;

                case 'id':
                    $model = StoreUids::find($item['item_id']);
                    $image = $model?->badge;
                    break;
            }

            if ($image) {
                $items[] = [
                    'type' => ucfirst(str_replace('_', ' ', $item['type'])),
                    'image' => Helper::showImage($image, true)
                ];
            }
        }

        return collect($items)->unique('image')->values();
    }

    public function deleteProfile($id)
    {
        $user = AppUser::find($id);

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found.'
            ]);
        }

        // Delete image from storage
        if (!empty($user->image)) {

            $imagePath = storage_path('app/public/' . $user->image);

            if (file_exists($imagePath)) {
                @unlink($imagePath);
            }

            // If public/storage copy exists
            $publicPath = public_path('storage/' . $user->image);

            if (file_exists($publicPath)) {
                @unlink($publicPath);
            }
        }

        $user->image = null;
        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'Profile image deleted successfully.'
        ]);
    }

    public function accountProcessing($id)
    {
        $user = AppUser::findOrFail($id);

        $admin = User::find($user->action_by);

        return response()->json([
            'status' => true,
            'data' => [
                'user' => $user->name,
                'status' => $user->is_blacklisted ? 'Blacklisted' : 'Disabled',
                'action_by' => $admin->name ?? 'System',
                'reason' => $user->is_blacklisted
                    ? $user->blacklist_reason
                    : $user->disabled_reason,
                'until' => $user->disabled_until,
                'date' => $user->blacklisted_at
                    ?? $user->updated_at?->format('Y-m-d H:i:s'),
            ]
        ]);
    }

    public function getWealthLevel($id)
    {
        $user = AppUser::findOrFail($id);

        $wealth = WcLevel::where('user_id', $id)
            ->where('type', 'wealth')
            ->first();

        return response()->json([
            'status' => true,
            'user_id' => $user->id,
            'name' => $user->name,
            'level' => $wealth->level ?? 1
        ]);
    }

    public function updateWealthLevel(Request $request, $id)
    {
        $request->validate([
            'level' => 'required|integer|min:1'
        ]);

        $wealth = WcLevel::firstOrCreate(
            [
                'user_id' => $id,
                'type' => 'wealth'
            ],
            [
                'exp' => 0
            ]
        );

        $wealth->level = $request->level;
        $wealth->save();

        return response()->json([
            'status' => true,
            'message' => 'Wealth level updated successfully.'
        ]);
    }

    public function getCharmLevel($id)
    {
        $user = AppUser::findOrFail($id);

        $charm = WcLevel::where('user_id', $id)
            ->where('type', 'charm')
            ->first();

        return response()->json([
            'status' => true,
            'user_id' => $user->id,
            'name' => $user->name,
            'level' => $charm->level ?? 1
        ]);
    }

    public function updateCharmLevel(Request $request, $id)
    {
        $request->validate([
            'level' => 'required|integer|min:1'
        ]);

        $charm = WcLevel::where('user_id', $id)
            ->where('type', 'charm')
            ->first();

        if (!$charm) {
            $charm = new WcLevel();
            $charm->user_id = $id;
            $charm->type = 'charm';
            $charm->exp = 0;
        }

        $charm->level = $request->level;
        $charm->save();

        return response()->json([
            'status' => true,
            'message' => 'Charm level updated successfully.'
        ]);
    }

    public function banAlbum($id)
    {
        $user = AppUser::findOrFail($id);

        $user->update([
            'is_album_banned' => 1
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Album upload banned successfully.'
        ]);
    }

    public function unbanAlbum($id)
    {
        $user = AppUser::findOrFail($id);

        $user->update([
            'is_album_banned' => 0
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Album upload enabled successfully.'
        ]);
    }

    public function deletedUsers(Request $request)
    {
        if ($request->ajax()) {

            $users = AppUser::onlyTrashed()->latest();

            return DataTables::of($users)

                ->addIndexColumn()

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
                    <div class="d-flex align-items-center gap-2 user-profile-trigger"
                        data-user-id="' . $row->id . '"
                        style="cursor:pointer;">

                        <img src="' . $image . '"
                            width="45"
                            height="45"
                            class="rounded-circle">

                        <div>
                            <div class="fw-bold">' . e($row->name) . '</div>
                            ' . $uidHtml . '
                        </div>

                    </div>';
                })

                ->addColumn('country', function ($row) {
                    return $row->country ?? '-';
                })

                ->editColumn('deleted_at', function ($row) {
                    return $row->deleted_at
                        ? $row->deleted_at->format('Y-m-d H:i:s')
                        : '-';
                })

                ->addColumn('action', function ($row) {

                    return '
                    <button class="btn btn-success btn-sm restoreUser"
                            data-id="' . $row->id . '">
                        <i class="fas fa-trash-restore me-1"></i>
                        Restore
                    </button>
                ';
                })

                ->rawColumns(['user', 'action'])

                ->make(true);
        }

        return view('app_users.deleted_user');
    }

    public function restoreUser($id)
    {
        $user = AppUser::onlyTrashed()->findOrFail($id);

        $user->restore();

        return response()->json([
            'status' => true,
            'message' => 'User restored successfully.'
        ]);
    }


    public function userDeviceList(Request $request)
    {
        if ($request->ajax()) {

            $query = AppUser::query()
                ->select([
                    'imei',
                    DB::raw('MAX(brand) as brand'),
                    DB::raw('MAX(equipment_model) as equipment_model'),
                    DB::raw('MAX(operating_system) as operating_system'),
                    DB::raw('MAX(app_version) as app_version'),
                    DB::raw('COUNT(*) as total_accounts'),
                ])
                ->whereNotNull('imei')
                ->where('imei', '<>', '')
                ->groupBy('imei');

            return DataTables::of($query)

                ->addIndexColumn()

                ->addColumn('imei', function ($row) {
                    return '<span class="fw-bold text-primary">' . e($row->imei) . '</span>';
                })

                ->addColumn('device', function ($row) {

                    return '
                    <div>
                        <div><b>' . e($row->brand ?? '-') . '</b></div>
                        <small class="text-muted">
                            ' . e($row->equipment_model ?? '-') . '
                        </small>
                    </div>
                ';
                })

                ->addColumn('os', function ($row) {

                    return '
                    <div>
                        <div>' . e($row->operating_system ?? '-') . '</div>
                        <small class="text-muted">
                            App : ' . e($row->app_version ?? '-') . '
                        </small>
                    </div>
                ';
                })

                ->addColumn('accounts', function ($row) {

                    return '
                    <span class="badge bg-primary fs-6">
                        ' . $row->total_accounts . '
                    </span>
                ';
                })
                ->addColumn('status', function ($row) {

                    $isBanned = AppUser::where('imei', $row->imei)
                        ->where('is_device_banned', 1)
                        ->exists();

                    if ($isBanned) {

                        return '
                            <span class="badge bg-danger">
                                <i class="fas fa-ban me-1"></i>
                                Device Banned
                            </span>
                        ';
                    }

                    return '
                        <span class="badge bg-success">
                            <i class="fas fa-check-circle me-1"></i>
                            Active
                        </span>
                    ';
                })

                ->addColumn('action', function ($row) {

                    if (!Helper::userCan(104, 'can_view')) {
                        return '-';
                    }

                    // Check device ban status
                    $isBanned = AppUser::where('imei', $row->imei)
                        ->where('is_device_banned', 1)
                        ->exists();

                    $banButton = '';

                    if ($isBanned) {

                        if (Helper::userCan(104, 'can_edit')) {

                            $banButton = '
                                    <button class="dropdown-item text-success device-unban"
                                            data-imei="' . $row->imei . '">

                                        <i class="fas fa-lock-open me-2"></i>
                                        Device Unban

                                    </button>
                                ';
                        }
                    } else {

                        if (Helper::userCan(104, 'can_edit')) {

                            $banButton = '
                                    <button class="dropdown-item text-danger device-ban"
                                            data-imei="' . $row->imei . '">

                                        <i class="fas fa-ban me-2"></i>
                                        Device Ban

                                    </button>
                                ';
                        }
                    }

                    return '
                            <div class="dropdown">

                                <button class="btn btn-sm btn-light border rounded-pill px-3"
                                        data-bs-toggle="dropdown">

                                    <i class="fas fa-ellipsis-h"></i>

                                </button>

                                <div class="dropdown-menu dropdown-menu-end shadow-sm">

                                    <a href="' . route('device.user.list', $row->imei) . '"
                                        class="dropdown-item">

                                        <i class="fas fa-users text-primary me-2"></i>

                                        View Users

                                    </a>

                                    ' . $banButton . '

                                </div>

                            </div>
                        ';
                })

                ->rawColumns([
                    'imei',
                    'device',
                    'os',
                    'accounts',
                    'status',
                    'action'
                ])

                ->make(true);
        }

        return view('app_users.user_devices');
    }

    public function deviceBan(Request $request)
    {
        AppUser::where('imei', $request->imei)
            ->update([
                'is_device_banned' => 1
            ]);

        return response()->json([
            'status' => true,
            'message' => 'Device banned successfully.'
        ]);
    }

    public function deviceUnban(Request $request)
    {
        AppUser::where('imei', $request->imei)
            ->update([
                'is_device_banned' => 0
            ]);

        return response()->json([
            'status' => true,
            'message' => 'Device unbanned successfully.'
        ]);
    }

    public function deviceUsers(Request $request, $imei)
    {
        if ($request->ajax()) {

            $query = AppUser::where('imei', $imei)->latest();

            return DataTables::of($query)

                ->addIndexColumn()

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
                    <div class="d-flex align-items-center gap-2 user-profile-trigger"
                        data-user-id="' . $row->id . '"
                        style="cursor:pointer;">

                        <img src="' . $image . '"
                            width="45"
                            height="45"
                            class="rounded-circle">

                        <div>
                            <div class="fw-bold">' . e($row->name) . '</div>
                            ' . $uidHtml . '
                        </div>

                    </div>
                ';
                })

                ->addColumn('email', function ($row) {
                    return $row->email ?? '-';
                })

                ->addColumn('country', function ($row) {
                    return $row->country ?? '-';
                })

                ->addColumn('registered_at', function ($row) {
                    return $row->registration_time
                        ? \Carbon\Carbon::parse($row->registration_time)->format('d M Y H:i')
                        : '-';
                })

                ->addColumn('status', function ($row) {

                    if ($row->is_blacklisted) {
                        return '<span class="badge bg-danger">Blacklisted</span>';
                    }

                    if ($row->is_disabled) {
                        return '<span class="badge bg-warning">Disabled</span>';
                    }

                    return '<span class="badge bg-success">Active</span>';
                })

                ->rawColumns([
                    'user',
                    'status'
                ])

                ->make(true);
        }

        return view('app_users.device_user_list', compact('imei'));
    }
}
