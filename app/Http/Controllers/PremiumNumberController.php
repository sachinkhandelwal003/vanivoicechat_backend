<?php

namespace App\Http\Controllers;

use App\Models\AppUser;
use App\Helper\Helper;
use App\Models\PremiumNumber;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PremiumNumberController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {

            $query = PremiumNumber::with('user');

            if ($request->filled('uid')) {
                $query->where('user_id', 'like', '%' . $request->uid . '%');
            }

            if ($request->filled('pNumber')) {
                $query->where('premium_number', 'like', '%' . $request->pNumber . '%');
            }

            if ($request->filled('username')) {
                $query->whereHas('user', function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->username . '%');
                });
            }


            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('user_info', function ($row) {

                    $user = $row->user;

                    if (!$user) {
                        return 'N/A';
                    }

                    $avatar = (!empty($user->image))
                        ? Helper::showImage($user->image, true)
                        : asset('assets/img/avatar.png');

                    return '
                    <div class="d-flex align-items-center gap-3">
                        <img src="' . $avatar . '" class="rounded-circle" width="45" height="45" style="object-fit:cover;">

                        <div>
                            <div class="fw-bold">
                                ' . e($user->name) . '

                            </div>

                            <small class="text-muted">UID: ' . e($row->uid) . '</small>
                        </div>
                    </div>';
                })
                ->addColumn('action', function ($row) {
                    $btn = '<div class="dropdown">
                    <button class="btn btn-sm btn-link dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-ellipsis-h"></i>
                    </button>
                    <div class="dropdown-menu">';

                    if (Helper::userCan(110, 'can_edit')) {
                        $btn .= '<a class="dropdown-item" href="' . route('premium_number.edit', $row->id) . '">Edit</a>';
                    }

                    if (Helper::userCan(110, 'can_delete')) {
                        $btn .= '<button class="dropdown-item text-danger delete" data-id="' . $row->id . '">Delete</button>';
                    }

                    $btn .= '</div></div>';

                    return $btn;
                })
                ->rawColumns(['user_info', 'action'])
                ->make(true);
        }

        return view('premium_number.index');
    }

    public function add(): View
    {
        return view('premium_number.add');
    }


    public function save(Request $request)
    {
        $rules = [
            'uid' => 'required',
            'premium_number' => 'required',
            'valid_days'    => 'required'
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        return DB::transaction(function () use ($request) {
            $user = AppUser::where('uid', $request->uid)->first();
            if (!$user) {
                return redirect()->back()
                    ->withErrors(['uid' => 'User not found for this UID'])
                    ->withInput();
            }

            $hasActiveStoreUid = DB::table('item_deliveries')
                ->where('recipient', $user->id)
                ->where('type', 'id')
                ->where('end_at', '>', now())
                ->exists();

            $hasGiftedStoreUid = DB::table('item_gift_transactions')
                ->where('receiver_id', $user->id)
                ->where('type', 'id')
                ->where('end_at', '>', now())
                ->exists();

            if ($hasActiveStoreUid || $hasGiftedStoreUid) {
                return redirect()->back()
                    ->withErrors([
                        'uid' => 'User already has an active Store UID.'
                    ])
                    ->withInput();
            }

            $exists = PremiumNumber::where('premium_number', $request->premium_number)
                ->where('end_at', '>', now())
                ->exists();

            if ($exists) {
                return redirect()->back()
                    ->withErrors([
                        'premium_number' => 'Premium UID already assigned.'
                    ]);
            }


            $validDays = (int) $request->valid_days;

            PremiumNumber::create([
                'uid'            => $request->uid,
                'user_id'        => $user->id,
                'premium_number' => $request->premium_number,
                'valid_days'     => $request->valid_days,
                'start_at'       => now(),
                'end_at'         => now()->addDays($validDays),
            ]);

            return redirect()->route('premium_number')
                ->with('success', 'Premium Number added successfully');
        });
    }

    public function edit($id): View|RedirectResponse
    {
        $pNumber = PremiumNumber::find($id);
        // dd($banner);
        if (!$pNumber) {
            return to_route('premium_number')->withError('Premium Number Not Found!');
        }
        return view('premium_number.edit', compact('pNumber'));
    }

    public function update(Request $request, $id)
    {
        $pNumber = PremiumNumber::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'uid' => 'required',
            'premium_number' => 'required',
            'valid_days'    => 'required'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $user = AppUser::where('uid', $request->uid)->first();
        $hasActiveStoreUid = DB::table('item_deliveries')
            ->where('recipient', $user->id)
            ->where('type', 'id')
            ->where('end_at', '>', now())
            ->exists();

        $hasGiftedStoreUid = DB::table('item_gift_transactions')
            ->where('receiver_id', $user->id)
            ->where('type', 'id')
            ->where('end_at', '>', now())
            ->exists();

        if ($hasActiveStoreUid || $hasGiftedStoreUid) {
            return redirect()->back()
                ->withErrors([
                    'uid' => 'User already has an active Store UID.'
                ])
                ->withInput();
        }

        $exists = PremiumNumber::where('premium_number', $request->premium_number)
            ->where('id', '!=', $id)
            ->where('end_at', '>', now())
            ->exists();

        if ($exists) {
            return back()
                ->withErrors([
                    'premium_number' => 'Premium UID already assigned.'
                ])
                ->withInput();
        }

        $validDays = (int) $request->valid_days;

        $pNumber->uid = $request->uid;
        $pNumber->premium_number = $request->premium_number;
        $pNumber->valid_days  = $validDays;
        $pNumber->end_at = \Carbon\Carbon::parse($pNumber->start_at)->addDays($validDays);

        $pNumber->save();

        return redirect()->route('premium_number.edit', $pNumber->id)
            ->with('success', 'Premium Number updated successfully');
    }

    public function delete(Request $request): JsonResponse
    {
        return Helper::deleteRecord(new PremiumNumber, $request->id);
    }
}
