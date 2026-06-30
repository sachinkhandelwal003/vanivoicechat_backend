<?php

namespace App\Http\Controllers;

use App\Models\Gift;
use App\Models\Country;
use App\Helper\Helper;
use App\Models\Cars;
use App\Models\LuckyGiftWinningSetting;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Services\FirebaseService;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;


class CarController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {

            $query = Cars::latest();

            return DataTables::of($query)
                ->addIndexColumn()
                ->editColumn('icon', function ($row) {
                    return '<img src="' . asset('storage/' . $row->icon) . '" width="40">';
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

                    if (Helper::userCan(104, 'can_edit')) {
                        $btn .= '<a class="dropdown-item" href="' . route('cars.edit', $row->id) . '">Edit</a>';
                    }

                    if (Helper::userCan(105, 'can_delete')) {
                        $btn .= '<button class="dropdown-item text-danger delete" data-id="' . $row->id . '">Delete</button>';
                    }

                    $btn .= '</div></div>';

                    return $btn;
                })
                ->rawColumns(['icon', 'status', 'action'])
                ->make(true);
        }

        return view('cars.index');
    }

    public function add(): View
    {
        return view('cars.add');
    }

    public function save(Request $request)
    {
        $rules = [
            'name'            => 'required|string|max:255',
            'visibility_type' => 'required|in:backend,in_app',
            'icon'            => 'required|image|mimes:png,jpg,jpeg,webp',
            'animation'       => 'required',
            'status'          => 'required|in:0,1',
        ];

        if ($request->visibility_type == 'in_app') {

            $rules['needcoin']   = 'required|array|min:1';
            $rules['needcoin.*'] = 'required|integer|min:1|max:9999999';

            $rules['validity']   = 'required|array|min:1';
            $rules['validity.*'] = 'required|integer|min:1';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // ✅ declare outside
        $car = null;

        DB::transaction(function () use ($request, &$car) {

            $icon = Helper::saveFile($request->file('icon'), 'car_images');
            $animation = Helper::saveFile($request->file('animation'), 'car_images');

            $car = Cars::create([
                'name' => $request->name,
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

            Notification::create([
                'user_id' => Auth::id(),
                'title' => "New Car Added 🚗",
                'message' => $car->name . " added successfully",
            ]);
        });

        // ✅ Firebase outside transaction
        $firebase = new FirebaseService();

        $admin = User::whereNotNull('fcm_token')->first();

        if ($admin) {

            $firebase->sendNotification(
                $admin->fcm_token,
                "New Car Added 🚗",
                $car->name . " added successfully"
            );
        }

        return redirect()
            ->route('cars')
            ->with('success', 'Car added successfully');
    }
    public function edit($id): View|RedirectResponse
    {
        $car = Cars::find($id);

        if (!$car) {
            return to_route('car')->withError('Car Not Found!');
        }
        return view('cars.edit', compact('car'));
    }

    public function update(Request $request, $id)
    {
        $car = Cars::findOrFail($id);

        $rules = [
            'name'            => 'required|string|max:255',
            'visibility_type' => 'required|in:backend,in_app',
            'icon'            => 'nullable|image|mimes:png,jpg,jpeg,webp',
            'animation'       => 'nullable',
            'status'          => 'required|in:0,1'
        ];

        if ($request->visibility_type == 'in_app') {

            $rules['needcoin']   = 'required|array|min:1';
            $rules['needcoin.*'] = 'required|integer|min:1|max:9999999';

            $rules['validity']   = 'required|array|min:1';
            $rules['validity.*'] = 'required|integer|min:1';
        }

        $request->validate($rules);

        return DB::transaction(function () use ($request, $car) {

            $data = [
                'name'            => $request->name,
                'visibility_type' => $request->visibility_type,
                'status'          => $request->status,
            ];
            if ($request->visibility_type === 'in_app') {
                $data['needcoin'] = array_values($request->needcoin);
                $data['validity'] = array_values($request->validity);
            } else {
                $data['needcoin'] = null;
                $data['validity'] = null;
            }

            if ($request->hasFile('icon')) {

                if ($car->icon && file_exists(public_path($car->icon))) {
                    @unlink(public_path($car->icon));
                }

                $data['icon'] = Helper::saveFile($request->file('icon'), 'care_images');
            }

            if ($request->hasFile('animation')) {

                if ($car->gif && file_exists(public_path($car->gif))) {
                    @unlink(public_path($car->gif));
                }

                $data['gif'] = Helper::saveFile($request->file('animation'), 'car_images');
            }


            $car->update($data);

            return redirect()->route('cars')->with('success', 'Car updated successfully');
        });
    }


    public function delete(Request $request): JsonResponse
    {
        return Helper::deleteRecord(new Cars, $request->id);
    }
}
