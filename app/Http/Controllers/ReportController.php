<?php

namespace App\Http\Controllers;


use App\Models\ChatReport;
use App\Models\PostReport;
use App\Helper\Helper;
use Illuminate\View\View;
use Illuminate\Http\Request;
use \Yajra\Datatables\Datatables;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function postIndex(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            $data = PostReport::with(['user', 'post'])->latest();
            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('user', function ($row) {
                    if (!$row->user) return '-';

                    $image = $row->user->image
                        ? Helper::showImage($row->user->image, true)
                        : asset('assets/img/avatar.png');

                    return '
                    <div class="d-flex align-items-center gap-2">
                        <img src="' . $image . '" class="rounded-circle" width="40" height="40">
                        <div>
                            <div class="fw-bold">' . e($row->user->name) . '</div>
                            <small class="text-muted">' . e($row->user->uid) . '</small>
                        </div>
                    </div>
                ';
                })
                ->addColumn('post_title', function ($row) {
                    return $row->post ? $row->post->description : 'N/A';
                })
                ->addColumn('action', function ($row) {
                    $btn = '<div class="dropdown">
                    <button class="btn btn-sm btn-link dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-ellipsis-h"></i>
                    </button>
                    <div class="dropdown-menu">';
                    if (Helper::userCan(105, 'can_delete')) {
                        $btn .= '<button class="dropdown-item text-danger delete" data-id="' . $row->id . '">Delete</button>';
                    }

                    $btn .= '</div></div>';

                    return $btn;
                })
                ->rawColumns(['action', 'user', 'post_title'])
                ->make(true);
        }
        return view('reports.post-index');
    }


    public function postDestroy(Request $request): JsonResponse
    {
        return Helper::deleteRecord(new PostReport, $request->id);
    }
    public function userIndex(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            $data = ChatReport::with(['reporter', 'reportedUser'])->latest();
            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('reporter_name', function ($row) {
                    return $row->reporter ? $row->reporter->name : 'N/A';
                })

                ->addColumn('reported_user_name', function ($row) {
                    return $row->reportedUser ? $row->reportedUser->name : 'N/A';
                })
                ->addColumn('action', function ($row) {
                    $btn = '<div class="dropdown">
                    <button class="btn btn-sm btn-link dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-ellipsis-h"></i>
                    </button>
                    <div class="dropdown-menu">';
                    if (Helper::userCan(105, 'can_delete')) {
                        $btn .= '<button class="dropdown-item text-danger delete" data-id="' . $row->id . '">Delete</button>';
                    }

                    $btn .= '</div></div>';

                    return $btn;
                })
                ->rawColumns(['action', 'reporter_name', 'reported_user_name'])
                ->make(true);
        }
        return view('reports.user-index');
    }


    public function userDestroy(Request $request): JsonResponse
    {
        return Helper::deleteRecord(new ChatReport(), $request->id);
    }
}
