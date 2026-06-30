<?php

namespace App\Http\Controllers;

use App\Models\UserMusic;
use App\Models\AppUser;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class UserMusicController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {

            $query = UserMusic::with('user');

            // Filters
            if ($request->uid) {
                $query->whereHas('user', function($q) use ($request) {
                    $q->where('uid', $request->uid);
                });
            }

            if ($request->username) {
                $query->whereHas('user', function($q) use ($request) {
                    $q->where('name', 'like', "%{$request->username}%");
                });
            }

            if ($request->review_status) {
                $query->where('review_status', $request->review_status);
            }

            return DataTables::of($query)
                ->addColumn('user_info', function ($row) {

                    $img = asset('storage/' . ($row->user->image ?? 'default.png'));

                    return '
                        <div class="d-flex align-items-center">
                            <img src="'.$img.'" class="rounded-circle" width="40" height="40">
                            <div class="ms-2">
                                <div>'.$row->user->name.'</div>
                                <small>'.$row->user->uid.'</small>
                            </div>
                        </div>
                    ';
                })
                ->editColumn('review_status', function ($row) {

                    $color = match($row->review_status) {
                        'approved' => 'badge-success',
                        'rejected' => 'badge-danger',
                        default => 'badge-warning'
                    };

                    return '<span class="badge '.$color.'">'.$row->review_status.'</span>';
                })
                ->addColumn('music_content', function ($row) {

                    return '<a href="#" class="text-primary">Play</a>
                            <a href="#" class="text-danger ms-2">pause</a>';
                })
                ->editColumn('created_at', function ($row) {
                    return 'Creation time: ' . $row->created_at->format('Y-m-d H:i:s') .
                           '<br>Updated: ' . $row->updated_at->format('Y-m-d H:i:s');
                })
                ->addColumn('action', function ($row) {
                    return '<button data-id="'.$row->id.'" class="btn btn-sm text-danger delete">...</button>';
                })
                ->rawColumns(['user_info', 'review_status', 'music_content', 'created_at', 'action'])
                ->make(true);
        }

        return view('usermusic.index');
    }


    // Delete Action
    public function delete(Request $request)
    {
        $music = UserMusic::find($request->id);

        if (!$music) {
            return response()->json(['status' => false, 'message' => 'Record not found!']);
        }

        $music->delete();

        return response()->json(['status' => true, 'message' => 'Music entry deleted successfully']);
    }
}
