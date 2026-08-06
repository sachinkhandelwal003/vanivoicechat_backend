<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\RoomRewardSlab;
use App\Models\RoomRewardClaim;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use App\Helper\Helper;

class RoomRewardSlabController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {

            $query = RoomRewardSlab::orderBy('sort_order', 'asc')
                ->orderBy('room_contribution', 'asc');


            return DataTables::of($query)
                ->addIndexColumn()

                ->editColumn('created_at', function ($row) {
                    return $row->created_at
                        ? $row->created_at->timezone('Asia/Kolkata')->format('d M Y, h:i A')
                        : '-';
                })
                ->editColumn('status', function ($row) {
                    return $row['status'] == 1 ? '<small class="badge fw-semi-bold rounded-pill status badge-light-success"> Enable</small>' : '<small class="badge fw-semi-bold rounded-pill status badge-light-danger"> Disable</small>';
                })
                ->addColumn('action', function ($row) {

                    $btn = '
                            <div class="dropup text-center">
                                <button class="btn btn-sm btn-light rounded-pill px-3" data-bs-toggle="dropdown">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>

                                <div class="dropdown-menu dropdown-menu-end p-2">';

                    // Edit Permission
                    if (Helper::userCan(130, 'can_edit')) {
                        $btn .= '
                                <a class="dropdown-item"
                                href="' . route('room_reward_slabs.edit', $row->id) . '">
                                    <i class="fas fa-edit text-primary me-2"></i> Edit
                                </a>';
                    }

                    // Delete Permission
                    if (Helper::userCan(130, 'can_delete')) {
                        $btn .= '
                                <button class="dropdown-item text-danger delete"
                                        data-id="' . $row->id . '">
                                    <i class="fas fa-trash me-2"></i> Delete
                                </button>';
                    }

                    $btn .= '
                            </div>
                        </div>';

                    return $btn;
                })

                ->rawColumns(['status', 'action'])
                ->make(true);
        }

        return view('room_reward_slabs.index');
    }

    public function add()
    {
        return view('room_reward_slabs.create');
    }

    public function save(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'room_contribution' => 'required|integer|min:1|unique:room_reward_slabs,room_contribution',
            'reward_coins'      => 'required|integer|min:1',
            'sort_order'        => 'nullable|integer|min:0',
            'status'            => 'required|in:0,1',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        RoomRewardSlab::create([
            'room_contribution' => $request->room_contribution,
            'reward_coins'      => $request->reward_coins,
            'sort_order'        => $request->sort_order ?? 0,
            'status'            => $request->status,
        ]);

        return redirect()
            ->route('room_reward_slabs')
            ->with('success', 'Room reward slab created successfully.');
    }

    public function edit($id)
    {
        $slab = RoomRewardSlab::findOrFail($id);

        return view('room_reward_slabs.edit', compact('slab'));
    }

    public function update(Request $request, $id)
    {
        $slab = RoomRewardSlab::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'room_contribution' => 'required|integer|min:1|unique:room_reward_slabs,room_contribution,' . $slab->id,
            'reward_coins'      => 'required|integer|min:1',
            'sort_order'        => 'nullable|integer|min:0',
            'status'            => 'required|in:0,1',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $slab->update([
            'room_contribution' => $request->room_contribution,
            'reward_coins'      => $request->reward_coins,
            'sort_order'        => $request->sort_order ?? 0,
            'status'            => $request->status,
        ]);

        return redirect()
            ->route('room_reward_slabs')
            ->with('success', 'Room reward slab updated successfully.');
    }

    public function delete()
    {
        return Helper::deleteRecord(new RoomRewardSlab, $request->id);
    }




    public function claims(Request $request)
    {
        if ($request->ajax()) {

            // $query = RoomRewardClaim::latest();
            $query = RoomRewardClaim::with(['room', 'owner'])->latest();

            return DataTables::of($query)

                ->addColumn('owner_id', function ($row) {

                    if (!$row->owner) {
                        return '-';
                    }

                    $user = $row->owner;

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

                ->addColumn('room_id', function ($row) {

                    if (!$row->room) {
                        return '-';
                    }

                    $image = $row->room->image
                        ? Helper::showImage($row->room->image, true)
                        : asset('assets/img/avatar.png');

                    return '
                        <div class="d-flex align-items-center gap-2">
                            <img src="' . $image . '"
                                 class="rounded-circle"
                                 width="40"
                                 height="40">

                            <div>
                                <div class="fw-bold">
                                    ' . e($row->room->room_name) . '
                                </div>

                                <small class="text-muted">
                                    Room ID : ' . e($row->room->room_id) . '
                                </small>
                            </div>
                        </div>
                    ';
                })

                ->editColumn('is_claimed', function ($row) {

                    return $row->is_claimed == 1
                        ? '<small class="badge fw-semi-bold rounded-pill badge-light-success">Claimed</small>'
                        : '<small class="badge fw-semi-bold rounded-pill badge-light-warning">Pending</small>';
                })

                ->editColumn('claimed_at', function ($row) {

                    return $row->claimed_at
                        ? date('d M Y, h:i A', strtotime($row->claimed_at))
                        : '-';
                })

                ->editColumn('created_at', function ($row) {

                    return $row->created_at
                        ? $row->created_at->format('d M Y, h:i A')
                        : '-';
                })

                ->addColumn('action', function ($row) {

                    if (!Helper::userCan(131, 'can_delete')) {
                        return '-';
                    }

                    return '
                            <div class="dropup text-center">
                                <button class="btn btn-sm btn-light rounded-pill px-3" data-bs-toggle="dropdown">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>

                                <div class="dropdown-menu dropdown-menu-end p-2">

                                    <button class="dropdown-item text-danger delete"
                                            data-id="' . $row->id . '">
                                        <i class="fas fa-trash me-2"></i> Delete
                                    </button>

                                </div>
                            </div>';
                })

                ->rawColumns(['room_id', 'owner_id', 'is_claimed', 'action'])
                ->make(true);
        }

        return view('room_reward_slabs.room-reward-claims');
    }

    public function deleteClaim(Request $request)
    {
        return Helper::deleteRecord(new RoomRewardClaim, $request->id);
    }
}
