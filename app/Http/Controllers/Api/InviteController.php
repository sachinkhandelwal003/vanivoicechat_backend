<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StaticPage;
use Illuminate\Support\Facades\Auth;

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
        $rewardInviting = \App\Models\RewardInviting::where('status', 1)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'target_person' => $item->target_person,
                    'reward_coin' => $item->reward_coin,
                ];
            });

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

        return response()->json([
            'status' => true,
            'message' => 'Data fetched successfully',
            'data' => [
                'invite_code' => $user->invite_code ?? null,
                'cms' => $cms,
                'reward_inviting' => $rewardInviting,
                'reward_invitation_recharge' => $rewardRecharge,
            ]
        ]);
    }
}
