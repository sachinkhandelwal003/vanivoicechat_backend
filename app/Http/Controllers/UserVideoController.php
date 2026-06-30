<?php

namespace App\Http\Controllers;

use App\Models\UserVideo;
use App\Models\AppUser;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class UserVideoController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {

            $query = UserVideo::with('user');

            // ---- Filters ----
            if ($request->uid != '') {
                $query->whereHas('user', function ($q) use ($request) {
                    $q->where('uid', $request->uid);
                });
            }

            if ($request->username != '') {
                $query->whereHas('user', function ($q) use ($request) {
                    $q->where('name', 'LIKE', "%{$request->username}%");
                });
            }

            if ($request->review_status != '') {
                $query->where('review_status', $request->review_status);
            }

            return DataTables::of($query)
                ->addColumn('user_info', function($row) {
                    return '
                        <div class="d-flex align-items-center">
                            <img src="/storage/'.$row->user->image.'" class="rounded-circle" width="40">
                            <div class="ms-2">
                                <strong>'.$row->user->name.'</strong><br>
                                <small>'.$row->user->uid.'</small>
                            </div>
                        </div>
                    ';
                })

                ->addColumn('video_preview', function($row) {
                    return '
                        <div class="video-hover-box">
                            <i class="fa-solid fa-video text-primary fs-4"></i>
                            <div class="video-popup">
                                <video width="200" controls>
                                    <source src="/storage/'.$row->video_content.'" type="video/mp4">
                                </video>
                            </div>
                        </div>
                    ';
                })

                ->editColumn('review_status', function($row) {
                    $color = $row->review_status == "approved" ? "success" : ($row->review_status == "rejected" ? "danger" : "warning");
                    return '<span class="badge bg-'.$color.'">'.ucfirst($row->review_status).'</span>';
                })

                ->editColumn('created_at', function ($row) {
                    return $row->created_at->format('d M Y h:i A');
                })

                ->addColumn('action', function($row){
                    return '<button class="btn btn-sm btn-danger delete" data-id="'.$row->id.'">Delete</button>';
                })

                ->rawColumns(['user_info','video_preview','review_status','action'])
                ->make(true);
        }

        return view('uservideo.index');
    }


    // Delete Video
    public function delete(Request $request)
    {
        $video = UserVideo::find($request->id);
        if ($video) {
            $video->delete();
            return response()->json(['status' => true, 'message' => 'Video deleted successfully']);
        }

        return response()->json(['status' => false, 'message' => 'Record not found']);
    }
}
