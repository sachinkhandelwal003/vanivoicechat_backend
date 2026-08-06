<?php

namespace App\Http\Controllers;

use App\Models\Level;
use App\Models\WcLevelSetting;
use App\Helper\Helper;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class WCLevelController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {

            $query = Level::query();

            if ($request->type) {
                $query->where('type', $request->type);
            }

            $query->latest();

            return DataTables::of($query)
                ->addIndexColumn()

                ->editColumn('icon', function ($row) {

                    if (!$row->icon) {
                        return '-';
                    }

                    $image = asset('storage/' . $row->icon);

                    return '
                        <img src="' . $image . '" width="40" class="image-preview" data-image="' . $image . '"
                             style="cursor:pointer;border-radius:6px;object-fit:cover;">
                    ';
                })

                ->editColumn('entry_effect', function ($row) {
                    return $row->entry_effect
                        ? '<img src="' . asset('storage/' . $row->entry_effect) . '" width="60">'
                        : '-';
                })

                ->editColumn('created_at', function ($row) {
                    return $row->created_at
                        ? $row->created_at->timezone('Asia/Kolkata')->format('d M Y, h:i A')
                        : '-';
                })

                ->editColumn('type', function ($row) {
                    return ucfirst($row->type);
                })

                ->addColumn('action', function ($row) {

                    if (!Helper::userCan(154, 'can_edit') && !Helper::userCan(154, 'can_delete')) {
                        return '-';
                    }

                    $btn = '
                            <div class="dropup text-center">
                                <button class="btn btn-sm btn-light rounded-pill px-3" data-bs-toggle="dropdown">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>

                                <div class="dropdown-menu dropdown-menu-end p-2">';

                    // Edit Permission
                    if (Helper::userCan(154, 'can_edit')) {
                        $btn .= '
                                <a class="dropdown-item"
                                href="' . route('levels.form', $row->id) . '">
                                    <i class="fas fa-edit text-primary me-2"></i> Edit
                                </a>';
                    }

                    // Delete Permission
                    if (Helper::userCan(154, 'can_delete')) {
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

                ->rawColumns(['icon', 'entry_effect', 'action'])
                ->make(true);
        }

        return view('wealth_charm.index');
    }

    public function form($id = null)
    {
        $level = $id ? Level::find($id) : null;

        return view('wealth_charm.form', compact('level'));
    }

    public function save(Request $request, $id = null)
    {
        $rules = [
            'type' => 'required|in:wealth,charm',
            'level' => 'required',
            'required_exp' => 'required|integer',

            'icon' => 'nullable|image',
            'entry_effect' => 'nullable|image',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        return DB::transaction(function () use ($request, $id) {

            $level = $id ? Level::find($id) : new Level();

            if ($id && !$level) {
                return back()->with('error', 'Level not found');
            }

            $data = $request->only([
                'type',
                'level',
                'required_exp'
            ]);

            foreach (['icon', 'entry_effect'] as $file) {

                if ($request->hasFile($file)) {

                    if ($id && $level->$file && file_exists(public_path($level->$file))) {
                        @unlink(public_path($level->$file));
                    }

                    $data[$file] = Helper::saveFile($request->file($file), 'levels');
                }
            }

            $level->fill($data)->save();

            return redirect()
                ->route('levels')
                ->with('success', $id ? 'Level updated successfully' : 'Level added successfully');
        });
    }

    public function delete(Request $request)
    {
        return Helper::deleteRecord(new Level, $request->id);
    }




    public function settingForm()
    {
        $wealth = WcLevelSetting::where('type', 'wealth')->first();
        $charm  = WcLevelSetting::where('type', 'charm')->first();

        return view('wealth_charm.level_setting', compact('wealth', 'charm'));
    }

    public function settingSave(Request $request)
    {
        $request->validate([
            'wealth_description' => 'required',
            'charm_description' => 'required',
        ]);

        WcLevelSetting::updateOrCreate(
            ['type' => 'wealth'],
            ['description' => $request->wealth_description]
        );

        WcLevelSetting::updateOrCreate(
            ['type' => 'charm'],
            ['description' => $request->charm_description]
        );

        return redirect()->back()->with('success', 'Settings saved successfully');
    }
}
