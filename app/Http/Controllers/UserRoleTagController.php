<?php

namespace App\Http\Controllers;

use App\Models\UserRoleTag;
use App\Models\User;
use App\Models\Notification;
use App\Helper\Helper;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Yajra\DataTables\Facades\DataTables;
use App\Services\FirebaseService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\RedirectResponse;


class UserRoleTagController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            $query = UserRoleTag::latest();

            return DataTables::of($query)
                ->addIndexColumn()
                ->editColumn('file', function ($row) {
                    if (!$row->file) {
                        return '-';
                    }
                    $file = Helper::showImage($row->file);
                    return '
                    <img src="' . $file . '"
                        width="50" height="50" class="image-preview" data-image="' . $file . '"
                        style="cursor:pointer;border-radius:8px;object-fit:cover;">
                    ';
                })
                ->editColumn('role_type', function ($row) {
                    return ucwords(str_replace('_', ' ', $row->role_type));
                })
                ->editColumn('file_type', function ($row) {
                    return strtoupper($row->file_type);
                })
                ->editColumn('status', function ($row) {
                    return $row->status == 1
                        ? '<small class="badge fw-semi-bold rounded-pill badge-light-success">Active</small>'
                        : '<small class="badge fw-semi-bold rounded-pill badge-light-danger">Inactive</small>';
                })
                ->editColumn('created_at', function ($row) {
                    return $row->created_at
                        ? $row->created_at->format('d M Y h:i A') : '-';
                })
                ->addColumn('action', function ($row) {
                    $btn = '<div class="dropdown">
                    <button class="btn btn-sm btn-link dropdown-toggle"
                            data-bs-toggle="dropdown">
                        <i class="fas fa-ellipsis-h"></i>
                    </button>

                    <div class="dropdown-menu">';
                    if (Helper::userCan(125, 'can_edit')) {
                        $btn .= '<a class="dropdown-item" href="' . route('user-role-tags.edit', $row->id) . '"> Edit </a>';
                    }
                    if (Helper::userCan(125, 'can_delete')) {
                        $btn .= '<button class="dropdown-item text-danger delete" data-id="' . $row->id . '"> Delete </button>';
                    }
                    $btn .= '</div></div>';
                    return $btn;
                })
                ->rawColumns(['file', 'status', 'action'])
                ->make(true);
        }
        return view('user_role_tags.index');
    }

    public function add(): View
    {
        return view('user_role_tags.add');
    }

    public function save(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name'      => 'required|string|max:255',
            'role_type' => 'required|string|max:55',
            'file_type' => 'required|in:image,gif,svga',
            'file'      => 'required|file',
            'status'    => 'required|in:0,1',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $userRoleTag = null;

        DB::transaction(function () use ($request, &$userRoleTag) {

            $file = Helper::saveFile($request->file('file'), 'user_role_tags');

            $userRoleTag = UserRoleTag::create([
                'name'      => $request->name,
                'role_type' => $request->role_type,
                'file_type' => $request->file_type,
                'file'      => $file,
                'status'    => $request->status,
            ]);
        });

        return redirect()
            ->route('user-role-tags')
            ->with('success', 'User Role Tag added successfully.');
    }

    public function edit($id): View|RedirectResponse
    {
        $userRoleTag = UserRoleTag::find($id);

        if (!$userRoleTag) {
            return to_route('user-role-tags')
                ->withError('User Role Tag Not Found!');
        }

        return view('user_role_tags.edit', compact('userRoleTag'));
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $userRoleTag = UserRoleTag::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name'      => 'required|string|max:255',
            'role_type' => 'required|in:admin_center,bd,agency,host,coinseller,merchant',
            'file_type' => 'required|in:image,gif,svga',
            'status'    => 'required|in:0,1',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator)
                ->withInput();
        }

        // File validation (only if new file uploaded)
        if ($request->hasFile('file')) {

            $extension = strtolower($request->file('file')->getClientOriginalExtension());

            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svga'];

            if (!in_array($extension, $allowedExtensions)) {
                return redirect()
                    ->back()
                    ->withErrors([
                        'file' => 'Only JPG, JPEG, PNG, WEBP, GIF and SVGA files are allowed.'
                    ])
                    ->withInput();
            }
        }

        DB::transaction(function () use ($request, $userRoleTag) {

            $data = [
                'name'      => $request->name,
                'role_type' => $request->role_type,
                'file_type' => $request->file_type,
                'status'    => $request->status,
            ];

            if ($request->hasFile('file')) {

                // Delete old file
                if (!empty($userRoleTag->file)) {

                    $oldFile = public_path($userRoleTag->file);

                    if (file_exists($oldFile)) {
                        @unlink($oldFile);
                    }
                }

                // Upload new file
                $data['file'] = Helper::saveFile(
                    $request->file('file'),
                    'user_role_tags'
                );
            }

            $userRoleTag->update($data);
        });

        return redirect()
            ->route('user-role-tags')
            ->with('success', 'User Role Tag updated successfully.');
    }


    public function delete(Request $request): JsonResponse
    {
        return Helper::deleteRecord(new UserRoleTag, $request->id);
    }
}
