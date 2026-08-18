<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Helper\Helper;
use App\Models\AppUser;
use App\Models\StaticPage;
use App\Models\InviteUser;
use App\Models\InviteRewardHistory;
use App\Models\RewardInviting;

class InviteController extends Controller
{
    public function cmsData()
    {
        $user = Auth::user();

        // CMS Types
        $types = ['other_notes', 'regarding_invitation', 'activity_rules'];

        $pages = StaticPage::whereIn('type', $types)->get()->keyBy('type');

        $cms = [];

        foreach ($types as $type) {
            $cms[$type] = [
                'type' => $pages[$type]->type ?? null,
                'title' => $pages[$type]->title ?? null,
                'description' => $pages[$type]->description ?? null,
            ];
        }

        //  Reward Inviting Data
        // $rewardInviting = \App\Models\RewardInviting::where('status', 1)
        //     ->get()
        //     ->map(function ($item) {
        //         return [
        //             'id' => $item->id,
        //             'target_person' => $item->target_person,
        //             'reward_coin' => $item->reward_coin,
        //         ];
        //     });

        // Reward Invitation Recharge Data
        $rewardRecharge = \App\Models\RewardInvitationRecharge::where('status', 1)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'deposit_amount' => $item->deposit_amount,
                    'gain_benefits' => $item->gain_benefits,
                ];
            });

        $completedInviteCount = InviteUser::where('inviter_id', $user->id)
            ->where('is_completed', 1)
            ->count();

        $rewardInviting = RewardInviting::where('status', 1)
            ->orderBy('target_person')
            ->get()
            ->map(function ($item) use ($completedInviteCount) {

                // $progress = $completedInviteCount >= $item->target_person
                //     ? $item->target_person . '/' . $item->target_person
                //     : '0/' . $item->target_person;

                $progress = $completedInviteCount >= $item->target_person
                    ? $item->target_person . '/' . $item->target_person
                    : $completedInviteCount . '/' . $item->target_person;

                return [
                    'id' => $item->id,
                    'target_person' => $item->target_person,
                    'reward_coin' => $item->reward_coin,
                    'progress' => $progress,
                    'is_completed' => $completedInviteCount >= $item->target_person,
                ];
            });

        return response()->json([
            'status' => true,
            'message' => 'Data fetched successfully',
            'data' => [
                'invite_code' => $user->invite_code ?? null,
                'completed_invite_count' => $completedInviteCount,
                'cms' => $cms,
                'reward_inviting' => $rewardInviting,
                'reward_invitation_recharge' => $rewardRecharge,
            ]
        ]);
    }

    public function sendInviteCode(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'status' => true,
            'message' => 'Invite code fetched successfully.',
            'data' => [
                'invite_code' => $user->invite_code,
                // 'invite_link' => url('/register?invite_code=' . $user->invite_code),
                'invite_link' => 'https://play.google.com/store/apps/details?id=com.anantam.vanivoice',
                'share_message' => "Join our app using my invite code {$user->invite_code} and enjoy exciting rewards.",
            ]
        ]);
    }

    public function invitedUsers(Request $request)
    {
        $user = $request->user();

        $invitedUsers = InviteUser::with('invitedUser:id,uid,name,image')
            ->where('inviter_id', $user->id)
            ->where('is_completed', 1)
            ->latest()
            ->get()
            ->map(function ($invite) {

                return [
                    'id'           => $invite->invitedUser->id ?? null,
                    // 'uid'          => $invite->invitedUser->uid ?? null,
                    'name'         => $invite->invitedUser->name ?? '',
                    'image'        => $invite->invitedUser->image
                        ? Helper::showImage($invite->invitedUser->image, true)
                        : null,
                    'completed_at' => [
                        'date' => optional($invite->completed_at)->format('d M Y h:i A'),
                        'human' => optional($invite->completed_at)->diffForHumans(),
                    ],
                ];
            });

        return response()->json([
            'status' => true,
            'message' => 'Invited users fetched successfully.',
            'data' => $invitedUsers
        ]);
    }

    public function invitationRevenueHistory()
    {
        $user = Auth::user();

        $histories = InviteRewardHistory::with('reward')
            ->where('user_id', $user->id)
            ->latest()
            ->get()
            ->map(function ($item) {

                return [
                    'id' => $item->id,
                    'target_person' => $item->target_person,
                    'reward_coin' => $item->reward_coin,
                    'reward_title' => $item->reward
                        ? 'Invite ' . $item->reward->target_person . ' Users Reward'
                        : null,
                    'date' => $item->created_at->format('d M Y'),
                    'time' => $item->created_at->format('h:i A'),
                    'created_at' => $item->created_at->diffForHumans(),
                ];
            });

        $totalRevenue = InviteRewardHistory::where('user_id', $user->id)
            ->sum('reward_coin');

        return response()->json([
            'status' => true,
            'message' => 'Invitation revenue fetched successfully.',
            'data' => [
                'total_revenue' => $totalRevenue,
                'histories' => $histories
            ]
        ]);
    }
}
