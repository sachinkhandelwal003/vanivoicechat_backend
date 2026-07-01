<?php

namespace App\Http\Controllers;


use App\Helper\Helper;
use App\Models\CoinPackages;
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
                        <img src="'.$image.'" width="40" height="40" class="image-preview" data-image="'.$image.'"
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

                    if (Helper::userCan(104, 'can_edit')) {
                        $btn .= '<a class="dropdown-item" href="' . route('coin.package.edit', $row->id) . '">Edit</a>';
                    }

                    if (Helper::userCan(105, 'can_delete')) {
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
}
