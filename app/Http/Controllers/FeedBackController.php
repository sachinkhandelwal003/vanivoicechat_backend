<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use App\Models\AppUser;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class FeedBackController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Feedback::with('user');
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
            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('user_name', function ($row) {
                    return $row->user ? $row->user->name : 'N/A';
                })
                ->editColumn('created_at', function ($row) {
                    return $row->created_at
                        ? $row->created_at->format('Y-m-d H:i:s')
                        : '';
                })
                ->addColumn('action', function ($row) {
                    return '<button class="btn btn-sm btn-light text-danger delete" data-id="' . $row->id . '">
                                <i class="fa fa-trash"></i>
                            </button>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('feedback.index');
    }
}
