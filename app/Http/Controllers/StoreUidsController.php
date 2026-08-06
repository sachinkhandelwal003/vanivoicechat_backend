<?php

namespace App\Http\Controllers;


use App\Helper\Helper;
use App\Models\Pattern;
use App\Models\Rank;
use App\Models\StoreUids;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class StoreUidsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {

            $query = Rank::latest();

            return DataTables::of($query)
                ->addIndexColumn()


                ->addColumn('action', function ($row) {
                    $btn = '<div class="dropdown">
                    <button class="btn btn-sm btn-link dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-ellipsis-h"></i>
                    </button>
                    <div class="dropdown-menu">';

                    if (Helper::userCan(121, 'can_edit')) {
                        $btn .= '<a class="dropdown-item" href="' . route('rank.edit', $row->id) . '">Edit</a>';
                    }

                    if (Helper::userCan(121, 'can_delete')) {
                        $btn .= '<button class="dropdown-item text-danger delete" data-id="' . $row->id . '">Delete</button>';
                    }

                    $btn .= '</div></div>';

                    return $btn;
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('store_uids.rank.index');
    }

    public function add(): View
    {
        return view('store_uids.rank.add');
    }


    public function save(Request $request)
    {
        $rules = [
            'name' => 'required|string|max:255',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        return DB::transaction(function () use ($request) {


            Rank::create([
                'name'            => $request->name,

            ]);

            return redirect()
                ->route('rank')
                ->with('success', 'Rank added successfully');
        });
    }

    public function edit($id): View|RedirectResponse
    {
        $rank = Rank::find($id);

        if (!$rank) {
            return to_route('rank')->withError('Rank Not Found!');
        }
        return view('store_uids.rank.edit', compact('rank'));
    }

    public function update(Request $request, $id)
    {
        $rank = Rank::findOrFail($id);

        $rules = [
            'name'            => 'required|string|max:255',
        ];

        $request->validate($rules);

        return DB::transaction(function () use ($request, $rank) {

            $data = [
                'name' => $request->name,
            ];

            $rank->update($data);

            return redirect()->route('rank')->with('success', 'Rank updated successfully');
        });
    }


    public function delete(Request $request): JsonResponse
    {
        return Helper::deleteRecord(new Rank, $request->id);
    }


    public function patternIndex(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {

            $query = Pattern::latest();

            return DataTables::of($query)
                ->addIndexColumn()


                ->addColumn('action', function ($row) {
                    $btn = '<div class="dropdown">
                    <button class="btn btn-sm btn-link dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-ellipsis-h"></i>
                    </button>
                    <div class="dropdown-menu">';

                    if (Helper::userCan(121, 'can_edit')) {
                        $btn .= '<a class="dropdown-item" href="' . route('pattern.edit', $row->id) . '">Edit</a>';
                    }

                    if (Helper::userCan(121, 'can_delete')) {
                        $btn .= '<button class="dropdown-item text-danger delete" data-id="' . $row->id . '">Delete</button>';
                    }

                    $btn .= '</div></div>';

                    return $btn;
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('store_uids.pattern.index');
    }

    public function patternAdd(): View
    {
        return view('store_uids.pattern.add');
    }


    public function patternSave(Request $request)
    {
        $rules = [
            'name' => 'required|string|max:255',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        return DB::transaction(function () use ($request) {


            Pattern::create([
                'name'            => $request->name,

            ]);

            return redirect()
                ->route('pattern')
                ->with('success', 'Pattern added successfully');
        });
    }

    public function patternEdit($id): View|RedirectResponse
    {
        $pattern = Pattern::find($id);

        if (!$pattern) {
            return to_route('pattern')->withError('pattern Not Found!');
        }
        return view('store_uids.pattern.edit', compact('pattern'));
    }

    public function patternUpdate(Request $request, $id)
    {
        $pattern = Pattern::findOrFail($id);

        $rules = [
            'name' => 'required|string|max:255',
        ];

        $request->validate($rules);

        return DB::transaction(function () use ($request, $pattern) {

            $data = [
                'name' => $request->name,
            ];

            $pattern->update($data);

            return redirect()->route('pattern')->with('success', 'Pattern updated successfully');
        });
    }


    public function patternDelete(Request $request): JsonResponse
    {
        return Helper::deleteRecord(new Pattern, $request->id);
    }

    public function storeUidIndex(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {

            $query = StoreUids::with(['rank:id,name', 'pattern:id,name'])->latest();

            return DataTables::of($query)
                ->addIndexColumn()

                ->addColumn('rank_name', function ($row) {
                    return $row->rank?->name ?? '-';
                })

                ->addColumn('pattern_name', function ($row) {
                    return $row->pattern?->name ?? '-';
                })

                ->addColumn('unique_id', function ($row) {
                    return $row->unique_id ?? '-';
                })

                ->addColumn('badge', function ($row) {

                    if (!$row->badge) {
                        return '-';
                    }

                    $image = asset('storage/' . $row->badge);

                    return '
                        <img src="'.$image.'"
                             width="40"
                             height="40"
                             class="image-preview"
                             data-image="'.$image.'"
                             style="cursor:pointer;border-radius:6px;object-fit:cover;">
                    ';
                })

                ->addColumn('rank_badge', function ($row) {

                    if (!$row->rank_badge) {
                        return '-';
                    }

                    $image = asset('storage/' . $row->rank_badge);

                    return '
                        <img src="'.$image.'"
                             width="40"
                             height="40"
                             class="image-preview"
                             data-image="'.$image.'"
                             style="cursor:pointer;border-radius:6px;object-fit:cover;">
                    ';
                })

                ->addColumn('action', function ($row) {
                    $btn = '<div class="dropdown">
                    <button class="btn btn-sm btn-link dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-ellipsis-h"></i>
                    </button>
                    <div class="dropdown-menu">';

                    if (Helper::userCan(121, 'can_edit')) {
                        $btn .= '<a class="dropdown-item" href="' . route('store.uid.edit', $row->id) . '">Edit</a>';
                    }

                    if (Helper::userCan(121, 'can_delete')) {
                        $btn .= '<button class="dropdown-item text-danger delete" data-id="' . $row->id . '">Delete</button>';
                    }

                    $btn .= '</div></div>';

                    return $btn;
                })

                ->rawColumns(['rank_name', 'pattern_name', 'unique_id', 'badge', 'rank_badge', 'action'])
                ->make(true);
        }

        return view('store_uids.index');
    }


    public function storeUidAdd(): View
    {
        $ranks = Rank::get();
        $patterns = Pattern::get();
        return view('store_uids.add', compact('ranks', 'patterns'));
    }

    public function storeUidSave(Request $request)
    {
        $rules = [
            'rank'            => 'required|exists:ranks,id',
            'pattern'         => 'required|exists:patterns,id',
            'uid'             => 'required|string|max:255|unique:store_uids,unique_id',
            'visibility_type' => 'required|in:backend,in_app',

            'icon'            => 'required|image|mimes:png,jpg,jpeg,webp',
            'rank_badge'      => 'required|image|mimes:png,jpg,jpeg,webp',
            'rank_badge_color' => 'required|string|max:255',
        ];

        if ($request->visibility_type == 'in_app') {

            $rules['needcoin']   = 'required|array|min:1';
            $rules['needcoin.*'] = 'required|integer|min:1|max:9999999';

            $rules['validity']   = 'required|array|min:1';
            $rules['validity.*'] = 'required|integer|min:1';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        return DB::transaction(function () use ($request) {

            $badge = Helper::saveFile($request->file('icon'), 'store_uid_images');
            $rankBadge = Helper::saveFile($request->file('rank_badge'), 'store_uid_images');

            StoreUids::create([
                'rank_id'          => $request->rank,
                'pattern_id'       => $request->pattern,
                'unique_id'        => $request->uid,
                'visibility_type'  => $request->visibility_type,
                'badge'            => $badge,
                'rank_badge'       => $rankBadge,
                'rank_badge_color' => $request->rank_badge_color,
                'status'           => $request->status,

                'needcoin'       => $request->visibility_type === 'in_app'
                    ? array_values($request->needcoin)
                    : null,

                'validity'         => $request->visibility_type === 'in_app'
                    ? array_values($request->validity)
                    : null,
            ]);

            return redirect()
                ->route('store.uid')
                ->with('success', 'Store UID added successfully');
        });
    }


    public function storeUidEdit($id): View|RedirectResponse
    {
        $storeUid = StoreUids::find($id);
        $ranks = Rank::get();
        $patterns = Pattern::get();

        if (!$storeUid) {
            return to_route('store.uid')->withError('Data Card Not Found!');
        }
        return view('store_uids.edit', compact('storeUid', 'ranks', 'patterns'));
    }

    public function storeUidUpdate(Request $request, $id)
    {
        $storeUid = StoreUids::findOrFail($id);

        $rules = [
            'rank'            => 'required|exists:ranks,id',
            'pattern'         => 'required|exists:patterns,id',
            'uid'             => 'required|string|max:255|unique:store_uids,unique_id,' . $storeUid->id,
            'visibility_type' => 'required|in:backend,in_app',
            'icon'            => 'nullable|image|mimes:png,jpg,jpeg,webp',
            'rank_badge'      => 'nullable|image|mimes:png,jpg,jpeg,webp',
            'rank_badge_color' => 'nullable|string|max:255',
            'status'          => 'required|in:0,1',
        ];

        if ($request->visibility_type == 'in_app') {

            $rules['needcoin']   = 'required|array|min:1';
            $rules['needcoin.*'] = 'required|integer|min:1|max:9999999';
            $rules['validity']   = 'required|array|min:1';
            $rules['validity.*'] = 'required|integer|min:1';
        }

        $request->validate($rules);

        return DB::transaction(function () use ($request, $storeUid) {

            $data = [
                'rank_id'          => $request->rank,
                'pattern_id'       => $request->pattern,
                'unique_id'        => $request->uid,
                'visibility_type'  => $request->visibility_type,
                'status'           => $request->status,

                'needcoin'       => $request->visibility_type === 'in_app'
                    ? array_values($request->needcoin)
                    : null,

                'validity'         => $request->visibility_type === 'in_app'
                    ? array_values($request->validity)
                    : null,
                'rank_badge_color' => $request->rank_badge_color ?? null,
            ];

            if ($request->hasFile('icon')) {
                if ($storeUid->badge && file_exists(public_path('storage/' . $storeUid->badge))) {
                    @unlink(public_path('storage/' . $storeUid->badge));
                }

                $data['badge'] = Helper::saveFile($request->file('icon'), 'store_uid_images');
            }

            if ($request->hasFile('rank_badge')) {
                if ($storeUid->rank_badge && file_exists(public_path('storage/' . $storeUid->rank_badge))) {
                    @unlink(public_path('storage/' . $storeUid->rank_badge));
                }

                $data['rank_badge'] = Helper::saveFile($request->file('rank_badge'), 'store_uid_images');
            }

            $storeUid->update($data);

            return redirect()
                ->route('store.uid')
                ->with('success', 'Store UID updated successfully');
        });
    }



    public function storeUidDelete(Request $request): JsonResponse
    {
        return Helper::deleteRecord(new StoreUids, $request->id);
    }
}
