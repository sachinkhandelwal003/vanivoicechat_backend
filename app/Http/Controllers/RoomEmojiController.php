<?php

namespace App\Http\Controllers;

use App\Models\RoomEmoji;
use App\Models\AppUser;
use App\Models\Country;
use App\Helper\Helper;
use Carbon\Carbon;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RoomEmojiController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {

            $query = RoomEmoji::latest();

            return DataTables::of($query)
                ->addIndexColumn()

                ->addColumn('file', function ($row) {

                    if (!$row->file) {
                        return '-';
                    }

                    $extension = strtolower(pathinfo($row->file, PATHINFO_EXTENSION));

                    if (in_array($extension, ['png', 'jpg', 'jpeg', 'gif', 'webp'])) {

                        return '
                        <img src="' . Helper::showImage($row->file, true) . '"
                            style="width:50px;height:50px;object-fit:contain;"
                            class="img-thumbnail">
                    ';
                    }

                    return '
                    <a href="' . Helper::showImage($row->file, true) . '"
                        target="_blank"
                        class="btn btn-sm btn-outline-primary">
                        View File
                    </a>
                ';
                })

                ->editColumn('status', function ($row) {

                    return $row->status
                        ? '<span class="badge bg-success">Active</span>'
                        : '<span class="badge bg-danger">Inactive</span>';
                })

                ->editColumn('created_at', function ($row) {

                    return Carbon::parse($row->created_at)->format('Y-m-d H:i:s');
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
                    if (Helper::userCan(136, 'can_edit')) {
                        $btn .= '
                                <a class="dropdown-item"
                                href="' . route('room-emojis.form', $row->id) . '">
                                    <i class="fas fa-edit text-primary me-2"></i> Edit
                                </a>';
                    }

                    // Delete Permission
                    if (Helper::userCan(136, 'can_delete')) {
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
                    'file',
                    'status',
                    'action'
                ])

                ->make(true);
        }

        return view('room_emojis.index');
    }

    public function form($id = null): View|RedirectResponse
    {
        $emoji = null;

        if ($id) {

            $emoji = RoomEmoji::find($id);

            if (!$emoji) {
                return redirect()
                    ->route('room-emojis')
                    ->with('error', 'Room Emoji not found');
            }
        }

        return view('room_emojis.form', compact('emoji'));
    }


    public function save(Request $request, $id = null)
    {
        $rules = [
            'title'  => 'required|string|max:255',
            'type'   => 'required|string|max:50',
            'file'   => $id ? 'nullable|file|mimes:gif' : 'required|file|mimes:gif',
            'status' => 'required|in:0,1',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator)
                ->withInput();
        }

        return DB::transaction(function () use ($request, $id) {

            $emoji = $id ? RoomEmoji::find($id) : new RoomEmoji();

            if ($id && !$emoji) {
                return redirect()
                    ->back()
                    ->with('error', 'Room Emoji not found');
            }

            $data = [
                'title'  => $request->title,
                'type'   => $request->type,
                'status' => $request->status,
            ];

            if ($request->hasFile('file')) {
                $data['file'] = Helper::saveFile($request->file('file'), 'room_emojis');
            }

            $emoji->fill($data)->save();

            return redirect()
                ->route('room-emojis')
                ->with(
                    'success',
                    $id
                        ? 'Room Emoji updated successfully'
                        : 'Room Emoji added successfully'
                );
        });
    }

    public function delete(Request $request): JsonResponse
    {
        return Helper::deleteRecord(new RoomEmoji, $request->id);
    }
}
