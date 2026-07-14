<?php

namespace App\Http\Controllers;

use App\Models\CoinSeller;
use App\Models\AppUser;
use App\Models\Country;
use App\Models\CoinSellerTransaction;
use App\Models\CoinConversionRate;
use App\Helper\Helper;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CoinSellerController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    // Coin Seller functions
    public function index(Request $request)
    {
        if ($request->ajax()) {

            $query = CoinSeller::select('coin_sellers.*')->with(['user', 'country'])
                ->where('is_merchant', 0)
                ->latest();

            if ($request->has('type') && $request->type !== '') {
                $query->where('is_merchant', $request->type);
            }

            return DataTables::of($query)

                ->addColumn('user', function ($row) {

                    if (!$row->user) {
                        return '-';
                    }

                    $image = $row->user->image
                        ? Helper::showImage($row->user->image, true)
                        : asset('assets/img/avatar.png');

                    return '
                        <div class="d-flex align-items-center gap-2 user-profile-trigger"
                             data-user-id="' . $row->user->id . '" style="cursor:pointer;">

                            <img src="' . $image . '" width="40" height="40" class="rounded-circle">

                            <div>
                                <div class="fw-bold">' . e($row->user->name) . '</div>
                                <small class="text-muted">UID: ' . e($row->user->uid) . '</small>
                            </div>

                        </div>
                    ';
                })

                ->editColumn('is_merchant', function ($row) {
                    return $row->is_merchant
                        ? '<span class="badge bg-primary">Merchant</span>'
                        : '<span class="badge bg-secondary">Seller</span>';
                })

                ->addColumn('balance', function ($row) {
                    return $row->user->total_points ?? 0;
                })

                ->addColumn('sold_coins', function ($row) {
                    return $row->sold_coins ?? 0;
                })

                ->addColumn('country', function ($row) {
                    return $row->country->nicename ?? '-';
                })

                ->addColumn('whatsapp', function ($row) {
                    return $row->whatsapp_number ?? '-';
                })

                ->addColumn('action', function ($row) {
                    return '
                    <div class="dropdown">
                        <button class="btn btn-sm btn-light border dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="fas fa-ellipsis-h"></i>
                        </button>

                        <div class="dropdown-menu dropdown-menu-end">

                            <a class="dropdown-item" href="' . route('coin_seller.form', $row->id) . '">
                                <i class="fas fa-edit text-primary me-2"></i> Edit Info
                            </a>

                            <button class="dropdown-item recharge" data-id="' . $row->id . '">
                                <i class="fas fa-plus-circle text-success me-2"></i> Recharge
                            </button>

                            <button class="dropdown-item deduct" data-id="' . $row->id . '">
                                <i class="fas fa-minus-circle text-warning me-2"></i> Deduct Coins
                            </button>

                            <button class="dropdown-item toggle-merchant" data-id="' . $row->id . '" data-type="' . $row->is_merchant . '">
                                <i class="fas fa-user-tag text-info me-2"></i> Make Merchant
                            </button>

                            <div class="dropdown-divider"></div>

                            <button class="dropdown-item text-danger delete" data-id="' . $row->id . '">
                                <i class="fas fa-trash me-2"></i> Delete Seller
                            </button>

                        </div>
                    </div>';
                })

                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && $request->search['value']) {

                        $search = $request->search['value'];

                        $query->where(function ($q) use ($search) {

                            $q->whereHas('user', function ($uq) use ($search) {
                                $uq->where('name', 'like', "%{$search}%")
                                    ->orWhere('uid', 'like', "%{$search}%");
                            })
                                ->orWhereHas('country', function ($cq) use ($search) {
                                    $cq->where('nicename', 'like', "%{$search}%");
                                })
                                ->orWhere('whatsapp_number', 'like', "%{$search}%");
                        });
                    }
                })

                ->rawColumns(['user', 'is_merchant', 'action'])
                ->make(true);
        }

        return view('coin_seller.index');
    }

    public function form($id = null)
    {
        $coinSeller = $id ? CoinSeller::find($id) : null;

        if ($id && !$coinSeller) {
            return redirect()->route('coin_seller')->with('error', 'Coin Seller not found');
        }

        $countries = Country::all();

        return view('coin_seller.form', compact('coinSeller', 'countries'));
    }

    public function save11(Request $request, $id = null)
    {
        $rules = [
            'user_uid' => 'required|exists:app_users,uid',
            'country_id' => 'required|exists:countries,id',
            'whatsapp_number' => 'nullable|string|max:20',
            'is_merchant' => 'nullable|in:0,1',
            'status' => 'required|in:0,1',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        return DB::transaction(function () use ($request, $id) {

            $coinSeller = $id ? CoinSeller::find($id) : new CoinSeller();

            $user = AppUser::where('uid', $request->user_uid)->first();

            // Prevent duplicate
            $exists = CoinSeller::where('user_id', $user->id)
                ->when($id, fn($q) => $q->where('id', '!=', $id))
                ->exists();

            if ($exists) {
                return back()->with('error', 'User already exists as Coin Seller');
            }

            $coinSeller->fill([
                'user_id' => $user->id,
                'country_id' => $request->country_id,
                'whatsapp_number' => $request->whatsapp_number,
                'is_merchant' => $request->is_merchant ?? $coinSeller->is_merchant ?? 0,
                'status' => $request->status,
            ])->save();

            return redirect()
                ->route('coin_seller')
                ->with('success', $id ? 'Coin Seller updated successfully' : 'Coin Seller added successfully');
        });
    }

    public function save(Request $request, $id = null)
    {
        $rules = [
            'user_uid' => 'required',
            'country_id' => 'required|exists:countries,id',
            'whatsapp_number' => 'nullable|string|max:20',
            'is_merchant' => 'nullable|in:0,1',
            'status' => 'required|in:0,1',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        return DB::transaction(function () use ($request, $id) {

            //    Find Coin Seller
            $coinSeller = $id ? CoinSeller::find($id) : new CoinSeller();

            // Find User (System UID / Premium UID / Store UID)

            $user = Helper::findUserByAnyUid($request->user_uid);

            if (!$user) {
                return back()->with('error', 'User not found');
            }

            // Check Existing Coin Seller

            $exists = CoinSeller::where('user_id', $user->id)
                ->when(
                    $id,
                    fn($q) => $q->where('id', '!=', $id)
                )->exists();

            if ($exists) {
                //   Existing Role Type
                $existingRole = CoinSeller::where('user_id', $user->id)->first();
                $roleName =  $existingRole?->is_merchant ? 'Merchant' : 'Seller';

                return back()->with('error', "User already exists as {$roleName}");
            }

            //  Seller / Merchant Replacement Logic
            //  Only one business role active

            $oldBusinessRole = CoinSeller::where('user_id', $user->id)->first();

            // If Existing Seller/Merchant Found

            if ($oldBusinessRole && (!$id || $oldBusinessRole->id != $id)) {

                //   Replace Existing Role
                $oldBusinessRole->delete();
            }

            //   Save Coin Seller / Merchant

            $coinSeller->fill([
                'user_id' => $user->id,
                'country_id' => $request->country_id,
                'whatsapp_number' => $request->whatsapp_number,
                //    0 = Seller, 1 = Merchant
                'is_merchant' =>  $request->is_merchant  ?? $coinSeller->is_merchant ?? 0,
                'status' => $request->status,
            ])->save();

            // Success Message

            $roleName = $coinSeller->is_merchant ? 'Merchant' : 'Coin Seller';

            return redirect()
                ->route('coin_seller')
                ->with(
                    'success',
                    $id
                        ? "{$roleName} updated successfully"
                        : "{$roleName} added successfully"
                );
        });
    }

    public function delete(Request $request)
    {
        return Helper::deleteRecord(new CoinSeller, $request->id);
    }

    // Extra for popup modal
    public function toggleMerchant(Request $request)
    {
        $seller = CoinSeller::find($request->id);

        if (!$seller) {
            return response()->json(['status' => false, 'message' => 'Seller not found']);
        }

        $seller->is_merchant = !$seller->is_merchant;
        $seller->save();

        return response()->json([
            'status' => true,
            'message' => $seller->is_merchant ? 'Now Merchant' : 'Removed from Merchant'
        ]);
    }

    public function recharge(Request $request)
    {
        if ($request->amount <= 0) {
            return response()->json(['status' => false, 'message' => 'Invalid amount']);
        }

        $seller = CoinSeller::find($request->id);
        $user = $seller->user;

        $before = $user->total_points;

        $user->total_points += $request->amount;
        $user->save();

        CoinSellerTransaction::create([
            'sender_id' => auth()->id(),
            'sender_type' => 'admin',
            'receiver_id' => $user->id,
            'receiver_type' => 'user',
            'coins' => $request->amount,
            'balance_before' => $before,
            'balance_after' => $user->total_points,
            'transaction_type' => 'recharge',
            'remark' => 'Recharge by admin'
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Wallet recharged successfully'
        ]);
    }

    public function deduct(Request $request)
    {
        if ($request->amount <= 0) {
            return response()->json(['status' => false, 'message' => 'Invalid amount']);
        }

        $seller = CoinSeller::find($request->id);
        $user = $seller->user;

        if ($user->total_points < $request->amount) {
            return response()->json([
                'status' => false,
                'message' => 'Insufficient balance'
            ]);
        }

        $before = $user->total_points;

        $user->total_points -= $request->amount;
        $user->save();

        CoinSellerTransaction::create([
            'sender_id' => auth()->id(),
            'sender_type' => 'admin',
            'receiver_id' => $user->id,
            'receiver_type' => 'user',
            'coins' => $request->amount,
            'balance_before' => $before,
            'balance_after' => $user->total_points,
            'transaction_type' => 'deduct',
            'remark' => 'Deduct by admin'
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Coins deducted successfully'
        ]);
    }

    public function transactions(Request $request)
    {
        if ($request->ajax()) {

            $query = CoinSellerTransaction::with(['sender', 'receiver'])->latest();

            return DataTables::of($query)
                ->addIndexColumn()

                ->addColumn('sender', function ($row) {

                    if ($row->sender_type == 'admin') {
                        return '<span class="fw-bold text-primary">Admin</span>';
                    }

                    $user = $row->sender;

                    if (!$user) {
                        return '-';
                    }

                    $image = $user->image
                        ? Helper::showImage($user->image, true)
                        : asset('assets/img/avatar.png');

                    return '
                        <div class="d-flex align-items-center gap-2 user-profile-trigger"
                             data-user-id="' . $user->id . '" style="cursor:pointer;">

                            <img src="' . $image . '" width="40" height="40" class="rounded-circle">

                            <div>
                                <div class="fw-bold">' . $user->name . '</div>
                                <small class="text-muted">UID: ' . $user->uid . '</small>
                            </div>

                        </div>
                    ';
                })

                ->addColumn('receiver', function ($row) {

                    $user = $row->receiver;

                    if (!$user) {
                        return '-';
                    }

                    $image = $user->image
                        ? Helper::showImage($user->image, true)
                        : asset('assets/img/avatar.png');

                    return '
                        <div class="d-flex align-items-center gap-2 user-profile-trigger"
                             data-user-id="' . $user->id . '" style="cursor:pointer;">

                            <img src="' . $image . '" width="40" height="40" class="rounded-circle">

                            <div>
                                <div class="fw-bold">' . $user->name . '</div>
                                <small class="text-muted">UID: ' . $user->uid . '</small>
                            </div>

                        </div>
                    ';
                })

                ->editColumn('coins', function ($row) {
                    return $row->transaction_type == 'recharge'
                        ? '<span class="text-success">+' . $row->coins . '</span>'
                        : '<span class="text-danger">-' . $row->coins . '</span>';
                })

                ->editColumn('created_at', function ($row) {
                    return $row->created_at->timezone('Asia/Kolkata')->format('Y-m-d h:i A');
                })

                ->filter(function ($query) use ($request) {

                    if ($request->has('search') && $request->search['value'] != '') {

                        $search = $request->search['value'];

                        $query->where(function ($q) use ($search) {

                            // Coins
                            $q->where('coins', 'like', "%{$search}%")
                                ->orWhere('balance_before', 'like', "%{$search}%")
                                ->orWhere('balance_after', 'like', "%{$search}%");

                            // Sender search
                            $q->orWhereHas('sender', function ($q2) use ($search) {
                                $q2->where('name', 'like', "%{$search}%")
                                    ->orWhere('uid', 'like', "%{$search}%");
                            });

                            // Receiver search
                            $q->orWhereHas('receiver', function ($q3) use ($search) {
                                $q3->where('name', 'like', "%{$search}%")
                                    ->orWhere('uid', 'like', "%{$search}%");
                            });
                        });
                    }
                })

                ->rawColumns(['sender', 'receiver', 'coins'])
                ->make(true);
        }

        return view('coin_seller.transactions');
    }



    // Merchant functions
    public function merchantIndex(Request $request)
    {
        if ($request->ajax()) {

            $query = CoinSeller::select('coin_sellers.*')->with(['user', 'country'])
                ->where('is_merchant', 1)
                ->latest();

            return DataTables::of($query)

                ->addColumn('user', function ($row) {

                    if (!$row->user) {
                        return '-';
                    }

                    $image = $row->user->image
                        ? Helper::showImage($row->user->image, true)
                        : asset('assets/img/avatar.png');

                    return '
                        <div class="d-flex align-items-center gap-2 user-profile-trigger"
                             data-user-id="' . $row->user->id . '" style="cursor:pointer;">

                            <img src="' . $image . '" width="40" height="40" class="rounded-circle">

                            <div>
                                <div class="fw-bold">' . e($row->user->name) . '</div>
                                <small class="text-muted">UID: ' . e($row->user->uid) . '</small>
                            </div>

                        </div>
                    ';
                })

                ->editColumn('is_merchant', function ($row) {
                    return $row->is_merchant
                        ? '<span class="badge bg-primary">Merchant</span>'
                        : '<span class="badge bg-secondary">Seller</span>';
                })

                ->addColumn('balance', fn($row) => $row->user->total_points ?? 0)

                ->addColumn('country', fn($row) => $row->country->nicename ?? '-')

                ->addColumn('whatsapp', function ($row) {
                    return $row->whatsapp_number ?? '-';
                })

                ->addColumn('created_at', function ($row) {
                    return '
                    <div>
                        <div><strong>Created:</strong> ' . Carbon::parse($row->created_at)->format('Y-m-d H:i:s') . '</div>
                        <div><strong>Updated:</strong> ' . Carbon::parse($row->updated_at)->format('Y-m-d H:i:s') . '</div>
                    </div>';
                })

                ->filter(function ($query) use ($request) {

                    if ($request->has('search') && $request->search['value']) {

                        $search = $request->search['value'];

                        $query->where(function ($q) use ($search) {

                            // USER SEARCH (name + uid)
                            $q->whereHas('user', function ($uq) use ($search) {
                                $uq->where('name', 'like', "%{$search}%")
                                    ->orWhere('uid', 'like', "%{$search}%");
                            })
                                // COUNTRY SEARCH
                                ->orWhereHas('country', function ($cq) use ($search) {
                                    $cq->where('nicename', 'like', "%{$search}%");
                                })
                                // WHATSAPP SEARCH
                                ->orWhere('whatsapp_number', 'like', "%{$search}%");
                        });
                    }
                })

                ->addColumn('action', function ($row) {
                    return '
                    <div class="dropdown">
                        <button class="btn btn-sm btn-light border dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="fas fa-ellipsis-h"></i>
                        </button>

                        <div class="dropdown-menu dropdown-menu-end">

                            <a class="dropdown-item" href="' . route('merchant.form', $row->id) . '">
                                <i class="fas fa-edit text-primary me-2"></i> Edit Info
                            </a>

                            <button class="dropdown-item recharge" data-id="' . $row->id . '">
                                <i class="fas fa-plus-circle text-success me-2"></i> Recharge
                            </button>

                            <button class="dropdown-item deduct" data-id="' . $row->id . '">
                                <i class="fas fa-minus-circle text-warning me-2"></i> Deduct Coins
                            </button>

                            <button class="dropdown-item text-warning remove-merchant" data-id="' . $row->id . '">
                                <i class="fas fa-user-times me-2"></i> Remove Merchant
                            </button>

                            <div class="dropdown-divider"></div>

                            <button class="dropdown-item text-danger delete" data-id="' . $row->id . '">
                                <i class="fas fa-trash me-2"></i> Delete Seller
                            </button>

                        </div>
                    </div>';
                })

                ->rawColumns(['user', 'created_at', 'is_merchant', 'whatsapp', 'action'])
                ->make(true);
        }

        return view('coin_seller.merchant_index');
    }

    public function merchantForm($id = null)
    {
        $merchant = $id ? CoinSeller::find($id) : null;
        $countries = Country::all();

        return view('coin_seller.merchant_form', compact('merchant', 'countries'));
    }

    public function merchantSave11(Request $request, $id = null)
    {
        $request->validate([
            'user_uid' => 'required',
            'country_id' => 'required|exists:countries,id',
            'whatsapp_number' => 'required'
        ]);

        $user = AppUser::where('uid', $request->user_uid)->first();

        $seller = $id ? CoinSeller::find($id) : new CoinSeller();

        $exists = CoinSeller::where('user_id', $user->id)
            ->when($id, fn($q) => $q->where('id', '!=', $id))
            ->exists();

        if ($exists) {
            return back()->with('error', 'User already exists');
        }

        $seller->fill([
            'user_id' => $user->id,
            'country_id' => $request->country_id,
            'whatsapp_number' => $request->whatsapp_number,
            'is_merchant' => 1,
            'status' => 1,
        ])->save();

        return redirect()->route('merchant')->with('success', $id ? 'Merchant updated successfully' : 'Merchant added successfully');
    }

    public function merchantSave(Request $request, $id = null)
    {
        $request->validate([
            'user_uid' => 'required',
            'country_id' => 'required|exists:countries,id',
            'whatsapp_number' => 'required'
        ]);

        return DB::transaction(function () use ($request, $id) {

            //   Find User
            $user = Helper::findUserByAnyUid($request->user_uid);

            if (!$user) {
                return back()->with('error', 'User not found');
            }

            // Find Merchant
            $seller = $id ? CoinSeller::find($id) : new CoinSeller();

            //   Existing Business Role, Seller & Merchant are replaceable

            $existingBusinessRole = CoinSeller::where('user_id', $user->id)
                ->when(
                    $id,
                    fn($q) => $q->where('id', '!=', $id)
                )->first();

            //  Replace Existing Seller

            if ($existingBusinessRole) {

                // If already merchant

                if ((int) $existingBusinessRole->is_merchant === 1) {
                    return back()->with('error', 'User already exists as Merchant');
                }

                //   Remove Seller
                $existingBusinessRole->delete();
            }

            //  Save Merchant

            $seller->fill([
                'user_id' => $user->id,
                'country_id' => $request->country_id,
                'whatsapp_number' => $request->whatsapp_number,
                'is_merchant' => 1,
                'status' => 1,
            ])->save();

            return redirect()
                ->route('merchant')
                ->with(
                    'success',
                    $id
                        ? 'Merchant updated successfully'
                        : 'Merchant added successfully'
                );
        });
    }

    public function removeMerchant(Request $request)
    {
        $seller = CoinSeller::find($request->id);

        if (!$seller) {
            return response()->json(['status' => false, 'message' => 'Not found']);
        }

        $seller->is_merchant = 0; // back to seller
        $seller->save();

        return response()->json([
            'status' => true,
            'message' => 'Moved to Coin Seller'
        ]);
    }

    public function merchantDelete(Request $request)
    {
        return Helper::deleteRecord(new CoinSeller, $request->id);
    }

    public function coinConversionRate()
    {
        $rate = CoinConversionRate::first();

        return view('coin_seller.coin_conversion_rate', compact('rate'));
    }

    public function coinConversionRateupdate(Request $request)
    {
        $request->validate([
            'merchant_to_user_rate'   => 'required|numeric|min:1',
            'merchant_to_seller_rate' => 'required|numeric|min:1',
            'seller_to_user_rate'     => 'required|numeric|min:1',
            'coin_exchange_rate'     => 'required|numeric|min:1',
        ]);

        $rate = CoinConversionRate::first();

        $rate->update([
            'merchant_to_user_rate'   => $request->merchant_to_user_rate,
            'merchant_to_seller_rate' => $request->merchant_to_seller_rate,
            'seller_to_user_rate'     => $request->seller_to_user_rate,
            'coin_exchange_rate'     => $request->coin_exchange_rate,
        ]);

        return back()->with('success', 'Coin conversion rates updated successfully.');
    }
}
