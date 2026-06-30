<?php

namespace App\Http\Controllers;

use App\Models\BdUser;
use App\Models\AppUser;
use App\Models\Country;
use App\Models\Host;
use App\Models\Agency;
use App\Models\AdminAccount;
use App\Helper\Helper;
use Carbon\Carbon;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BdUserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {

            $query = BdUser::with(['user', 'admin.user', 'country'])->latest();

            return DataTables::of($query)
                ->addIndexColumn()

                ->addColumn('user', function ($row) {
                    if (!$row->user) return '-';

                    $image = $row->user->image
                        ? Helper::showImage($row->user->image, true)
                        : asset('assets/img/avatar.png');

                    return '
                        <div class="d-flex align-items-center gap-2">
                            <img src="' . $image . '" width="40" height="40" class="rounded-circle">
                            <div>
                                <div class="fw-bold">' . e($row->user->name) . '</div>
                                <small class="text-muted">UID: ' . e($row->user->uid) . '</small>
                            </div>
                        </div>
                    ';
                })

                ->addColumn('admin', function ($row) {
                    if (!$row->admin || !$row->admin->user) return '-';

                    $adminUser = $row->admin->user;

                    $image = $adminUser->image
                        ? Helper::showImage($adminUser->image, true)
                        : asset('assets/img/avatar.png');

                    return '
                    <div class="d-flex align-items-center gap-2">
                        <img src="' . $image . '" width="40" height="40" class="rounded-circle">
                        <div>
                            <div class="fw-bold">' . e($adminUser->name) . '</div>
                            <small class="text-muted">UID: ' . e($adminUser->uid) . '</small>
                        </div>
                    </div>';
                })

                ->addColumn('country', fn($row) => $row->country->name ?? '-')

                ->addColumn('time', function ($row) {
                    return '
                    <div>
                        <div><strong>Created:</strong> ' . Carbon::parse($row->created_at)->format('Y-m-d H:i:s') . '</div>
                        <div><strong>Updated:</strong> ' . Carbon::parse($row->updated_at)->format('Y-m-d H:i:s') . '</div>
                    </div>';
                })

                ->editColumn('status', function ($row) {
                    return $row->status
                        ? '<span class="badge bg-success">Active</span>'
                        : '<span class="badge bg-danger">Inactive</span>';
                })

                ->addColumn('action', function ($row) {
                    return '
                    <div class="dropdown">
                        <button class="btn btn-sm btn-link dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="fas fa-ellipsis-h"></i>
                        </button>
                        <div class="dropdown-menu">
                            <a class="dropdown-item" href="' . route('bd-user.form', $row->id) . '">
                                <i class="fas fa-edit text-primary"></i> Edit
                            </a>
                            <a class="dropdown-item" href="' . route('bd-user.transfer.form', $row->id) . '">
                                <i class="fas fa-exchange-alt text-warning"></i> Transfer BD
                            </a>
                            <button class="dropdown-item text-danger delete" data-id="' . $row->id . '">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                    </div>';
                })

                ->rawColumns(['user', 'admin', 'time', 'status', 'action'])
                ->make(true);
        }

        return view('bd.index');
    }

    public function form($id = null): View|RedirectResponse
    {
        $bd = $id ? BdUser::find($id) : null;

        if ($id && !$bd) {
            return redirect()->route('bd-user')->with('error', 'BD not found');
        }

        $countries = Country::all();

        return view('bd.form', compact('bd', 'countries'));
    }

    public function save(Request $request, $id = null)
    {
        $rules = [
            'user_uid' => 'required|exists:app_users,uid',
            'country_id' => 'required|exists:countries,id',
            'whatsapp_number' => 'nullable|string|max:20',
            'status' => 'required|in:0,1',
        ];

        if ($request->is_admin_bound) {
            $rules['admin_uid'] = 'required|exists:app_users,uid';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        return DB::transaction(function () use ($request, $id) {

            $bd = $id ? BdUser::find($id) : new BdUser();

            $user = AppUser::where('uid', $request->user_uid)->first();
            $userId = $user->id;

            $existsInAdmin = AdminAccount::where('user_id', $userId)->exists();
            $existsInAgency = Agency::where('user_id', $userId)->exists();
            $existsInHost = Host::where('user_id', $userId)->exists();
            $existsInBd = BdUser::where('user_id', $userId)
                ->when($id, fn($q) => $q->where('id', '!=', $id))
                ->exists();

            $role = null;

            if ($existsInAdmin) $role = 'Admin';
            elseif ($existsInAgency) $role = 'Agency';
            elseif ($existsInHost) $role = 'Host';
            elseif ($existsInBd) $role = 'BD';

            if ($role) {
                return redirect()->back()->with('error', "User already exists as $role");
            }

            $adminUser = $request->admin_uid
                ? AppUser::where('uid', $request->admin_uid)->first()
                : null;

            $admin = $adminUser
                ? AdminAccount::where('user_id', $adminUser->id)->first()
                : null;

            $bd->fill([
                'user_id' => $user->id,
                'is_admin_bound' => $request->is_admin_bound ?? 0,
                'admin_id' => $admin->id ?? null,
                'country_id' => $request->country_id,
                'whatsapp_number' => $request->whatsapp_number,
                'briefing' => $request->briefing,
                'status' => $request->status,
            ])->save();

            return redirect()
                ->route('bd-user')
                ->with('success', $id ? 'BD updated successfully' : 'BD added successfully');
        });
    }

    public function delete(Request $request): JsonResponse
    {
        return Helper::deleteRecord(new BdUser, $request->id);
    }


    public function transferForm($id)
    {
        $bd = BdUser::with('user')->findOrFail($id);

        return view('bd.transfer', compact('bd'));
    }

    public function transferSave(Request $request, $id)
    {
        $request->validate([
            'admin_uid' => 'required|exists:app_users,uid'
        ]);

        $bd = BdUser::findOrFail($id);

        $adminUser = AppUser::where('uid', $request->admin_uid)->first();

        $admin = AdminAccount::where('user_id', $adminUser->id)->first();

        if (!$admin) {
            return back()->with('error', 'Admin not found');
        }

        $bd->admin_id = $admin->id;
        $bd->is_admin_bound = 1;
        $bd->save();

        return redirect()->route('bd-user')->with('success', 'BD transferred successfully');
    }
}
