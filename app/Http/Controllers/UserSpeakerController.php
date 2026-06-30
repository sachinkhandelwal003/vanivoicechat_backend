<?php

namespace App\Http\Controllers;

use App\Models\UserSpeaker;
use App\Models\AppUser;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class UserSpeakerController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {

            $query = UserSpeaker::with('user');

            if ($request->uid) {
                $query->whereHas('user', function ($q) use ($request) {
                    $q->where('uid', $request->uid);
                });
            }

            if ($request->username) {
                $query->whereHas('user', function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->username . '%');
                });
            }

            return DataTables::of($query)
                ->addColumn('user_info', function ($row) {

                    $img = asset('storage/' . ($row->user->image ?? 'default.png'));

                    return '
                        <div class="d-flex align-items-center">
                            <img src="'.$img.'" class="rounded-circle me-2" width="40" height="40">
                            <div>
                                <strong>'.$row->user->name.'</strong><br>
                                '.$row->user->uid.'
                            </div>
                        </div>';
                })
                ->editColumn('created_at', function ($row) {
                    return "Creation time: " . $row->created_at->format("Y-m-d H:i:s");
                })
                ->addColumn('action', function ($row) {
                    return '<a href="javascript:void(0)" class="text-danger delete" data-id="'.$row->id.'">delete</a>';
                })
                ->rawColumns(['user_info', 'action'])
                ->make(true);
        }

        return view('userspeaker.index');
    }

    public function delete(Request $request)
    {
        $speaker = UserSpeaker::find($request->id);

        if (!$speaker) {
            return response()->json(['status' => false, 'message' => 'Record not found']);
        }

        $speaker->delete();

        return response()->json(['status' => true, 'message' => 'Deleted successfully']);
    }
}
