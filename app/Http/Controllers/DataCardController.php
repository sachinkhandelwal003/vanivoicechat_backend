<?php

namespace App\Http\Controllers;


use App\Helper\Helper;
use App\Models\DataCard;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class DataCardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {

            $query = DataCard::latest();

            return DataTables::of($query)
                ->addIndexColumn()
                ->editColumn('icon', function ($row) {
                    $image = asset('storage/' . $row->icon);
                    return '
                        <img src="'.$image.'"
                             width="40"
                             height="40"
                             class="image-preview"
                             data-image="'.$image.'"
                             style="cursor:pointer;border-radius:6px;object-fit:cover;">
                    ';
                })
                ->editColumn('status', function ($row) {
                    return $row['status'] == 1 ? '<small class="badge fw-semi-bold rounded-pill status badge-light-success"> Enable</small>' : '<small class="badge fw-semi-bold rounded-pill status badge-light-danger"> Disable</small>';
                })
                ->editColumn('validity', function ($row) {
                    return $row['validity'] ? $row['validity'] : '-';
                })
                ->addColumn('action', function ($row) {
                    $btn = '<div class="dropdown">
                    <button class="btn btn-sm btn-link dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-ellipsis-h"></i>
                    </button>
                    <div class="dropdown-menu">';

                    if (Helper::userCan(118, 'can_edit')) {
                        $btn .= '<a class="dropdown-item" href="' . route('data.card.edit', $row->id) . '">Edit</a>';
                    }

                    if (Helper::userCan(118, 'can_delete')) {
                        $btn .= '<button class="dropdown-item text-danger delete" data-id="' . $row->id . '">Delete</button>';
                    }

                    $btn .= '</div></div>';

                    return $btn;
                })
                ->rawColumns(['icon', 'status', 'action'])
                ->make(true);
        }

        return view('data_card.index');
    }

    public function add(): View
    {
        return view('data_card.add');
    }


    public function save(Request $request)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'short_tag' => 'required|string|max:255',
            'visibility_type' => 'required|in:backend,in_app',
            'icon' => 'required|image|mimes:png,jpg,jpeg,webp',
            'animation' => 'required',
            'status' => 'required|in:0,1',
        ];

        if ($request->visibility_type == 'in_app') {

            $rules['needcoin'] = 'required|array|min:1';
            $rules['needcoin.*'] = 'required|integer|min:1|max:9999999';

            $rules['validity'] = 'required|array|min:1';
            $rules['validity.*'] = 'required|integer|min:1';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        return DB::transaction(function () use ($request) {

            $icon = Helper::saveFile($request->file('icon'), 'data_card_images');
            $animation = Helper::saveFile($request->file('animation'), 'data_card_images');

            DataCard::create([
                'name' => $request->name,
                'short_tag' => $request->short_tag,
                'visibility_type' => $request->visibility_type,
                'status' => $request->status,
                'icon' => $icon,
                'gif' => $animation,
                'needcoin' => $request->visibility_type === 'in_app'
                    ? array_values($request->needcoin)
                    : null,

                'validity' => $request->visibility_type === 'in_app'
                    ? array_values($request->validity)
                    : null,
            ]);

            return redirect()
                ->route('data.card')
                ->with('success', 'Data Card added successfully');
        });
    }
    public function getDataCards(Request $request)
    {
        try {
            $user = Auth::user(); // 👈 login user

            $cards = \App\Models\DataCard::where('status', 1)
                ->latest()
                ->get();

            $data = $cards->map(function ($card) {
                return [
                    'id' => $card->id,
                    'name' => $card->name,
                    'short_tag' => $card->short_tag,
                    'validity' => $card->validity,
                    'visibility_type' => $card->visibility_type,
                    'needcoin' => $card->needcoin,
                    'icon' => \App\Helper\Helper::showImage($card->icon, true),
                    'gif' => \App\Helper\Helper::showImage($card->gif, true),
                    'status' => $card->status,
                    'created_at' => $card->created_at,
                ];
            });

            return response()->json([
                'status' => true,
                'message' => 'Data cards fetched successfully',

                // 👇 user info
                'user' => [
                    'id' => $user->id ?? null,
                    'name' => $user->name ?? null,
                    'image' => \App\Helper\Helper::showImage($user->image ?? null, true),
                ],

                'data' => $data
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function edit($id): View|RedirectResponse
    {
        $dataCard = DataCard::find($id);

        if (!$dataCard) {
            return to_route('data.card')->withError('Data Card Not Found!');
        }
        return view('data_card.edit', compact('dataCard'));
    }

    public function update(Request $request, $id)
    {
        $dataCard = DataCard::findOrFail($id);

        $rules = [
            'name' => 'required|string|max:255',
            'visibility_type' => 'required|in:backend,in_app',
            'icon' => 'nullable|image|mimes:png,jpg,jpeg,webp',
            'animation' => 'nullable',
            'status' => 'required|in:0,1',
        ];

        if ($request->visibility_type == 'in_app') {

            $rules['needcoin'] = 'required|array|min:1';
            $rules['needcoin.*'] = 'required|integer|min:1|max:9999999';

            $rules['validity'] = 'required|array|min:1';
            $rules['validity.*'] = 'required|integer|min:1';
        }

        $request->validate($rules);

        return DB::transaction(function () use ($request, $dataCard) {

            $data = [
                'name' => $request->name,
                'visibility_type' => $request->visibility_type,
                'status' => $request->status,
            ];
            if ($request->visibility_type === 'in_app') {
                $data['needcoin'] = array_values($request->needcoin);
                $data['validity'] = array_values($request->validity);
            } else {
                $data['needcoin'] = null;
                $data['validity'] = null;
            }

            if ($request->hasFile('icon')) {

                if ($dataCard->icon && file_exists(public_path($dataCard->icon))) {
                    @unlink(public_path($dataCard->icon));
                }

                $data['icon'] = Helper::saveFile($request->file('icon'), 'data_card_images');
            }

            if ($request->hasFile('animation')) {

                if ($dataCard->gif && file_exists(public_path($dataCard->gif))) {
                    @unlink(public_path($dataCard->gif));
                }

                $data['gif'] = Helper::saveFile($request->file('animation'), 'data_card_images');
            }


            $dataCard->update($data);

            return redirect()->route('data.card')->with('success', 'Data Card updated successfully');
        });
    }


    public function delete(Request $request): JsonResponse
    {
        return Helper::deleteRecord(new DataCard, $request->id);
    }
}
