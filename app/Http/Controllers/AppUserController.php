<?php

namespace App\Http\Controllers;

use App\Models\AppUser;
use App\Models\Theme;
use App\Models\Frame;
use App\Models\EntryTag;
use App\Models\Voice;
use App\Models\DataCard;
use App\Models\ChatBubble;
use App\Models\Cars;
use App\Models\Gift;
use App\Models\Vip;
use App\Models\StoreUids;
use App\Models\PostReport;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Carbon\Carbon;
use App\Helper\Helper;

class AppUserController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {

            $users = AppUser::query()->latest()->whereNull('deleted_at');

            if ($request->uid != '') {
                $users->where('uid', $request->uid);
            }

            if ($request->username != '') {
                $users->where('name', 'LIKE', "%{$request->username}%");
            }

            if ($request->equipment != '') {
                $users->where('equipment_number', $request->equipment);
            }

            if ($request->region != '') {
                $users->where('region', $request->region);
            }

            return DataTables::of($users)
                ->addIndexColumn()

                ->addColumn('user', function ($row) {

                    $image = $row->image
                        ? Helper::showImage($row->image, true)
                        : asset('assets/img/avatar.png');
                
                    return '
                    <div class="d-flex align-items-center gap-2 user-profile-trigger"
                         data-user-id="'.$row->id.'"
                         style="cursor:pointer;">
                
                        <img src="'.$image.'"
                             width="45"
                             height="45"
                             class="rounded-circle">
                
                        <div>
                            <div class="fw-bold">'.$row->name.'</div>
                            <small class="text-muted">'.$row->uid.'</small>
                        </div>
                
                    </div>';
                })

                ->editColumn('disable_status', function ($row) {

                    if ($row->is_blacklisted) {
                        return '<span class="badge bg-dark">
                        <i class="fa fa-ban"></i> Blacklisted
                    </span>';
                    }

                    if ($row->is_disabled) {
                        $until = $row->disabled_until
                            ? \Carbon\Carbon::parse($row->disabled_until)->format('Y-m-d')
                            : 'Permanent';

                        return '<span class="badge bg-danger">
                            <i class="fa fa-clock"></i> Disabled <br>
                            <small>Until: ' . $until . '</small>
                        </span>';
                    }

                    return '<span class="badge bg-success">Active</span>';
                })

                ->editColumn(
                    'created_at',
                    fn($row) =>
                    $row->created_at ? $row->created_at->format('Y-m-d H:i:s') : ''
                )
                ->addColumn('operate', function ($row) {
                    $btn = '<div class="dropdown">
                    <button class="btn btn-sm btn-link dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-ellipsis-h"></i>
                    </button>
                    <div class="dropdown-menu">';

                    if (Helper::userCan(104, 'can_view')) {
                        $btn .= '<a class="dropdown-item" href="' . route('user-details', $row->id) . '">User Details</a>';
                    }

                    if ($row->is_disabled) {
                        $btn .= '<button class="dropdown-item text-success activateUserBtn" 
                            data-id="' . $row->id . '" 
                            data-name="' . e($row->name) . '">
                            Activate User
                        </button>';
                    } else {
                        $btn .= '<button class="dropdown-item text-danger disableUserBtn" 
                            data-id="' . $row->id . '" 
                            data-name="' . e($row->name) . '">
                            Disable User
                        </button>';
                    }

                    if ($row->is_blacklisted) {
                        $btn .= '<span class="dropdown-item text-danger disabled">
                            Blacklisted
                        </span>';
                    } else {
                        $btn .= '<button class="dropdown-item text-dark blacklistUserBtn" 
                            data-id="' . $row->id . '" 
                            data-name="' . e($row->name) . '">
                            Blacklist User
                        </button>';
                    }

                    $btn .= '<a class="dropdown-item" href="' . route('user.edit', $row->id) . '">
                        <i class="fas fa-edit text-primary me-2"></i> Edit
                    </a>';

                    $btn .= '<button class="dropdown-item text-danger delete"
                        data-id="' . $row->id . '"
                        data-name="' . e($row->name) . '">
                        <i class="fas fa-trash me-2"></i> Delete
                    </button>';

                    $btn .= '</div></div>';

                    return $btn;
                })

