<?php

namespace App\Http\Controllers;

use App\Models\WalletTabSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WalletTabSettingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display wallet tabs management page
     */
    public function index()
    {
        $tabs = WalletTabSetting::orderBy('sort_order', 'asc')->get();
        return view('wallet_tabs.index', compact('tabs'));
    }

    /**
     * Update all wallet tab settings
     */
    public function update(Request $request)
    {
        $request->validate([
            'tabs'               => 'required|array',
            'tabs.*.id'          => 'required|exists:wallet_tab_settings,id',
            'tabs.*.tab_name'    => 'required|string|max:255',
            'tabs.*.sub_title'   => 'nullable|string|max:255',
            'tabs.*.status'      => 'nullable|in:0,1',
        ]);

        foreach ($request->tabs as $id => $data) {
            $tab = WalletTabSetting::find($data['id']);
            if ($tab) {
                $tab->tab_name   = $data['tab_name'];
                $tab->sub_title  = $data['sub_title'] ?? $tab->sub_title;
                $tab->status     = isset($data['status']) && $data['status'] == '1' ? 1 : 0;
                $tab->save();
            }
        }

        return redirect()->back()->with('success', 'Wallet Tab Settings updated successfully.');
    }

    /**
     * AJAX Toggle status (Hide / Show)
     */
    public function toggleStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'     => 'required|exists:wallet_tab_settings,id',
            'status' => 'required|in:0,1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $tab = WalletTabSetting::find($request->id);
        $tab->status = (int) $request->status;
        $tab->save();

        $stateText = $tab->status ? 'shown (visible)' : 'hidden';

        return response()->json([
            'status'  => true,
            'message' => "{$tab->tab_name} tab is now {$stateText} in the mobile app.",
            'data'    => $tab,
        ]);
    }
}
