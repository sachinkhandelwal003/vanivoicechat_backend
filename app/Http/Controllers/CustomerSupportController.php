<?php

namespace App\Http\Controllers;

use App\Helper\Helper;
use App\Models\AppUser;
use App\Models\Country;
use App\Models\CustomerSupport;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class CustomerSupportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {

            $query = CustomerSupport::with('user')->latest();

            return DataTables::of($query)
                ->addIndexColumn()

                ->editColumn('user_id', function ($row) {

                    if (!$row->user) {return '-';}
                
                    $image = $row->user->image
                        ? Helper::showImage($row->user->image, true)
                        : asset('assets/img/avatar.png');
                
                    return '
                        <div class="d-flex align-items-center gap-2 user-profile-trigger" data-user-id="'.$row->user->id.'" style="cursor:pointer;">
                
                            <img src="'.$image.'" width="40" height="40" class="rounded-circle">
                
                            <div>
                                <div class="fw-bold">'.$row->user->name.'</div>
                                <small class="text-muted">'.$row->user->uid.'</small>
                            </div>
                
                        </div>
                    ';
                })

                ->editColumn('region', function ($row) {
                    return $row->region ?? '-';
                })

                ->editColumn('created_at', function ($row) {
                    return $row->created_at->format('d M Y');
                })

                ->addColumn('action', function ($row) {
                    $btn = '<div class="dropdown">
                        <button class="btn btn-sm btn-link dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="fas fa-ellipsis-h"></i>
                        </button>
                        <div class="dropdown-menu">';

                    if (Helper::userCan(104, 'can_edit')) {
                        $btn .= '<a class="dropdown-item" href="' . route('customer_support.form', $row->id) . '"><i class="fa-solid fa-pen-to-square me-2 text-primary"></i>Edit</a>';
                    }

                    if (Helper::userCan(105, 'can_delete')) {
                        $btn .= '<button class="dropdown-item text-danger delete" data-id="' . $row->id . '"><i class="fa-solid fa-trash me-2"></i>Delete</button>';
                    }

                    $btn .= '</div></div>';

                    return $btn;
                })

                ->rawColumns(['action','user_id'])
                ->make(true);
        }

        return view('customer_support.index');
    }

    public function form($id = null): View
    {
        $data = null;

        if ($id) {
            $data = CustomerSupport::with('user')->findOrFail($id);
        }

        $users = AppUser::latest()->get();
        $country = Country::orderBy('name', 'ASC')->get();

        return view('customer_support.form', compact('data', 'users', 'country'));
    }


    // Store Function for Add + Update
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'user' => 'required',
            'region'  => 'required|string|max:255',
        ]);

        $user = AppUser::where('uid', $request->user)->first();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found with this ID'
            ]);
        }

        CustomerSupport::updateOrCreate(
            ['id' => $request->id],
            [
                'user_id' => $user->id,
                'region'  => $request->region,
            ]
        );

        return response()->json([
            'status' => true,
            'message' => 'Customer Support saved successfully'
        ]);
    }

    public function delete(Request $request): JsonResponse
    {
        CustomerSupport::findOrFail($request->id)->delete();

        return response()->json([
            'status' => true,
            'message' => 'Deleted successfully'
        ]);
    }
}
