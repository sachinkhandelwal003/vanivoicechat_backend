<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\State;
use App\Helper\Helper;
use Illuminate\View\View;
use Illuminate\Http\Request;
use \Yajra\Datatables\Datatables;
use Illuminate\Http\JsonResponse;

class CityController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except('get_cities');
    }

    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            $data = City::select('cities.*', 'states.name as state_name')
                ->leftJoin('states', 'states.id', '=', 'cities.state_id');
            return Datatables::of($data)
                ->editColumn('image', function ($row) {
                    if (empty($row['image'])) {
                        $row['image'] = 'categories/image.png';
                    }
                    $btn = '<div class="img-group"><img class="" src="' . asset('storage/' . $row['image']) . '" alt=""></div>';
                    return $btn;
                })
                ->addColumn('action', function ($row) {
                    $btn = '<button class="text-600 btn-reveal dropdown-toggle btn btn-link btn-sm" id="drop" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="fas fa-ellipsis-h fs--1"></span></button><div class="dropdown-menu" aria-labelledby="drop">';
                    if (Helper::userCan(106, 'can_edit')) {
                        $btn .= '<button class="dropdown-item edit" data-all="' . htmlspecialchars(json_encode($row))  . '">Edit</button>';
                    }
                    if (Helper::userCan(106, 'can_delete')) {
                        $btn .= '<button class="dropdown-item text-danger delete" data-id="' . $row['id'] . '">Delete</button>';
                    }

                    if (Helper::userAllowed(106)) {
                        return $btn;
                    } else {
                        return '';
                    }
                })
                ->editColumn('is_other', function ($row) {
                    return $row['is_other'] == 1 ? '<small class="badge fw-semi-bold rounded-pill status badge-light-success"> Yes</small>' : '<small class="badge fw-semi-bold rounded-pill status badge-light-danger"> No</small>';
                })
                ->editColumn('is_popular', function ($row) {
                    return $row['is_popular'] == 1 ? '<small class="badge fw-semi-bold rounded-pill status badge-light-success"> Yes</small>' : '<small class="badge fw-semi-bold rounded-pill status badge-light-danger"> No</small>';
                })
                ->editColumn('status', function ($row) {
                    return $row['status'] == 1 ? '<small class="badge fw-semi-bold rounded-pill status badge-light-success"> Active</small>' : '<small class="badge fw-semi-bold rounded-pill status badge-light-danger"> Inactive</small>';
                })
                ->rawColumns(['action', 'status','image','is_popular','is_other'])
                ->make(true);
        }

        $states = State::active()->get();
        return view('cities.index', compact('states'));
    }

    public function save(Request $request): JsonResponse
    {
        // dd($request->all());
        return Helper::checkValid([
            'name'        => ['required', 'string', 'max:100', 'unique:cities,name'],
            'state_id'    => ['required', 'integer'],
            'status'      => ['required', 'integer'],
            'is_popular'  => ['required', 'integer'],
            'is_other'    => ['required', 'integer'],
            'image'       => ['image', 'mimes:jpg,png,jpeg', 'max:5048']
        ], function ($validator) use ($request) {
            $data = $validator->validated();
            $data['image'] =  'categories/image.png';
            if ($request->file('image')) {
                $data['image'] = Helper::saveFile($request->file('image'), 'categories');
            }
            City::create($data);
            return response()->json([
                'status'    => true,
                'message'   => 'City Added Successfully',
                'data'      => ''
            ]);
        });
    }

    public function update(Request $request): JsonResponse
    {
       
        $city = City::find($request->id);
        if (!$city) {
            return response()->json([
                'status'    => false,
                'message'   => 'City Not Found..!!',
            ]);
        }

        return Helper::checkValid([
            'id'          => ['required'],
            'name'        => ['required', 'string', 'max:100', 'unique:cities,name,'.$city['id']],
            'state_id'    => ['required', 'integer'],
            'status'      => ['required', 'integer'],
            'is_popular'  => ['required', 'integer'],
            'is_other'    => ['required', 'integer'],
            'image'       => ['image', 'mimes:jpg,png,jpeg', 'max:5048']
        ], function ($validator) use ($city, $request) {
            $data = $validator->validated();
            $data['image'] = $city->image;
            if ($request->file('image')) {
                Helper::deleteFile($city->image);
                $data['image'] = Helper::saveFile($request->file('image'), 'categories');
            }
            $city->update($data);
            return response()->json([
                'status'    => true,
                'message'   => 'City Updated Successfully',
            ]);
        });
    }

    public function delete(Request $request): JsonResponse
    {
        return Helper::deleteRecord(new City, $request->id);
    }

    public function get_cities(Request $request): string
    {
        $state_id   = $request->state_id;
        $city_id    = $request->city_id;

        $html   = '<option value="">Select City</option>';
        $cities = City::where('state_id', $state_id)->active()->get()->map(function ($city) use ($city_id) {
            return '<option value="' . $city->id . '" ' . ($city_id == $city->id ? 'selected' : '') . '>' . $city->name . '</option > ';
        });

        return $html . $cities->implode('');
    }
}
