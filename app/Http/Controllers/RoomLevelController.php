<?php

namespace App\Http\Controllers;

use App\Models\AppUser;
use App\Models\Country;
use App\Helper\Helper;
use App\Models\RoomLevel;
use Carbon\Carbon;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RoomLevelController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {

            $query = RoomLevel::query();

            return DataTables::of($query)
                ->addIndexColumn()

                ->editColumn('status', function ($row) {

                    return $row->status
                        ? '<span class="badge bg-success">Active</span>'
                        : '<span class="badge bg-danger">Inactive</span>';
                })

                ->editColumn('created_at', function ($row) {

                    return Carbon::parse($row->created_at)
                        ->format('Y-m-d H:i:s');
                })

                ->addColumn('action', function ($row) {

                    $btn = '
                            <div class="dropdown">

                                <button class="btn btn-sm btn-link dropdown-toggle"
                                        data-bs-toggle="dropdown">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>

                                <div class="dropdown-menu">';

                    // Edit Permission
                    if (Helper::userCan(137, 'can_edit')) {
                        $btn .= '
                                <a class="dropdown-item"
                                href="' . route('room-levels.form', $row->id) . '">
                                    <i class="fas fa-edit text-primary me-2"></i>
                                    Edit
                                </a>';
                    }

                    // Delete Permission
                    if (Helper::userCan(137, 'can_delete')) {
                        $btn .= '
                                <button class="dropdown-item text-danger delete"
                                        data-id="' . $row->id . '">
                                    <i class="fas fa-trash me-2"></i>
                                    Delete
                                </button>';
                    }

                    $btn .= '
                            </div>

                        </div>';

                    return $btn;
                })

                ->rawColumns([
                    'status',
                    'action'
                ])

                ->make(true);
        }

        return view('room_levels.index');
    }

    public function form($id = null): View|RedirectResponse
    {
        $roomLevel = null;

        if ($id) {

            $roomLevel = RoomLevel::find($id);

            if (!$roomLevel) {
                return redirect()
                    ->route('room-levels')
                    ->with('error', 'Room Level not found');
            }
        }

        return view('room_levels.form', compact('roomLevel'));
    }


    public function save(Request $request, $id = null)
    {
        $rules = [
            'level'   => 'required|integer|min:1|unique:room_levels,level,' . $id,
            'xp'      => 'required|numeric|min:0',
            'admins'  => 'required|integer|min:0',
            'members' => 'required|integer|min:0',
            'status'  => 'required|in:0,1',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator)
                ->withInput();
        }

        return DB::transaction(function () use ($request, $id) {

            $roomLevel = $id
                ? RoomLevel::find($id)
                : new RoomLevel();

            if ($id && !$roomLevel) {
                return redirect()
                    ->back()
                    ->with('error', 'Room Level not found');
            }

            $roomLevel->fill([
                'level'   => $request->level,
                'xp'      => $request->xp,
                'admins'  => $request->admins,
                'members' => $request->members,
                'status'  => $request->status,
            ])->save();

            return redirect()
                ->route('room-levels')
                ->with(
                    'success',
                    $id
                        ? 'Room Level updated successfully.'
                        : 'Room Level added successfully.'
                );
        });
    }

    public function delete(Request $request): JsonResponse
    {
        return Helper::deleteRecord(new RoomLevel, $request->id);
    }
}
