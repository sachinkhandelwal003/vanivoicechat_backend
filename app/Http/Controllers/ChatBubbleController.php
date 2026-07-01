<?php

namespace App\Http\Controllers;

use App\Helper\Helper;
use App\Models\ChatBubble;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ChatBubbleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {

            $query = ChatBubble::latest();

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

                    if (Helper::userCan(104, 'can_edit')) {
                        $btn .= '<a class="dropdown-item" href="' . route('chat.bubble.edit', $row->id) . '">Edit</a>';
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

        return view('chat_bubble.index');
    }

    public function add(): View
    {
        return view('chat_bubble.add');
    }


    public function save(Request $request)
    {
        $rules = [
            'name'            => 'required|string|max:255',
            'visibility_type' => 'required|in:backend,in_app',
            'icon'            => 'required|image|mimes:png,jpg,jpeg,webp',
            // 'slice_rect'      => 'required',
            // 'padding_rect'    => 'required',
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

            $icon = Helper::saveFile($request->file('icon'), 'chat_bubble_images');

            ChatBubble::create([
                'name'            => $request->name,
                'visibility_type' => $request->visibility_type,
                'status'          => $request->status,
                'slice_rect'      => $request->slice_rect,
                'padding_rect'    => $request->padding_rect,
                'icon'             => $icon,
                'needcoin' => $request->visibility_type === 'in_app'
                    ? array_values($request->needcoin)
                    : null,

                'validity' => $request->visibility_type === 'in_app'
                    ? array_values($request->validity)
                    : null,
            ]);

            return redirect()
                ->route('chat.bubble')
                ->with('success', 'Chat Bubble added successfully');
        });
    }

    public function edit($id): View|RedirectResponse
    {
        $chatBubble = ChatBubble::find($id);

        if (!$chatBubble) {
            return to_route('chat.bubble')->withError('Chat Bubble Not Found!');
        }
        return view('chat_bubble.edit', compact('chatBubble'));
    }

    public function update(Request $request, $id)
    {
        $chatBubble = ChatBubble::findOrFail($id);

        $rules = [
            'name'            => 'required|string|max:255',
            'visibility_type' => 'required|in:backend,in_app',
            'icon'            => 'nullable|image|mimes:png,jpg,jpeg,webp',
            // 'slice_rect'      => 'required',
            // 'padding_rect'    => 'required',
            'status'          => 'required|in:0,1',
        ];

        if ($request->visibility_type == 'in_app') {

            $rules['needcoin']   = 'required|array|min:1';
            $rules['needcoin.*'] = 'required|integer|min:1|max:9999999';

            $rules['validity']   = 'required|array|min:1';
            $rules['validity.*'] = 'required|integer|min:1';
        }

        $request->validate($rules);

        return DB::transaction(function () use ($request, $chatBubble) {

            $data = [
                'name'            => $request->name,
                'visibility_type' => $request->visibility_type,
                'slice_rect'      => $request->slice_rect,
                'padding_rect'    => $request->padding_rect,
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

                if ($chatBubble->icon && file_exists(public_path($chatBubble->icon))) {
                    @unlink(public_path($chatBubble->icon));
                }

                $data['icon'] = Helper::saveFile($request->file('icon'), 'chat_bubble_images');
            }


            $chatBubble->update($data);

            return redirect()->route('chat.bubble')->with('success', 'Chat Bubble updated successfully');
        });
    }


    public function delete(Request $request): JsonResponse
    {
        return Helper::deleteRecord(new ChatBubble, $request->id);
    }
}
