<?php

namespace App\Http\Controllers;

use App\Models\Cms;
use App\Helper\Helper;
use App\Models\RewardInviting;
use App\Models\InviteUser;
use Illuminate\View\View;
use Illuminate\Http\Request;
use \Yajra\Datatables\Datatables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class RewardInvitingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            $data = RewardInviting::select('id', 'target_person', 'reward_coin', 'status', 'created_at');
            return Datatables::of($data)
                ->editColumn('image', function ($row) {
                    $btn = '<div class="img-group"><img class="" src="' . asset('storage/' . $row['image']) . '" alt=""></div>';
                    return $btn;
                })
                ->editColumn('created_at', function ($row) {
                    return $row['created_at']->format('d M, Y');
                })
                ->editColumn('status', function ($row) {
                    return $row['status'] == 1 ? '<small class="badge fw-semi-bold rounded-pill status badge-light-success"> Active</small>' : '<small class="badge fw-semi-bold rounded-pill status badge-light-danger"> Inactive</small>';
                })
                ->addColumn('action', function ($row) {

                    $btn = '<button class="text-600 btn-reveal dropdown-toggle btn btn-link btn-sm" id="drop" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="fas fa-ellipsis-h fs--1"></span></button><div class="dropdown-menu" aria-labelledby="drop">';
                    if (Helper::userCan(107, 'can_edit')) {
                        $btn .= '<a class="dropdown-item" href="' . route('cms.edit', $row['id']) . '">Edit</a>';
                    }
                    if (Helper::userAllowed(107)) {
                        return $btn;
                    } else {
                        return '';
                    }
                })
                ->orderColumn('created_at', function ($query, $order) {
                    $query->orderBy('created_at', $order);
                })
                ->rawColumns(['action', 'image', 'status'])
                ->make(true);
        }
        return view('rewardinviting.index');
    }

    public function add(): View
    {
        return view('rewardinviting.add');
    }

    public function save(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'target_person'         => ['required'],
            'reward_coin'   => ['required'],
            'status'        => ['required']
        ]);

        RewardInviting::create($validated);

        return to_route('reward-inviting')->withSuccess('Reward Inviting Added Successfully..!!');
    }

    public function edit($id): View|RedirectResponse
    {
        $cms = Cms::find($id);
        if (!$cms) {
            return to_route('cms')->withError('Cms Not Found..!!');
        }
        return view('cms.edit', compact('cms'));
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $cms = Cms::find($id);
        if (!$cms) {
            return to_route('cms')->withError('Cms Not Found..!!');
        }

        $data = $request->validate([
            'title'         => ['required', 'string', 'max:200'],
            'description'   => ['required', 'string', 'max:10000'],
            'status'        => ['required', 'integer'],
            'image'         => ['image', 'mimes:jpg,png,jpeg', 'max:5048']
        ]);

        if ($request->file('image')) {
            Helper::deleteFile($cms->image);
            $data['image'] = Helper::saveFile($request->file('image'), 'cms');
        }

        $cms->update($data);
        return to_route('cms')->withSuccess('Cms Updated Successfully..!!');
    }

    public function delete(Request $request): JsonResponse
    {
        return Helper::deleteRecord(new Cms, $request->id);
    }

    public function inviteUserIndex(Request $request)
{
    if ($request->ajax()) {

        $query = InviteUser::with(['inviter', 'invitedUser'])->latest();

        return DataTables::of($query)

            ->addIndexColumn()

            ->addColumn('inviter', function ($row) {

                if (!$row->inviter) {
                    return '-';
                }

                $user = $row->inviter;

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
                            '.$badgeHtml.'
                            <span style="color:'.($uidData['badge_color'] ?? '#000').';font-weight:600;">
                                '.e($uidData['uid']).'
                            </span>
                            <span class="text-muted">/</span>
                            <span class="text-muted">'.e($uidData['system_uid']).'</span>
                        </small>';

                } else {

                    $uidHtml = '
                        <small class="text-muted">
                            '.e($uidData['system_uid'] ?? $user->uid).'
                        </small>';
                }

                return '
                    <div class="d-flex align-items-center gap-2">

                        <img src="'.$image.'"
                             width="45"
                             height="45"
                             class="rounded-circle">

                        <div>
                            <div class="fw-bold">'.e($user->name).'</div>
                            '.$uidHtml.'
                        </div>

                    </div>';
            })

            ->addColumn('invited_user', function ($row) {

                if (!$row->invitedUser) {
                    return '-';
                }

                $user = $row->invitedUser;

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
                            '.$badgeHtml.'
                            <span style="color:'.($uidData['badge_color'] ?? '#000').';font-weight:600;">
                                '.e($uidData['uid']).'
                            </span>
                            <span class="text-muted">/</span>
                            <span class="text-muted">'.e($uidData['system_uid']).'</span>
                        </small>';

                } else {

                    $uidHtml = '
                        <small class="text-muted">
                            '.e($uidData['system_uid'] ?? $user->uid).'
                        </small>';
                }

                return '
                    <div class="d-flex align-items-center gap-2">

                        <img src="'.$image.'"
                             width="45"
                             height="45"
                             class="rounded-circle">

                        <div>
                            <div class="fw-bold">'.e($user->name).'</div>
                            '.$uidHtml.'
                        </div>

                    </div>';
            })

            ->editColumn('invite_code', function ($row) {
                return '<span class="badge bg-primary">'.$row->invite_code.'</span>';
            })

            ->editColumn('is_completed', function ($row) {

                return $row->is_completed
                    ? '<span class="badge bg-success">Accepted</span>'
                    : '<span class="badge bg-warning">Pending</span>';
            })

            ->editColumn('completed_at', function ($row) {

                return $row->completed_at
                    ? \Carbon\Carbon::parse($row->completed_at)->format('Y-m-d H:i:s')
                    : '-';
            })

            ->editColumn('created_at', function ($row) {

                return $row->created_at
                    ? $row->created_at->format('Y-m-d H:i:s')
                    : '-';
            })

            ->rawColumns([
                'inviter',
                'invited_user',
                'invite_code',
                'is_completed'
            ])

            ->make(true);
    }

    return view('rewardinviting.invite_user_list');
}
}
