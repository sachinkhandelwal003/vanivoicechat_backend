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
                    return '<img src="' . asset('storage/' . $row->badge) . '" width="40">';
                })
                ->editColumn('entry_tag', function ($row) {
                    return '<img src="' . asset('storage/' . $row->entry_tag) . '" width="40">';
                })
                ->editColumn('chat_card', function ($row) {
                    return $row->chat_card ? '<img src="' . asset('storage/' . $row->chat_card) . '" width="40">' : " - ";
                })
                ->editColumn('image_frame', function ($row) {
                    return '<img src="' . asset('storage/' . $row->image_frame) . '" width="40">';
                })
                ->editColumn('profile_frame', function ($row) {
                    return '<img src="' . asset('storage/' . $row->profile_frame) . '" width="40">';
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
            'name'       => 'required|string|max:255',
            'day'        => 'required|integer|min:1',
            'coin'       => 'required|integer|min:0',
            'color'       => 'required',
            'name_color' => 'required|string|max:50',

            'badge'      => 'required|image|mimes:jpg,jpeg,png,webp|max:5048',
            'entry_tag'  => 'required|image|mimes:jpg,jpeg,png,webp|max:5048',
            'chat_card'  => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5048',
            'avatar'     => 'required|image|mimes:jpg,jpeg,png,webp|max:5048',
            'frame'      => 'required|image|mimes:jpg,jpeg,png,webp|max:5048',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();

        try {
            $vip = new Vip();

            $vip->name      = $request->name;
            $vip->days      = $request->day;
            $vip->needcoins = $request->coin;
            $vip->color = $request->color;
            $vip->username  = $request->name_color;

            $vip->badge         = Helper::saveFile($request->file('badge'), 'vip');
            $vip->entry_tag     = Helper::saveFile($request->file('entry_tag'), 'vip');
            $vip->chat_card     = Helper::saveFile($request->file('chat_card'), 'vip');
            $vip->image_frame   = Helper::saveFile($request->file('avatar'), 'vip');
            $vip->profile_frame = Helper::saveFile($request->file('frame'), 'vip');

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
            'name'       => 'required|string|max:255',
            'day'        => 'required|integer|min:1',
            'coin'       => 'required|integer|min:0',
            'color'       => 'required',
            'name_color' => 'required|string|max:50',

            'badge'      => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5048',
            'entry_tag'  => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5048',
            'chat_card'  => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5048',
            'avatar'     => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5048',
            'frame'      => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5048',
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
            $vip->days      = $request->day;
            $vip->needcoins = $request->coin;
            $vip->color = $request->color;
            $vip->username  = $request->name_color;

            if ($request->hasFile('badge')) {
                if (!empty($vip->badge) && file_exists(public_path($vip->badge))) {
                    unlink(public_path($vip->badge));
                }
                $vip->badge = Helper::saveFile($request->file('badge'), 'vip');
            }

            if ($request->hasFile('entry_tag')) {
                if (!empty($vip->entry_tag) && file_exists(public_path($vip->entry_tag))) {
                    unlink(public_path($vip->entry_tag));
                }
                $vip->entry_tag = Helper::saveFile($request->file('entry_tag'), 'vip');
            }

            if ($request->hasFile('chat_card')) {
                if (!empty($vip->chat_card) && file_exists(public_path($vip->chat_card))) {
                    unlink(public_path($vip->chat_card));
                }
                $vip->chat_card = Helper::saveFile($request->file('chat_card'), 'vip');
            }

            if ($request->hasFile('avatar')) {
                if (!empty($vip->image_frame) && file_exists(public_path($vip->image_frame))) {
                    unlink(public_path($vip->image_frame));
                }
                $vip->image_frame = Helper::saveFile($request->file('avatar'), 'vip');
            }

            if ($request->hasFile('frame')) {
                if (!empty($vip->profile_frame) && file_exists(public_path($vip->profile_frame))) {
                    unlink(public_path($vip->profile_frame));
                }
                $vip->profile_frame = Helper::saveFile($request->file('frame'), 'vip');
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
