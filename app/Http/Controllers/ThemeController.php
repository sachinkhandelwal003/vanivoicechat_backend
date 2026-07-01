<?php

namespace App\Http\Controllers;

use App\Helper\Helper;
use App\Models\Theme;
use App\Models\ThemeGiven;
use App\Models\AppUser;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ThemeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {

            $query = Theme::latest();

            return DataTables::of($query)
                ->addIndexColumn()
                ->editColumn('icon', function ($row) {

                    $image = asset('storage/' . $row->icon);

                    return '
                        <img src="'.$image.'"
                             width="40"
                             height="40"
                             class="image-preview"
                             data-image="'.$image.'"
                             style="cursor:pointer;border-radius:6px;object-fit:cover;">
                    ';
                })
                
                ->editColumn('status', function ($row) {
                    return $row['status'] == 1 ? '<small class="badge fw-semi-bold rounded-pill status badge-light-success"> Enable</small>' : '<small class="badge fw-semi-bold rounded-pill status badge-light-danger"> Disable</small>';
                })
                ->editColumn('validity', function ($row) {
                    return $row['validity'] ? $row['validity'] : '-';
                })
                ->addColumn('action', function ($row) {
                    $btn = '<div class="dropdown">
                    <button class="btn btn-sm btn-link dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-ellipsis-h"></i>
                    </button>
                    <div class="dropdown-menu">';

                    if (Helper::userCan(104, 'can_edit')) {
                        $btn .= '<a class="dropdown-item" href="' . route('theme.edit', $row->id) . '">Edit</a>';
                    }

                    // if (Helper::userCan(104, 'can_edit')) {
                    //     $btn .= '<a class="dropdown-item" href="' . route('theme.give', $row->id) . '">Give</a>';
                    // }

                    if (Helper::userCan(105, 'can_delete')) {
                        $btn .= '<button class="dropdown-item text-danger delete" data-id="' . $row->id . '">Delete</button>';
                    }

                    $btn .= '</div></div>';

                    return $btn;
                })
                ->rawColumns(['icon', 'status', 'action'])
                ->make(true);
        }

        return view('theme.index');
    }

    public function add(): View
    {
        return view('theme.add');
    }


    public function save(Request $request)
    {
        $rules = [
            'name'            => 'required|string|max:255',
            'visibility_type' => 'required|in:backend,in_app',
            'icon'            => 'required|image|mimes:png,jpg,jpeg,webp,gif',
            'status'          => 'required|in:0,1',
        ];

        if ($request->visibility_type == 'in_app') {

            $rules['needcoin']   = 'required|array|min:1';
            $rules['needcoin.*'] = 'required|integer|min:1|max:9999999';

            $rules['validity']   = 'required|array|min:1';
            $rules['validity.*'] = 'required|integer|min:1';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        return DB::transaction(function () use ($request) {

            $icon = Helper::saveFile($request->file('icon'), 'theme_images');

            Theme::create([
                'name'            => $request->name,
                'visibility_type' => $request->visibility_type,
                'status'          => $request->status,
                'icon'            => $icon,
                'needcoin' => $request->visibility_type === 'in_app'
                    ? array_values($request->needcoin)
                    : null,

                'validity' => $request->visibility_type === 'in_app'
                    ? array_values($request->validity)
                    : null,

            ]);

            return redirect()
                ->route('theme')
                ->with('success', 'Theme added successfully');
        });
    }

    public function edit($id): View|RedirectResponse
    {
        $theme = Theme::find($id);

        if (!$theme) {
            return to_route('theme')->withError('Theme Not Found!');
        }
        return view('theme.edit', compact('theme'));
    }

    public function update(Request $request, $id)
    {
        $theme = Theme::findOrFail($id);

        $rules = [
            'name'            => 'required|string|max:255',
            'visibility_type' => 'required|in:backend,in_app',
            'icon'            => 'nullable|image|mimes:png,jpg,jpeg,webp,gif',
            'status'          => 'required|in:0,1',
        ];

        if ($request->visibility_type == 'in_app') {

            $rules['needcoin']   = 'required|array|min:1';
            $rules['needcoin.*'] = 'required|integer|min:1|max:9999999';

            $rules['validity']   = 'required|array|min:1';
            $rules['validity.*'] = 'required|integer|min:1';
        }

        $validated = $request->validate($rules);

        return DB::transaction(function () use ($request, $theme) {

            $data = [
                'name'            => $request->name,
                'visibility_type' => $request->visibility_type,
                'status'          => $request->status,
            ];

            if ($request->visibility_type === 'in_app') {
                $data['needcoin'] = array_values($request->needcoin);
                $data['validity'] = array_values($request->validity);
            } else {
                $data['needcoin'] = null;
                $data['validity'] = null;
            }

            if ($request->hasFile('icon')) {
                if ($theme->icon && file_exists(public_path($theme->icon))) {
                    @unlink(public_path($theme->icon));
                }

                $data['icon'] = Helper::saveFile($request->file('icon'), 'theme_images');
            }

            $theme->update($data);

            return redirect()
                ->route('theme')
                ->with('success', 'Theme updated successfully');
        });
    }



    public function delete(Request $request): JsonResponse
    {
        return Helper::deleteRecord(new Theme, $request->id);
    }

    public function give($theme_id): View
    {
        return view('theme.theme_given', compact('theme_id'));
    }

    public function giveSave(Request $request)
    {
        $request->validate([
            'theme_id' => 'required|exists:themes,id',
            'user_id'  => 'required|exists:app_users,uid',
            'duration' => 'required|integer|min:1',
        ]);

        try {

            $theme = Theme::where('id', $request->theme_id)
                ->where('status', 1)
                ->where('visibility_type', 'in_app')
                ->firstOrFail();

            $daysRequested = (int) $request->duration;

            $validities = array_map('intval', $theme->validity);
            $prices     = array_map('intval', $theme->needcoin);

            $index = array_search($daysRequested, $validities, true);

            if ($index === false || !isset($prices[$index])) {
                return back()->with('error', 'Invalid duration selected');
            }

            $needCoin = $prices[$index];

            DB::transaction(function () use ($request, $theme, $needCoin, $daysRequested) {

                $user = AppUser::where('uid', $request->user_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $already = ThemeGiven::where('theme_id', $theme->id)
                    ->where('user_id', $user->id)
                    ->where('end_at', '>', now())
                    ->exists();

                if ($already) {
                    throw new \Exception('Theme already active');
                }

                if ($user->total_points < $needCoin) {
                    throw new \Exception('Insufficient balance');
                }

                $user->decrement('total_points', $needCoin);

                ThemeGiven::create([
                    'theme_id' => $theme->id,
                    'user_id'  => $user->id,
                    'source'   => 'admin',
                    'start_at' => now(),
                    'end_at'   => now()->addDays($daysRequested),
                    'duration' => $daysRequested,
                ]);
            });
        } catch (\Exception $e) {

            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('theme')
            ->with('success', 'Theme assigned and coins deducted successfully');
    }
}
