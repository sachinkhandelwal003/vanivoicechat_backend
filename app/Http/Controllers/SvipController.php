<?php

namespace App\Http\Controllers;

use App\Models\AppUser;
use App\Models\Svip;
use App\Models\SvipPrivilege;
use App\Models\SvipLevelPrivilege;
use App\Models\SvipTransaction;
use App\Helper\Helper;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SvipController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {

            $query = Svip::latest();

            return DataTables::of($query)
                ->addIndexColumn()

                ->editColumn('medal', function ($row) {

                    if (!$row->medal) {
                        return '-';
                    }

                    $image = asset('storage/' . $row->medal);

                    return '
                        <img src="' . $image . '" width="40" height="40" class="image-preview" data-image="' . $image . '"
                             style="cursor:pointer;border-radius:6px;object-fit:cover;">
                    ';
                })

                ->editColumn('medal_gif', function ($row) {

                    if (!$row->medal_gif) {
                        return '-';
                    }

                    $image = asset('storage/' . $row->medal_gif);

                    return '
                        <img src="' . $image . '" width="50" height="50" class="image-preview" data-image="' . $image . '"
                             style="cursor:pointer;object-fit:contain;">
                    ';
                })

                ->editColumn('created_at', function ($row) {
                    return $row->created_at
                        ? $row->created_at->timezone('Asia/Kolkata')->format('d M Y, h:i A')
                        : '-';
                })

                ->editColumn('status', function ($row) {
                    return $row->status ? 'Active' : 'Inactive';
                })

                ->addColumn('action', function ($row) {

                    $btn = '
                            <div class="dropup text-center">
                                <button class="btn btn-sm btn-light rounded-pill px-3" data-bs-toggle="dropdown">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>

                                <div class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 p-2">';

                    // Edit Permission
                    if (Helper::userCan(123, 'can_edit')) {
                        $btn .= '
                                <a class="dropdown-item d-flex align-items-center gap-2"
                                href="' . route('svip.form', $row->id) . '">
                                    <i class="fas fa-edit text-primary"></i> Edit
                                </a>';
                    }

                    // Delete Permission
                    if (Helper::userCan(123, 'can_delete')) {
                        $btn .= '
                                <button class="dropdown-item d-flex align-items-center gap-2 text-danger delete"
                                        data-id="' . $row->id . '">
                                    <i class="fas fa-trash"></i> Delete
                                </button>';
                    }

                    $btn .= '
                            </div>
                        </div>';

                    return $btn;
                })

                ->rawColumns(['medal', 'medal_gif', 'action'])
                ->make(true);
        }

        return view('svip.index');
    }

    public function form($id = null): View|RedirectResponse
    {
        $svip = null;

        if ($id) {
            $svip = Svip::with('privileges')->find($id);

            if (!$svip) {
                return to_route('svip')->withError('SVIP Not Found!');
            }
        }


        // all privileges
        $privileges = SvipPrivilege::where('status', 1)->orderBy('sort_order')->get();

        // selected privileges
        $selectedPrivileges = $svip
            ? $svip->privileges->pluck('id')->toArray()
            : [];

        return view('svip.form', compact('svip', 'privileges', 'selectedPrivileges'));
    }

    public function save(Request $request, $id = null)
    {
        $rules = [
            'name'       => 'required|string|max:100',
            'need_coins' => 'required|integer',
            'days'       => 'nullable|integer',
            'admin_limit' => 'nullable|integer',
            'color'      => 'nullable|string|max:55',
            'status'     => 'nullable|in:0,1',

            'img_key'   => 'nullable|string|max:55',
            'text_key'  => 'nullable|string|max:55',
            'frame_key' => 'nullable|string|max:55',

            'medal'              => 'nullable|image|mimes:jpg,jpeg,png,webp',
            'medal_gif'          => 'nullable',
            'title'              => 'nullable|image|mimes:jpg,jpeg,png,webp',
            'bubble'             => 'nullable|image|mimes:jpg,jpeg,png,webp',
            'headwear'           => 'nullable|image|mimes:jpg,jpeg,png,webp',
            'headwear_animation' => 'nullable',
            'entry'              => 'nullable|image|mimes:jpg,jpeg,png,webp',
            'entry_animation'    => 'nullable',
            'entrance_image'              => 'nullable|image|mimes:jpg,jpeg,png,webp',
            'entrance_animation'    => 'nullable',
            'voice_image'              => 'nullable|image|mimes:jpg,jpeg,png,webp',
            'voice_animation'    => 'nullable',
            'profile_card'              => 'nullable|image|mimes:jpg,jpeg,png,webp',
            'profile_animation'    => 'nullable',

            'privileges'   => 'nullable|array',
            'privileges.*' => 'integer|exists:svip_privileges,id',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        return DB::transaction(function () use ($request, $id) {

            $svip = $id ? Svip::find($id) : new Svip();

            if ($id && !$svip) {
                return redirect()->back()->with('error', 'SVIP not found');
            }

            $data = $request->only([
                'name',
                'need_coins',
                'days',
                'admin_limit',
                'color',
                'status',
                'img_key',
                'text_key',
                'frame_key',
            ]);

            $data['status'] = $request->input('status', 1);

            $uploadFields = [
                'medal',
                'medal_gif',
                'title',
                'bubble',
                'headwear',
                'headwear_animation',
                'entry',
                'entry_animation',
                'entrance_image',
                'entrance_animation',
                'voice_image',
                'voice_animation',
                'profile_card',
                'profile_animation',
            ];

            foreach ($uploadFields as $file) {
                if ($request->hasFile($file)) {

                    if ($id && !empty($svip->$file)) {
                        $oldPath = public_path('storage/' . $svip->$file);

                        if (file_exists($oldPath)) {
                            @unlink($oldPath);
                        }
                    }

                    $data[$file] = Helper::saveFile($request->file($file), 'svip');
                }
            }

            $svip->fill($data)->save();

            SvipLevelPrivilege::where('svip_id', $svip->id)->delete();

            if ($request->filled('privileges')) {
                foreach ($request->privileges as $privilegeId) {
                    SvipLevelPrivilege::create([
                        'svip_id'      => $svip->id,
                        'privilege_id' => $privilegeId,
                        'is_active'    => 1,
                    ]);
                }
            }

            return redirect()
                ->route('svip')
                ->with('success', $id ? 'Updated successfully' : 'Created successfully');
        });
    }
    public function delete(Request $request): JsonResponse
    {
        return Helper::deleteRecord(new Svip, $request->id);
    }


    // Privilege Functions
    public function privilegeList(Request $request)
    {
        if ($request->ajax()) {

            $query = SvipPrivilege::latest();

            return DataTables::of($query)
                ->addIndexColumn()

                ->editColumn('icon', function ($row) {
                    return $row->icon
                        ? '<img src="' . asset('storage/' . $row->icon) . '" width="40">'
                        : '-';
                })

                ->editColumn('created_at', function ($row) {
                    return $row->created_at
                        ? $row->created_at->timezone('Asia/Kolkata')->format('d M Y, h:i A')
                        : '-';
                })

                ->editColumn('status', function ($row) {
                    return $row->status ? 'Active' : 'Inactive';
                })

                ->addColumn('action', function ($row) {
                    return '
                    <div class="dropdown">
                        <button class="btn btn-sm btn-link dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="fas fa-ellipsis-h"></i>
                        </button>
                        <div class="dropdown-menu">
                            <a class="dropdown-item" href="' . route('svip-privilege', $row->id) . '"><i class="fas fa-edit text-primary"></i> Edit</a>
                            <button class="dropdown-item text-danger delete" data-id="' . $row->id . '"><i class="fas fa-trash"></i> Delete</button>
                        </div>
                    </div>';
                })

                ->rawColumns(['icon', 'action'])
                ->make(true);
        }

        return view('svip.privilege_index');
    }

    public function privilegeForm($id = null): View|RedirectResponse
    {
        $privilege = null;

        if ($id) {
            $privilege = SvipPrivilege::find($id);

            if (!$privilege) {
                return redirect()->route('svip')->with('error', 'Privilege not found');
            }
        }

        return view('svip.privilege_form', compact('privilege'));
    }

    public function privilegeAdd(Request $request, $id = null)
    {
        $rules = [
            'name' => 'required|string|max:100',
            'icon' => 'nullable|image',
            'sort_order' => 'nullable|integer',
            'status' => 'nullable|boolean',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        return DB::transaction(function () use ($request, $id) {

            $privilege = $id ? SvipPrivilege::find($id) : new SvipPrivilege();

            if ($id && !$privilege) {
                return redirect()->back()->with('error', 'Privilege not found');
            }

            $data = $request->only([
                'name',
                'sort_order',
                'status'
            ]);

            $slug = Str::slug($request->name, '_');

            $originalSlug = $slug;
            $count = 1;

            while (
                SvipPrivilege::where('slug', $slug)
                ->when($id, function ($q) use ($id) {
                    $q->where('id', '!=', $id);
                })
                ->exists()
            ) {
                $slug = $originalSlug . '_' . $count;
                $count++;
            }

            $data['slug'] = $slug;

            if ($request->hasFile('icon')) {

                if ($id && $privilege->icon && file_exists(public_path($privilege->icon))) {
                    @unlink(public_path($privilege->icon));
                }

                $data['icon'] = Helper::saveFile($request->file('icon'), 'svip_privilege');
            }

            $privilege->fill($data)->save();

            return redirect()
                ->route('svip-privilege.list')
                ->with('success', $id ? 'Privilege updated successfully' : 'Privilege added successfully');
        });
    }

    public function privilegeDelete(Request $request): JsonResponse
    {
        return Helper::deleteRecord(new SvipPrivilege, $request->id);
    }


    public function svipUserIndex(Request $request)
    {
        if ($request->ajax()) {

            $query = SvipTransaction::with(['user', 'svip'])
                ->where('end_at', '>', now());

            if ($request->filled('uid')) {
                $query->whereHas('user', function ($q) use ($request) {
                    $q->where('uid', 'like', '%' . $request->uid . '%');
                });
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

                    if (!$user) return 'N/A';

                    $avatar = !empty($user->image)
                        ? Helper::showImage($user->image, true)
                        : asset('assets/img/avatar.png');

                    return '
                    <div class="d-flex align-items-center gap-3">
                        <img src="' . $avatar . '" class="rounded-circle"
                            width="45" height="45"
                            style="object-fit:cover;">

                        <div>
                            <div class="fw-bold">' . e($user->name) . '</div>
                            <small class="text-muted">
                                UID : ' . e($user->uid) . '
                            </small>
                        </div>
                    </div>';
                })

                ->addColumn('svip_name', function ($row) {
                    return $row->svip->name ?? '-';
                })

                ->editColumn('coins_used', function ($row) {
                    return number_format($row->coins_used);
                })

                ->editColumn('start_at', function ($row) {
                    return $row->start_at
                        ? \Carbon\Carbon::parse($row->start_at)->format('d M Y')
                        : '-';
                })

                ->editColumn('end_at', function ($row) {
                    return $row->end_at
                        ? \Carbon\Carbon::parse($row->end_at)->format('d M Y')
                        : '-';
                })

                ->addColumn('status', function ($row) {

                    if ($row->end_at > now()) {
                        return '<span class="badge bg-success">Active</span>';
                    }

                    return '<span class="badge bg-danger">Expired</span>';
                })
                ->addColumn('action', function ($row) {
                    $btn = '<div class="dropdown">
                    <button class="btn btn-sm btn-link dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-ellipsis-h"></i>
                    </button>
                    <div class="dropdown-menu">';
                    if (Helper::userCan(115, 'can_delete')) {
                        $btn .= '<button class="dropdown-item text-danger delete" data-id="' . $row->id . '">Delete</button>';
                    }

                    $btn .= '</div></div>';

                    return $btn;
                })
                ->rawColumns(['action', 'user_info', 'status'])
                ->make(true);
        }

        return view('svip.svip_user_index');
    }

    public function deleteUserSvip(Request $request): JsonResponse
    {
        $request->validate([
            'id' => 'required|exists:svip_transactions,id'
        ]);

        $transaction = SvipTransaction::findOrFail($request->id);

        // User ke active SVIP items remove karo
        AppUser::where('id', $transaction->user_id)->update([
            'active_frame_id'            => null,
            'active_frame_type'          => null,

            'active_voice_id'            => null,
            'active_voice_type'          => null,

            'active_chat_bubble_id'      => null,
            'active_chat_bubble_type'    => null,

            'active_card_id'             => null,
            'active_profile_card_type'   => null,

            'active_car_id'              => null,
            'active_entry_type'          => null,

            'active_entry_id'            => null,
            'active_entry_tag_type'      => null,
        ]);

        // SVIP purchase record delete
        $transaction->delete();

        return response()->json([
            'status' => true,
            'message' => 'SVIP removed successfully.'
        ]);
    }
}
