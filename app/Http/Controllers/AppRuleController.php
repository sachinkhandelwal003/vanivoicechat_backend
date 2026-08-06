<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AppRule;
use App\Helper\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class AppRuleController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {

            $data = AppRule::query()->latest();

            return DataTables::of($data)
                ->addIndexColumn()

                ->addColumn('heading', function ($row) {
                    return $row->heading;
                })

                ->addColumn('type', function ($row) {
                    return '<span class="badge bg-primary">' . ucfirst(str_replace('_', ' ', $row->type)) . '</span>';
                })

                ->addColumn('rule', function ($row) {
                    return strip_tags(\Illuminate\Support\Str::limit($row->rule, 80));
                })

                ->addColumn('status', function ($row) {
                    return $row['status'] == 1 ? '<small class="badge fw-semi-bold rounded-pill status badge-light-success"> Enable</small>' : '<small class="badge fw-semi-bold rounded-pill status badge-light-danger"> Disable</small>';
                })

                ->addColumn('action', function ($row) {
                    $btn = '<div class="dropdown">
                    <button class="btn btn-sm btn-link dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-ellipsis-h"></i>
                    </button>
                    <div class="dropdown-menu">';

                    if (Helper::userCan(168, 'can_edit')) {
                        $btn .= '<a class="dropdown-item" href="' . route('app-rules.edit', $row->id) . '">Edit</a>';
                    }

                    if (Helper::userCan(168, 'can_delete')) {
                        $btn .= '<button class="dropdown-item text-danger delete" data-id="' . $row->id . '">Delete</button>';
                    }
                    if (Helper::userCan(168, 'can_view')) {
                        $btn .= '<a href="' . route('admin.rules.view', $row->id) . '" class="dropdown-item">
                        View
                    </a>';
                    }
                    $btn .= '</div></div>';

                    return $btn;
                })

                ->rawColumns(['type', 'status', 'action'])
                ->make(true);
        }

        return view('app_rules.index');
    }


    public function add()
    {
        return view('app_rules.add');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'heading' => 'required|string|max:255',
            'type'    => 'required|string|max:100',
            'rule'    => 'required|string',
            'status'  => 'nullable|in:0,1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors()
            ], 422);
        }

        AppRule::create([
            'heading'    => $request->heading,
            'type'       => strtolower($request->type),
            'rule'       => $request->rule,
            'status'     => $request->status ?? 1,
        ]);

        return redirect()
            ->route('app-rules.index')
            ->with('success', 'App Rule added successfully');
    }

    public function edit($id)
    {
        $rule = AppRule::find($id);

        if (!$rule) {
            return response()->json([
                'status'  => false,
                'message' => 'Rule not found'
            ], 404);
        }

        return view('app_rules.edit', compact('rule'));
    }

    public function update(Request $request, $id)
    {
        $rule = AppRule::find($id);

        if (!$rule) {
            return response()->json([
                'status'  => false,
                'message' => 'Rule not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'heading' => 'required|string|max:255',
            'type'    => 'required|string|max:100',
            'rule'    => 'required|string',
            'status'  => 'nullable|in:0,1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors()
            ], 422);
        }

        $rule->update([
            'heading'    => $request->heading,
            'type'       => strtolower($request->type),
            'rule'       => $request->rule,
            'status'     => $request->status ?? $rule->status,
            'updated_by' => Auth::id(),
        ]);

        return redirect()->route('app-rules.index')->with('success', 'App Rule updated successfully');
    }

    public function view($id)
    {
        $rule = AppRule::findOrFail($id);

        return view('app_rules.view', compact('rule'));
    }
}
