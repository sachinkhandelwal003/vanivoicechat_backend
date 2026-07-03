<?php

namespace App\Http\Controllers;

use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\FamilyRank;
use App\Models\FamilyRankLevel;
use App\Models\FamilyRankBenefit;
use App\Helper\Helper;
use App\Models\Room;
use App\Models\LuckyGiftWinningSetting;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class FamilyController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {

            $query = Family::with('user')->latest();

            return DataTables::of($query)
                ->addIndexColumn()

                ->addColumn('user_info', function ($row) {

                    if (!$row->user) {return '-';}

                    $image = $row->user->image
                        ? Helper::showImage($row->user->image, true)
                        : asset('assets/img/default-vani.png');

                    return '
                        <div class="d-flex align-items-center gap-2 user-profile-trigger" data-user-id="'.$row->user->id.'" style="cursor:pointer;">

                            <img src="'.$image.'" class="rounded-circle" width="40" height="40">

                            <div>
                                <div class="fw-bold">'.e($row->user->name).'</div>
                                <small class="text-muted">ID: '.e($row->user->uid).'</small>
                            </div>

                        </div>
                    ';
                })

                ->addColumn('family_info', function ($row) {
                    return '
                    <div>
                        <div class="fw-bold">' . e($row->name ?? 'Room') . '</div>
                        <small class="text-muted"> ID: ' . e($row->id) . '</small>
                    </div>
                ';
                })

                ->editColumn('family_member', function ($row) {
                    $totalMember = FamilyMember::where('family_id', $row->id)->count();
                    return $totalMember;
                })
                ->editColumn('family_rank', function ($row) {
                    return '-';
                })
                ->editColumn('total_points', function ($row) {
                    return $row->total_points ?? 0;
                })

                ->addColumn('members', function ($row) {

                    $totalMember = FamilyMember::where('family_id', $row->id)->count();

                    $url = route('family.members', $row->id); // route for member list page

                    return '<a href="' . $url . '" class="badge bg-secondary text-decoration-none">
                        Members
                        </a>';
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
                ->editColumn('status', function ($row) {
                    return $row['status'] == 1 ? '<small class="badge fw-semi-bold rounded-pill status badge-light-success"> Enable</small>' : '<small class="badge fw-semi-bold rounded-pill status badge-light-danger"> Disable</small>';
                })

                ->addColumn('action', function ($row) {
                    $btn = '<div class="dropdown">
                    <button class="btn btn-sm btn-link dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-ellipsis-h"></i>
                    </button>
                    <div class="dropdown-menu">';

                    if (Helper::userCan(104, 'can_edit')) {
                        if ($row->status == 1) {
                            $btn .= '<a class="dropdown-item" href="' . route('family.toggleStatus', $row->id) . '">Disable</a>';
                        } else {
                            $btn .= '<a class="dropdown-item text-success" href="' . route('family.toggleStatus', $row->id) . '">Enable</a>';
                        }
                    }

                    if (Helper::userCan(105, 'can_delete')) {
                        $btn .= '<button class="dropdown-item text-danger delete" data-id="' . $row->id . '">Delete</button>';
                    }

                    $btn .= '</div></div>';

                    return $btn;
                })

                ->rawColumns(['user_info', 'family_info', 'family_member', 'total_points', 'members', 'time','status', 'action'])
                ->make(true);
        }

        return view('family.index');
    }

    public function toggleStatus($id)
    {
        $family = Family::findOrFail($id);

        $family->status = $family->status == 1 ? 0 : 1;
        $family->save();

        return redirect()->back()->with(
            'success',
            $family->status == 1 ? 'Family enabled successfully.' : 'Family disabled successfully.'
        );
    }

    public function familyMember(Request $request, $id): View|JsonResponse
    {
        if ($request->ajax()) {

            $query = FamilyMember::with(['user', 'family'])
                ->where('family_id', $id)
                ->latest();

            return DataTables::of($query)
                ->addIndexColumn()

                ->addColumn('member', function ($row) {

                    if (!$row->user) {return '-';}
                
                    $image = $row->user->image
                        ? Helper::showImage($row->user->image, true)
                        : asset('assets/img/default-vani.png');
                
                    return '
                        <div class="d-flex align-items-center gap-2 user-profile-trigger" data-user-id="'.$row->user->id.'" style="cursor:pointer;">
                
                            <img src="'.$image.'" class="rounded-circle" width="40" height="40">
                
                            <div>
                                <div class="fw-bold">'.e($row->user->name).'</div>
                                <small class="text-muted">ID: '.e($row->user->uid).'</small>
                            </div>
                
                        </div>
                    ';
                })

                ->addColumn('family', function ($row) {

                    if (!$row->family) return '-';

                    return '
                <div>
                    <div class="fw-bold">' . e($row->family->name ?? 'Room') . '</div>
                    <small class="text-muted"> ID: ' . e($row->family->id) . '</small>
                </div>';
                })

                ->addColumn('joined', function ($row) {

                    return '
                <div>
                    <div>' . \Carbon\Carbon::parse($row->created_at)->format('Y-m-d') . '</div>
                    <small class="text-muted">' . \Carbon\Carbon::parse($row->created_at)->format('H:i:s') . '</small>
                </div>';
                })

                ->addColumn('operate', function ($row) {
                    $btn = '<div class="dropdown">
                    <button class="btn btn-sm btn-link dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-ellipsis-h"></i>
                    </button>
                    <div class="dropdown-menu">';

                    // if (Helper::userCan(104, 'can_edit')) {
                    //     $btn .= '<a class="dropdown-item" href="' . route('family.edit', $row->id) . '">Edit</a>';
                    // }

                    if (Helper::userCan(105, 'can_delete')) {
                        $btn .= '<button class="dropdown-item text-danger delete" data-id="' . $row->id . '">Delete</button>';
                    }

                    $btn .= '</div></div>';

                    return $btn;
                })

                ->rawColumns(['member', 'family', 'joined', 'operate'])
                ->make(true);
        }

        $family = Family::findOrFail($id);

        return view('family.family_members', compact('family'));
    }

    public function familyMemberRemove(Request $request): JsonResponse
    {
        return Helper::deleteRecord(new FamilyMember, $request->id);
    }

    public function rank(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {

            $query = FamilyRank::latest();

            return DataTables::of($query)
                ->addIndexColumn()

                ->addColumn('action', function ($row) {
                    $btn = '<div class="dropdown">
                    <button class="btn btn-sm btn-link dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-ellipsis-h"></i>
                    </button>
                    <div class="dropdown-menu">';

                    if (Helper::userCan(104, 'can_edit')) {
                        $btn .= '<a class="dropdown-item" href="' . route('family.level', $row->id) . '">Add Level</a>';
                    }

                    if (Helper::userCan(104, 'can_edit')) {
                        $btn .= '<a class="dropdown-item" href="' . route('family.rank.edit', $row->id) . '">Edit</a>';
                    }

                    if (Helper::userCan(105, 'can_delete')) {
                        $btn .= '<button class="dropdown-item text-danger delete" data-id="' . $row->id . '">Delete</button>';
                    }

                    $btn .= '</div></div>';

                    return $btn;
                })

                ->rawColumns(['action'])
                ->make(true);
        }

        return view('family.family_rank.index');
    }


    public function rankAdd(): View
    {
        return view('family.family_rank.add');
    }

    public function rankSave(Request $request)
    {
        $rules = [
            'name' => 'required',
            'sort' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        return DB::transaction(function () use ($request) {


            FamilyRank::create([
                'name'   => $request->name,
                'sort'  => $request->sort,
            ]);

            return redirect()
                ->route('family.rank')
                ->with('success', 'Family Rank added successfully');
        });
    }

    public function rankEdit($id): View|RedirectResponse
    {
        $fRank = FamilyRank::find($id);

        if (!$fRank) {
            return to_route('family.rank')->withError('Family Rank Not Found!');
        }
        return view('family.family_rank.edit', compact('fRank'));
    }

    public function rankUpdate(Request $request, $id)
    {
        $fRank = FamilyRank::findOrFail($id);

        $rules = [
            'name' => 'required',
            'sort' => 'required',
        ];

        $request->validate($rules);

        return DB::transaction(function () use ($request, $fRank) {

            $data = [
                'name' => $request->name,
                'sort' => $request->sort,
            ];

            $fRank->update($data);

            return redirect()->route('family.rank')->with('success', 'Family Rank updated successfully');
        });
    }

    public function rankDelete(Request $request): JsonResponse
    {
        return Helper::deleteRecord(new FamilyRank, $request->id);
    }

    public function level(Request $request, $id): View|JsonResponse
    {
        $rankId = $id;
        if ($request->ajax()) {

            $query = FamilyRankLevel::where('family_rank_id', $id)->latest();

            return DataTables::of($query)
                ->addIndexColumn()
                ->editColumn('level_badge', function ($row) {
                    return '<img src="' . asset('storage/' . $row->badge) . '" width="40">';
                })
                ->addColumn('action', function ($row) {
                    $btn = '<div class="dropdown">
                    <button class="btn btn-sm btn-link dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-ellipsis-h"></i>
                    </button>
                    <div class="dropdown-menu">';
                    if (Helper::userCan(104, 'can_edit')) {
                        $btn .= '<a class="dropdown-item" href="' . route('family.level.privilege', $row->id) . '">Add Privilege</a>';
                    }
                    if (Helper::userCan(104, 'can_edit')) {
                        $btn .= '<a class="dropdown-item" href="' . route('family.level.edit', $row->id) . '">Edit</a>';
                    }

                    if (Helper::userCan(105, 'can_delete')) {
                        $btn .= '<button class="dropdown-item text-danger delete" data-id="' . $row->id . '">Delete</button>';
                    }

                    $btn .= '</div></div>';

                    return $btn;
                })

                ->rawColumns(['level_badge', 'action'])
                ->make(true);
        }

        return view('family.family_level.index', compact('rankId'));
    }

    public function levelAdd($rankId): View
    {
        return view('family.family_level.add', compact('rankId'));
    }

    public function levelSave(Request $request, $rankId)
    {
        $rules = [
            'level' => 'required',
            'required_points' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        return DB::transaction(function () use ($request, $rankId) {
            $badge = Helper::saveFile($request->file('badge'), 'family_rank_image');


            FamilyRankLevel::create([
                'family_rank_id'   => $request->rank_id,
                'level'   => $request->level,
                'badge'   => $badge,
                'required_points'  => $request->required_points,
            ]);

            return redirect()
                ->route('family.level', $rankId)
                ->with('success', 'Family Rank Level added successfully');
        });
    }

    public function levelEdit($id): View|RedirectResponse
    {
        $fRankLevel = FamilyRankLevel::find($id);

        // if (!$fRankLevel) {
        //     return to_route('family.rank')->withError('Family Rank Not Found!');
        // }
        return view('family.family_level.edit', compact('fRankLevel'));
    }

    public function levelUpdate(Request $request, $id)
    {
        $fRankLevel = FamilyRankLevel::findOrFail($id);

        $rules = [
            'level' => 'required',
            'required_points' => 'required',
        ];

        $request->validate($rules);

        return DB::transaction(function () use ($request, $fRankLevel) {

            $data = [
                'level'   => $request->level,
                'required_points'  => $request->required_points,
            ];

            if ($request->hasFile('badge')) {

                if ($fRankLevel->badge && file_exists(public_path($fRankLevel->badge))) {
                    @unlink(public_path($fRankLevel->badge));
                }

                $data['badge'] = Helper::saveFile($request->file('badge'), 'family_rank_image');
            }

            $fRankLevel->update($data);

            return redirect()->route('family.level', $fRankLevel->family_rank_id)->with('success', 'Family Rank Level updated successfully');
        });
    }

    public function levelDelete(Request $request): JsonResponse
    {
        return Helper::deleteRecord(new FamilyRankLevel, $request->id);
    }


    public function levelPrivilege(Request $request, $id): View|JsonResponse
    {
        $levelId = $id;
        $level = FamilyRankLevel::findOrFail($levelId);
        $rankId = $level->family_rank_id;
        if ($request->ajax()) {

            $query = FamilyRankBenefit::where('family_level_id', $id)->latest();

            return DataTables::of($query)
                ->addIndexColumn()
                ->editColumn('level_badge', function ($row) {
                    return '<img src="' . asset('storage/' . $row->level_badge) . '" width="40">';
                })
                ->editColumn('level_frame', function ($row) {
                    return '<img src="' . asset('storage/' . $row->level_frame) . '" width="40">';
                })
                ->addColumn('action', function ($row) {
                    $btn = '<div class="dropdown">
                    <button class="btn btn-sm btn-link dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-ellipsis-h"></i>
                    </button>
                    <div class="dropdown-menu">';

                    if (Helper::userCan(104, 'can_edit')) {
                        $btn .= '<a class="dropdown-item" href="' . route('family.level.privilege.edit', $row->id) . '">Edit</a>';
                    }

                    if (Helper::userCan(105, 'can_delete')) {
                        $btn .= '<button class="dropdown-item text-danger delete" data-id="' . $row->id . '">Delete</button>';
                    }

                    $btn .= '</div></div>';

                    return $btn;
                })
                ->rawColumns(['level_badge', 'level_frame', 'action'])
                ->make(true);
        }

        return view('family.family_level_privilege.index', compact('levelId', 'rankId'));
    }

    public function levelPrivilegeAdd($levelId): View
    {
        return view('family.family_level_privilege.add', compact('levelId'));
    }

    public function levelPrivilegeSave(Request $request, $levelId)
    {
        $rules = [
            'level_badge' => 'required|image|mimes:png,jpg,jpeg,webp',
            'level_frame' => 'required|image|mimes:png,jpg,jpeg,webp',
            'member'      => 'required',
            'admin'       => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        return DB::transaction(function () use ($request, $levelId) {

            $level_badge = Helper::saveFile($request->file('level_badge'), 'family_rank_image');
            $level_frame = Helper::saveFile($request->file('level_frame'), 'family_rank_image');

            FamilyRankBenefit::create([
                'family_level_id'  => $request->level_id,
                'level_badge'  => $level_badge,
                'level_frame' => $level_frame,
                'members'      => $request->member,
                'admin'       => $request->admin,
            ]);

            return redirect()
                ->route('family.level.privilege', $levelId)
                ->with('success', 'Privilege added successfully');
        });
    }

    public function levelPrivilegeEdit($id): View|RedirectResponse
    {
        $privilege = FamilyRankBenefit::find($id);

        return view('family.family_level_privilege.edit', compact('privilege'));
    }

    public function levelPrivilegeUpdate(Request $request, $id)
    {
        $privilege = FamilyRankBenefit::findOrFail($id);

        $rules = [
            'level_badge' => 'nullable|image|mimes:png,jpg,jpeg,webp',
            'level_frame' => 'nullable|image|mimes:png,jpg,jpeg,webp',
            'member'      => 'required',
            'admin'       => 'required',
        ];

        $request->validate($rules);

        return DB::transaction(function () use ($request, $privilege) {

            $data = [
                'members' => $request->member,
                'admin'   => $request->admin,
            ];


            if ($request->hasFile('level_badge')) {

                if ($privilege->level_badge && file_exists(public_path($privilege->level_badge))) {
                    @unlink(public_path($privilege->level_badge));
                }

                $data['level_badge'] = Helper::saveFile($request->file('level_badge'), 'family_rank_image');
            }

            if ($request->hasFile('level_frame')) {

                if ($privilege->level_frame && file_exists(public_path($privilege->level_frame))) {
                    @unlink(public_path($privilege->level_frame));
                }

                $data['level_frame'] = Helper::saveFile($request->file('level_frame'), 'family_rank_image');
            }


            $privilege->update($data);

            return redirect()->route('family.level.privilege', $privilege->family_level_id)->with('success', 'Privilege updated successfully');
        });
    }

    public function levelPrivilegeDelete(Request $request): JsonResponse
    {
        return Helper::deleteRecord(new FamilyRankBenefit, $request->id);
    }
}
