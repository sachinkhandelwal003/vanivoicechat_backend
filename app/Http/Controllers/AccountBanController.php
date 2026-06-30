<?php

namespace App\Http\Controllers;

use App\Models\AccountBan;
use App\Models\BlockedDevice;
use App\Models\BlockedIp;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class AccountBanController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }


    // ---------------- ACCOUNT BAN TAB -------------------
    public function index(Request $request): View|\Illuminate\Http\JsonResponse
    {
        if ($request->ajax()) {
            $data = AccountBan::select('id', 'user_id', 'operator', 'remark', 'operation_time', 'created_at');

            return DataTables::of($data)
                ->editColumn('created_at', fn($row) => $row->created_at->format('d M, Y'))
                ->addColumn('action', function ($row) {
                    return '<button class="btn btn-sm btn-danger delete" data-id="' . $row->id . '">Delete</button>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('accountban.index');
    }



    // ---------------- BLOCKED DEVICES TAB -------------------
    public function deviceList(Request $request)
    {
        if ($request->ajax()) {

            $data = BlockedDevice::select('id', 'device_number', 'operator', 'remark', 'operation_time', 'created_at');

            return DataTables::of($data)
                ->editColumn('created_at', fn($row) => $row->created_at->format('d M, Y'))
                ->addColumn('action', function ($row) {
                    return '<button class="btn btn-sm btn-danger delete" data-id="' . $row->id . '">Delete</button>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }
    }


    // ---------------- BLOCKED IPS TAB -------------------
    public function ipList(Request $request)
    {
        if ($request->ajax()) {

            $data = BlockedIp::select('id', 'ip_address', 'operator', 'remark', 'operation_time', 'created_at');

            return DataTables::of($data)
                ->editColumn('created_at', fn($row) => $row->created_at->format('d M, Y'))
                ->addColumn('action', function ($row) {
                    return '<button class="btn btn-sm btn-danger delete" data-id="' . $row->id . '">Delete</button>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }
    }
}
