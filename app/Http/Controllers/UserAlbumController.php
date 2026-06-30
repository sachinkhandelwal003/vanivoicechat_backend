<?php

namespace App\Http\Controllers;

use App\Models\UserAlbum;
use App\Models\AppUser;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class UserAlbumController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {

            $albums = UserAlbum::with('user');

            // ---------- FILTERS ----------
            if ($request->uid != '') {
                $albums->whereHas('user', function ($q) use ($request) {
                    $q->where('uid', $request->uid);
                });
            }

            if ($request->username != '') {
                $albums->whereHas('user', function ($q) use ($request) {
                    $q->where('name', "LIKE", "%{$request->username}%");
                });
            }

            return DataTables::of($albums)
                ->addIndexColumn()

                // USER COLUMN
                ->addColumn('user_info', function ($row) {
                    if (!$row->user) return '-';

                    return '
                        <div class="d-flex align-items-center">
                            <img src="'.asset('storage/'.$row->user->image).'" width="40" class="rounded-circle me-2">
                            <div>
                                <strong>'.$row->user->name.'</strong><br>
                                <small>'.$row->user->uid.'</small>
                            </div>
                        </div>
                    ';
                })

                // PHOTO ALBUM IMAGE
                ->addColumn('photo', function ($row) {
                    return '<img src="'.asset('storage/'.$row->image).'" width="60" class="rounded">';
                })

                ->editColumn('created_at', function ($row) {
                    return $row->created_at->format('d M Y • h:i A');
                })

                ->addColumn('action', function ($row) {
                    return '
                        <button class="btn btn-sm btn-danger delete" data-id="'.$row->id.'">
                            Delete
                        </button>
                    ';
                })

                ->rawColumns(['user_info', 'photo', 'action'])
                ->make(true);
        }

        return view('useralbum.index');
    }


    // DELETE
    public function destroy(Request $request)
    {
        $album = UserAlbum::find($request->id);

        if (!$album) {
            return response()->json(['status' => false, 'message' => 'Record not found']);
        }

        $album->delete();

        return response()->json(['status' => true, 'message' => 'Album image deleted successfully']);
    }
}
