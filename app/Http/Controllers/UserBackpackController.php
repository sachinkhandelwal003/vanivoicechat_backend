<?php

namespace App\Http\Controllers;

use App\Models\UserBackpack;
use App\Models\AppUser;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class UserBackpackController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {

            $query = UserBackpack::with('user')->orderBy('id', 'DESC');

            if ($request->uid) {
                $query->whereHas('user', function($q) use ($request) {
                    $q->where('uid', $request->uid);
                });
            }

            if ($request->username) {
                $query->whereHas('user', function($q) use ($request) {
                    $q->where('name', 'LIKE', '%' . $request->username . '%');
                });
            }

            return DataTables::of($query)
                ->addColumn('user_info', function ($row) {
                    return '
                        <div>
                            <img src="'.asset('storage/'.$row->user->image).'" width="40" class="rounded-circle">
                            <div>'.$row->user->name.'<br>'.$row->user->uid.'</div>
                        </div>
                    ';
                })
                ->addColumn('photo', function ($row) {
                    return '<img src="'.asset('storage/'.$row->prop_cover).'" width="50">';
                })
                ->addColumn('is_worn', function ($row) {
                    return $row->is_worn ? '<span class="badge bg-success">yes</span>' : '<span class="badge bg-warning">no</span>';
                })
                ->addColumn('is_giftable', function ($row) {
                    return $row->is_giftable ? '<span class="badge bg-success">yes</span>' : '<span class="badge bg-danger">no</span>';
                })
                ->addColumn('action', function ($row) {
                    return '<button class="btn btn-danger btn-sm delete" data-id="'.$row->id.'">Delete</button>';
                })
                ->rawColumns(['user_info', 'photo', 'is_worn', 'is_giftable', 'action'])
                ->make(true);
        }

        return view('userbackpack.index');
    }
}
