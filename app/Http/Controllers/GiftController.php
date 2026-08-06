<?php

namespace App\Http\Controllers;

use App\Models\Gift;
use App\Models\GiftTransaction;
use App\Models\Country;
use App\Helper\Helper;
use App\Models\LuckyGiftWinningSetting;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class GiftController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {

            $query = Gift::latest();

            return DataTables::of($query)
                ->addIndexColumn()
                // ->editColumn('cover', function ($row) {
                //     return '<img src="' . asset('storage/' . $row->cover) . '" width="40">';
                // })
                ->editColumn('cover', function ($row) {

                    $image = asset('storage/' . $row->cover);

                    return '
                        <img src="' . $image . '"
                             width="40"
                             height="40"
                             class="image-preview"
                             data-image="' . $image . '"
                             style="cursor:pointer;border-radius:6px;object-fit:cover;">
                    ';
                })

                ->editColumn('status', function ($row) {
                    return $row['status'] == 1 ? '<small class="badge fw-semi-bold rounded-pill status badge-light-success"> Active</small>' : '<small class="badge fw-semi-bold rounded-pill status badge-light-danger"> Inactive</small>';
                })

                ->addColumn('action', function ($row) {
                    $btn = '<div class="dropdown">
                    <button class="btn btn-sm btn-link dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-ellipsis-h"></i>
                    </button>
                    <div class="dropdown-menu">';

                    // if (Helper::userCan(104, 'can_edit')) {
                    //     $btn .= '<a class="dropdown-item" href="' . route('lucky-gift-setting', $row->id) . '">Winning Setting</a>';
                    // }

                    if (Helper::userCan(113, 'can_edit')) {
                        $btn .= '<a class="dropdown-item" href="' . route('gift.edit', $row->id) . '">Edit</a>';
                    }

                    if (Helper::userCan(113, 'can_delete')) {
                        $btn .= '<button class="dropdown-item text-danger delete" data-id="' . $row->id . '">Delete</button>';
                    }

                    $btn .= '</div></div>';

                    return $btn;
                })
                ->rawColumns(['cover', 'status', 'action'])
                ->make(true);
        }

        return view('gift.index');
    }

    public function add(): View
    {
        return view('gift.add');
    }

    public function save(Request $request)
    {
        $rules = [
            'gift_type' => 'required|in:ordinary,luxury,hand_painted',
            'logo'      => 'required|in:gift,lucky,cp,national,activity',
            'name'      => 'required|string|max:255',

            'cover'     => 'required|image|mimes:jpg,jpeg,png,webp',
            'price'     => 'required|numeric',
            'status'    => 'required|in:0,1',

            'animation_type'     => 'required_if:gift_type,luxury|nullable|in:gif,svga',
            'gif_image'          => 'nullable|file|mimes:gif',

            // svga me mimes mat lagao
            'svga_file'          => 'nullable|file',
            'svga_path'          => 'nullable|string',

            'animation_duration' => 'nullable|numeric|min:0',
        ];

        $validator = Validator::make($request->all(), $rules);

        $validator->after(function ($validator) use ($request) {

            if ($request->gift_type === 'luxury' && $request->animation_type === 'svga') {

                if ($request->hasFile('svga_file')) {
                    $ext = strtolower($request->file('svga_file')->getClientOriginalExtension());

                    if ($ext !== 'svga') {
                        $validator->errors()->add('svga_file', 'Please upload only .svga file');
                    }
                }

                if (!$request->hasFile('svga_file') && empty($request->svga_path)) {
                    $validator->errors()->add('svga_path', 'Please upload SVGA file or enter full SVGA URL');
                }
            }

            if ($request->gift_type === 'luxury' && $request->animation_type === 'gif') {
                if (!$request->hasFile('gif_image')) {
                    $validator->errors()->add('gif_image', 'Please upload a GIF file');
                }
            }
        });

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        if ($request->gift_type === 'luxury') {

            if ($request->animation_type === 'gif') {
                if (!$request->hasFile('gif_image')) {
                    return back()->withErrors([
                        'gif_image' => 'Please upload a GIF file'
                    ])->withInput();
                }
            }

            if ($request->animation_type === 'svga') {
                if (
                    !$request->hasFile('svga_file') &&
                    empty($request->svga_path)
                ) {
                    return back()->withErrors([
                        'svga_path' => 'Please upload SVGA file or enter full SVGA URL'
                    ])->withInput();
                }
            }
        }

        return DB::transaction(function () use ($request) {

            $cover = Helper::saveFile(
                $request->file('cover'),
                'gift_images'
            );

            $animationFile = null;

            if ($request->gift_type === 'luxury') {

                if ($request->animation_type === 'gif') {

                    $animationFile = Helper::saveFile(
                        $request->file('gif_image'),
                        'gift_animations/gif'
                    );
                }

                if ($request->animation_type === 'svga') {

                    if ($request->hasFile('svga_file')) {
                        $animationFile = Helper::saveFile(
                            $request->file('svga_file'),
                            'gift_animations/svga'
                        );
                    } else {
                        $animationFile = $request->svga_path;
                    }
                }
            }

            Gift::create([
                'gift_type'          => $request->gift_type,
                'cover_type'         => $request->logo,
                'name'               => $request->name,
                'cover'              => $cover,
                'price'              => $request->price,
                'status'             => $request->status,

                'animation_type'     => $request->gift_type === 'luxury'
                    ? $request->animation_type
                    : null,

                'file_path'          => $animationFile,
                'animation_duration' => $request->animation_duration ?? 0,
            ]);

            return redirect()
                ->route('gift')
                ->with('success', 'Gift added successfully');
        });
    }


    public function edit($id): View|RedirectResponse
    {
        $gift = Gift::find($id);

        if (!$gift) {
            return to_route('gift')->withError('Gift Not Found!');
        }
        return view('gift.edit', compact('gift'));
    }

    public function update(Request $request, $id)
    {
        $gift = Gift::findOrFail($id);

        $rules = [
            'gift_type' => 'required|in:ordinary,luxury,hand_painted',
            'logo'      => 'required|in:gift,lucky,cp,national,activity',
            'name'      => 'required|string|max:255',

            'cover'     => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'price'     => 'required|numeric',
            'status'    => 'required|in:0,1',

            'animation_type'     => 'nullable|in:gif,svga',
            'gif_image'          => 'nullable|file|mimes:gif|max:4096',
            'gif_path'           => 'nullable|string',
            'svga_path'          => 'nullable|string',
            'svga_file'          => 'nullable|file|mimes:svga|max:5120',

            'animation_duration' => 'nullable|numeric|min:0',
        ];

        $request->validate($rules);

        return DB::transaction(function () use ($request, $gift) {

            $data = [
                'gift_type'          => $request->gift_type,
                'cover_type'         => $request->logo,
                'name'               => $request->name,
                'price'              => $request->price,
                'status'             => $request->status,
                'animation_duration' => $request->animation_duration,
            ];

            if ($request->hasFile('cover')) {
                $data['cover'] = Helper::saveFile(
                    $request->file('cover'),
                    'gift_images'
                );
            } else {
                $data['cover'] = $gift->cover;
            }

            $data['animation_type'] = null;
            $data['file_path']      = null;

            if ($request->gift_type === 'luxury') {

                $data['animation_type'] = $request->animation_type;
                $data['file_path']      = $gift->file_path;

                if ($request->animation_type === 'gif') {

                    if ($request->hasFile('gif_image')) {
                        $data['file_path'] = Helper::saveFile(
                            $request->file('gif_image'),
                            'gift_animations/gif'
                        );
                    } elseif (!empty($request->gif_path)) {
                        $data['file_path'] = $request->gif_path;
                    }
                }

                if ($request->animation_type === 'svga') {

                    if ($request->hasFile('svga_file')) {
                        $data['file_path'] = Helper::saveFile(
                            $request->file('svga_file'),
                            'gift_animations/svga'
                        );
                    } elseif (!empty($request->svga_path)) {
                        $data['file_path'] = $request->svga_path;
                    }
                }
            }

            $gift->update($data);

            return redirect()
                ->route('gift')
                ->with('success', 'Gift updated successfully');
        });
    }



    public function delete(Request $request): JsonResponse
    {
        return Helper::deleteRecord(new Gift, $request->id);
    }

    public function luckyGiftSettingindex(Request $request, $id): View|JsonResponse
    {
        $giftId = $id;
        if ($request->ajax()) {

            $query = LuckyGiftWinningSetting::latest();

            return DataTables::of($query)
                ->addIndexColumn()
                ->editColumn('is_whole_site', function ($row) {
                    return $row['status'] == 1 ? '<small class="badge fw-semi-bold rounded-pill status badge-light-success"> Yes</small>' : '<small class="badge fw-semi-bold rounded-pill status badge-light-danger"> No</small>';
                })
                ->addColumn('action', function ($row) {
                    $btn = '<div class="dropdown">
                    <button class="btn btn-sm btn-link dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-ellipsis-h"></i>
                    </button>
                    <div class="dropdown-menu">';


                    if (Helper::userCan(113, 'can_edit')) {
                        $btn .= '<a class="dropdown-item" href="' . route('lucky-gift-setting.edit', $row->id) . '">Edit</a>';
                    }

                    if (Helper::userCan(113, 'can_delete')) {
                        $btn .= '<button class="dropdown-item text-danger delete" data-id="' . $row->id . '">Delete</button>';
                    }

                    $btn .= '</div></div>';

                    return $btn;
                })
                ->rawColumns(['is_whole_site', 'action'])
                ->make(true);
        }

        return view('gift.lucky_gift_setting.index', compact('giftId'));
    }

    public function luckyGiftSettingAdd($id): View
    {
        $giftId = $id;
        return view('gift.lucky_gift_setting.add', compact('giftId'));
    }

    public function luckyGiftSettingSave(Request $request, $id)
    {

        $rules = [
            'quantity'    => 'required|integer|min:1',
            'multiple'    => 'required|integer|min:1',
            'probability' => 'required|numeric|min:0.001|max:100',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        return DB::transaction(function () use ($request, $id) {

            LuckyGiftWinningSetting::create([
                'gift_id'       => $request->gift_id,
                'quantity'      => $request->quantity,
                'multiple'      => $request->multiple,
                'is_whole_site' => $request->is_whole_site,
                'probability'   => $request->probability,
            ]);

            return redirect()
                ->route('lucky-gift-setting', $id)
                ->with('success', 'Lucky Gift Winning Setting added successfully');
        });
    }

    public function luckyGiftSettingEdit($id): View|RedirectResponse
    {
        $luckyGift = LuckyGiftWinningSetting::find($id);

        if (!$luckyGift) {
            return to_route('lucky-gift-setting', $luckyGift->gift_id)->withError('Lucky Gift Winning Setting Not Found!');
        }
        return view('gift.lucky_gift_setting.edit', compact('luckyGift'));
    }

    public function luckyGiftSettingUpdate(Request $request, $id)
    {
        $luckyGift = LuckyGiftWinningSetting::findOrFail($id);

        $rules = [
            'quantity'    => 'required|integer|min:1',
            'multiple'    => 'required|integer|min:1',
            'probability' => 'required|numeric|min:0.001|max:100',
        ];

        $request->validate($rules);

        return DB::transaction(function () use ($request, $luckyGift) {

            $data = [
                'quantity'      => $request->quantity,
                'multiple'      => $request->multiple,
                'is_whole_site' => $request->is_whole_site,
                'probability'   => $request->probability,
            ];

            $luckyGift->update($data);

            return redirect()->route('lucky-gift-setting', $luckyGift->gift_id)->with('success', 'Lucky Gift Winning Setting updated successfully');
        });
    }

    public function luckyGiftSettingDelete(Request $request): JsonResponse
    {
        return Helper::deleteRecord(new LuckyGiftWinningSetting, $request->id);
    }

    public function giftRecords(Request $request): View|JsonResponse
    {
        // dd('ok');
        if ($request->ajax()) {

            $query = GiftTransaction::with([
                'sender:id,name,uid,image',
                'receiver:id,name,uid,image',
                'gift:id,name,cover,file_path,price,gift_type'
            ])->latest();

            return DataTables::of($query)
                ->addIndexColumn()

                // ->addColumn('sender', function ($row) {

                //     if (!$row->sender) {
                //         return '-';
                //     }

                //     $image = $row->sender->image
                //         ? Helper::showImage($row->sender->image, true)
                //         : asset('assets/img/avatar.png');

                //     return '
                //         <div class="d-flex align-items-center gap-2 user-profile-trigger"
                //              data-user-id="' . $row->sender->id . '"
                //              style="cursor:pointer;">

                //             <img src="' . $image . '"
                //                  width="45"
                //                  height="45"
                //                  class="rounded-circle">

                //             <div>
                //                 <div class="fw-bold">' . $row->sender->name . '</div>
                //                 <small class="text-muted">' . $row->sender->uid . '</small>
                //             </div>

                //         </div>
                //     ';
                // })

                // ->addColumn('receiver', function ($row) {

                //     if (!$row->receiver) {
                //         return '-';
                //     }

                //     $image = $row->receiver->image
                //         ? Helper::showImage($row->receiver->image, true)
                //         : asset('assets/img/avatar.png');

                //     return '
                //         <div class="d-flex align-items-center gap-2 user-profile-trigger"
                //             data-user-id="' . $row->receiver->id . '"
                //             style="cursor:pointer;">

                //             <img src="' . $image . '"
                //                 width="45"
                //                 height="45"
                //                 class="rounded-circle">

                //             <div>
                //                 <div class="fw-bold">' . $row->receiver->name . '</div>
                //                 <small class="text-muted">' . $row->receiver->uid . '</small>
                //             </div>

                //         </div>
                //     ';
                // })


                ->addColumn('sender', function ($row) {

                    if (!$row->sender) {
                        return '-';
                    }

                    $user = $row->sender;

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
                                width="45"
                                height="45"
                                class="rounded-circle">

                            <div>
                                <div class="fw-bold">' . e($user->name) . '</div>
                                ' . $uidHtml . '
                            </div>

                        </div>
                    ';
                })

                ->addColumn('receiver', function ($row) {

                    if (!$row->receiver) {
                        return '-';
                    }

                    $user = $row->receiver;

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
                                width="45"
                                height="45"
                                class="rounded-circle">

                            <div>
                                <div class="fw-bold">' . e($user->name) . '</div>
                                ' . $uidHtml . '
                            </div>

                        </div>
                    ';
                })

                ->addColumn('gift_type', function ($row) {

                    return $row->gift->gift_type ?? '-';
                })

                ->addColumn('number_of_gifts', function ($row) {

                    $giftImage = '';

                    if ($row->gift && !empty($row->gift->cover)) {
                        $giftImage = Helper::showImage($row->gift->cover, true);
                    }

                    $multiplier = $row->multiplier ?? 1;

                    return '
                        <div class="d-flex align-items-center gap-1">
                            <img src="' . $giftImage . '"
                                 width="35"
                                 height="35"
                                 style="object-fit:cover;border-radius:6px;">

                            <span>x' . $multiplier . '</span>
                        </div>
                    ';
                })

                ->addColumn('number_of_recipients', function ($row) {
                    return '1 person';
                })

                ->addColumn('unit_price', function ($row) {
                    return number_format((float)($row->coin_value ?? 0), 2);
                })

                ->addColumn('total_price', function ($row) {
                    return number_format((float)($row->total_value ?? 0), 0);
                })

                ->addColumn('time', function ($row) {
                    return '
                    <div>
                        <div>Creation time: ' . optional($row->created_at)->format('Y-m-d') . '</div>
                        <small class="text-muted">' . optional($row->created_at)->format('H:i:s') . '</small>
                    </div>
                ';
                })

                ->addColumn('action', function ($row) {
                    $btn = '<div class="dropdown">
                    <button class="btn btn-sm btn-link dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-ellipsis-h"></i>
                    </button>
                    <div class="dropdown-menu">';

                    if (Helper::userCan(108, 'can_view')) {
                        $btn .= '<a class="dropdown-item" href="' . route('gift.details', $row->id) . '">Details</a>';
                    }

                    $btn .= '</div></div>';

                    return $btn;
                })

                ->rawColumns([
                    'sender',
                    'number_of_gifts',
                    'number_of_recipients',
                    'unit_price',
                    'total_price',
                    'time',
                    'gift_type',
                    'receiver',
                    'action'
                ])
                ->make(true);
        }

        return view('gift.gift_flow_index');
    }

    public function giftDetails($id)
    {
        $giftTransaction = GiftTransaction::with([
            'sender:id,name,uid,image,country',
            'receiver:id,name,uid,image,country',
            'gift:id,name,cover,file_path,price',
            'room' => function ($q) {
                $q->withCount('activeMembers')->with('user:id,name,uid,image');
            },
        ])->find($id);

        if (!$giftTransaction) {
            return redirect()->route('gift.giftrecords')->with('error', 'Gift transaction not found.');
        }

        return view('gift.gift_details', compact('giftTransaction'));
    }
}
