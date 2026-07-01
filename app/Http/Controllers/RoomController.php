<?php

namespace App\Http\Controllers;

use App\Helper\Helper;
use App\Models\AppUser;
use App\Models\RoomMusicPlaylist;
use App\Models\Room;
use App\Models\RoomMember;
use App\Models\RoomUserRole;
use App\Models\Theme;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class RoomController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {

            $query = Room::with('user')->withCount([
                'members as members_count' => function ($q) {
                    $q->whereNull('left_at');
                }
            ])->latest();

            return DataTables::of($query)
                ->addIndexColumn()

                // ->addColumn('room_owner', function ($row) {
                //     if (!$row->user) return '-';

                //     $image = $row->user->image
                //         ? Helper::showImage($row->user->image, true)
                //         : asset('assets/img/avatar.png');

                //     return '
                //     <div class="d-flex align-items-center gap-2">
                //         <img src="' . $image . '" class="rounded-circle" width="40" height="40">
                //         <div>
                //             <div class="fw-bold">' . e($row->user->name) . '</div>
                //             <small class="text-muted">' . e($row->user->uid) . '</small>
                //         </div>
                //     </div>
                //  ';
                // })
                ->addColumn('room_owner', function ($row) {

                    if (!$row->user) {return '-';}

                    $image = $row->user->image
                        ? Helper::showImage($row->user->image, true)
                        : asset('assets/img/avatar.png');

                    return '
                        <div class="d-flex align-items-center gap-2 user-profile-trigger"
                             data-user-id="'.$row->user->id.'"
                             style="cursor:pointer;">

                            <img src="'.$image.'"
                                 class="rounded-circle"
                                 width="40"
                                 height="40">

                            <div>
                                <div class="fw-bold">'.e($row->user->name).'</div>
                                <small class="text-muted">'.e($row->user->uid).'</small>
                            </div>

                        </div>
                    ';
                })

                ->addColumn('room_info', function ($row) {
                    return '
                    <div>
                        <div class="fw-bold">' . e($row->room_name ?? 'Room') . '</div>
                        <small class="text-muted">' . e($row->room_id) . '</small>
                    </div>
                ';
                })

                ->editColumn('room_number', function ($row) {
                    return '<span class="badge rounded-pill bg-secondary">' . ($row->room_seat ?? '-') . '</span>';
                })

                ->addColumn('room_members', function ($row) {
                    return '<span class="badge rounded-pill bg-info">' . $row->members_count . '</span>';
                })

                ->editColumn('status', function ($row) {
                    return $row->status == 1
                        ? '<span class="badge rounded-pill bg-success">Enable</span>'
                        : '<span class="badge rounded-pill bg-danger">Disable</span>';
                })

                ->addColumn('time', function ($row) {
                    return '
                        <div>
                            <div class="text-muted small">Creation time:</div>
                            <div>' . Carbon::parse($row->created_at)->format('Y-m-d') . '</div>
                            <div>' . Carbon::parse($row->created_at)->format('H:i:s') . '</div>
                        </div>
                    ';
                })

                ->addColumn('action', function ($row) {

                    $btn = '<div class="dropdown">
                    <button class="btn btn-sm btn-link dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-ellipsis-h"></i>
                    </button>
                    <div class="dropdown-menu">';

                    $btn .= '<a class="dropdown-item" href="' . route('room.view', $row->id) . '">
                    <i class="fas fa-eye text-info me-2"></i> View
                    </a>';

                    $btn .= '<a class="dropdown-item" href="' . route('room.members', $row->id) . '">
                        <i class="fas fa-users text-success me-2"></i> Members
                    </a>';

                    if (Helper::userCan(105, 'can_delete')) {
                        $btn .= '<button class="dropdown-item text-danger delete" data-id="' . $row->id . '">
                        <i class="fas fa-trash me-2"></i> Delete
                        </button>';
                    }

                    $btn .= '</div></div>';

                    return $btn;
                })

                ->rawColumns(['room_owner', 'room_number', 'room_members', 'room_info', 'time', 'status', 'action'])
                ->make(true);
        }

        return view('room.index');
    }

    public function delete(Request $request): JsonResponse
    {
        return Helper::deleteRecord(new Frame, $request->id);
    }




    public function members($room_id)
    {
        $room = Room::findOrFail($room_id);

        $members = RoomMember::with('user')
            ->where('room_id', $room_id)
            ->whereNull('left_at')
            ->latest()
            ->get();

        foreach ($members as $member) {

            if ($member->user_id == $room->user_id) {
                $member->role = 'Owner';
            } else {
                $role = \App\Models\RoomUserRole::where('room_id', $room_id)
                    ->where('user_id', $member->user_id)
                    ->value('role');

                $member->role = $role ? ucfirst($role) : 'Member';
            }
        }

        return view('room.members', compact('room', 'members'));
    }

    public function membersAjax(Request $request, $room_id)
    {
        $room = Room::findOrFail($room_id);

        $query = RoomMember::with('user')
            ->where('room_id', $room_id)
            ->whereNull('left_at');

        if ($request->search) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('uid', 'like', '%' . $request->search . '%');
            });
        }

        $members = $query->paginate(10);

        $data = [];

        foreach ($members as $member) {

            // Role logic
            if ($member->user_id == $room->user_id) {
                $role = 'Owner';
            } else {
                $roleData = RoomUserRole::where('room_id', $room_id)
                    ->where('user_id', $member->user_id)
                    ->value('role');

                $role = $roleData ? ucfirst($roleData) : 'Member';
            }

            $roleClass = $role == 'Owner' ? 'badge bg-danger' : ($role == 'Admin' ? 'badge bg-warning text-dark' : 'badge bg-secondary');

            $data[] = [
                'name' => $member->user->name,
                'uid' => $member->user->uid,
                'image' => $member->user->image
                    ? Helper::showImage($member->user->image, true)
                    : asset('assets/img/avatar.png'),
                'role' => $role,
                'role_class' => $roleClass,
                'joined_at' => Carbon::parse($member->joined_at)->format('d M Y, h:i A'),
            ];
        }

        return response()->json([
            'data' => $data,
            'pagination' => $members->links()->render(),
            'total' => $members->total(),
            'from' => $members->firstItem(),
            'to' => $members->lastItem(),
        ]);
    }

    public function view($id)
    {
        $room = Room::with('user')->findOrFail($id);

        return view('room.view', compact('room'));
    }


    public function userMusics(Request $request)
    {
        if ($request->ajax()) {
    
            $query = AppUser::with('musics')->has('musics')->latest();
    
            return DataTables::of($query)

                ->addColumn('user', function ($row) {

                    $image = $row->image
                        ? Helper::showImage($row->image, true)
                        : asset('assets/img/avatar.png');

                    return '
                        <div class="user-box user-profile-trigger"
                             data-user-id="'.$row->id.'"
                             style="cursor:pointer;">

                            <img src="'.$image.'">

                            <div class="user-info">
                                <div class="name">'.$row->name.'</div>
                                <div class="uid">'.$row->uid.'</div>
                            </div>

                        </div>
                    ';
                })
    
                ->addColumn('total_music', function ($row) {
                    return '
                        <span class="music-badge">
                            🎵 '.$row->musics->count().' Songs
                        </span>
                    ';
                })
    
                ->addColumn('musics', function ($row) {

                    return '
                        <button class="btn-playlist view-musics"
                                data-user="'.$row->id.'">
                            <i class="fas fa-music me-1"></i>
                            View Playlist
                        </button>
                    ';
                })
    
                ->rawColumns(['user', 'total_music', 'musics'])

                ->filterColumn('user', function ($query, $keyword) {
                    $query->where(function ($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%")->orWhere('uid', 'like', "%{$keyword}%");
                    });
                })

                ->make(true);
        }
    
        return view('room.user_music');
    }

    public function getUserMusicList($id)
    {
        $musics = RoomMusicPlaylist::where('user_id', $id)
            ->select('title', 'artist', 'audio_url')
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'data' => $musics
        ]);
    }

    public function userThemes(Request $request)
    {
        if ($request->ajax()) {
    
            $query = Room::with(['user'])->whereNotNull('active_theme_id')->latest();
    
            return DataTables::of($query)
    
                ->addColumn('user', function ($row) {

                    if (!$row->user) {return '-';}
                
                    $image = $row->user->image
                        ? Helper::showImage($row->user->image, true)
                        : asset('assets/img/avatar.png');
                
                    return '
                        <div class="d-flex align-items-center user-profile-trigger"
                             data-user-id="'.$row->user->id.'"
                             style="cursor:pointer;">
                
                            <img src="'.$image.'"
                                 width="50"
                                 height="50"
                                 class="rounded-circle me-2">
                
                            <div>
                                <div class="fw-bold">'.$row->user->name.'</div>
                                <small>'.$row->user->uid.'</small>
                            </div>
                
                        </div>
                    ';
                })
    
                ->addColumn('theme', function ($row) {

                    if (!$row->active_theme_id) {return '-';}
                
                    $theme = Theme::find($row->active_theme_id);
                
                    if (!$theme) {return '-';}
                
                    return '
                        <div class="d-flex align-items-center">
                            <img src="'.Helper::showImage($theme->icon, true).'"
                                 width="60"
                                 height="60"
                                 class="rounded me-2">
                
                            <div>
                                <div class="fw-bold">'.$theme->name.'</div>
                            </div>
                        </div>
                    ';
                })
    
                ->addColumn('room', function ($row) {

                    return '
                        <div>
                            <div class="fw-bold">'.$row->room_name.'</div>
                            <small>'.$row->room_id.'</small>
                        </div>
                    ';
                })
    
                ->filterColumn('user', function ($query, $keyword) {
    
                    $query->where(function ($q) use ($keyword) {
    
                        $q->where('name', 'like', "%{$keyword}%")
                          ->orWhere('uid', 'like', "%{$keyword}%");
    
                    });
                })

                ->addColumn('activated_at', function ($row) {
                    return Carbon::parse($row->updated_at)->timezone('Asia/Kolkata')->format('d M Y h:i A');
                })
    
                ->rawColumns(['user','theme','room','activated_at'])
                ->make(true);
        }
    
        return view('room.user_theme');
    }
}
