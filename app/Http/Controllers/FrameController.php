<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Helper\Helper;
use App\Models\Frame;
use App\Models\LuckyGiftWinningSetting;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class FrameController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {

            $query = Frame::latest();

            return DataTables::of($query)
                ->addIndexColumn()
                ->editColumn('icon', function ($row) {
                    return '<img src="' . asset('storage/' . $row->icon) . '" width="40">';
                })
                ->editColumn('status', function ($row) {
                    return $row['status'] == 1 ? '<small class="badge fw-semi-bold rounded-pill status badge-light-success"> Enable</small>' : '<small class="badge fw-semi-bold rounded-pill status badge-light-danger"> Disable</small>';
                })
                ->editColumn('validity', function ($row) {
                    return $row['validity'] ? $row['validity'] : '-';
                })
                ->addColumn('action', function ($row) {
                    $btn = '<div class="dropdown">
                    <button class="btn btn-sm btn-link dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-ellipsis-h"></i>
                    </button>
                    <div class="dropdown-menu">';

                    if (Helper::userCan(104, 'can_edit')) {
                        $btn .= '<a class="dropdown-item" href="' . route('frame.edit', $row->id) . '">Edit</a>';
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

        return view('frame.index');
    }

    public function add(): View
    {
        return view('frame.add');
    }


    public function save(Request $request)
    {
        $rules = [
            'name'            => 'required|string|max:255',
            'visibility_type' => 'required|in:backend,in_app',
            'icon'            => 'required|image|mimes:png,jpg,jpeg,webp',
            'animation'       => 'required',
            'status'          => 'required|in:0,1',
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

            $icon = Helper::saveFile($request->file('icon'), 'frame_images');
            $animation = Helper::saveFile($request->file('animation'), 'frame_images');

            Frame::create([
                'name'            => $request->name,
                'visibility_type' => $request->visibility_type,
                'status'          => $request->status,
                'icon'             => $icon,
                'gif'              => $animation,
                'needcoin' => $request->visibility_type === 'in_app'
                    ? array_values($request->needcoin)
                    : null,

                'validity' => $request->visibility_type === 'in_app'
                    ? array_values($request->validity)
                    : null,
            ]);

            return redirect()
                ->route('frame')
                ->with('success', 'Frame added successfully');
        });
    }

    public function edit($id): View|RedirectResponse
    {
        $frame = Frame::find($id);

        if (!$frame) {
            return to_route('frame')->withError('Frame Not Found!');
        }
        return view('frame.edit', compact('frame'));
    }

    public function update(Request $request, $id)
    {
        $frame = Frame::findOrFail($id);

        $rules = [
            'name'            => 'required|string|max:255',
            'visibility_type' => 'required|in:backend,in_app',
            'icon'            => 'nullable|image|mimes:png,jpg,jpeg,webp',
            'animation'       => 'nullable',
            'status'          => 'required|in:0,1',
        ];

        if ($request->visibility_type == 'in_app') {

            $rules['needcoin']   = 'required|array|min:1';
            $rules['needcoin.*'] = 'required|integer|min:1|max:9999999';

            $rules['validity']   = 'required|array|min:1';
            $rules['validity.*'] = 'required|integer|min:1';
        }

        $request->validate($rules);

        return DB::transaction(function () use ($request, $frame) {

            $data = [
                'name'            => $request->name,
                'visibility_type' => $request->visibility_type,
                'status'          => $request->status,
            ];
            if ($request->visibility_type === 'in_app') {
                $data['needcoin'] = array_values($request->needcoin);
                $data['validity'] = array_values($request->validity);
            } else {
                $data['needcoin'] = null;
                $data['validity'] = null;
            }

            if ($request->hasFile('icon')) {

                if ($frame->icon && file_exists(public_path($frame->icon))) {
                    @unlink(public_path($frame->icon));
                }

                $data['icon'] = Helper::saveFile($request->file('icon'), 'frame_images');
            }

            if ($request->hasFile('animation')) {

                if ($frame->gif && file_exists(public_path($frame->gif))) {
                    @unlink(public_path($frame->gif));
                }

                $data['gif'] = Helper::saveFile($request->file('animation'), 'frame_images');
            }


            $frame->update($data);

            return redirect()->route('frame')->with('success', 'Frame updated successfully');
        });
    }


    public function delete(Request $request): JsonResponse
    {
        return Helper::deleteRecord(new Frame, $request->id);
    }
}
