<?php

namespace App\Http\Controllers;

use App\Models\Host;
use App\Models\AppUser;
use App\Models\HostPolicy;
use App\Models\Country;
use App\Models\BdUser;
use App\Models\AdminAccount;
use App\Helper\Helper;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class HostPolicyController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {

            $query = HostPolicy::query()->orderBy('level', 'asc');

            return DataTables::of($query)
                ->addIndexColumn()

                ->editColumn('level', function ($row) {
                    return '<span class="badge bg-primary">Level ' . $row->level . '</span>';
                })

                ->editColumn('time_hours', function ($row) {
                    return $row->time_hours;
                })

                ->editColumn('target_value', function ($row) {
                    return number_format($row->target_value);
                })

                ->editColumn('host_salary', function ($row) {
                    return $row->host_salary;
                })

                ->editColumn('agent_commission', function ($row) {
                    return $row->agent_commission;
                })

                ->editColumn('total_salary', function ($row) {
                    return $row->total_salary;
                })

                ->editColumn('country', function ($row) {
                    return $row->country;
                })

                ->editColumn('status', function ($row) {
                    return $row->status
                        ? '<span class="badge bg-success">Active</span>'
                        : '<span class="badge bg-danger">Inactive</span>';
                })

                ->addColumn('created_at', function ($row) {
                    return '
                    <div>
                        <div><strong>Created:</strong> ' . $row->created_at->format('Y-m-d H:i:s') . '</div>
                        <div><strong>Updated:</strong> ' . $row->updated_at->format('Y-m-d H:i:s') . '</div>
                    </div>
                ';
                })

                ->addColumn('action', function ($row) {

                    if (!Helper::userCan(147, 'can_edit') && !Helper::userCan(147, 'can_delete')) {
                        return '-';
                    }

                    $btn = '
                            <div class="dropdown">
                                <button class="btn btn-sm btn-link dropdown-toggle" data-bs-toggle="dropdown">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>

                                <div class="dropdown-menu">';

                    // Edit Permission
                    if (Helper::userCan(147, 'can_edit')) {
                        $btn .= '
                                <a class="dropdown-item"
                                href="' . route('host-policy.form', $row->id) . '">
                                    <i class="fas fa-edit text-primary me-2"></i> Edit
                                </a>';
                    }

                    // Delete Permission
                    if (Helper::userCan(147, 'can_delete')) {
                        $btn .= '
                                <button class="dropdown-item text-danger delete"
                                        data-id="' . $row->id . '">
                                    <i class="fas fa-trash me-2"></i> Delete
                                </button>';
                    }

                    $btn .= '
                            </div>
                        </div>';

                    return $btn;
                })

                ->rawColumns([
                    'level',
                    'status',
                    'created_at',
                    'action'
                ])
                ->make(true);
        }

        return view('host_policies.index');
    }

    public function form($id = null)
    {
        $policy = null;

        if ($id) {
            $policy = HostPolicy::findOrFail($id);
        }

        $countries = Country::get();

        return view('host_policies.form', compact('policy', 'countries'));
    }

    public function save(Request $request, $id = null)
    {
        $rules = [

            'level' => ['required', 'integer', 'min:1', Rule::unique('host_policies', 'level')->ignore($id)],
            // 'time_hours' => 'required|numeric|min:0',
            'target_value' => 'required|numeric|min:0',
            'host_salary' => 'required|numeric|min:0',
            'agent_commission' => 'required|numeric|min:0',
            'total_salary' => 'required|numeric|min:0',
            'country' => 'required',
            'status' => 'required|in:0,1',
        ];

        $validator = Validator::make(
            $request->all(),
            $rules
        );

        if ($validator->fails()) {

            return back()
                ->withErrors($validator)
                ->withInput();
        }

        return DB::transaction(function () use ($request, $id) {

            $policy = $id
                ? HostPolicy::findOrFail($id)
                : new HostPolicy();

            $policy->fill([
                'level' => $request->level,
                'time_hours' => $request->time_hours,
                'target_value' => $request->target_value,
                'host_salary' => $request->host_salary,
                'agent_commission' => $request->agent_commission,
                'total_salary' => $request->total_salary,
                'country' => $request->country,
                'status' => $request->status,

            ])->save();

            return redirect()
                ->route('host-policy')
                ->with(
                    'success',
                    $id
                        ? 'Host Policy updated successfully'
                        : 'Host Policy added successfully'
                );
        });
    }

    public function delete(Request $request)
    {
        return Helper::deleteRecord(new HostPolicy, $request->id);
    }
}
