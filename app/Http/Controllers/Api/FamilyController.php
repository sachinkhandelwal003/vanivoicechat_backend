<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Helper\Helper;
use App\Models\Room;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\FamilyRank;
use App\Models\FamilyRankLevel;
use App\Models\FamilyRankBenefit;
use App\Models\FamilyJoinRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class FamilyController extends Controller
{
    public function createFamily(Request $request)
    {
        $user = Auth::user();

        if (FamilyMember::where('user_id', $user->id)->whereNull('left_at')->exists()) {
            return response()->json([
                'status' => false,
                'message' => 'User already in a family'
            ], 422);
        }

        $validate = Validator::make($request->all(), [
            'name' => 'required|unique:families,name|max:20',
            'description' => 'nullable|max:500',
            'logo' => 'nullable|image',
        ]);

        if ($validate->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validate->errors()
            ], 422);
        }

        $logo = null;

        if ($request->hasFile('logo')) {
            $logo = Helper::saveFile($request->file('logo'), 'family_image');
        }

        $family = Family::create([
            'name' => $request->name,
            'logo' => $logo,
            'description' => $request->description,
            'leader_id' => $user->id,
            'level' => 1,
            'total_points' => 0,
        ]);

        FamilyMember::create([
            'family_id' => $family->id,
            'user_id' => $user->id,
            'role' => 'leader',
            'joined_at' => now(),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Family created successfully',
            'family' => $family
        ]);
    }


    public function topFamilies(Request $request)
    {
        $type = $request->get('type', 'week');

        if ($type === 'month') {
            $from = now()->startOfMonth();
            $to   = now()->endOfMonth();
        } else {
            $from = now()->startOfWeek();
            $to   = now()->endOfWeek();
        }
        // dd($from,$to);
        $families = DB::table('gift_transactions')
            ->join('family_members', 'family_members.user_id', '=', 'gift_transactions.sender_id')
            ->join('families', 'families.id', '=', 'family_members.family_id')
            ->whereBetween('gift_transactions.created_at', [$from, $to])
            ->select(
                'families.id',
                'families.name',
                'families.logo',
                DB::raw('SUM(COALESCE(gift_transactions.total_value, gift_transactions.coin_value)) as total_points'),
                DB::raw('(SELECT COUNT(*) FROM family_members WHERE family_id = families.id) as total_members')
            )
            ->groupBy('families.id', 'families.name', 'families.logo')
            ->orderByDesc('total_points')
            ->limit(20)
            ->get();

        $families->each(function ($family) {
            if ($family->logo) {
                $family->logo = Helper::showImage($family->logo, true);
            }

            $points = (int) $family->total_points;

            if ($points >= 1000000) {
                $family->total_points = round($points / 1000000, 2) . 'M';
            } elseif ($points >= 1000) {
                $family->total_points = round($points / 1000, 2) . 'K';
            } else {
                $family->total_points = (string) $points;
            }
        });

        return response()->json([
            'status' => true,
            'type'   => $type,
            'data'   => $families
        ]);
    }

    public function familyRank()
    {
        $ranks = FamilyRank::orderBy('sort')->get();

        return response()->json([
            'status' => true,
            'message' => 'Family Rank Fetched Successfully',
            'data'   => $ranks
        ]);
    }

    public function familyRankLevels($rankId)
    {
        $levels = FamilyRankLevel::where('family_rank_id', $rankId)->orderBy('required_points')->get();

        $defaultLevel = $levels->first();

        return response()->json([
            'status'        => true,
            'message' => 'Rank Level Fetch Successfully',
            'levels'        => $levels,
            // 'default_level' => $defaultLevel
        ]);
    }

    public function levelBenefits($levelId)
    {
        $benefit = FamilyRankBenefit::where('family_level_id', $levelId)->first();

        if ($benefit) {
            $benefit->level_badge = Helper::showImage($benefit->level_badge, true);
            $benefit->level_frame = Helper::showImage($benefit->level_frame, true);
        }

        return response()->json([
            'status' => true,
            'data'   => $benefit
        ]);
    }

    public function joinFamily(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'family_id' => 'required|exists:families,id'
        ]);

        if (FamilyMember::where('user_id', $user->id)
            ->whereNull('left_at')
            ->exists()
        ) {
            return response()->json([
                'status' => false,
                'message' => 'User already in a family'
            ]);
        }

        $alreadyRequested = FamilyJoinRequest::where('family_id', $request->family_id)
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->exists();

        if ($alreadyRequested) {
            return response()->json([
                'status' => false,
                'message' => 'Join request already sent'
            ]);
        }

        FamilyJoinRequest::create([
            'family_id' => $request->family_id,
            'user_id' => $user->id,
            'status' => 'pending'
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Join request sent. Waiting for leader approval'
        ]);
    }

    public function approveJoinRequest(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'request_id' => 'required|exists:family_join_requests,id',
            'action'     => 'required|in:approve,reject'
        ]);

        $joinRequest = FamilyJoinRequest::findOrFail($request->request_id);

        $isLeader = FamilyMember::where('family_id', $joinRequest->family_id)
            ->where('user_id', $user->id)
            ->where('role', 'leader')
            ->exists();

        if (!$isLeader) {
            return response()->json([
                'status' => false,
                'message' => 'Only leader can approve or reject requests'
            ], 403);
        }

        if ($request->action === 'reject') {

            $joinRequest->delete();

            return response()->json([
                'status' => true,
                'message' => 'Join request rejected successfully'
            ]);
        }

        $alreadyMember = FamilyMember::where('user_id', $joinRequest->user_id)
            ->whereNull('left_at')
            ->exists();

        if ($alreadyMember) {
            return response()->json([
                'status' => false,
                'message' => 'User already joined another family'
            ], 400);
        }

        DB::transaction(function () use ($joinRequest) {

            FamilyMember::create([
                'family_id' => $joinRequest->family_id,
                'user_id'   => $joinRequest->user_id,
                'role'      => 'member',
                'joined_at' => now()
            ]);

            $joinRequest->update([
                'status' => 'approved'
            ]);

            FamilyJoinRequest::where('user_id', $joinRequest->user_id)
                ->where('id', '!=', $joinRequest->id)
                ->delete();
        });

        return response()->json([
            'status' => true,
            'message' => 'User approved and added to family'
        ]);
    }

    public function leaveFamily()
    {
        $user = Auth::user();

        $member = FamilyMember::where('user_id', $user->id)->whereNull('left_at')->first();

        if (!$member) {
            return response()->json([
                'status' => false,
                'message' => 'User is not in any family'
            ], 422);
        }

        if ($member->role === 'leader') {
            return response()->json([
                'status' => false,
                'message' => 'Leader must transfer leadership before leaving family'
            ], 422);
        }

        DB::transaction(function () use ($user, $member) {

            FamilyJoinRequest::where('user_id', $user->id)
                ->where('family_id', $member->family_id)
                ->delete();

            $member->update([
                'left_at' => now()
            ]);
        });

        return response()->json([
            'status' => true,
            'message' => 'Left family successfully'
        ]);
    }

    public function pendingFamilyRequests(Request $request)
    {
        $user = Auth::user();

        $leader = FamilyMember::where('user_id', $user->id)
            ->where('role', 'leader')
            ->first();

        if (!$leader) {
            return response()->json([
                'status' => false,
                'message' => 'Only family leader can view requests'
            ], 403);
        }

        $requests = FamilyJoinRequest::where('family_id', $leader->family_id)
            ->where('status', 'pending')
            ->with('user:id,name,uid,image')
            ->latest()
            ->get();

        $requests->each(function ($request) {

            if (Str::startsWith($request->user->image, ['http://', 'https://'])) {
                $request->user->image = $request->user->image;
            } else {
                $request->user->image = Helper::showImage($request->user->image, true);
            }
        });

        return response()->json([
            'status' => true,
            'message' => 'Pending requests fetched successfully',
            'data' => $requests
        ]);
    }

    public function familyMembersList(Request $request)
    {
        $user = Auth::user();

        $member = FamilyMember::where('user_id', $user->id)
            ->whereNull('left_at')
            ->first();

        if (!$member) {
            return response()->json([
                'status' => false,
                'message' => 'User is not in any family'
            ], 404);
        }

        $members = FamilyMember::where('family_id', $member->family_id)
            ->whereNull('left_at')
            ->with('user:id,name,uid,image')
            ->orderByRaw("FIELD(role, 'leader') DESC")
            ->latest()
            ->get();

        $contributions = DB::table('gift_transactions')
            ->join('family_members', function ($join) use ($member) {
                $join->on('family_members.user_id', '=', 'gift_transactions.sender_id')
                    ->where('family_members.family_id', '=', $member->family_id)
                    ->whereColumn('gift_transactions.created_at', '>=', 'family_members.joined_at')
                    ->where(function ($q) {
                        $q->whereNull('family_members.left_at')
                            ->orWhereColumn('gift_transactions.created_at', '<=', 'family_members.left_at');
                    });
            })
            ->select('gift_transactions.sender_id', DB::raw('SUM(gift_transactions.coin_value) as total_coins'))
            ->groupBy('gift_transactions.sender_id')
            ->pluck('total_coins', 'sender_id');


        $members->each(function ($member) use ($contributions) {
            if (Str::startsWith($member->user->image, ['http://', 'https://'])) {
                $member->user->image = $member->user->image;
            } else {
                $member->user->image = Helper::showImage($member->user->image, true);
            }

            $member->contribution_points = $contributions[$member->user_id] ?? 0;
        });

        return response()->json([
            'status' => true,
            'message' => 'Family members fetched successfully',
            'data' => $members
        ]);
    }

    public function removeFamilyMember(Request $request)
    {

        $leader = Auth::user();

        $request->validate([
            'user_id' => 'required|exists:app_users,id'
        ]);

        $leaderMember = FamilyMember::where('user_id', $leader->id)->whereNull('left_at')->first();

        if (!$leaderMember) {
            return response()->json([
                'status' => false,
                'message' => 'Leader not in any family'
            ], 403);
        }

        if ($leaderMember->role !== 'leader') {
            return response()->json([
                'status' => false,
                'message' => 'Only leader can remove members'
            ], 403);
        }

        if ($request->user_id == $leader->id) {
            return response()->json([
                'status' => false,
                'message' => 'Leader cannot remove themselves'
            ], 422);
        }

        $target = FamilyMember::where('user_id', $request->user_id)
            ->where('family_id', $leaderMember->family_id)
            ->whereNull('left_at')
            ->first();

        if (!$target) {
            return response()->json([
                'status' => false,
                'message' => 'User not found in this family'
            ], 404);
        }

        DB::transaction(function () use ($request, $leaderMember, $target) {

            $target->update([
                'left_at' => now()
            ]);

            FamilyJoinRequest::where('family_id', $leaderMember->family_id)
                ->where('user_id', $request->user_id)
                ->delete();
        });

        return response()->json([
            'status' => true,
            'message' => 'Member removed successfully'
        ]);
    }

    public function setAsAdmin(Request $request)
    {
        $authUser = Auth::user();

        $request->validate([
            'user_id' => 'required|exists:app_users,id'
        ]);

        $authMember = FamilyMember::where('user_id', $authUser->id)->whereNull('left_at')->first();

        if (!$authMember) {
            return response()->json([
                'status' => false,
                'message' => 'You are not in a family'
            ], 403);
        }

        if ($authMember->role !== 'leader') {
            return response()->json([
                'status' => false,
                'message' => 'Only leader can assign admin role'
            ], 403);
        }

        $target = FamilyMember::where('user_id', $request->user_id)
            ->where('family_id', $authMember->family_id)
            ->whereNull('left_at')
            ->first();

        if (!$target) {
            return response()->json([
                'status' => false,
                'message' => 'User not found in family'
            ], 404);
        }

        if ($target->role === 'leader') {
            return response()->json([
                'status' => false,
                'message' => 'Cannot change leader role'
            ], 422);
        }

        $target->update(['role' => 'admin']);

        return response()->json([
            'status' => true,
            'message' => 'User promoted to admin'
        ]);
    }

    public function setAsMember(Request $request)
    {
        $authUser = Auth::user();

        $request->validate([
            'user_id' => 'required|exists:app_users,id'
        ]);

        $authMember = FamilyMember::where('user_id', $authUser->id)->whereNull('left_at')->first();

        if (!$authMember) {
            return response()->json([
                'status' => false,
                'message' => 'You are not in a family'
            ], 403);
        }

        if (!in_array($authMember->role, ['leader', 'admin'])) {
            return response()->json([
                'status' => false,
                'message' => 'Permission denied'
            ], 403);
        }

        $target = FamilyMember::where('user_id', $request->user_id)
            ->where('family_id', $authMember->family_id)
            ->whereNull('left_at')
            ->first();

        if (!$target) {
            return response()->json([
                'status' => false,
                'message' => 'User not found in family'
            ], 404);
        }

        if ($target->role === 'leader') {
            return response()->json([
                'status' => false,
                'message' => 'Cannot downgrade leader'
            ], 422);
        }

        $target->update(['role' => 'member']);

        return response()->json([
            'status' => true,
            'message' => 'User set as member'
        ]);
    }

    public function myFamily()
    {
        $user = Auth::user();

        $membership = FamilyMember::where('user_id', $user->id)
            ->with('family')
            ->whereNull('left_at')
            ->first();

        if (!$membership) {
            return response()->json([
                'status' => true,
                'data' => $membership,
                'message' => 'User not in any family'
            ]);
        }

        $family = $membership->family;

        $members = FamilyMember::where('family_id', $family->id)
            ->whereNull('left_at')
            ->with('user:id,name,uid,image')
            ->orderByRaw("FIELD(role, 'leader', 'admin', 'member')")
            ->get();

        $totalMembers = $members->count();

        $familyMemberIds = $members->pluck('user_id')->toArray();

        if ($family->logo) {
            $family->logo = Helper::showImage($family->logo, true);
        }

        $members->each(function ($member) {
            if ($member->user && $member->user->image) {
                if (!Str::startsWith($member->user->image, ['http://', 'https://'])) {
                    $member->user->image = Helper::showImage($member->user->image, true);
                }
            }

            $member->user->rating = 4.5;
        });

        $currentLevel = FamilyRankLevel::where('required_points', '<=', $family->total_points)
            ->orderByDesc('required_points')
            ->first();

        if (!$currentLevel) {
            $currentLevel = FamilyRankLevel::orderBy('required_points')->first();
        }

        $nextLevel = FamilyRankLevel::where('required_points', '>', $currentLevel->required_points)
            ->orderBy('required_points')
            ->first();

        $benefits = FamilyRankBenefit::where('family_level_id', $currentLevel->id)->first();

        $pointsToNextLevel = $nextLevel
            ? ($nextLevel->required_points - $family->total_points)
            : 0;

        $rooms = Room::whereIn('user_id', $familyMemberIds)
            ->whereHas('onlineUsers')
            ->with([
                'user:id,name,uid,image,country',
                'user.countryData:id,name,iso'
            ])
            ->withCount('onlineUsers as active_users_count')
            ->latest()
            ->get();

        $rooms->each(function ($room) {

            if ($room->room_image) {
                $room->room_image = Helper::showImage($room->room_image, true);
            }

            if ($room->owner && $room->owner->image) {
                if (!Str::startsWith($room->owner->image, ['http://', 'https://'])) {
                    $room->owner->image = Helper::showImage($room->owner->image, true);
                }
            }

            if ($room->user->countryData && $room->user->countryData->iso) {
                $room->user->flag =
                    'https://flagcdn.com/w40/' .
                    strtolower($room->user->countryData->iso) .
                    '.png';
            } else {
                $room->user->flag = null;
            }
        });

        $isCreator = $family->leader_id == $user->id;
        $isLeader  = $membership->role === 'leader';
        $isAdmin   = $membership->role === 'admin';

        return response()->json([
            'status' => true,
            'is_family_creator' => $isCreator,
            'is_leader'         => $isLeader,
            'is_admin'          => $isAdmin,
            'my_role'           => $membership->role,
            'family' => [
                'id'            => $family->id,
                'name'          => $family->name,
                'logo'          => $family->logo,
                'bio'           => $family->description,
                'leader_id'     => $family->leader_id,
                'level'         => $currentLevel?->level ?? 1,
                'total_points'  => $family->total_points,
                'created_at'    => $family->created_at,

                'total_members' => $totalMembers,

                'level_badge'   => $benefits?->level_badge ? Helper::showImage($benefits->level_badge, true) : null,
                'level_frame'   => $benefits?->level_frame ? Helper::showImage($benefits->level_frame, true) : null,
                'max_members'   => $benefits?->members ?? null,
                'max_admins'    => $benefits?->admin ?? null,

                'current_level_required_points' => $currentLevel?->required_points ?? 0,
                'next_level_required_points'    => $nextLevel?->required_points ?? null,
                'points_to_next_level'          => $nextLevel
                    ? ($nextLevel->required_points - $family->total_points)
                    : 0,
            ],

            'members' => $members,

            'rooms' => $rooms,
        ]);
    }

    public function familyEditData(Request $request)
    {
        $familyId = $request->family_id;

        $familydata = Family::select('id', 'name', 'logo', 'description')->find($familyId);
        if (!$familydata) {
            return response()->json([
                'status' => false,
                'message' => 'Family not found'
            ], 404);
        }

        if ($familydata->logo) {
            $familydata->logo = Helper::showImage($familydata->logo, true);
        }

        return response()->json([
            'status' => true,
            'message' => 'Edit Data Fetched Successfully',
            'data' => $familydata
        ]);
    }

    public function updateFamily(Request $request)
    {
        $user = Auth::user();

        $member = FamilyMember::where('family_id', $request->family_id)->where('user_id', $user->id)->first();

        if (!$member) {
            return response()->json([
                'status' => false,
                'message' => 'You are not in a family'
            ], 403);
        }

        if ($member->role !== 'leader') {
            return response()->json([
                'status' => false,
                'message' => 'Only family leader can update family'
            ], 403);
        }

        $family = Family::findOrFail($member->family_id);

        $validate = Validator::make($request->all(), [
            'name'        => 'nullable|string|max:20|unique:families,name,' . $family->id,
            'description' => 'nullable|string|max:500',
            'logo'        => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048'
        ]);

        if ($validate->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validate->errors()
            ], 422);
        }

        if ($request->hasFile('logo')) {
            $logo = Helper::saveFile($request->file('logo'), 'family_image');
            $family->logo = $logo;
        }

        if ($request->filled('name')) {
            $family->name = $request->name;
        }

        if ($request->filled('description')) {
            $family->description = $request->description;
        }

        $family->save();

        if ($family->logo) {
            $family->logo = Helper::showImage($family->logo, true);
        }

        return response()->json([
            'status' => true,
            'message' => 'Family updated successfully',
            'family' => $family
        ]);
    }

    public function familyDetails($familyId)
    {
        $user = Auth::user();

        $family = Family::find($familyId);

        if (!$family) {
            return response()->json([
                'status' => false,
                'message' => 'Family not found'
            ], 404);
        }

        $members = FamilyMember::where('family_id', $family->id)
            ->whereNull('left_at')
            ->with('user:id,name,uid,image')
            ->orderByRaw("FIELD(role, 'leader', 'admin', 'member')")
            ->get();

        $totalMembers = $members->count();

        $familyMemberIds = $members->pluck('user_id')->toArray();

        $rooms = Room::whereIn('user_id', $familyMemberIds)
            ->whereHas('onlineUsers')
            ->with([
                'user:id,name,uid,image,country',
                'user.countryData:id,name,iso'
            ])
            ->withCount('onlineUsers as active_users_count')
            ->latest()
            ->get();

        $rooms->each(function ($room) {

            if ($room->room_image) {
                $room->room_image = Helper::showImage($room->room_image, true);
            }

            if ($room->owner && $room->owner->image) {
                if (!Str::startsWith($room->owner->image, ['http://', 'https://'])) {
                    $room->owner->image = Helper::showImage($room->owner->image, true);
                }
            }

            if ($room->user->countryData && $room->user->countryData->iso) {
                $room->user->flag =
                    'https://flagcdn.com/w40/' .
                    strtolower($room->user->countryData->iso) .
                    '.png';
            } else {
                $room->user->flag = null;
            }
        });

        if ($family->logo) {
            $family->logo = Helper::showImage($family->logo, true);
        }

        $members->each(function ($member) {
            if (Str::startsWith($member->user->image, ['http://', 'https://'])) {
                $member->user->image = $member->user->image;
            } else {
                $member->user->image = Helper::showImage($member->user->image, true);
            }

            $member->user->rating = 4.5;
        });

        $currentLevel = FamilyRankLevel::where('required_points', '<=', $family->total_points)
            ->orderByDesc('required_points')
            ->first();

        if (!$currentLevel) {
            $currentLevel = FamilyRankLevel::orderBy('required_points')->first();
        }

        $nextLevel = FamilyRankLevel::where('required_points', '>', $currentLevel->required_points)
            ->orderBy('required_points')
            ->first();

        $benefits = FamilyRankBenefit::where('family_level_id', $currentLevel->id)->first();

        $pointsToNextLevel = $nextLevel
            ? ($nextLevel->required_points - $family->total_points)
            : 0;

        $myMembership = null;
        $joinRequestPending = false;
        $isFamilyMember = false;

        if ($user) {
            $myMembership = FamilyMember::where('user_id', $user->id)->first();

            $joinRequestPending = FamilyJoinRequest::where('family_id', $family->id)
                ->where('user_id', $user->id)
                ->where('status', 'pending')
                ->exists();

            $isFamilyMember = FamilyMember::where('user_id', $user->id)
                ->whereNull('left_at')
                ->exists();
        }

        return response()->json([
            'status' => true,
            'family' => [
                'id'            => $family->id,
                'name'          => $family->name,
                'logo'          => $family->logo,
                'bio'           => $family->description,
                'leader_id'     => $family->leader_id,

                'level'         => $currentLevel?->level ?? 1,
                'total_points'  => $family->total_points,
                'created_at'    => $family->created_at,
                'total_members' => $totalMembers,
                'level_badge'   => $benefits?->level_badge ? Helper::showImage($benefits->level_badge, true) : null,
                'level_frame'   => $benefits?->level_frame ? Helper::showImage($benefits->level_frame, true) : null,
                'max_members'   => $benefits?->members ?? null,
                'max_admins'    => $benefits?->admin ?? null,

                'current_level_required_points' => $currentLevel?->required_points ?? 0,
                'next_level_required_points'    => $nextLevel?->required_points ?? null,
                'points_to_next_level'          => $nextLevel
                    ? ($nextLevel->required_points - $family->total_points)
                    : 0,

                'is_my_family'        => $myMembership?->family_id == $family->id,
                'join_request_pending' => $joinRequestPending,
                'is_family_member' => $isFamilyMember,

            ],

            'members' => $members,
            'rooms' => $rooms

        ]);
    }
}
