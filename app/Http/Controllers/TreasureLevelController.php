<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\TreasureLevel;
use App\Models\TreasureLevelReward;
use App\Models\TreasureLevelClaims;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use App\Helper\Helper;
use Illuminate\Http\JsonResponse;


class TreasureLevelController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = TreasureLevel::withCount('rewards')->latest();

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('level', function ($row) {
                    return 'Lv.' . $row->level;
                })
                ->addColumn('target_points', function ($row) {
                    return number_format($row->target_points);
                })
                ->addColumn('chest_image', function ($row) {

                    if (!$row->chest_image) {
                        return '-';
                    }

                    $image = asset('storage/' . $row->chest_image);

                    return '
                        <img src="' . $image . '" width="55" height="55" class="image-preview" data-image="' . $image . '"
                             style="cursor:pointer;object-fit:contain;border-radius:8px;">
                    ';
                })
                ->addColumn('status', function ($row) {
                    if ($row->status == 1) {
                        return '<span class="badge bg-success">Active</span>';
                    }

                    return '<span class="badge bg-danger">Inactive</span>';
                })
                ->addColumn('rewards_count', function ($row) {
                    return '<span class="badge bg-primary">' . $row->rewards_count . '</span>';
                })
                ->addColumn('action', function ($row) {
                    $btn = '<div class="dropdown">
                    <button class="btn btn-sm btn-link dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-ellipsis-h"></i>
                    </button>
                    <div class="dropdown-menu">';

                    if (Helper::userCan(111, 'can_edit')) {
                        $btn .= '<a class="dropdown-item" href="' . route('treasure-levels.edit', $row->id) . '">Edit</a>';
                    }

                    if (Helper::userCan(111, 'can_delete')) {
                        $btn .= '<button class="dropdown-item text-danger delete" data-id="' . $row->id . '">Delete</button>';
                    }

                    $btn .= '</div></div>';

                    return $btn;
                })
                ->rawColumns(['chest_image', 'status', 'rewards_count', 'action'])
                ->make(true);
        }

        return view('treasure_levels.index');
    }

    public function create()
    {
        return view('treasure_levels.create');
    }



    public function store(Request $request)
    {
        $request->validate([
            'level' => 'required|integer|unique:treasure_levels,level',
            'target_points' => 'required|integer|min:1',
            'chest_image' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:4096',
            'status' => 'required|in:0,1',

            'reward_type.*' => 'nullable|string',
            'reward_item_id.*' => 'nullable|integer',
            'coins.*' => 'nullable|integer|min:0',
            'valid_days.*' => 'nullable|integer|min:1',
            'reward_image.*' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:4096',
        ]);

        DB::beginTransaction();

        try {

            $chestImagePath = null;

            if ($request->hasFile('chest_image')) {
                $chestImagePath = Helper::saveFile($request->file('chest_image'), 'uploads/treasure/chests');
            }

            $level = TreasureLevel::create([
                'level' => $request->level,
                'target_points' => $request->target_points,
                'chest_image' => $chestImagePath,
                'status' => $request->status,
            ]);

            if ($request->reward_type) {
                foreach ($request->reward_type as $key => $type) {

                    if (!$type) {
                        continue;
                    }

                    $isCoins = $type === 'coins';

                    if ($isCoins && empty($request->coins[$key])) {
                        continue;
                    }

                    if (!$isCoins && empty($request->reward_item_id[$key])) {
                        continue;
                    }

                    $rewardItemId = $isCoins ? null : ($request->reward_item_id[$key] ?? null);
                    $coins = $isCoins ? ($request->coins[$key] ?? 0) : 0;
                    $validDays = $isCoins ? null : ($request->valid_days[$key] ?? null);

                    $rewardImagePath = null;

                    if (!$isCoins) {
                        $rewardImagePath = $this->getRewardItemImagePath($type, $rewardItemId);
                    }


                    TreasureLevelReward::create([
                        'treasure_level_id' => $level->id,
                        'reward_type'      => $type,
                        'reward_item_id'   => $rewardItemId,
                        'coins'            => $coins,
                        'valid_days'       => $validDays,
                        'reward_image'     => $rewardImagePath,
                        'status'           => 1,
                    ]);
                }
            }

            DB::commit();

            return redirect()
                ->route('treasure-levels.index')
                ->with('success', 'Treasure level created successfully');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    public function edit($id)
    {
        $level = TreasureLevel::with('rewards')->findOrFail($id);

        return view('treasure_levels.edit', compact('level'));
    }

    public function update(Request $request, $id)
    {
        $level = TreasureLevel::findOrFail($id);

        $request->validate([
            'level' => 'required|integer|unique:treasure_levels,level,' . $level->id,
            'target_points' => 'required|integer|min:1',
            'chest_image' => 'nullable|image',
            'status' => 'required|in:0,1',
        ]);

        DB::beginTransaction();

        try {

            $chestImagePath = $level->chest_image;

            if ($request->hasFile('chest_image')) {
                $chestImagePath = Helper::saveFile($request->file('chest_image'), 'uploads/treasure/chests', $level->chest_image);
            }

            $level->update([
                'level' => $request->level,
                'target_points' => $request->target_points,
                'chest_image' => $chestImagePath,
                'status' => $request->status,
            ]);

            //  old rewards delete
            TreasureLevelReward::where('treasure_level_id', $level->id)->delete();

            //  re-insert rewards
            if ($request->reward_type) {
                foreach ($request->reward_type as $key => $type) {

                    if (!$type) continue;

                    $isCoins = $type === 'coins';

                    $rewardItemId = $isCoins ? null : ($request->reward_item_id[$key] ?? null);
                    $coins = $isCoins ? ($request->coins[$key] ?? 0) : 0;
                    $validDays = $isCoins ? null : ($request->valid_days[$key] ?? null);

                    $rewardImagePath = !$isCoins
                        ? $this->getRewardItemImagePath($type, $rewardItemId)
                        : null;

                    TreasureLevelReward::create([
                        'treasure_level_id' => $level->id,
                        'reward_type' => $type,
                        'reward_item_id' => $rewardItemId,
                        'coins' => $coins,
                        'valid_days' => $validDays,
                        'reward_image' => $rewardImagePath,
                        'status' => 1,
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('treasure-levels.index')
                ->with('success', 'Treasure level updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function destroy(Request $request): JsonResponse
    {
        $request->validate([
            'id' => 'required|exists:treasure_levels,id'
        ]);

        DB::beginTransaction();

        try {

            $level = TreasureLevel::with('rewards')->findOrFail($request->id);

            // delete chest image
            if ($level->chest_image && file_exists(public_path($level->chest_image))) {
                unlink(public_path($level->chest_image));
            }

            // delete reward images
            foreach ($level->rewards as $reward) {
                if ($reward->reward_image && file_exists(public_path($reward->reward_image))) {
                    unlink(public_path($reward->reward_image));
                }
            }

            // delete level (cascade se rewards bhi delete ho jayenge)
            $level->delete();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Treasure level deleted successfully'
            ]);
        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function getRewardItems(Request $request)
    {
        $type = $request->type;

        $items = collect();

        switch ($type) {
            case 'theme':
                $items = DB::table('themes')
                    ->select('id', 'name', 'icon')
                    ->whereNull('user_id')
                    ->where('status', 1)
                    ->get();
                break;

            case 'entry':
                $items = DB::table('cars')
                    ->select('id', 'name', 'icon')
                    ->where('status', 1)
                    ->get();
                break;

            case 'frame':
                $items = DB::table('frames')
                    ->select('id', 'name', 'icon')
                    ->where('status', 1)
                    ->get();
                break;

            case 'chat_bubble':
                $items = DB::table('chat_bubbles')
                    ->select('id', 'name', 'icon')
                    ->where('status', 1)
                    ->get();
                break;

            case 'profile_card':
                $items = DB::table('data_cards')
                    ->select('id', 'name', 'icon')
                    ->where('status', 1)
                    ->get();
                break;

            case 'voice':
                $items = DB::table('voices')
                    ->select('id', 'name', 'icon')
                    ->where('status', 1)
                    ->get();
                break;

            case 'id':
                $items = DB::table('store_uids')
                    ->select('id', 'unique_id as name')
                    ->where('status', 1)
                    ->get();
                break;

            case 'entry_tag':
                $items = DB::table('entry_tags')
                    ->select('id', 'name', 'icon')
                    ->where('status', 1)
                    ->get();
                break;

            case 'vip':
                $items = DB::table('vips')
                    ->select('id', 'name', 'badge')
                    ->get();
                break;
        }

        return response()->json([
            'status' => true,
            'data' => $items
        ]);
    }



    public function rewardList(Request $request)
    {
        if ($request->ajax()) {

            $query = TreasureLevelClaims::with(['room', 'user', 'reward', 'levelInfo'])->latest();

            return DataTables::of($query)

                ->addColumn('room', function ($row) {

                    if (!$row->room) {
                        return '-';
                    }

                    $image = $row->room->image
                        ? Helper::showImage($row->room->image, true)
                        : asset('assets/img/avatar.png');

                    return '
                        <div class="d-flex align-items-center gap-2">
                            <img src="' . $image . '"
                                 class="rounded-circle"
                                 width="40"
                                 height="40">

                            <div>
                                <div class="fw-bold">
                                    ' . e($row->room->room_name) . '
                                </div>

                                <small class="text-muted">
                                    Room ID : ' . e($row->room->room_id) . '
                                </small>
                            </div>
                        </div>
                    ';
                })

                ->addColumn('user', function ($row) {

                    if (!$row->user) {
                        return '-';
                    }

                    $user = $row->user;

                    $image = $user->image
                        ? Helper::showImage($user->image, true)
                        : asset('assets/img/avatar.png');

                    $uidData = Helper::getDisplayUidData($user);

                    $badgeHtml = '';

                    if (!empty($uidData['badge'])) {
                        $badgeHtml = '
                            <img src="' . $uidData['badge'] . '"
                                width="16"
                                height="16"
                                style="vertical-align:middle;margin-right:4px;">
                        ';
                    }

                    if (!empty($uidData['uid']) && $uidData['uid'] != $uidData['system_uid']) {

                        $uidHtml = '
                            <small class="d-flex align-items-center flex-wrap" style="gap:4px;">
                                ' . $badgeHtml . '
                                <span style="color:' . ($uidData['badge_color'] ?? '#000') . ';font-weight:600;">
                                    ' . e($uidData['uid']) . '
                                </span>
                                <span class="text-muted">/</span>
                                <span class="text-muted">' . e($uidData['system_uid']) . '</span>
                            </small>';
                    } else {

                        $uidHtml = '
                            <small class="text-muted">
                                ' . e($uidData['system_uid'] ?? $user->uid) . '
                            </small>';
                    }

                    return '
                        <div class="d-flex align-items-center gap-2 user-profile-trigger"
                            data-user-id="' . $user->id . '"
                            style="cursor:pointer;">

                            <img src="' . $image . '"
                                class="rounded-circle"
                                width="40"
                                height="40">

                            <div>
                                <div class="fw-bold">
                                    ' . e($user->name) . '
                                </div>

                                ' . $uidHtml . '
                            </div>

                        </div>
                    ';
                })

                ->addColumn('reward', function ($row) {

                    if ($row->reward_type == 'coins') {

                        return '
                            <span class="badge badge-light-success fs--1">
                                💰 ' . $row->coins . ' Coins
                            </span>
                        ';
                    }

                    if ($row->reward && $row->reward->reward_image) {

                        return '
                        <div class="d-flex align-items-center gap-2">

                            <img src="' . Helper::showImage($row->reward->reward_image, true) . '"
                                 width="45"
                                 height="45"
                                 style="border-radius:8px;object-fit:cover;">

                            <div>
                                <div class="fw-bold">
                                    ' . ucwords(str_replace('_', ' ', $row->reward_type)) . '
                                </div>

                                <small class="text-muted">
                                    ' . $row->valid_days . ' Days
                                </small>
                            </div>

                        </div>';
                    }

                    return ucwords(str_replace('_', ' ', $row->reward_type));
                })

                ->editColumn('created_at', function ($row) {
                    return $row->created_at
                        ? \Carbon\Carbon::parse($row->created_at)->timezone('Asia/Kolkata')->format('d-M-Y h:i A')
                        : '-';
                })

                ->addColumn('level', function ($row) {
                    return $row->levelInfo ? 'Level ' . $row->levelInfo->level : '-';
                })

                ->addColumn('action', function ($row) {

                    $btn = '
                            <div class="dropup text-center">
                                <button class="btn btn-sm btn-light rounded-pill px-3"
                                    data-bs-toggle="dropdown">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end p-2">';
                    if (Helper::userCan(112, 'can_delete')) {

                        $btn .= '
                                <button class="dropdown-item text-danger delete"
                                data-id="' . $row->id . '">
                                <i class="fas fa-trash"></i> Delete
                                </button>';
                    }
                    $btn .= '
                            </div>
                            </div>';
                    return $btn;
                })

                ->rawColumns(['room', 'user', 'reward', 'level', 'action'])
                ->make(true);
        }

        return view('treasure_levels.rewards');
    }

    public function deleteReward(Request $request)
    {
        return Helper::deleteRecord(new TreasureLevelClaims, $request->id);
    }

    private function getRewardItemImagePath($type, $itemId)
    {
        if (!$type || !$itemId) {
            return null;
        }

        $table = null;
        $column = null;

        switch ($type) {

            case 'theme':
                $table = 'themes';
                $column = 'icon';
                break;

            case 'entry':
                $table = 'cars';
                $column = 'icon';
                break;

            case 'frame':
                $table = 'frames';
                $column = 'icon';
                break;

            case 'chat_bubble':
                $table = 'chat_bubbles';
                $column = 'icon';
                break;

            case 'profile_card':
                $table = 'data_cards';
                $column = 'icon';
                break;

            case 'voice':
                $table = 'voices';
                $column = 'icon';
                break;

            case 'id':
                $table = 'store_uids';
                $column = 'image'; // check if correct
                break;

            case 'entry_tag':
                $table = 'entry_tags';
                $column = 'icon';
                break;

            case 'vip':
                $table = 'vips';
                $column = 'badge';
                break;

            default:
                return null;
        }

        $item = DB::table($table)->where('id', $itemId)->first();

        if (!$item || !isset($item->{$column})) {
            return null;
        }

        return $item->{$column};
    }
}
