<?php

namespace App\Http\Controllers;

use App\Models\EntryTag;
use App\Helper\Helper;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class EntryTagController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {

            $query = EntryTag::latest();

            return DataTables::of($query)
                ->addIndexColumn()
                ->editColumn('icon', function ($row) {

                    $image = asset('storage/' . $row->icon);

                    return '
                        <img src="'.$image.'"
                             width="40"
                             height="40"
                             class="image-preview"
                             data-image="'.$image.'"
                             style="cursor:pointer;border-radius:6px;object-fit:cover;">
                    ';
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

                    if (Helper::userCan(119, 'can_edit')) {
                        $btn .= '<a class="dropdown-item" href="' . route('entry.tag.edit', $row->id) . '">Edit</a>';
                    }

                    if (Helper::userCan(119, 'can_delete')) {
                        $btn .= '<button class="dropdown-item text-danger delete" data-id="' . $row->id . '">Delete</button>';
                    }

                    $btn .= '</div></div>';

                    return $btn;
                })
                ->rawColumns(['icon', 'status', 'action'])
                ->make(true);
        }

        return view('entry_tag.index');
    }

    public function add(): View
    {
        return view('entry_tag.add');
    }


    public function save(Request $request)
    {
        $rules = [
            'name'            => 'required|string|max:255',
            'short_tag'            => 'required|string|max:255',
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

            $icon = Helper::saveFile($request->file('icon'), 'entry_tag_images');
            $animation = Helper::saveFile($request->file('animation'), 'entry_tag_images');

            EntryTag::create([
                'name'            => $request->name,
                'short_tag'            => $request->short_tag,
                'visibility_type' => $request->visibility_type,
                'status'          => $request->status,
                'icon'             => $icon,
                'gif'              => $animation,
                'img_key'              => $request->img_key,
                'text_key'              => $request->text_key,
                'frame_key'              => $request->frame_key,
                'needcoin' => $request->visibility_type === 'in_app'
                    ? array_values($request->needcoin)
                    : null,

                'validity' => $request->visibility_type === 'in_app'
                    ? array_values($request->validity)
                    : null,
            ]);

            return redirect()
                ->route('entry.tag')
                ->with('success', 'Entry Tag added successfully');
        });
    }

    public function edit($id): View|RedirectResponse
    {
        $entryTag = EntryTag::find($id);

        if (!$entryTag) {
            return to_route('entry.tag')->withError('Entry Tag Not Found!');
        }
        return view('entry_tag.edit', compact('entryTag'));
    }

    public function update(Request $request, $id)
    {
        $entryTag = EntryTag::findOrFail($id);

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

        return DB::transaction(function () use ($request, $entryTag) {

            $data = [
                'name'            => $request->name,
                'visibility_type' => $request->visibility_type,
                'status'          => $request->status,
                'img_key'              => $request->img_key,
                'text_key'              => $request->text_key,
                'frame_key'              => $request->frame_key,
            ];
            if ($request->visibility_type === 'in_app') {
                $data['needcoin'] = array_values($request->needcoin);
                $data['validity'] = array_values($request->validity);
            } else {
                $data['needcoin'] = null;
                $data['validity'] = null;
            }

            if ($request->hasFile('icon')) {

                if ($entryTag->icon && file_exists(public_path($entryTag->icon))) {
                    @unlink(public_path($entryTag->icon));
                }

                $data['icon'] = Helper::saveFile($request->file('icon'), 'entry_tag_images');
            }

            if ($request->hasFile('animation')) {

                if ($entryTag->gif && file_exists(public_path($entryTag->gif))) {
                    @unlink(public_path($entryTag->gif));
                }

                $data['gif'] = Helper::saveFile($request->file('animation'), 'entry_tag_images');
            }


            $entryTag->update($data);

            return redirect()->route('entry.tag')->with('success', 'Entry Tag updated successfully');
        });
    }


    public function delete(Request $request): JsonResponse
    {
        return Helper::deleteRecord(new EntryTag, $request->id);
    }
}