                ->rawColumns(['user', 'uid', 'disable_status', 'operate'])
                ->make(true);
        }

        return view('app_users.index');
    }

    public function userDetails($id)
    {
        $user = AppUser::find($id);

        return view('app_users.view', compact('user'));
    }

    public function disable(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:app_users,id',
            'reason' => 'required|string|max:255',
            'disabled_until' => 'nullable|date'
        ]);

        $user = AppUser::findOrFail($request->user_id);

        $user->is_disabled = 1;
        $user->disabled_reason = $request->reason;
        $user->disabled_until = $request->disabled_until;
        $user->save();

        return response()->json(['status' => true]);
    }

    public function activate(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:app_users,id'
        ]);

        $user = AppUser::findOrFail($request->user_id);

        $user->is_disabled = 0;
        $user->disabled_reason = null;
        $user->disabled_until = null;
        $user->save();

        return response()->json(['status' => true]);
    }

    public function blacklist(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:app_users,id',
            'reason' => 'required|string'
        ]);

        $user = AppUser::findOrFail($request->user_id);

        $user->is_blacklisted = true;
        $user->blacklist_reason = $request->reason;
        $user->blacklisted_at = now();
        $user->save();

        return response()->json(['status' => true]);
    }

    public function edit($id = null)
    {
        $user = $id ? AppUser::findOrFail($id) : null;
        return view('app_users.form', compact('user'));
    }

    public function save(Request $request, $id = null)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'uid' => 'required|unique:app_users,uid,' . $id,
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:20',
        ]);

        $user = $id ? AppUser::findOrFail($id) : new AppUser();

        $user->name = $request->name;
        $user->uid = $request->uid;
        $user->email = $request->email;
        $user->phone = $request->phone;
        $user->gender = $request->gender;
        $user->country = $request->country;
        $user->region = $request->region;
        $user->birthdate = $request->birthdate;

        // IMAGE UPLOAD
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('users', 'public');
            $user->image = $path;
        }

        $user->save();

        return redirect()
            ->route('app-users')->with('success', $id ? 'User updated successfully' : 'User added successfully');
    }

    public function delete(Request $request)
    {
        return Helper::deleteRecord(new AppUser, $request->id);
    }


    public function userAlbums(Request $request)
    {
        if ($request->ajax()) {

            $query = AppUser::with('albums')
                ->has('albums')
                ->latest();

            return DataTables::of($query)

                // ->addColumn('user', function ($row) {

                //     $image = $row->image
                //         ? Helper::showImage($row->image, true)
                //         : asset('assets/img/avatar.png');

                //     return '
                //     <div class="d-flex align-items-center gap-2">
                //         <img src="'.$image.'"
                //             width="40"
                //             height="40"
                //             class="rounded-circle">

                //         <div>
                //             <div class="fw-bold">'.$row->name.'</div>
                //             <small class="text-muted">'.$row->uid.'</small>
                //         </div>
                //     </div>';
                // })
                ->addColumn('user', function ($row) {

                    $image = $row->image
                        ? Helper::showImage($row->image, true)
                        : asset('assets/img/avatar.png');

                    return '
                    <div class="d-flex align-items-center gap-2 user-profile-trigger"
                         data-user-id="'.$row->id.'"
                         style="cursor:pointer;">

                        <img src="'.$image.'"
                             width="40"
                             height="40"
                             class="rounded-circle">

                        <div>
                            <div class="fw-bold">'.$row->name.'</div>
                            <small class="text-muted">'.$row->uid.'</small>
                        </div>

                    </div>';
                })

                ->addColumn('total_albums', function ($row) {
                    return $row->albums->count();
                })

                ->addColumn('albums', function ($row) {

                    $html = '<div class="d-flex flex-wrap gap-2">';

                    foreach ($row->albums as $album) {

                        $file = asset('storage/' . $album->file);

                        if (str_contains($album->file_type, 'video')) {

                            $html .= '
                                <video class="album-video"
                                       data-video="'.$file.'"
                                       width="45"
                                       height="45"
                                       style="cursor:pointer;border-radius:8px;object-fit:cover;">
                                    <source src="'.$file.'" type="'.$album->file_type.'">
                                </video>
                            ';

                        } else {

                            $html .= '
                                <img src="'.$file.'"
                                     class="album-thumb"
                                     data-image="'.$file.'"
                                     width="45"
                                     height="45"
                                     style="cursor:pointer;border-radius:8px;object-fit:cover;">
                            ';
                        }
                    }

                    $html .= '</div>';

                    return $html;
                })

                ->rawColumns(['user', 'albums'])
                ->make(true);
        }

        return view('app_users.user_albums');
    }


    public function userItems(Request $request)
    {
        if ($request->ajax()) {

            $query = AppUser::with(['deliveredItems', 'giftedItems'])->where(function ($q) {
                $q->has('deliveredItems')->orHas('giftedItems');
            })->latest();

            return DataTables::of($query)

                ->addColumn('user', function ($row) {

                    $image = $row->image
                        ? Helper::showImage($row->image, true)
                        : asset('assets/img/avatar.png');

                    return '
                    <div class="d-flex align-items-center gap-2 user-profile-trigger"
                         data-user-id="'.$row->id.'"
                         style="cursor:pointer;">

                        <img src="'.$image.'"
                             width="40"
                             height="40"
                             class="rounded-circle">

                        <div>
                            <div class="fw-bold">'.$row->name.'</div>
                            <small class="text-muted">'.$row->uid.'</small>
                        </div>

                    </div>';
                })

                ->addColumn('total_items', function ($row) {

                    $today = now();

                    $allItems = $row->deliveredItems
                        ->where('end_at', '>=', $today)
                        ->map(function ($item) {
                            return $item->type . '_' . $item->item_id;
                        })
                        ->merge(
                            $row->giftedItems
                                ->where('end_at', '>=', $today)
                                ->map(function ($item) {
                                    return $item->type . '_' . $item->item_id;
                                })
                        )
                        ->unique();

                    return $allItems->count();
                })

                ->addColumn('items', function ($row) {

                    return '
                        <button class="btn btn-sm btn-primary view-items"
                                data-user="'.$row->id.'">
                            View Items
                        </button>
                    ';
                })

                ->filterColumn('user', function ($query, $keyword) {
                    $query->where(function ($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%")->orWhere('uid', 'like', "%{$keyword}%");
                    });
                })

                ->rawColumns(['user', 'items'])

                ->make(true);
        }

        return view('app_users.user_items');
    }

    public function getUserItems($id)
    {
        $user = AppUser::with(['deliveredItems', 'giftedItems'])->findOrFail($id);

        $today = now();

        $allItems = $user->deliveredItems
            ->filter(function ($item) use ($today) {
                return !empty($item->end_at) && $item->end_at >= $today;
            })
            ->map(function ($item) {
                return [
                    'type'    => $item->type,
                    'item_id' => $item->item_id
                ];
            })
            ->merge(
                $user->giftedItems
                    ->filter(function ($item) use ($today) {
                        return !empty($item->end_at) && $item->end_at >= $today;
                    })
                    ->map(function ($item) {
                        return [
                            'type'    => $item->type,
                            'item_id' => $item->item_id
                        ];
                    })
            );

        $allItems = $allItems->groupBy(function ($item) {
            return $item['type'] . '_' . $item['item_id'];
        });    

        $items = [];

        foreach ($allItems as $group) {

            $item = $group->first();
            $count = $group->count();

            // \Log::info([
            //     'type' => $item['type'],
            //     'item_id' => $item['item_id']
            // ]);

            $image = null;

            switch ($item['type']) {

                case 'theme':

                    $theme = Theme::find($item['item_id']);

                    if ($theme && !empty($theme->icon)) {
                        $image = Helper::showImage($theme->icon, true);
                    }

                    break;

                case 'entry':

                    $car = Cars::find($item['item_id']);

                    if ($car && !empty($car->icon)) {
                        $image = Helper::showImage($car->icon, true);
                    }

                    break;

                case 'entry_tag':

                    $entry = EntryTag::find($item['item_id']);

                    if ($entry && !empty($entry->icon)) {
                        $image = Helper::showImage($entry->icon, true);
                    }

                    break;

                case 'avatar_frame':
                case 'frame':

                    $frame = Frame::find($item['item_id']);

                    if ($frame && !empty($frame->icon)) {
                        $image = Helper::showImage($frame->icon, true);
                    }

                    break;

                case 'voice':

                    $voice = Voice::find($item['item_id']);

                    if ($voice && !empty($voice->icon)) {
                        $image = Helper::showImage($voice->icon, true);
                    }

                    break;

                case 'vip':

                    $vip = Vip::find($item['item_id']);

                    if ($vip && !empty($vip->badge)) {
                        $image = Helper::showImage($vip->badge, true);
                    }

                    break;

                case 'id':

                    $uid = StoreUids::find($item['item_id']);

                    if ($uid && !empty($uid->badge)) {
                        $image = Helper::showImage($uid->badge, true);
                    }

                    break;

                case 'profile_card':

                    $card = DataCard::find($item['item_id']);

                    if ($card && !empty($card->icon)) {
                        $image = Helper::showImage($card->icon, true);
                    }

                    break;

                case 'chat_bubble':

                    $bubble = ChatBubble::find($item['item_id']);

                    if ($bubble && !empty($bubble->icon)) {
                        $image = Helper::showImage($bubble->icon, true);
                    }

                    break;

                case 'car':

                    $car = Cars::find($item['item_id']);

                    if ($car && !empty($car->icon)) {
                        $image = Helper::showImage($car->icon, true);
                    }

                    break;

                case 'gift':

                    $gift = Gift::find($item['item_id']);

                    if ($gift && !empty($gift->icon)) {
                        $image = Helper::showImage($gift->icon, true);
                    }

                    break;
            }

            if (!$image) {
                continue;
            }

            $items[] = [
                'type'  => ucfirst(str_replace('_', ' ', $item['type'])),
                'image' => $image,
                'count' => $count
            ];
        }

        return response()->json([
            'status' => true,
            'data'   => $items
        ]);
    }

    public function postReportList(Request $request)
    {
        if ($request->ajax()) {

            $query = PostReport::with(['user', 'post.user', 'post.media'])->latest();

            return DataTables::of($query)

                ->addIndexColumn()

                // ->addColumn('reporter', function ($row) {

                //     if (!$row->user) {
                //         return '-';
                //     }

                //     $image = $row->user->image
                //         ? Helper::showImage($row->user->image, true)
                //         : asset('assets/img/avatar.png');

                //     return '
                //         <div class="d-flex align-items-center gap-2">
                //             <img src="'.$image.'"
                //                  width="45"
                //                  height="45"
                //                  class="rounded-circle">

                //             <div>
                //                 <div class="fw-bold">'.$row->user->name.'</div>
                //                 <small class="text-muted">UID: '.$row->user->uid.'</small>
                //             </div>
                //         </div>
                //     ';
                // })
                ->addColumn('reporter', function ($row) {

                    if (!$row->user) {return '-';}

                    $image = $row->user->image
                        ? Helper::showImage($row->user->image, true)
                        : asset('assets/img/avatar.png');

                    return '
                        <div class="d-flex align-items-center gap-2 user-profile-trigger"
                             data-user-id="'.$row->user->id.'"
                             style="cursor:pointer;">

                            <img src="'.$image.'"
                                 width="45"
                                 height="45"
                                 class="rounded-circle">

                            <div>
                                <div class="fw-bold">'.$row->user->name.'</div>
                                <small class="text-muted">UID: '.$row->user->uid.'</small>
                            </div>

                        </div>
                    ';
                })

                ->addColumn('post_owner', function ($row) {

                    if (!$row->post) {return '<span class="badge bg-danger">Post Deleted</span>';}

                    $owner = $row->post->user;

                    if (!$owner) {return '<span class="badge bg-warning">User Deleted</span>';}

                    $image = $owner->image
                        ? Helper::showImage($owner->image, true)
                        : asset('assets/img/avatar.png');

                    return '
                        <div class="d-flex align-items-center gap-2 user-profile-trigger"
                            data-user-id="'.$owner->id.'" style="cursor:pointer;">

                            <img src="'.$image.'"width="45" height="45" class="rounded-circle">

                            <div>
                                <div class="fw-bold text-danger">'.$owner->name.'</div>
                                <small class="text-muted">UID: '.$owner->uid.'</small>
                            </div>

                        </div>
                    ';
                })

                ->addColumn('post', function ($row) {

                    if (!$row->post) {return '<span class="badge bg-danger">Post Deleted (#'.$row->post_id.')</span>';}

                    $media = $row->post->media->first();

                    if (!$media) {return '<span class="text-muted">No Media</span>';}

                    $url = Helper::showImage($media->file_path, true);

                    $extension = strtolower(pathinfo($media->file_path, PATHINFO_EXTENSION));

                    // IMAGE
                    if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {

                        return '
                            <a href="'.$url.'" target="_blank">
                                <img src="'.$url.'"width="90" height="90"
                                    style="object-fit:cover; border-radius:10px; border:1px solid #ddd; box-shadow:0 2px 8px rgba(0,0,0,.15);">
                            </a>
                        ';
                    }

                    // VIDEO
                    if (in_array($extension, ['mp4', 'mov', 'avi', 'mkv', 'webm']) || str_contains($media->file_type, 'video') ||
                        $media->file_type == 'application/octet-stream'
                    ) {

                        return '
                            <video width="120" height="90" controls preload="metadata"
                                   style="border-radius:10px; border:1px solid #ddd; background:#000;">
                                <source src="'.$url.'" type="video/mp4">
                            </video>
                        ';
                    }

                    return '
                        <span class="badge bg-secondary">
                            '.$media->file_type.'
                        </span>
                    ';
                })

                ->addColumn('reason', function ($row) {

                    return '
                        <span class="badge bg-danger">
                            '.$row->reason.'
                        </span>
                    ';
                })

                ->addColumn('reported_at', function ($row) {
                    return Carbon::parse($row->created_at)->timezone('Asia/Kolkata')->format('d M Y Y h:i A');
                })

                ->rawColumns(['reporter', 'post_owner', 'post', 'reason', 'reported_at',])
                ->make(true);
        }

        return view('app_users.post_reports');
    }

    // User detail side modal
    public function userProfile($id)
    {
        $user = AppUser::with([
            'premium',
            'host',
            'agency',
            'activeFrame',
            'activeCard',
            'activeTheme',
            'deliveredItems',
            'giftedItems'
        ])->findOrFail($id);

        $items = $this->getUserItemsData($user);

        return response()->json([
            'status' => true,
            'html' => view('components.user_profile', compact('user', 'items'))->render()
        ]);
    }

    private function getUserItemsData($user)
    {
        $today = now();

        $delivered = $user->deliveredItems
            ->filter(fn($item) => !empty($item->end_at) && $item->end_at >= $today)
            ->map(fn($item) => [
                'type' => $item->type,
                'item_id' => $item->item_id
            ])
            ->toBase();

        $gifted = $user->giftedItems
            ->filter(fn($item) => !empty($item->end_at) && $item->end_at >= $today)
            ->map(fn($item) => [
                'type' => $item->type,
                'item_id' => $item->item_id
            ])
            ->toBase();

        $allItems = $delivered->merge($gifted);

        $items = [];

        foreach ($allItems as $item) {

            $image = null;

            switch ($item['type']) {

                case 'theme':
                    $model = Theme::find($item['item_id']);
                    $image = $model?->icon;
                    break;

                case 'frame':
                case 'avatar_frame':
                    $model = Frame::find($item['item_id']);
                    $image = $model?->icon;
                    break;

                case 'profile_card':
                    $model = DataCard::find($item['item_id']);
                    $image = $model?->icon;
                    break;

                case 'chat_bubble':
                    $model = ChatBubble::find($item['item_id']);
                    $image = $model?->icon;
                    break;

                case 'voice':
                    $model = Voice::find($item['item_id']);
                    $image = $model?->icon;
                    break;

                case 'vip':
                    $model = Vip::find($item['item_id']);
                    $image = $model?->badge;
                    break;

                case 'entry':
                case 'car':
                    $model = Cars::find($item['item_id']);
                    $image = $model?->icon;
                    break;

                case 'entry_tag':
                    $model = EntryTag::find($item['item_id']);
                    $image = $model?->icon;
                    break;

                case 'gift':
                    $model = Gift::find($item['item_id']);
                    $image = $model?->icon;
                    break;

                case 'id':
                    $model = StoreUids::find($item['item_id']);
                    $image = $model?->badge;
                    break;
            }

            if ($image) {
                $items[] = [
                    'type' => ucfirst(str_replace('_', ' ', $item['type'])),
                    'image' => Helper::showImage($image, true)
                ];
            }
        }

        return collect($items)->unique('image')->values();
    }
}
