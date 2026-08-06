<?php

namespace App\Http\Controllers;

use App\Models\Medal;
use App\Models\AppUser;
use App\Helper\Helper;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MedalController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {

            $query = Medal::latest();

            return DataTables::of($query)
                ->addIndexColumn()

                ->editColumn('icon', function ($row) {

                    $image = asset('storage/' . $row->icon);

                    return '
                        <img src="' . $image . '" width="40" height="40" class="image-preview" data-image="' . $image . '"
                             style="cursor:pointer;border-radius:6px;object-fit:cover;">
                    ';
                })

                ->editColumn('type', function ($row) {
                    return ucfirst($row->type); // achievement / event
                })

                ->editColumn('created_at', function ($row) {
                    return $row->created_at
                        ? $row->created_at->timezone('Asia/Kolkata')->format('d M Y, h:i A')
                        : '-';
                })

                ->editColumn('status', function ($row) {
                    return $row->status ? 'Active' : 'Inactive';
                })

                ->addColumn('action', function ($row) {

                    if (!Helper::userCan(104, 'can_edit') && !Helper::userCan(105, 'can_delete')) {
                        return '-';
                    }

                    $btn = '
                            <div class="dropdown">
                                <button class="btn btn-sm btn-link dropdown-toggle" data-bs-toggle="dropdown">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>

                                <div class="dropdown-menu">';

                    // Edit Permission
                    if (Helper::userCan(124, 'can_edit')) {
                        $btn .= '
                                <a class="dropdown-item"
                                href="' . route('medals.form', $row->id) . '">
                                    <i class="fas fa-edit text-primary"></i> Edit
                                </a>';
                    }

                    // Delete Permission
                    if (Helper::userCan(124, 'can_delete')) {
                        $btn .= '
                                <button class="dropdown-item text-danger delete"
                                        data-id="' . $row->id . '">
                                    <i class="fas fa-trash"></i> Delete
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

        return view('medals.index');
    }

    public function form($id = null): View|RedirectResponse
    {
        $medal = null;

        if ($id) {
            $medal = Medal::find($id);

            if (!$medal) {
                return redirect()->route('medals.index')->with('error', 'Medal not found');
            }
        }

        return view('medals.form', compact('medal'));
    }

    public function store(Request $request, $id = null)
    {
        $rules = [
            'title' => 'required|string|max:255',
            'type' => 'required|in:achievement,event',
            'file_type' => 'nullable',
            'icon' => 'nullable|file',
            'level' => 'nullable',
            'sort'  => 'required',
            'target_value' => 'required',
            'status' => 'nullable|boolean',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        return DB::transaction(function () use ($request, $id) {

            $medal = $id ? Medal::find($id) : new Medal();

            if ($id && !$medal) {
                return redirect()->back()->with('error', 'Medal not found');
            }

            $data = $request->only([
                'title',
                'type',
                'status',
                'sort',
                'level',
                'target_value',
                'file_type',
            ]);

            if ($request->hasFile('icon')) {

                if ($id && $medal->icon && file_exists(public_path($medal->icon))) {
                    @unlink(public_path($medal->icon));
                }

                $data['icon'] = Helper::saveFile($request->file('icon'), 'medals');
            }

            $medal->fill($data)->save();

            return redirect()
                ->route('medals.index')
                ->with('success', $id ? 'Medal updated successfully' : 'Medal added successfully');
        });
    }

    public function delete(Request $request): JsonResponse
    {
        return Helper::deleteRecord(new Medal, $request->id);
    }



    public function userMedals(Request $request)
    {
        if ($request->ajax()) {

            $query = AppUser::with([
                'userMedals.medal'
            ])->has('userMedals');

            return DataTables::of($query)

                ->addColumn('user', function ($row) {

                    $user = $row;

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
                                width="40"
                                height="40"
                                class="rounded-circle">

                            <div>
                                <div class="fw-bold">' . e($user->name) . '</div>
                                ' . $uidHtml . '
                            </div>

                        </div>
                    ';
                })

                ->addColumn('total_medals', function ($row) {

                    return $row->userMedals->count();
                })

                ->addColumn('medals', function ($row) {

                    $html = '<div class="d-flex flex-wrap gap-2">';

                    foreach ($row->userMedals as $userMedal) {

                        if (!$userMedal->medal) {
                            continue;
                        }

                        $image = $userMedal->medal->icon
                            ? asset('storage/' . $userMedal->medal->icon)
                            : asset('assets/img/avatar.png');

                        $html .= '
                            <img src="' . $image . '" class="medal-image" data-image="' . $image . '" width="40" height="40"
                                 style="cursor:pointer;border-radius:50%;object-fit:cover;">
                        ';
                    }

                    $html .= '</div>';

                    return $html;
                })

                ->rawColumns([
                    'user',
                    'medals'
                ])

                ->make(true);
        }

        return view('medals.user_medals');
    }
}
