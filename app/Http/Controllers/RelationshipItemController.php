<?php

namespace App\Http\Controllers;

use App\Models\RelationshipItem;
use App\Models\RelationshipInvitation;
use App\Helper\Helper;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RelationshipItemController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {

            $query = RelationshipItem::latest();

            return DataTables::of($query)
                ->addIndexColumn()

                ->editColumn('icon', function ($row) {

                    if (!$row->icon) {
                        return '-';
                    }

                    $image = asset('storage/' . $row->icon);

                    return '
                        <img src="' . $image . '" width="40" height="40" class="image-preview" data-image="' . $image . '"
                             style="cursor:pointer;border-radius:6px;object-fit:cover;">
                    ';
                })

                ->editColumn('type', function ($row) {
                    return ucfirst($row->type);
                })

                ->addColumn('action', function ($row) {

                    $btn = '
                        <div class="dropdown">
                            <button class="btn btn-sm btn-link dropdown-toggle" data-bs-toggle="dropdown">
                                <i class="fas fa-ellipsis-h"></i>
                            </button>

                            <div class="dropdown-menu">';

                    // Edit Permission
                    if (Helper::userCan(127, 'can_edit')) {
                        $btn .= '
                                <a class="dropdown-item"
                                href="' . route('relationship.item.form', $row->id) . '">
                                    <i class="fas fa-edit text-primary me-2"></i> Edit
                                </a>';
                    }

                    // Delete Permission
                    if (Helper::userCan(127, 'can_delete')) {
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

                ->rawColumns(['icon', 'action'])
                ->make(true);
        }

        return view('relationship.index');
    }

    public function form($id = null): View|RedirectResponse
    {
        $item = null;

        if ($id) {
            $item = RelationshipItem::find($id);

            if (!$item) {
                return to_route('relationship')->withError('Item Not Found!');
            }
        }

        $ringTypes = RelationshipItem::whereNotNull('ring')
            ->where('ring', '!=', '')
            ->pluck('type')
            ->map(fn($type) => strtolower($type))
            ->unique()
            ->values()
            ->toArray();

        return view('relationship.form', compact('item', 'ringTypes'));
    }

    public function save(Request $request, $id = null)
    {
        $rules = [
            'name' => 'required|string|max:100',
            'type' => 'required|in:CP,brother,sister,confident',
            'required_coins' => 'nullable|integer',

            'icon' => 'nullable|image',
            'gif' => 'nullable',
            'ring' => 'nullable|image',
            'avatar' => 'nullable|image',
            'frame' => 'nullable|image',
            'frame_animation' => 'nullable',
            'badge' => 'nullable|image',
            'background' => 'nullable|image',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        return DB::transaction(function () use ($request, $id) {

            $item = $id ? RelationshipItem::find($id) : new RelationshipItem();

            if ($id && !$item) {
                return redirect()->back()->with('error', 'Item not found');
            }

            $data = $request->only(['name', 'type', 'required_coins']);

            foreach (['icon', 'gif', 'ring', 'avatar', 'frame', 'frame_animation', 'badge', 'background'] as $file) {

                if ($request->hasFile($file)) {

                    if ($id && $item->$file && file_exists(public_path($item->$file))) {
                        @unlink(public_path($item->$file));
                    }

                    $data[$file] = Helper::saveFile($request->file($file), 'relationship');
                }
            }

            $item->fill($data)->save();

            return redirect()
                ->route('relationship.item')
                ->with('success', $id ? 'Updated successfully' : 'Created successfully');
        });
    }

    public function delete(Request $request): JsonResponse
    {
        return Helper::deleteRecord(new RelationshipItem, $request->id);
    }

    public function userRelationshipList(Request $request)
    {
        if ($request->ajax()) {

            $query = RelationshipInvitation::with(['sender', 'receiver', 'relationshipItem'])->latest();

            return DataTables::of($query)

                ->addIndexColumn()

                ->addColumn('sender', function ($row) {

                    if (!$row->sender) {
                        return '-';
                    }

                    $user = $row->sender;

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

                ->addColumn('receiver', function ($row) {

                    if (!$row->receiver) {
                        return '-';
                    }

                    $user = $row->receiver;

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

                ->addColumn('relation_item', function ($row) {
                    return $row->relationshipItem->name ?? '-';
                })

                ->editColumn('type', function ($row) {
                    return ucfirst($row->type);
                })

                ->editColumn('status', function ($row) {

                    if ($row->status == 'accept') {
                        return '<span class="badge bg-success">Accepted</span>';
                    }

                    return '<span class="badge bg-warning">' . $row->status . '</span>';
                })

                ->rawColumns(['sender', 'receiver', 'status'])
                ->make(true);
        }

        return view('relationship.user-relation-list');
    }
}
