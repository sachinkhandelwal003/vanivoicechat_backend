<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use App\Models\AppUser;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class WalletController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {

            $wallet = Wallet::with('user');

            // ---------- FILTERS ----------
            if ($request->uid != '') {
                $wallet->whereHas('user', function ($q) use ($request) {
                    $q->where('uid', $request->uid);
                });
            }

            if ($request->username != '') {
                $wallet->whereHas('user', function ($q) use ($request) {
                    $q->where('name', 'LIKE', "%{$request->username}%");
                });
            }

            if ($request->type != '') {
                $wallet->where('type', $request->type);
            }

            if ($request->transaction_type != '') {
                $wallet->where('operate', $request->transaction_type);
            }

            if ($request->date_from != '') {
                $wallet->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->date_to != '') {
                $wallet->whereDate('created_at', '<=', $request->date_to);
            }

            return DataTables::of($wallet)

                ->addIndexColumn()

                // USER INFO COLUMN
                ->addColumn('user_info', function ($row) {
                    if (!$row->user) return "N/A";
                    return '
                        <div class="d-flex align-items-center">
                            <img src="' . asset('storage/' . $row->user->image) . '"
                                 class="rounded-circle" width="40">
                            <div class="ms-2">
                                <strong>' . $row->user->name . '</strong><br>
                                <small>' . $row->user->uid . '</small>
                            </div>
                        </div>
                    ';
                })

                ->editColumn('type', function ($row) {
                    $color = $row->type == 'income' ? 'bg-primary' : 'bg-success';

                    return '<span class="badge ' . $color . '">' . ucfirst($row->type) . '</span>';
                })

                ->editColumn('wallet_type', function ($row) {
                    return '<span class="badge bg-info">' . $row->wallet_type . '</span>';
                })

                ->editColumn('created_at', function ($row) {
                    return date('d M Y H:i A', strtotime($row->created_at));
                })

                ->addColumn('action', function ($row) {
                    return '
                        <button class="btn btn-sm btn-light">...</button>
                    ';
                })

                ->rawColumns(['user_info', 'type', 'wallet_type', 'action'])
                ->make(true);
        }

        return view('wallets.index');
    }
}
