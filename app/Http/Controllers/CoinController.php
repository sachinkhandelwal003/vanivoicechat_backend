<?php

namespace App\Http\Controllers;


use App\Helper\Helper;
use App\Models\CoinPackages;
use App\Models\CoinTransaction;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class CoinController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {

            $query = CoinPackages::get();

            return DataTables::of($query)
                ->addIndexColumn()

                ->editColumn('icon', function ($row) {

                    $image = asset('storage/' . $row->icon);

                    return '
                        <img src="' . $image . '" width="40" height="40" class="image-preview" data-image="' . $image . '"
                             style="cursor:pointer;border-radius:6px;object-fit:cover;">
                    ';
                })

                ->editColumn('status', function ($row) {
                    return $row['status'] == 1 ? '<small class="badge fw-semi-bold rounded-pill status badge-light-success"> Enable</small>' : '<small class="badge fw-semi-bold rounded-pill status badge-light-danger"> Disable</small>';
                })
                ->addColumn('action', function ($row) {
                    $btn = '<div class="dropdown">
                    <button class="btn btn-sm btn-link dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-ellipsis-h"></i>
                    </button>
                    <div class="dropdown-menu">';

                    if (Helper::userCan(160, 'can_edit')) {
                        $btn .= '<a class="dropdown-item" href="' . route('coin.package.edit', $row->id) . '">Edit</a>';
                    }

                    if (Helper::userCan(160, 'can_delete')) {
                        $btn .= '<button class="dropdown-item text-danger delete" data-id="' . $row->id . '">Delete</button>';
                    }

                    $btn .= '</div></div>';

                    return $btn;
                })
                ->rawColumns(['icon', 'status', 'action'])
                ->make(true);
        }

        return view('coin_package.index');
    }

    public function add(): View
    {
        return view('coin_package.add');
    }


    public function save(Request $request)
    {
        $rules = [
            'coin'          => 'required|integer|min:1',
            'price'         => 'required|numeric|min:0',
            'bonus_percent' => 'nullable|numeric|min:0|max:100',
            'badge'         => 'nullable|string|max:50',
            'icon'          => 'required|image|mimes:png,jpg,jpeg,webp',
            'status'        => 'required|in:0,1',
        ];


        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        return DB::transaction(function () use ($request) {

            $icon = Helper::saveFile($request->file('icon'), 'coin_package_images');

            $bonusPercent = $request->bonus_percent ?? 0;
            $bonusCoins   = (int) round(($request->coin * $bonusPercent) / 100);
            $totalCoins   = (int) $request->coin + $bonusCoins;

            CoinPackages::create([
                'coins'         => $request->coin,
                'price'         => $request->price,
                'bonus_percent' => $bonusPercent,
                'bonus_coins'   => $bonusCoins,
                'total_coins'   => $totalCoins,
                'badge'         => $request->badge,
                'status'        => $request->status,
                'icon'          => $icon,
            ]);

            return redirect()
                ->route('coin.package')
                ->with('success', 'Coin Packages added successfully');
        });
    }

    public function edit($id): View|RedirectResponse
    {
        $coin = CoinPackages::find($id);

        if (!$coin) {
            return to_route('coin.package')->withError('Coin Packages Not Found!');
        }
        return view('coin_package.edit', compact('coin'));
    }

    public function update(Request $request, $id)
    {
        $coin = CoinPackages::findOrFail($id);

        $rules = [
            'coin'          => 'required|integer|min:1',
            'price'         => 'required|numeric|min:0',
            'bonus_percent' => 'nullable|numeric|min:0|max:100',
            'badge'         => 'nullable|string|max:50',
            'icon'          => 'nullable|image|mimes:png,jpg,jpeg,webp',
            'status'        => 'required|in:0,1',
        ];

        $request->validate($rules);

        return DB::transaction(function () use ($request, $coin) {

            $bonusPercent = $request->bonus_percent ?? 0;
            $bonusCoins   = (int) round(($request->coin * $bonusPercent) / 100);
            $totalCoins   = (int) $request->coin + $bonusCoins;

            $data = [
                'coins'         => $request->coin,
                'price'         => $request->price,
                'bonus_percent' => $bonusPercent,
                'bonus_coins'   => $bonusCoins,
                'total_coins'   => $totalCoins,
                'badge'         => $request->badge,
                'status'        => $request->status,
            ];

            if ($request->hasFile('icon')) {

                if ($coin->icon && file_exists(public_path($coin->icon))) {
                    @unlink(public_path($coin->icon));
                }

                $data['icon'] = Helper::saveFile($request->file('icon'), 'coin_package_images');
            }

            $coin->update($data);

            return redirect()->route('coin.package')->with('success', 'coin package updated successfully');
        });
    }


    public function delete(Request $request): JsonResponse
    {
        return Helper::deleteRecord(new CoinPackages, $request->id);
    }

    public function coinPurchaseHistory(Request $request)
    {
        if ($request->ajax()) {

            $query = CoinTransaction::with([
                'user:id,name,uid,image',
                'package:id'
            ])->latest();

            return DataTables::of($query)

                ->addIndexColumn()

                ->addColumn('user', function ($row) {

                    if (!$row->user) {
                        return '-';
                    }

                    $user = $row->user;

                    $image = $user->image
                        ? Helper::showImage($user->image, true)
                        : asset('assets/img/avatar.png');

                    $uidData = Helper::getDisplayUidData($user);

                    $badgeHtml = '';

                    if (!empty($uidData['badge'])) {
                        $badgeHtml = '
                            <img src="' . $uidData['badge'] . '"
                                width="16"
                                height="16"
                                style="vertical-align:middle;margin-right:4px;">
                        ';
                    }

                    if (!empty($uidData['uid']) && $uidData['uid'] != $uidData['system_uid']) {

                        $uidHtml = '
                            <small class="d-flex align-items-center flex-wrap" style="gap:4px;">
                                ' . $badgeHtml . '
                                <span style="color:' . ($uidData['badge_color'] ?? '#000') . ';font-weight:600;">
                                    ' . e($uidData['uid']) . '
                                </span>
                                <span class="text-muted">/</span>
                                <span class="text-muted">' . e($uidData['system_uid']) . '</span>
                            </small>';
                                    } else {

                                        $uidHtml = '
                            <small class="text-muted">
                                ' . e($uidData['system_uid'] ?? $user->uid) . '
                            </small>';
                    }

                    return '
                        <div class="d-flex align-items-center gap-2 user-profile-trigger"
                            data-user-id="' . $user->id . '"
                            style="cursor:pointer;">

                            <img src="' . $image . '"
                                width="45"
                                height="45"
                                class="rounded-circle">

                            <div>
                                <div class="fw-bold">' . e($user->name) . '</div>
                                ' . $uidHtml . '
                            </div>

                        </div>';
                })

                ->addColumn('coins', function ($row) {

                    return '<i class="fas fa-coins text-warning"></i> ' . number_format($row->coins);
                })

                ->addColumn('bonus_coins', function ($row) {

                    return '<i class="fas fa-gift text-success"></i> ' . number_format($row->bonus_coins);
                })

                ->addColumn('total_coins', function ($row) {

                    return '<strong><i class="fas fa-coins text-warning"></i> ' . number_format($row->total_coins) . '</strong>';
                })

                ->addColumn('amount', function ($row) {

                    return '$ ' . number_format($row->amount, 2);
                })

                ->addColumn('type', function ($row) {

                    return $row->type == 'credit'
                        ? '<span class="badge bg-success">Credit</span>'
                        : '<span class="badge bg-danger">Debit</span>';
                })

                ->addColumn('created', function ($row) {

                    return '
                <div>
                    <div>' . optional($row->created_at)->format('d M Y') . '</div>
                    <small class="text-muted">' . optional($row->created_at)->format('h:i A') . '</small>
                </div>';
                })

                ->addColumn('action', function ($row) {
                    return '-';
                })

                ->rawColumns([
                    'user',
                    'coins',
                    'bonus_coins',
                    'total_coins',
                    'type',
                    'created',
                    'action'
                ])

                ->make(true);
        }

        return view('coin_package.coin_purchase_history');
    }
}
