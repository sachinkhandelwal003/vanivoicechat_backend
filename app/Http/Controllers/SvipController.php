<?php

namespace App\Http\Controllers;

use App\Models\Svip;
use App\Models\SvipPrivilege;
use App\Models\SvipLevelPrivilege;
use App\Helper\Helper;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

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
                    return $row->medal
                        ? '<img src="' . asset('storage/' . $row->medal) . '" width="40">'
                        : '-';
                })

                ->editColumn('medal_gif', function ($row) {
                    return $row->medal_gif
                        ? '<img src="' . asset('storage/' . $row->medal_gif) . '" width="50" height="50" style="object-fit:contain;">'
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
                    <div class="dropup text-center">
                        <button class="btn btn-sm btn-light rounded-pill px-3" data-bs-toggle="dropdown">
                            <i class="fas fa-ellipsis-h"></i>
                        </button>

                        <div class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 p-2">

                            <a class="dropdown-item d-flex align-items-center gap-2" href="' . route('svip.form', $row->id) . '">
                                <i class="fas fa-edit text-primary"></i> Edit
                            </a>

                            <button class="dropdown-item d-flex align-items-center gap-2 text-danger delete" data-id="' . $row->id . '">
                                <i class="fas fa-trash"></i> Delete
                            </button>

                        </div>
                    </div>';
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
        $privileges = SvipPrivilege::orderBy('sort_order')->get();

        // selected privileges
        $selectedPrivileges = $svip
            ? $svip->privileges->pluck('id')->toArray()
            : [];

        return view('svip.form', compact('svip', 'privileges', 'selectedPrivileges'));
    }

    public function save(Request $request, $id = null)
    {
        $rules = [
            'name' => 'required|string|max:100',
            'need_coins' => 'required|integer',
            'days' => 'nullable|integer',
            'color' => 'nullable',

            'medal' => 'nullable|image',
            'medal_gif' => 'nullable|mimes:gif',
            'title' => 'nullable|image',
            'bubble' => 'nullable|image',
            'headwear' => 'nullable|image',
            'entry' => 'nullable|image',

            'privileges' => 'nullable|array'
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
                'color'
            ]);

            // FILE UPLOADS
            foreach (['medal', 'medal_gif', 'title', 'bubble', 'headwear', 'entry'] as $file) {

                if ($request->hasFile($file)) {

                    if ($id && $svip->$file && file_exists(public_path($svip->$file))) {
                        @unlink(public_path($svip->$file));
                    }

                    $data[$file] = Helper::saveFile($request->file($file), 'svip');
                }
            }

            $svip->fill($data)->save();

            // PRIVILEGES SYNC
            if ($request->has('privileges')) {

                SvipLevelPrivilege::where('svip_id', $svip->id)->delete();

                foreach ($request->privileges as $privilegeId) {
                    SvipLevelPrivilege::create([
                        'svip_id' => $svip->id,
                        'privilege_id' => $privilegeId,
                        'is_active' => 1
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
}
