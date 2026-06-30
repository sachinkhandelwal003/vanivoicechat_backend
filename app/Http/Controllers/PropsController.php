<?php

namespace App\Http\Controllers;

use App\Helper\Helper;
use App\Models\Theme;
use App\Models\Frame;
use App\Models\Cars;
use App\Models\ChatBubble;
use App\Models\DataCard;
use App\Models\EntryTag;
use App\Models\StoreUids;
use App\Models\Voice;
use App\Models\AppUser;
use App\Models\ItemDelivery;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class PropsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        return view('props.item_delivery');
    }

    public function getItems(Request $request, $type)
    {
        $search = $request->search;
        $page = $request->page ?? 1;

        $map = [
            'theme' => Theme::class,
            'frame' => Frame::class,
            'entry' => Cars::class,
            'chat bubble' => ChatBubble::class,
            'profile card' => DataCard::class,
            'entry tag' => EntryTag::class,
            'voice' => Voice::class,
            'id' => StoreUids::class,
        ];

        if (!isset($map[$type])) return response()->json([]);

        $model = $map[$type];

        $query = $model::query();

        if ($search) {
            $query->where('name', 'like', "%$search%");
        }

        $items = $query->paginate(12);

        $items->getCollection()->transform(function ($row) use ($type) {

            // UID
            if ($type === 'id') {
                return [
                    'id' => $row->id,
                    'title' => $row->unique_id,
                    'preview' => null,
                    'type' => 'text'
                ];
            }

            // Voice
            if ($type === 'voice') {
                return [
                    'id' => $row->id,
                    'title' => $row->name,
                    'preview' => Helper::ShowImage($row->voice, true),
                    'type' => 'audio'
                ];
            }

            // Image/Icon
            return [
                'id' => $row->id,
                'title' => $row->name,
                'preview' => Helper::ShowImage($row->icon, true),
                'type' => 'image'
            ];
        });

        return response()->json($items);
    }

    public function store(Request $request)
    {
        $request->validate([
            'recipient'   => 'required|string', // comma separated UIDs
            'type'        => 'required|string',
            'resource_id' => 'required|integer',
            'valid_days'  => 'required|integer|min:1'
        ]);

        // Split comma separated UIDs
        $uids = array_filter(array_map('trim', explode(',', $request->recipient)));

        if (!$uids) {
            return back()->with('error', 'No valid user UIDs provided');
        }

        // Type → Model Map
        $map = [
            'theme'        => Theme::class,
            'frame'        => Frame::class,
            'entry'        => Cars::class,
            'chat bubble'  => ChatBubble::class,
            'profile card' => DataCard::class,
            'entry tag'    => EntryTag::class,
            'voice'        => Voice::class,
            'id'           => StoreUids::class,
        ];

        if (!isset($map[$request->type])) {
            return back()->with('error', 'Invalid item type');
        }

        $model = $map[$request->type];

        $item = $model::findOrFail($request->resource_id);

        // Validate validity days
        $validity = array_map('intval', $item->validity ?? []);
        $prices   = array_map('intval', $item->needcoin ?? []);

        $index = array_search((int)$request->valid_days, $validity, true);

        if ($index === false || !isset($prices[$index])) {
            return back()->with('error', 'Invalid validity duration');
        }

        $needCoin = $prices[$index];

        $success = [];
        $failed  = [];

        DB::beginTransaction();

        try {

            foreach ($uids as $uid) {

                // Find user by UID
                $user = AppUser::where('uid', $uid)
                    ->lockForUpdate()
                    ->first();

                if (!$user) {
                    $failed[] = "$uid (user not found)";
                    continue;
                }

                // Check points
                if ($user->total_points < $needCoin) {
                    $failed[] = "$uid (insufficient points)";
                    continue;
                }

                // Prevent duplicate active item
                $already = ItemDelivery::where('recipient', $user->id) // STORE USER ID
                    ->where('type', $request->type)
                    ->where('item_id', $request->resource_id)
                    ->where('end_at', '>', now())
                    ->exists();

                if ($already) {
                    $failed[] = "$uid (already active)";
                    continue;
                }

                // Deduct points
                $user->decrement('total_points', $needCoin);
                $validDays = (int) $request->valid_days;
                // Save delivery using USER ID
                ItemDelivery::create([
                    'recipient'  => $user->id, // STORE INTERNAL USER ID
                    'type'       => $request->type,
                    'item_id'    => $request->resource_id,
                    'valid_days' => $request->valid_days,
                    'start_at'   => now(),
                    'end_at'     => now()->addDays($validDays),
                    'coins_used' => $needCoin,
                    'source'     => 'admin'
                ]);

                $success[] = $uid;
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Transaction failed: ' . $e->getMessage());
        }

        // Response message
        $msg = count($success) . " users received item successfully.";

        if ($failed) {
            $msg .= " Failed: " . implode(', ', $failed);
        }

        return back()->with('success', $msg);
    }
}
