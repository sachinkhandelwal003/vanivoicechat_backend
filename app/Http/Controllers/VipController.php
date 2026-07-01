<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Helper\Helper;
use App\Models\Frame;
use App\Models\Vip;
use App\Models\VipPrivilege;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class VipController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {

            $query = Vip::latest();

            return DataTables::of($query)
                ->addIndexColumn()
                ->editColumn('badge', function ($row) {

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
                
                ->editColumn('entry_tag', function ($row) {
                
                    $image = asset('storage/' . $row->entry_tag);
                
                    return '
                        <img src="'.$image.'"
                             width="40"
                             height="40"
                             class="image-preview"
                             data-image="'.$image.'"
                             style="cursor:pointer;border-radius:6px;object-fit:cover;">
                    ';
                })
                
                ->editColumn('chat_card', function ($row) {
                
                    if (!$row->chat_card) {
                        return '-';
                    }
                
                    $image = asset('storage/' . $row->chat_card);
                
                    return '
                        <img src="'.$image.'"
                             width="40"
                             height="40"
                             class="image-preview"
                             data-image="'.$image.'"
                             style="cursor:pointer;border-radius:6px;object-fit:cover;">
                    ';
                })
                
                ->editColumn('image_frame', function ($row) {
                
                    $image = asset('storage/' . $row->image_frame);
                
                    return '
                        <img src="'.$image.'"
                             width="40"
                             height="40"
                             class="image-preview"
                             data-image="'.$image.'"
                             style="cursor:pointer;border-radius:6px;object-fit:cover;">
                    ';
                })
                
                ->editColumn('profile_frame', function ($row) {
                
                    $image = asset('storage/' . $row->profile_frame);
                
                    return '
                        <img src="'.$image.'"
                             width="40"
                             height="40"
                             class="image-preview"
                             data-image="'.$image.'"
                             style="cursor:pointer;border-radius:6px;object-fit:cover;">
                    ';
                })
                ->editColumn('validity', function ($row) {
                    return $row->days . ' , ' . $row->needcoins;
                })
                ->addColumn('action', function ($row) {
                    $btn = '<div class="dropdown">
                    <button class="btn btn-sm btn-link dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-ellipsis-h"></i>
                    </button>
                    <div class="dropdown-menu">';

                    if (Helper::userCan(104, 'can_edit')) {
                        $btn .= '<a class="dropdown-item" href="' . route('privilege.index', $row->id) . '">Add Privilege</a>';
                    }
                    if (Helper::userCan(104, 'can_edit')) {
                        $btn .= '<a class="dropdown-item" href="' . route('vip.edit', $row->id) . '">Edit</a>';
                    }

                    if (Helper::userCan(105, 'can_delete')) {
                        $btn .= '<button class="dropdown-item text-danger delete" data-id="' . $row->id . '">Delete</button>';
                    }

                    $btn .= '</div></div>';

                    return $btn;
                })
                ->rawColumns(['badge', 'entry_tag', 'chat_card', 'image_frame', 'profile_frame', 'validity', 'action'])
                ->make(true);
        }

        return view('vips.index');
    }

    public function add(): View
    {
        return view('vips.add');
    }

    public function save(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [

            'name'      => 'required|string|max:255',
            'days'      => 'required|integer|min:1',
            'needcoins' => 'required|integer|min:0',

            'color'     => 'required|string|max:55',
            'username'  => 'required|string|max:55', // username color

            'img_key'   => 'nullable|string|max:55',
            'text_key'  => 'nullable|string|max:55',
            'frame_key' => 'nullable|string|max:55',

            // Images
            'badge'        => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5048',
            'chat_card'    => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5048',
            'entry_tag'    => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5048',
            'image_frame'  => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5048',
            'profile_frame' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5048',
            'voice_frame'  => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5048',

            // Animations
            'entry_tag_animation'     => 'nullable',
            'image_frame_animation'   => 'nullable',
            'profile_frame_animation' => 'nullable',
            'voice_animation'         => 'nullable',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();

        try {

            $vip = new Vip();

            // BASIC
            $vip->name      = $request->name;
            $vip->days      = $request->days;
            $vip->needcoins = $request->needcoins;

            $vip->color     = $request->color;
            $vip->username  = $request->username; // color

            // KEYS
            $vip->img_key   = $request->img_key;
            $vip->text_key  = $request->text_key;
            $vip->frame_key = $request->frame_key;

            // FILE FIELDS
            $fileFields = [
                'badge',
                'chat_card',
                'entry_tag',
                'entry_tag_animation',
                'image_frame',
                'image_frame_animation',
                'profile_frame',
                'profile_frame_animation',
                'voice_frame',
                'voice_animation',
            ];

            foreach ($fileFields as $field) {

                if ($request->hasFile($field)) {
                    $vip->$field = Helper::saveFile($request->file($field), 'vip');
                }
            }

            $vip->save();

            DB::commit();

            return redirect()->route('vip')->with('success', 'VIP added successfully.');
        } catch (\Throwable $e) {

            DB::rollBack();

            return redirect()->back()
                ->withInput()
                ->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }

    public function edit($id): View|RedirectResponse
    {
        $vip = Vip::find($id);

        if (!$vip) {
            return to_route('vip')->withError('Vip Not Found!');
        }
        return view('vips.edit', compact('vip'));
    }

    public function update(Request $request, $id): RedirectResponse
{
    $validator = Validator::make($request->all(), [
        'name'      => 'required|string|max:255',
        'days'      => 'required|integer|min:1',
        'needcoins' => 'required|integer|min:0',

        'color'    => 'required|string|max:55',
        'username' => 'required|string|max:55',

        'img_key'   => 'nullable|string|max:55',
        'text_key'  => 'nullable|string|max:55',
        'frame_key' => 'nullable|string|max:55',

        'badge'         => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5048',
        'chat_card'     => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5048',
        'entry_tag'     => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5048',
        'image_frame'   => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5048',
        'profile_frame' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5048',
        'voice_frame'   => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5048',

        'entry_tag_animation'     => 'nullable',
        'image_frame_animation'   => 'nullable',
        'profile_frame_animation' => 'nullable',
        'voice_animation'         => 'nullable',
    ]);

    if ($validator->fails()) {
        return redirect()->back()->withErrors($validator)->withInput();
    }

    $vip = Vip::find($id);

    if (!$vip) {
        return redirect()->route('vip')->with('error', 'VIP not found.');
    }

    DB::beginTransaction();

    try {
        $vip->name      = $request->name;
        $vip->days      = $request->days;
        $vip->needcoins = $request->needcoins;
        $vip->color     = $request->color;
        $vip->username  = $request->username;

        $vip->img_key   = $request->img_key;
        $vip->text_key  = $request->text_key;
        $vip->frame_key = $request->frame_key;

        $fileFields = [
            'badge',
            'chat_card',
            'entry_tag',
            'entry_tag_animation',
            'image_frame',
            'image_frame_animation',
            'profile_frame',
            'profile_frame_animation',
            'voice_frame',
            'voice_animation',
        ];

        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {

                if (!empty($vip->$field)) {
                    $oldPath = public_path('storage/' . $vip->$field);

                    if (file_exists($oldPath)) {
                        @unlink($oldPath);
                    }
                }

                $vip->$field = Helper::saveFile($request->file($field), 'vip');
            }
        }

        $vip->save();

        DB::commit();

        return redirect()->route('vip')->with('success', 'VIP updated successfully.');
    } catch (\Throwable $e) {
        DB::rollBack();

        return redirect()->back()
            ->withInput()
            ->with('error', 'Something went wrong: ' . $e->getMessage());
    }
}


    public function delete(Request $request): JsonResponse
    {
        return Helper::deleteRecord(new Vip, $request->id);
    }


    public function privilegeIndex(Request $request, $id): View|JsonResponse
    {
        $vipId = $id;

        if ($request->ajax()) {

            $query = VipPrivilege::where('vip_id', $id)->latest();

            return DataTables::of($query)
                ->addIndexColumn()
                ->editColumn('icon', function ($row) {
                    return '<img src="' . asset('storage/' . $row->icon) . '" width="40">';
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
                        $btn .= '<a class="dropdown-item" href="' . route('privilege.edit', $row->id) . '">Edit</a>';
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

        return view('vips.privilege.index', compact('vipId'));
    }

    public function privilegeAdd($vipId): View
    {
        return view('vips.privilege.add', compact('vipId'));
    }

    public function privilegeSave(Request $request, $vipId): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name'   => 'required|string|max:255',
            'status' => 'required|in:0,1',
            'icon'   => 'required|image|mimes:jpg,jpeg,png,webp|max:5048',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $privilege = new VipPrivilege();
            $privilege->vip_id = $vipId;
            $privilege->name = $request->name;
            $privilege->status = $request->status;
            $privilege->icon = Helper::saveFile($request->file('icon'), 'vip/privilege');

            $privilege->save();

            return redirect()->route('privilege.index', $vipId)
                ->with('success', 'Privilege added successfully.');
        } catch (\Throwable $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    public function privilegeEdit($id): View|RedirectResponse
    {
        $privilege = VipPrivilege::find($id);

        if (!$privilege) {
            return redirect()->route('vip')->with('error', 'Privilege not found.');
        }

        return view('vips.privilege.edit', compact('privilege'));
    }

    public function privilegeUpdate(Request $request, $id): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name'   => 'required|string|max:255',
            'status' => 'required|in:0,1',
            'icon'   => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5048',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $privilege = VipPrivilege::find($id);

        if (!$privilege) {
            return redirect()->route('vip')->with('error', 'Privilege not found.');
        }

        try {
            $privilege->name = $request->name;
            $privilege->status = $request->status;

            if ($request->hasFile('icon')) {
                $privilege->icon = Helper::saveFile($request->file('icon'), 'vip/privilege');
            }

            $privilege->save();

            return redirect()->route('privilege.index', $privilege->vip_id)
                ->with('success', 'Privilege updated successfully.');
        } catch (\Throwable $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }


    public function privilegeDelete(Request $request): JsonResponse
    {
        return Helper::deleteRecord(new VipPrivilege, $request->id);
    }
}
