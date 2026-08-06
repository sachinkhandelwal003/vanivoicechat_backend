<?php

namespace App\Http\Controllers;

use App\Models\RedEnvelope;
use App\Helper\Helper;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\JsonResponse;

class RedEnvelopeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {

            $query = RedEnvelope::latest();
            // $query = RedEnvelope::with(['room', 'sender'])->latest();

            return DataTables::of($query)

                ->addIndexColumn()

                ->addColumn('sender_user_id', function ($row) {

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

                ->addColumn('room_id', function ($row) {

                    return $row->room->room_name ?? $row->room_id;
                })

                ->editColumn('status', function ($row) {

                    if ($row->status == 'active') {
                        return '<span class="badge bg-success">Active</span>';
                    }

                    return '<span class="badge bg-danger">Completed</span>';
                })

                ->editColumn('created_at', function ($row) {
                    return $row->created_at
                        ? $row->created_at->format('d M Y h:i A')
                        : '-';
                })

                ->editColumn('expires_at', function ($row) {
                    return $row->expires_at
                        ? \Carbon\Carbon::parse($row->expires_at)->format('d M Y h:i A')
                        : '-';
                })

                ->addColumn('action', function ($row) {

                    $viewUrl = route('red.envelope.claims', $row->id);

                    $btn = '
                            <div class="dropdown">
                                <button class="btn btn-light action-btn" data-bs-toggle="dropdown">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>

                                <ul class="dropdown-menu shadow-sm">';

                    // View Permission
                    if (Helper::userCan(132, 'can_view')) {
                        $btn .= '
                                <li>
                                    <a href="' . $viewUrl . '" class="dropdown-item">
                                        <i class="fas fa-eye me-2 text-primary"></i> View
                                    </a>
                                </li>';
                    }

                    // Delete Permission
                    if (Helper::userCan(132, 'can_delete')) {
                        $btn .= '
                                <li>
                                    <a href="javascript:void(0)"
                                    data-id="' . $row->id . '"
                                    class="dropdown-item text-danger delete-envelope">
                                        <i class="fas fa-trash-alt me-2"></i> Delete
                                    </a>
                                </li>';
                    }

                    $btn .= '
                            </ul>
                        </div>';

                    return $btn;
                })

                ->rawColumns(['room_id', 'sender_user_id', 'status', 'action'])
                ->make(true);
        }

        return view('red_envelope.index');
    }

    public function claims($id)
    {
        $envelope = RedEnvelope::with(['claims.user', 'claims.room'])->findOrFail($id);
        return view('red_envelope.claims', compact('envelope'));
    }

    public function destroy($id)
    {
        $envelope = RedEnvelope::findOrFail($id);
        $envelope->delete();

        return response()->json([
            'status' => true,
            'message' => 'Red Envelope Deleted Successfully'
        ]);
    }
}
