<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Broadcast;
use App\Models\BroadcastPrice;
use App\Models\Country;
use App\Helper\Helper;
use Illuminate\View\View;
use Illuminate\Http\Request;
use \Yajra\Datatables\Datatables;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\RedirectResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;



class BroadcastController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            
            $data = Broadcast::with('user')->latest();

            return Datatables::of($data)

                ->addIndexColumn()

                ->addColumn('user_info', function ($row) {

                    if (!$row->user) {return '-';}

                    $image = $row->user->image
                        ? Helper::showImage($row->user->image, true)
                        : asset('assets/img/avatar.png');

                    return '
                        <div class="d-flex align-items-center gap-2 user-profile-trigger"
                             data-user-id="'.$row->user->id.'" style="cursor:pointer;">

                            <img src="'.$image.'" width="40" height="40" class="rounded-circle">

                            <div>
                                <div class="fw-bold">'.e($row->user->name).'</div>
                                <small class="text-muted">'.e($row->user->uid).'</small>
                            </div>

                        </div>
                    ';
                })
                
                ->addColumn('time', function ($row) {
                    return '
                        <div>
                            <div class="text-muted small">Broadcast time:</div>
                            <div>' . Carbon::parse($row->created_at)->format('Y-m-d') . '</div>
                            <div>' . Carbon::parse($row->created_at)->format('H:i:s') . '</div>
                        </div>
                    ';
                })
                ->addColumn('action', function ($row) {

                    $btn = '<button class="text-600 btn-reveal dropdown-toggle btn btn-link btn-sm" id="drop" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="fas fa-ellipsis-h fs--1"></span></button><div class="dropdown-menu" aria-labelledby="drop">';
                    // if (Helper::userCan(104, 'can_edit')) {
                    //     $btn .= '<a class="dropdown-item" href="' . route('categories.edit', $row['id']) . '">Edit</a>';
                    // }
                    if (Helper::userCan(105, 'can_delete')) {
                        $btn .= '<button class="dropdown-item text-danger delete" data-id="' . $row['id'] . '">Delete</button>';
                    }

                    if (Helper::userAllowed(104)) {
                        return $btn;
                    } else {
                        return '';
                    }
                })
                ->orderColumn('created_at', function ($query, $order) {
                    $query->orderBy('created_at', $order);
                })
                ->rawColumns(['action', 'user_info', 'time'])
                ->make(true);
        }
        return view('broadcast.index');
    }


    public function delete(Request $request): JsonResponse
    {
        $broadcast = Broadcast::find($request->id);

        if (!$broadcast) {
            return response()->json(['status' => false, 'message' => 'Broadcast not found'], 404);
        }

        $broadcast->delete();

        return response()->json([
            'status' => true,
            'message' => 'Broadcast deleted successfully'
        ]);
    }

    public function BroadcastPriceIndex(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            $data = BroadcastPrice::latest();
            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    $btn = '<button class="text-600 btn-reveal dropdown-toggle btn btn-link btn-sm" id="drop" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="fas fa-ellipsis-h fs--1"></span></button><div class="dropdown-menu" aria-labelledby="drop">';
                    if (Helper::userCan(104, 'can_edit')) {
                        $btn .= '<a class="dropdown-item" href="' . route('broadcast-price.edit', $row['id']) . '">Edit</a>';
                    }
                    if (Helper::userCan(105, 'can_delete')) {
                        $btn .= '<button class="dropdown-item text-danger delete" data-id="' . $row['id'] . '">Delete</button>';
                    }

                    if (Helper::userAllowed(104)) {
                        return $btn;
                    } else {
                        return '';
                    }
                })
                ->orderColumn('created_at', function ($query, $order) {
                    $query->orderBy('created_at', $order);
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        return view('broadcast.broadcast_price.index');
    }

    public function BroadcastPriceAdd(): View
    {
        $country = Country::get();
        return view('broadcast.broadcast_price.add', compact('country'));
    }

    public function BroadcastPriceSave(Request $request)
    {
        $rules = [
            'region_code' => 'required|unique:broadcast_prices,region_code',
            'price' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        return DB::transaction(function () use ($request) {


            BroadcastPrice::create([
                'region_code'   => $request->region_code,
                'price'  => $request->price,
            ]);

            return redirect()
                ->route('broadcast-price')
                ->with('success', 'Broadcast Price added successfully');
        });
    }


    public function BroadcastPriceEdit($id): View|RedirectResponse
    {
        $bPrice = BroadcastPrice::find($id);

        if (!$bPrice) {
            return to_route('broadcast-price')->withError('Broadcast Price Not Found!');
        }
        $country = Country::get();
        return view('broadcast.broadcast_price.edit', compact('bPrice', 'country'));
    }

    public function BroadcastPriceUpdate(Request $request, $id)
    {
        $bPrice = BroadcastPrice::findOrFail($id);

        $rules = [
            'region_code' => 'required|unique:broadcast_prices,region_code,' . $id,
            'price' => 'required',
        ];

        $request->validate($rules);

        return DB::transaction(function () use ($request, $bPrice) {

            $data = [
                'region_code'   => $request->region_code,
                'price'  => $request->price,
            ];

            $bPrice->update($data);

            return redirect()->route('broadcast-price')->with('success', 'Broadcast Price updated successfully');
        });
    }

    public function BroadcastPriceDelete(Request $request): JsonResponse
    {
        $broadcast = BroadcastPrice::find($request->id);

        if (!$broadcast) {
            return response()->json(['status' => false, 'message' => 'Broadcast Price not found'], 404);
        }

        $broadcast->delete();

        return response()->json([
            'status' => true,
            'message' => 'Broadcast Price deleted successfully'
        ]);
    }
}
