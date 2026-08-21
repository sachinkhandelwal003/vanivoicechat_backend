<?php

namespace App\Http\Controllers;

use App\Helper\Helper;
use App\Models\Game;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\RedirectResponse;

class GameController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }


    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {

            $query = Game::query()->latest();

            return DataTables::of($query)

                ->addIndexColumn()

                ->editColumn('icon', function ($row) {

                    if (!$row->icon) {
                        return '-';
                    }

                    $image = Helper::showImage($row->icon, true);

                    return '
                        <img src="' . $image . '"
                             width="45"
                             height="45"
                             class="image-preview"
                             data-image="' . $image . '"
                             style="cursor:pointer;border-radius:8px;object-fit:cover;">
                    ';
                })

                ->editColumn('name', function ($row) {

                    $html = '
                        <div>
                            <strong>' . e($row->name) . '</strong>
                    ';

                    if ($row->description) {
                        $html .= '
                            <br>
                            <small class="text-muted">
                                ' . e(\Illuminate\Support\Str::limit($row->description, 50)) . '
                            </small>
                        ';
                    }

                    $html .= '</div>';

                    return $html;
                })

                ->editColumn('entry_coins', function ($row) {

                    return '
                        <span>
                            <i class="fas fa-coins text-warning me-1"></i>
                            ' . number_format($row->entry_coins) . '
                        </span>
                    ';
                })

                ->editColumn('is_featured', function ($row) {

                    return $row->is_featured == 1

                        ? '<small class="badge fw-semi-bold rounded-pill badge-light-warning">
                                Featured
                           </small>'

                        : '<small class="badge fw-semi-bold rounded-pill badge-light-secondary">
                                Normal
                           </small>';
                })

                ->editColumn('status', function ($row) {

                    return $row->status == 1

                        ? '<small class="badge fw-semi-bold rounded-pill status badge-light-success">
                                Enable
                           </small>'

                        : '<small class="badge fw-semi-bold rounded-pill status badge-light-danger">
                                Disable
                           </small>';
                })

                ->editColumn('created_at', function ($row) {

                    return $row->created_at
                        ? $row->created_at->format('d M Y h:i A')
                        : '-';
                })


                ->addColumn('action', function ($row) {

                    $btn = '
                        <div class="dropdown">

                            <button
                                class="btn btn-sm btn-link dropdown-toggle"
                                data-bs-toggle="dropdown">

                                <i class="fas fa-ellipsis-h"></i>

                            </button>

                            <div class="dropdown-menu">
                    ';

                    if (Helper::userCan(142, 'can_edit')) {

                        $btn .= '
                            <a class="dropdown-item"
                               href="' . route('game.edit', $row->id) . '">

                                <i class="fas fa-edit me-2"></i>
                                Edit

                            </a>
                        ';
                    }

                    if (Helper::userCan(142, 'can_delete')) {

                        $btn .= '
                            <button
                                class="dropdown-item text-danger delete"
                                data-id="' . $row->id . '">

                                <i class="fas fa-trash me-2"></i>
                                Delete

                            </button>
                        ';
                    }


                    $btn .= '
                            </div>
                        </div>
                    ';

                    return $btn;
                })


                ->rawColumns([
                    'icon',
                    'name',
                    'entry_coins',
                    'is_featured',
                    'status',
                    'action'
                ])

                ->make(true);
        }
        return view('games.index');
    }

    public function add(): View
    {
        return view('games.add');
    }

    /*
|--------------------------------------------------------------------------
| Save Game
|--------------------------------------------------------------------------
*/

    public function save(Request $request): RedirectResponse
    {
        $rules = [
            'name'          => 'required|string|max:150',
            'slug'          => 'required|string|max:150|unique:games,slug',
            'sud_game_id'   => 'nullable|string|max:150',
            'sud_game_type' => 'nullable|string|max:100',
            'description'   => 'nullable|string',

            'icon'          => 'required|image|mimes:png,jpg,jpeg,webp',
            'banner'        => 'nullable|image|mimes:png,jpg,jpeg,webp',

            'entry_coins'   => 'required|integer|min:0',
            'min_coins'     => 'nullable|integer|min:0',
            'max_coins'     => 'nullable|integer|min:0',

            'sort_order'    => 'nullable|integer|min:0',
            'is_featured'   => 'required|in:0,1',
            'status'        => 'required|in:0,1',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        return DB::transaction(function () use ($request) {

            $icon = Helper::saveFile(
                $request->file('icon'),
                'game_icons'
            );

            $banner = null;

            if ($request->hasFile('banner')) {
                $banner = Helper::saveFile(
                    $request->file('banner'),
                    'game_banners'
                );
            }

            Game::create([
                'name'          => $request->name,
                'slug'          => $request->slug,
                'sud_game_id'   => $request->sud_game_id,
                'sud_game_type' => $request->sud_game_type,
                'description'   => $request->description,

                'icon'          => $icon,
                'banner'        => $banner,

                'entry_coins'   => $request->entry_coins,
                'min_coins'     => $request->min_coins ?? 0,
                'max_coins'     => $request->max_coins ?? 0,

                'sort_order'    => $request->sort_order ?? 0,
                'is_featured'   => $request->is_featured,
                'status'        => $request->status,
            ]);

            return redirect()
                ->route('game')
                ->with('success', 'Game added successfully');
        });
    }

    public function edit($id): View|RedirectResponse
    {
        $game = Game::find($id);

        if (!$game) {
            return to_route('game')
                ->withError('Game Not Found!');
        }

        return view('games.edit', compact('game'));
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $game = Game::findOrFail($id);

        $rules = [
            'name'          => 'required|string|max:150',
            'slug'          => 'required|string|max:150|unique:games,slug,' . $game->id,
            'sud_game_id'   => 'nullable|string|max:150',
            'sud_game_type' => 'nullable|string|max:100',
            'description'   => 'nullable|string',

            'icon'          => 'nullable|image|mimes:png,jpg,jpeg,webp',
            'banner'        => 'nullable|image|mimes:png,jpg,jpeg,webp',

            'entry_coins'   => 'required|integer|min:0',
            'min_coins'     => 'nullable|integer|min:0',
            'max_coins'     => 'nullable|integer|min:0',

            'sort_order'    => 'nullable|integer|min:0',
            'is_featured'   => 'required|in:0,1',
            'status'        => 'required|in:0,1',
        ];

        $request->validate($rules);

        return DB::transaction(function () use ($request, $game) {

            $data = [
                'name'          => $request->name,
                'slug'          => $request->slug,
                'sud_game_id'   => $request->sud_game_id,
                'sud_game_type' => $request->sud_game_type,
                'description'   => $request->description,

                'entry_coins'   => $request->entry_coins,
                'min_coins'     => $request->min_coins ?? 0,
                'max_coins'     => $request->max_coins ?? 0,

                'sort_order'    => $request->sort_order ?? 0,
                'is_featured'   => $request->is_featured,
                'status'        => $request->status,
            ];

            if ($request->hasFile('icon')) {

                if ($game->icon && file_exists(public_path($game->icon))) {
                    @unlink(public_path($game->icon));
                }

                $data['icon'] = Helper::saveFile(
                    $request->file('icon'),
                    'game_icons'
                );
            }

            if ($request->hasFile('banner')) {

                if ($game->banner && file_exists(public_path($game->banner))) {
                    @unlink(public_path($game->banner));
                }

                $data['banner'] = Helper::saveFile(
                    $request->file('banner'),
                    'game_banners'
                );
            }

            $game->update($data);

            return redirect()
                ->route('game')
                ->with('success', 'Game updated successfully');
        });
    }

       public function delete(Request $request): JsonResponse
    {
        return Helper::deleteRecord(new Game, $request->id);
    }
}
