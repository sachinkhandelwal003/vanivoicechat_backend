<?php

namespace App\Http\Controllers;

use App\Models\Violation;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class ViolationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }


    public function index(Request $request)
    {
        if ($request->ajax()) {

            $query = Violation::with('user');

            if ($request->type != '') {
                $query->where('type', $request->type);
            }

            if ($request->operator != '') {
                $query->where('operator', $request->operator);
            }

            return DataTables::of($query)
                ->addIndexColumn()

                ->addColumn('violation_user', function ($row) {
                    if (!$row->user) return 'Unknown';

                    $img = $row->user->image ? asset('storage/' . $row->user->image) : asset('default.png');

                    return '
                        <div class="d-flex align-items-center">
                            <img src="'.$img.'" class="rounded-circle" width="40" height="40">
                            <div class="ms-2">
                                <strong>'.$row->user->name.'</strong><br>
                                <small>'.$row->user->number.'</small>
                            </div>
                        </div>
                    ';
                })

                ->editColumn('created_at', function ($row) {
                    return $row->created_at
                        ? $row->created_at->format('Y-m-d H:i:s')
                        : '';
                })

                ->addColumn('action', function ($row) {
                    return '
                        <button class="btn btn-sm btn-light text-danger delete" data-id="'.$row->id.'">
                            <i class="fa fa-trash"></i>
                        </button>
                    ';
                })

                ->rawColumns(['violation_user', 'action'])
                ->make(true);
        }

        return view('violation.index');
    }
}
