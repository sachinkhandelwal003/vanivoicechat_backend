<?php

namespace App\Http\Controllers;

use App\Models\Agency;
use App\Models\AppUser;
use App\Models\Host;
use App\Models\BdUser;
use App\Models\AdminAccount;
use App\Models\CoinSeller;
use App\Models\ManualMoneyTransaction;
use App\Helper\Helper;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use \Yajra\Datatables\Datatables;

class ManualMoneyController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {

            $query = ManualMoneyTransaction::with([
                'user',
                'admin',
                'bd',
                'agency',
                'host',
                'coinSeller'
            ])->latest();

            /*
        |--------------------------------------------------------------------------
        | Filters
        |--------------------------------------------------------------------------
        */

            if ($request->filled('uid')) {

                $query->whereHas('user', function ($q) use ($request) {

                    $q->where('uid', $request->uid);
                });
            }

            if ($request->filled('type')) {

                $query->where('type', $request->type);
            }

            if ($request->filled('date')) {

                $query->whereDate('created_at', $request->date);
            }

            if ($request->filled('role')) {

                $role = $request->role;

                $query->where(function ($q) use ($role) {

                    switch ($role) {

                        case 'admin':
                            $q->has('adminProfile');
                            break;

                        case 'bd':
                            $q->has('bd');
                            break;

                        case 'agency':
                            $q->has('agency');
                            break;

                        case 'host':
                            $q->has('host');
                            break;

                        case 'coinseller':
                            $q->whereHas('coinSeller', function ($c) {
                                $c->where('is_merchant', 0);
                            });
                            break;

                        case 'merchant':
                            $q->whereHas('coinSeller', function ($c) {
                                $c->where('is_merchant', 1);
                            });
                            break;
                    }
                });
            }

            return DataTables::of($query)

                ->addIndexColumn()

                ->addColumn('user', function ($row) {

                    if (!$row->user) {
                        return '-';
                    }

                    $image = $row->user->image
                        ? Helper::showImage($row->user->image, true)
                        : asset('assets/img/avatar.png');

                    return '
                    <div class="d-flex align-items-center gap-2">

                        <img src="' . $image . '"
                             width="40"
                             height="40"
                             class="rounded-circle">

                        <div>

                            <div class="fw-bold">'
                        . e($row->user->name) .
                        '</div>

                            <small class="text-muted">
                                UID : ' . e($row->user->uid) . '
                            </small>

                        </div>

                    </div>
                ';
                })

                ->addColumn('role', function ($row) {

                    if ($row->adminProfile) {

                        return '<span class="badge bg-primary">Admin</span>';
                    }

                    if ($row->bd) {

                        return '<span class="badge bg-info">BD</span>';
                    }

                    if ($row->agency) {

                        return '<span class="badge bg-success">Agency</span>';
                    }

                    if ($row->host) {

                        return '<span class="badge bg-warning text-dark">Host</span>';
                    }

                    if ($row->coinSeller) {

                        return $row->coinSeller->is_merchant
                            ? '<span class="badge bg-dark">Merchant</span>'
                            : '<span class="badge bg-secondary">Coin Seller</span>';
                    }

                    return '<span class="badge bg-primary">Admin</span>';
                })

                ->editColumn('type', function ($row) {

                    return $row->type == 'credit'
                        ? '<span class="badge bg-success">Credit</span>'
                        : '<span class="badge bg-danger">Deduct</span>';
                })

                ->editColumn('amount', function ($row) {

                    return '$ ' . number_format($row->amount, 2);
                })

                ->editColumn('before_balance', function ($row) {

                    return '$ ' . number_format($row->before_balance, 2);
                })

                ->editColumn('after_balance', function ($row) {

                    return '$ ' . number_format($row->after_balance, 2);
                })

                ->addColumn('admin', function ($row) {

                    if (!$row->admin) {
                        return '-';
                    }

                    return $row->admin->name;
                        // '<br><small class="text-muted">UID : ' .
                        // $row->admin->uid .'</small>';
                })

                ->editColumn('reason', function ($row) {

                    return $row->reason ?: '-';
                })

                ->editColumn('created_at', function ($row) {

                    return Carbon::parse($row->created_at)
                        ->format('d M Y h:i A');
                })

                ->rawColumns([
                    'user',
                    'role',
                    'type',
                    'admin'
                ])

                ->make(true);
        }

        return view('manual_transfer.index');
    }

    public function manualTransfer()
    {
        return view('manual_transfer.manual_transfer');
    }

    public function manualTransferSave(Request $request)
    {
        $validator = Validator::make($request->all(), [

            'uid' => 'required|exists:app_users,uid',

            'type' => 'required|in:credit,deduct',

            'amount' => 'required|numeric|min:0.01',

            'reason' => 'nullable|string|max:500',

        ]);

        if ($validator->fails()) {

            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ]);
        }

        DB::beginTransaction();

        try {

            $user = AppUser::where('uid', $request->uid)->first();

            /*
        |--------------------------------------------------------------------------
        | Allow Only Wallet Users
        |--------------------------------------------------------------------------
        */

            $isAllowed = false;

            if (AdminAccount::where('user_id', $user->id)->where('status', 1)->exists()) {
                $isAllowed = true;
            }

            if (BdUser::where('user_id', $user->id)->where('invite_status', 'accept')->where('status', 1)->exists()) {
                $isAllowed = true;
            }

            if (Agency::where('user_id', $user->id)->where('invite_status', 'accept')->where('status', 1)->exists()) {
                $isAllowed = true;
            }

            if (Host::where('user_id', $user->id)->where('invite_status', 'accept')->where('status', 1)->exists()) {
                $isAllowed = true;
            }

            if (
                CoinSeller::where('user_id', $user->id)
                ->where('status', 1)
                ->where('is_merchant', 0)
                ->exists()
            ) {
                $isAllowed = true;
            }

            // Merchant


            if (
                CoinSeller::where('user_id', $user->id)
                ->where('status', 1)
                ->where('is_merchant', 1)
                ->exists()
            ) {
                $isAllowed = true;
            }

            if (!$isAllowed) {

                return response()->json([
                    'status' => false,
                    'message' => 'This user does not have an active wallet.'
                ]);
            }

            $beforeBalance = $user->balance ?? 0;

            /*
        |--------------------------------------------------------------------------
        | Credit
        |--------------------------------------------------------------------------
        */

            if ($request->type == 'credit') {

                $afterBalance = $beforeBalance + $request->amount;
            } else {

                /*
            |--------------------------------------------------------------------------
            | Deduct
            |--------------------------------------------------------------------------
            */

                if ($beforeBalance < $request->amount) {

                    return response()->json([
                        'status' => false,
                        'message' => 'Insufficient wallet balance.'
                    ]);
                }

                $afterBalance = $beforeBalance - $request->amount;
            }

            $user->balance = $afterBalance;
            $user->save();

            ManualMoneyTransaction::create([

                'user_id' => $user->id,

                'admin_id' => Auth::id(),

                'type' => $request->type,

                'amount' => $request->amount,

                'before_balance' => $beforeBalance,

                'after_balance' => $afterBalance,

                'reason' => $request->reason,

            ]);

            DB::commit();

            return response()->json([

                'status' => true,

                'message' => ucfirst($request->type) . ' successful.'

            ]);
        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([

                'status' => false,

                'message' => $e->getMessage()

            ]);
        }
    }
}
