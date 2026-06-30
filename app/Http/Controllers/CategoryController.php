<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Cms;
use App\Helper\Helper;
use Illuminate\View\View;
use Illuminate\Http\Request;
use \Yajra\Datatables\Datatables;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\RedirectResponse;

class CategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            $data = Category::whereNull('deleted_at')->latest();
            return Datatables::of($data)
                ->editColumn('image', function ($row) {
                    $btn = '<div class="img-group"><img class="" src="' . asset('storage/' . $row['image']) . '" alt=""></div>';
                    return $btn;
                })
                ->editColumn('created_at', function ($row) {
                    return $row['created_at']->format('d M, Y');
                })
                ->editColumn('status', function ($row) {
                    return $row['status'] == 1 ? '<small class="badge fw-semi-bold rounded-pill status badge-light-success"> Active</small>' : '<small class="badge fw-semi-bold rounded-pill status badge-light-danger"> Inactive</small>';
                })
                ->addColumn('action', function ($row) {

                    $btn = '<button class="text-600 btn-reveal dropdown-toggle btn btn-link btn-sm" id="drop" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="fas fa-ellipsis-h fs--1"></span></button><div class="dropdown-menu" aria-labelledby="drop">';
                    if (Helper::userCan(104, 'can_edit')) {
                        $btn .= '<a class="dropdown-item" href="' . route('categories.edit', $row['id']) . '">Edit</a>';
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
                ->rawColumns(['action', 'image', 'status'])
                ->make(true);
        }
        return view('categories.index');
    }

    public function add(): View
    {
        return view('categories.add');
    }

    public function save(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title'         => ['required', 'string', 'max:200'],
            'status'        => ['required', 'integer'],
            'image'         => ['image', 'mimes:jpg,png,jpeg', 'max:5048']
        ]);

        $data = [...$validated, 'image' => 'category/image.png'];
        if ($request->file('image')) {
            $data['image'] = Helper::saveFile($request->file('image'), 'cms');
        }

        Category::create($data);
        return to_route('categories')->withSuccess('Category Added Successfully..!!');
    }

    /**
     * Show the edit form for a category.
     */
    public function edit($id): View|RedirectResponse
    {
        $category = Category::find($id);

        if (!$category) {
            return to_route('categories')->withError('Category Not Found..!!');
        }

        return view('categories.edit', compact('category'));
    }

    /**
     * Update the category details.
     */
    public function update(Request $request, $id): RedirectResponse
    {
        $category = Category::find($id);

        if (!$category) {
            return to_route('categories')->withError('Category Not Found..!!');
        }

        $data = $request->validate([
            'title'       => ['required', 'string', 'max:200'],
            'status'      => ['required', 'integer'],
            'image'       => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:5048'],
        ]);

        // If new image uploaded → delete old one and save new
        if ($request->hasFile('image')) {
            Helper::deleteFile($category->image);
            $data['image'] = Helper::saveFile($request->file('image'), 'category');
        }

        $category->update($data);

        return to_route('categories')->withSuccess('Category Updated Successfully..!!');
    }

    // public function delete(Request $request): JsonResponse
    // {
    //     return Helper::deleteRecord(new Category, $request->id);
    // }
    public function delete(Request $request): JsonResponse
    {
        $category = Category::find($request->id);

        if (!$category) {
            return response()->json(['status' => false, 'message' => 'Category not found'], 404);
        }

        // Delete products under each subcategory
        foreach ($category->subcategories as $subcategory) {

            // Delete products
            foreach ($subcategory->products as $product) {

                // (Optional) If product has images in storage - delete them
                if (!empty($product->images)) {
                    $images = json_decode($product->images, true);
                    foreach ($images as $img) {
                        Storage::delete('public/' . $img);
                    }
                }

                $product->delete();
            }

            // Delete subcategory
            $subcategory->delete();
        }

        // Delete main category
        $category->delete();

        return response()->json([
            'status' => true,
            'message' => 'Category and related subcategories/products deleted successfully'
        ]);
    }
}
