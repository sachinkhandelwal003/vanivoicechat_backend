<?php

namespace App\Http\Controllers;

use App\Models\UserBadge;
use App\Models\AppUser;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class UserBadgeController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {

            $badges = UserBadge::with('user');

            // ---------------- FILTERS ----------------
            if ($request->uid != '') {
                $badges->whereHas('user', function ($q) use ($request) {
                    $q->where('number', $request->uid);
                });
            }

            if ($request->username != '') {
                $badges->whereHas('user', function ($q) use ($request) {
                    $q->where('name', 'LIKE', "%{$request->username}%");
                });
            }

            if ($request->badge_id != '') {
                $badges->where('badge_id', $request->badge_id);
            }

            return DataTables::of($badges)

                ->addColumn('user_info', function ($row) {
                    if (!$row->user) return 'N/A';

                    return '
                        <div class="d-flex align-items-center">
                            <img src="' . asset('storage/'.$row->user->image) . '" class="rounded-circle" width="40">
                            <div class="ms-2">
                                <div>' . $row->user->name . '</div>
                                <small>' . $row->user->number . '</small>
                            </div>
                        </div>
                    ';
                })

                ->addColumn('badge_image', function ($row) {
                    return '<img src="' . asset('storage/'.$row->badge_resources) . '" width="50">';
                })

                ->addColumn('status_text', function ($row) {
                    return $row->usage_status == 1
                        ? '<span class="badge bg-success">use</span>'
                        : '<span class="badge bg-danger">inactive</span>';
                })

                ->addColumn('time', function ($row) {
                    return '
                        Creation time: '.$row->created_at->format("Y-m-d H:i:s").'<br>
                        Expiration date: '.($row->updated_at ? $row->updated_at->format("Y-m-d H:i:s") : "N/A").'
                    ';
                })

                ->addColumn('action', function ($row) {
                    return '
                        <button class="btn btn-sm btn-light delete" data-id="'.$row->id.'">
                            <i class="fa fa-trash"></i>
                        </button>
                    ';
                })

                ->rawColumns(['user_info','badge_image','status_text','time','action'])
                ->make(true);
        }

        return view('userbadges.index');
    }
}
