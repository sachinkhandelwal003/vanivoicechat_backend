<?php

namespace App\Http\Controllers;

use App\Models\AppUser;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use App\Helper\Helper;

class AppUserController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {

            $users = AppUser::query()->latest()->whereNull('deleted_at');

            if ($request->uid != '') {
                $users->where('uid', $request->uid);
            }

            if ($request->username != '') {
                $users->where('name', 'LIKE', "%{$request->username}%");
            }

            if ($request->equipment != '') {
                $users->where('equipment_number', $request->equipment);
            }

            if ($request->region != '') {
                $users->where('region', $request->region);
            }

            return DataTables::of($users)
                ->addIndexColumn()
                ->editColumn('image', function ($row) {
                    if (!$row->image) {
                        return '<img src="' . asset('storage/default.png') . '" class="rounded-circle" width="45">';
                    }
                    if (filter_var($row->image, FILTER_VALIDATE_URL)) {
                        return '<img src="' . $row->image . '" class="rounded-circle" width="45">';
                    }
                    return '<img src="' . asset('storage/' . $row->image) . '" class="rounded-circle" width="45">';
                })

                ->editColumn(
                    'uid',
                    fn($row) =>
                    '<span class="badge bg-primary">' . $row->uid . '</span>'
                )
                ->editColumn('disable_status', function ($row) {

                    if ($row->is_blacklisted) {
                        return '<span class="badge bg-dark">
                        <i class="fa fa-ban"></i> Blacklisted
                    </span>';
                    }

                    if ($row->is_disabled) {
                        $until = $row->disabled_until
                            ? \Carbon\Carbon::parse($row->disabled_until)->format('Y-m-d')
                            : 'Permanent';

                        return '<span class="badge bg-danger">
                            <i class="fa fa-clock"></i> Disabled <br>
                            <small>Until: ' . $until . '</small>
                        </span>';
                    }

                    return '<span class="badge bg-success">Active</span>';
                })

                ->editColumn(
                    'created_at',
                    fn($row) =>
                    $row->created_at ? $row->created_at->format('Y-m-d H:i:s') : ''
                )
                ->addColumn('operate', function ($row) {
                    $btn = '<div class="dropdown">
                    <button class="btn btn-sm btn-link dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-ellipsis-h"></i>
                    </button>
                    <div class="dropdown-menu">';

                    if (Helper::userCan(104, 'can_view')) {
                        $btn .= '<a class="dropdown-item" href="' . route('user-details', $row->id) . '">User Details</a>';
                    }

                    if ($row->is_disabled) {
                        $btn .= '<button class="dropdown-item text-success activateUserBtn" 
                            data-id="' . $row->id . '" 
                            data-name="' . e($row->name) . '">
                            Activate User
                        </button>';
                    } else {
                        $btn .= '<button class="dropdown-item text-danger disableUserBtn" 
                            data-id="' . $row->id . '" 
                            data-name="' . e($row->name) . '">
                            Disable User
                        </button>';
                    }

                    if ($row->is_blacklisted) {
                        $btn .= '<span class="dropdown-item text-danger disabled">
                            Blacklisted
                        </span>';
                    } else {
                        $btn .= '<button class="dropdown-item text-dark blacklistUserBtn" 
                            data-id="' . $row->id . '" 
                            data-name="' . e($row->name) . '">
                            Blacklist User
                        </button>';
                    }

                    $btn .= '<a class="dropdown-item" href="' . route('user.edit', $row->id) . '">
                        <i class="fas fa-edit text-primary me-2"></i> Edit
                    </a>';

                    $btn .= '<button class="dropdown-item text-danger delete"
                        data-id="' . $row->id . '"
                        data-name="' . e($row->name) . '">
                        <i class="fas fa-trash me-2"></i> Delete
                    </button>';

                    $btn .= '</div></div>';

                    return $btn;
                })

                ->rawColumns(['image', 'uid', 'disable_status', 'operate'])
                ->make(true);
        }

        return view('app_users.index');
    }

    public function userDetails($id)
    {
        $user = AppUser::find($id);

        return view('app_users.view', compact('user'));
    }

    public function disable(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:app_users,id',
            'reason' => 'required|string|max:255',
            'disabled_until' => 'nullable|date'
        ]);

        $user = AppUser::findOrFail($request->user_id);

        $user->is_disabled = 1;
        $user->disabled_reason = $request->reason;
        $user->disabled_until = $request->disabled_until;
        $user->save();

        return response()->json(['status' => true]);
    }

    public function activate(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:app_users,id'
        ]);

        $user = AppUser::findOrFail($request->user_id);

        $user->is_disabled = 0;
        $user->disabled_reason = null;
        $user->disabled_until = null;
        $user->save();

        return response()->json(['status' => true]);
    }

    public function blacklist(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:app_users,id',
            'reason' => 'required|string'
        ]);

        $user = AppUser::findOrFail($request->user_id);

        $user->is_blacklisted = true;
        $user->blacklist_reason = $request->reason;
        $user->blacklisted_at = now();
        $user->save();

        return response()->json(['status' => true]);
    }

    public function edit($id = null)
    {
        $user = $id ? AppUser::findOrFail($id) : null;
        return view('app_users.form', compact('user'));
    }

    public function save(Request $request, $id = null)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'uid' => 'required|unique:app_users,uid,' . $id,
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:20',
        ]);

        $user = $id ? AppUser::findOrFail($id) : new AppUser();

        $user->name = $request->name;
        $user->uid = $request->uid;
        $user->email = $request->email;
        $user->phone = $request->phone;
        $user->gender = $request->gender;
        $user->country = $request->country;
        $user->region = $request->region;
        $user->birthdate = $request->birthdate;

        // IMAGE UPLOAD
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('users', 'public');
            $user->image = $path;
        }

        $user->save();

        return redirect()
            ->route('app-users')->with('success', $id ? 'User updated successfully' : 'User added successfully');
    }

    public function delete(Request $request)
    {
        return Helper::deleteRecord(new AppUser, $request->id);
    }
}
