<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Helper\Helper;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Carbon\Carbon;

class CouponController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    // Show Coupon list in DataTable
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Coupon::orderBy('created_at', 'desc');

            return DataTables::of($data)
                ->editColumn('status', function ($row) {
                    return $row->status === 'active'
                        ? '<span class="badge fw-semi-bold rounded-pill status badge-light-success">Active</span>'
                        : '<span class="badge fw-semi-bold rounded-pill status badge-light-danger">Inactive</span>';
                })
                // ->editColumn('created_at', function ($row) {
                //     return \Carbon\Carbon::parse($row->created_at)
                //         ->format('D, d M\'y, g:i A');
                // })
                ->addColumn('action', function ($row) {
                    $btn = '<button class="text-600 btn-reveal dropdown-toggle btn btn-link btn-sm" id="drop' . $row->id . '" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <span class="fas fa-ellipsis-h fs--1"></span>
                        </button>
                        <div class="dropdown-menu" aria-labelledby="drop' . $row->id . '">';

                    if (Helper::userCan(104, 'can_edit')) {
                        $btn .= '<a class="dropdown-item" href="' . route('coupon.edit', $row->id) . '">Edit</a>';
                    }

                    if (Helper::userCan(104, 'can_delete')) {
                        $btn .= '<a class="dropdown-item text-danger delete" href="javascript:void(0)" data-id="' . $row->id . '">Delete</a>';
                    }

                    $btn .= '</div>';
                    return $btn;
                })

                ->rawColumns(['status', 'action'])
                ->make(true);
        }

        return view('coupon.index');
    }

    // Show single coupon (optional)
    public function show($id)
    {
        $coupon = Coupon::find($id);

        if (!$coupon) {
            return redirect()->route('coupon.list')->with('error', 'Coupon not found');
        }

        return view('coupon.add', compact('coupon'));
    }

    public function create()
    {
        return view('coupon.add'); // Blade form
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'coupon_code' => 'required|string|max:50|unique:coupons,coupon_code',
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric',
            'min_order_amount' => 'nullable|numeric',
            'max_uses' => 'nullable|integer',
            'valid_from' => 'required|date',
            'valid_until' => 'required|date|after_or_equal:valid_from',
            'status' => 'required|in:active,inactive',
        ]);

        Coupon::create($validated);

        return redirect()->route('coupon.list')->with('success', 'Coupon added successfully!');
    }

    public function edit($id): View|RedirectResponse
    {
        $coupon = Coupon::find($id);
        if (!$coupon) {
            return to_route('coupon.list')->withError('Coupon not found!');
        }

        return view('coupon.edit', compact('coupon'));
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $coupon = Coupon::find($id);
        if (!$coupon) {
            return to_route('coupon.list')->withError('Coupon not found!');
        }

        $data = $request->validate([
            'coupon_code' => 'required|string|max:50|unique:coupons,coupon_code,' . $id,
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric',
            'min_order_amount' => 'nullable|numeric',
            'max_uses' => 'nullable|integer',
            'valid_from' => 'required|date',
            'valid_until' => 'required|date|after_or_equal:valid_from',
            'status' => 'required|in:active,inactive',
        ]);

        $coupon->update($data);

        return to_route('coupon.list')->withSuccess('Coupon updated successfully!');
    }

    public function delete(Request $request): JsonResponse
    {
        return Helper::deleteRecord(new Coupon, $request->id);
    }
}



    // public function index(Request $request): View|JsonResponse
    // {
    //     if ($request->ajax()) {
    //         $data = Coupon::orderBy('created_at', 'desc');

    //         return DataTables::of($data)
    //             ->editColumn('status', function ($row) {
    //                 return $row->status === 'active'
    //                     ? '<span class="badge bg-success">Active</span>'
    //                     : '<span class="badge bg-danger">Inactive</span>';
    //             })
    //             ->editColumn('valid_from', function ($row) {
    //                 return Carbon::parse($row->valid_from)->format('d M Y H:i');
    //             })
    //             ->editColumn('valid_until', function ($row) {
    //                 return Carbon::parse($row->valid_until)->format('d M Y H:i');
    //             })
    //             ->addColumn('action', function ($row) {
    //                 $btn = '<button class="text-600 btn-reveal dropdown-toggle btn btn-link btn-sm" id="drop' . $row->id . '" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
    //                         <span class="fas fa-ellipsis-h fs--1"></span>
    //                     </button>
    //                     <div class="dropdown-menu" aria-labelledby="drop' . $row->id . '">';

    //                 if (Helper::userCan(201, 'can_edit')) {
    //                     $btn .= '<a class="dropdown-item" href="' . route('coupon.edit', $row->id) . '">Edit</a>';
    //                 }

    //                 if (Helper::userCan(201, 'can_delete')) {
    //                     $btn .= '<a class="dropdown-item text-danger delete" href="javascript:void(0)" data-id="' . $row->id . '">Delete</a>';
    //                 }

    //                 $btn .= '</div>';
    //                 return $btn;
    //             })
    //             ->rawColumns(['status', 'action'])
    //             ->make(true);
    //     }

    //     return view('coupon.index');
    // }
