<?php

namespace App\Http\Controllers;

use App\Models\Medal;
use App\Helper\Helper;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MedalController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {

            $query = Medal::latest();

            return DataTables::of($query)
                ->addIndexColumn()

                ->editColumn('icon', function ($row) {
                    return $row->icon
                        ? '<img src="' . asset('storage/' . $row->icon) . '" width="40">'
                        : '-';
                })

                ->editColumn('type', function ($row) {
                    return ucfirst($row->type); // achievement / event
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
                            <a class="dropdown-item" href="' . route('medals.form', $row->id) . '">
                                <i class="fas fa-edit text-primary"></i> Edit
                            </a>
                            <button class="dropdown-item text-danger delete" data-id="' . $row->id . '">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                    </div>';
                })

                ->rawColumns(['icon', 'action'])
                ->make(true);
        }

        return view('medals.index');
    }

    public function form($id = null): View|RedirectResponse
    {
        $medal = null;

        if ($id) {
            $medal = Medal::find($id);

            if (!$medal) {
                return redirect()->route('medals.index')->with('error', 'Medal not found');
            }
        }

        return view('medals.form', compact('medal'));
    }

    public function store(Request $request, $id = null)
    {
        $rules = [
            'title' => 'required|string|max:255',
            'type' => 'required|in:achievement,event',
            'icon' => 'nullable|image',
            'status' => 'nullable|boolean',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        return DB::transaction(function () use ($request, $id) {

            $medal = $id ? Medal::find($id) : new Medal();

            if ($id && !$medal) {
                return redirect()->back()->with('error', 'Medal not found');
            }

            $data = $request->only([
                'title',
                'type',
                'status'
            ]);

            if ($request->hasFile('icon')) {

                if ($id && $medal->icon && file_exists(public_path($medal->icon))) {
                    @unlink(public_path($medal->icon));
                }

                $data['icon'] = Helper::saveFile($request->file('icon'), 'medals');
            }

            $medal->fill($data)->save();

            return redirect()
                ->route('medals.index')
                ->with('success', $id ? 'Medal updated successfully' : 'Medal added successfully');
        });
    }

    public function delete(Request $request): JsonResponse
    {
        return Helper::deleteRecord(new Medal, $request->id);
    }
}
