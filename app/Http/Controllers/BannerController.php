<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Country;
use App\Helper\Helper;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class BannerController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {

            $query = Banner::with('country');

            if ($request->filled('jump')) {
                $query->where('jump', $request->jump);
            }

            if ($request->filled('expired')) {

                $now = Carbon::now();

                if ($request->expired === 'expired') {
                    $query->where('end_time', '<', $now);
                }

                if ($request->expired === 'not_expired') {
                    $query->where('end_time', '>=', $now);
                }
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->editColumn('large_banner', function ($row) {
                    return '<img src="' . asset('storage/' . $row->large_banner) . '" width="150">';
                })
                ->addColumn('country_name', function ($row) {
                    return $row->country ? $row->country->name : '-';
                })
                ->addColumn('redirect_address', function ($row) {

                    if ($row->jump === 'h5') {
                        return e($row->address);
                    }

                    if ($row->jump === 'app') {

                        if ($row->type_address_app === 'personal' && $row->uid) {
                            return "app://user/{$row->uid}";
                        }

                        if ($row->type_address_app === 'room' && $row->room_id) {
                            return "app://enterRoom?roomId={$row->room_id}";
                        }
                    }

                    return '-';
                })
                ->addColumn('action', function ($row) {
                    $btn = '<div class="dropdown">
                    <button class="btn btn-sm btn-link dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-ellipsis-h"></i>
                    </button>
                    <div class="dropdown-menu">';

                    if (Helper::userCan(104, 'can_edit')) {
                        $btn .= '<a class="dropdown-item" href="' . route('banner.edit', $row->id) . '">Edit</a>';
                    }

                    if (Helper::userCan(105, 'can_delete')) {
                        $btn .= '<button class="dropdown-item text-danger delete" data-id="' . $row->id . '">Delete</button>';
                    }

                    $btn .= '</div></div>';

                    return $btn;
                })
                ->rawColumns(['large_banner', 'redirect_address', 'action'])
                ->make(true);
        }

        return view('banner.index');
    }

    public function add(): View
    {
        $country = Country::get();
        return view('banner.add', compact('country'));
    }


    public function save(Request $request)
    {
        $rules = [
            'large_banner' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
            'small_banner' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'jump_type'    => 'required',
            'end_time'     => 'required',
        ];

        if ($request->jump_type === 'h5') {
            $rules['type_address_url'] = 'required|string|max:255';
        }

        if ($request->jump_type === 'app') {
            $rules['type_address_app'] = 'required|in:personal,room';

            if ($request->type_address_app === 'personal') {
                $rules['uid'] = 'required|numeric';
            }

            if ($request->type_address_app === 'room') {
                $rules['roomId'] = 'required|numeric';
            }
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator)
                ->withInput();
        }

        return DB::transaction(function () use ($request) {

            $largeBanner = Helper::saveFile($request->file('large_banner'), 'banner');

            $smallBanner = null;
            if ($request->hasFile('small_banner')) {
                $smallBanner = Helper::saveFile($request->file('small_banner'), 'banner');
            }

            $typeAddress = null;

            if ($request->jump_type === 'h5') {
                $typeAddress = $request->type_address_url;
            }

            if ($request->jump_type === 'app') {
                if ($request->type_address_app === 'personal') {
                    $typeAddress = $request->uid;
                }
                if ($request->type_address_app === 'room') {
                    $typeAddress = $request->roomId;
                }
            }

            Banner::create([
                'large_banner'     => $largeBanner,
                'small_banner'     => $smallBanner,
                'jump'             => $request->jump_type,
                'address'          => $typeAddress,
                'type_address_app' => $request->type_address_app,
                'uid'              => $request->uid,
                'room_id'          => $request->roomId,
                'display'          => $request->display_space,
                'start_time'       => $request->start_time,
                'end_time'         => $request->end_time,
                'region'           => $request->region,
                'description'      => $request->description,
            ]);

            return redirect()
                ->route('banner')
                ->with('success', 'Banner added successfully');
        });
    }

    public function edit($id): View|RedirectResponse
    {
        $banner = Banner::find($id);
        // dd($banner);
        if (!$banner) {
            return to_route('banner')->withError('Banner Not Found!');
        }

        $country = Country::get();
        return view('banner.edit', compact('banner', 'country'));
    }

    public function update(Request $request, $id)
    {
        $banner = Banner::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'jump_type' => 'required',
            'end_time' => 'required',
            'large_banner' => 'nullable|image',
            'small_banner' => 'nullable|image',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $banner->jump = $request->jump_type;

        if ($request->jump_type == 'h5') {
            $banner->address = $request->type_address_url;
            $banner->type_address_app = null;
            $banner->uid = null;
            $banner->room_id = null;
        }

        if ($request->jump_type == 'app') {
            $banner->type_address_app = $request->type_address_app;

            if ($request->type_address_app == 'personal') {
                $banner->uid = $request->uid;
                $banner->room_id = null;
            }

            if ($request->type_address_app == 'room') {
                $banner->room_id = $request->roomId;
                $banner->uid = null;
            }

            $banner->address = null;
        }

        if ($request->hasFile('large_banner')) {
            $banner->large_banner = Helper::saveFile($request->file('large_banner'), 'banner');
        }

        if ($request->hasFile('small_banner')) {
            $banner->small_banner = Helper::saveFile($request->file('small_banner'), 'banner');
        }

        $banner->display = $request->display_space;
        $banner->start_time = $request->start_time;
        $banner->end_time = $request->end_time;
        $banner->region = $request->region;
        $banner->description = $request->description;

        $banner->save();

        return redirect()->route('banner.edit', $banner->id)
            ->with('success', 'Banner updated successfully');
    }

    public function delete(Request $request): JsonResponse
    {
        return Helper::deleteRecord(new Banner, $request->id);
    }
}
