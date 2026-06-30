<?php

namespace App\Http\Controllers;

use App\Helper\Helper;
use App\Models\UserLevel;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class UserLevelController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {

            $query = UserLevel::get();

            return DataTables::of($query)
                ->addIndexColumn()
                ->editColumn('nickname_color', function ($row) {
                    return '<span style="
                        background:' . $row->nickname_color . ';
                        padding: 5px 15px;
                        border-radius: 6px;
                        color: #fff;
                        display: inline-block;
                        min-width: 60px;
                        min-height: 20px;
                        text-align:center;">
                        
                    </span>';
                })
                ->editColumn('icon', function ($row) {
                    return '<img src="' . asset('storage/' . $row->icon) . '" width="40">';
                })
                ->editColumn('avatar_corner', function ($row) {
                    return '<img src="' . asset('storage/' . $row->avatar_corner) . '" width="40">';
                })
                ->editColumn('background_image', function ($row) {
                    return '<img src="' . asset('storage/' . $row->background_image) . '" width="40">';
                })
                ->editColumn('created_at', function ($row) {
                    return $row->created_at ? $row->created_at->format('Y-m-d H:i') : '';
                })

                ->addColumn('action', function ($row) {
                    $btn = '<div class="dropdown">
                    <button class="btn btn-sm btn-link dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-ellipsis-h"></i>
                    </button>
                    <div class="dropdown-menu">';

                    if (Helper::userCan(104, 'can_edit')) {
                        $btn .= '<a class="dropdown-item" href="' . route('user.level.edit', $row->id) . '">Edit</a>';
                    }

                    if (Helper::userCan(105, 'can_delete')) {
                        $btn .= '<button class="dropdown-item text-danger delete" data-id="' . $row->id . '">Delete</button>';
                    }

                    $btn .= '</div></div>';

                    return $btn;
                })
                ->rawColumns(['nickname_color', 'icon', 'avatar_corner', 'background_image', 'created_at', 'action'])
                ->make(true);
        }

        return view('user_level.index');
    }

    public function add(): View
    {
        return view('user_level.add');
    }


    public function save(Request $request)
    {
        $rules = [
            'grade'            => 'required|integer|min:1',
            'name'             => 'required|string|max:255',
            'experience_cap'   => 'required|integer|min:1',
            'nickname_color'   => 'required|string|max:50',

            'icon'             => 'required|image|mimes:png,jpg,jpeg,webp|max:2048',
            'avatar_corner'    => 'required|image|mimes:png,jpg,jpeg,webp|max:2048',
            // 'background_image' => 'required|image|mimes:png,jpg,jpeg,webp|max:4096',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        return DB::transaction(function () use ($request) {

            if ($request->hasFile('icon')) {
                $icon = Helper::saveFile($request->file('icon'), 'user_levels_images');
            }

            if ($request->hasFile('avatar_corner')) {
                $avatar_corner = Helper::saveFile($request->file('avatar_corner'), 'user_levels_images');
            }

            // if ($request->hasFile('background_image')) {
            //     $background_image = Helper::saveFile($request->file('background_image'), 'user_levels_images');
            // }

            UserLevel::create([
                'grade'            => $request->grade,
                'name'             => $request->name,
                'experience_cap'   => $request->experience_cap,
                'nickname_color'   => $request->nickname_color,
                'icon'             => $icon,
                'avatar_corner'    => $avatar_corner,
                // 'background_image' => $background_image
            ]);

            return redirect()
                ->route('user.level')
                ->with('success', 'User Level added successfully');
        });
    }

    public function edit($id): View|RedirectResponse
    {
        $userLevel = UserLevel::find($id);

        if (!$userLevel) {
            return to_route('user.level')->withError('User Level Not Found!');
        }
        return view('user_level.edit', compact('userLevel'));
    }

    public function update(Request $request, $id)
    {
        $userLevel = UserLevel::findOrFail($id);

        $rules = [
            'grade'            => 'required|integer|min:1',
            'name'             => 'required|string|max:255',
            'experience_cap'   => 'required|integer|min:1',
            'nickname_color'   => 'required|string|max:50',

            'icon'             => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
            'avatar_corner'    => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
            // 'background_image' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:4096',
        ];

        $request->validate($rules);

        return DB::transaction(function () use ($request, $userLevel) {

            $userLevel->grade = $request->grade;
            $userLevel->name = $request->name;
            $userLevel->experience_cap = $request->experience_cap;
            $userLevel->nickname_color = $request->nickname_color;

            if ($request->hasFile('icon')) {
                if ($userLevel->icon) {
                    Helper::deleteFile($userLevel->icon);
                }

                $userLevel->icon = Helper::saveFile($request->file('icon'), 'user_levels_images');
            }

            if ($request->hasFile('avatar_corner')) {
                if ($userLevel->avatar_corner) {
                    Helper::deleteFile($userLevel->avatar_corner);
                }

                $userLevel->avatar_corner = Helper::saveFile($request->file('avatar_corner'), 'user_levels_images');
            }

            // if ($request->hasFile('background_image')) {
            //     if ($userLevel->background_image) {
            //         Helper::deleteFile($userLevel->background_image);
            //     }

            //     $userLevel->background_image = Helper::saveFile($request->file('background_image'), 'user_levels_images');
            // }

            $userLevel->save();

            return redirect()
                ->route('user.level')
                ->with('success', 'User Level updated successfully');
        });
    }



    public function delete(Request $request): JsonResponse
    {
        return Helper::deleteRecord(new UserLevel, $request->id);
    }
}
