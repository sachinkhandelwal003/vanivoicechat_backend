<?php

namespace App\Http\Controllers;

use App\Models\RelationshipItem;
use App\Helper\Helper;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RelationshipItemController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {

            $query = RelationshipItem::latest();

            return DataTables::of($query)
                ->addIndexColumn()

                ->editColumn('icon', function ($row) {
                    return $row->icon
                        ? '<img src="' . asset('storage/' . $row->icon) . '" width="40">'
                        : '-';
                })

                ->editColumn('type', function ($row) {
                    return ucfirst($row->type);
                })

                ->addColumn('action', function ($row) {

                    return '
                    <div class="dropdown">
                        <button class="btn btn-sm btn-link dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="fas fa-ellipsis-h"></i>
                        </button>
                        <div class="dropdown-menu">
                            <a class="dropdown-item" href="' . route('relationship.item.form', $row->id) . '">Edit</a>
                            <button class="dropdown-item text-danger delete" data-id="' . $row->id . '">Delete</button>
                        </div>
                    </div>';
                })

                ->rawColumns(['icon', 'action'])
                ->make(true);
        }

        return view('relationship.index');
    }

    public function form($id = null): View|RedirectResponse
    {
        $item = null;

        if ($id) {
            $item = RelationshipItem::find($id);

            if (!$item) {
                return to_route('relationship')->withError('Item Not Found!');
            }
        }

        $ringTypes = RelationshipItem::whereNotNull('ring')
            ->where('ring', '!=', '')
            ->pluck('type')
            ->map(fn($type) => strtolower($type))
            ->unique()
            ->values()
            ->toArray();

        return view('relationship.form', compact('item', 'ringTypes'));
    }

    public function save(Request $request, $id = null)
    {
        $rules = [
            'name' => 'required|string|max:100',
            'type' => 'required|in:CP,brother,sister,confident',
            'required_coins' => 'nullable|integer',

            'icon' => 'nullable|image',
            'gif' => 'nullable|mimes:gif',
            'ring' => 'nullable|image',
            'avatar' => 'nullable|image',
            'frame' => 'nullable|image',
            'badge' => 'nullable|image',
            'background' => 'nullable|image',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        return DB::transaction(function () use ($request, $id) {

            $item = $id ? RelationshipItem::find($id) : new RelationshipItem();

            if ($id && !$item) {
                return redirect()->back()->with('error', 'Item not found');
            }

            $data = $request->only(['name', 'type', 'required_coins']);

            foreach (['icon', 'gif', 'ring', 'avatar', 'frame', 'badge', 'background'] as $file) {

                if ($request->hasFile($file)) {

                    if ($id && $item->$file && file_exists(public_path($item->$file))) {
                        @unlink(public_path($item->$file));
                    }

                    $data[$file] = Helper::saveFile($request->file($file), 'relationship');
                }
            }

            $item->fill($data)->save();

            return redirect()
                ->route('relationship.item')
                ->with('success', $id ? 'Updated successfully' : 'Created successfully');
        });
    }

    public function delete(Request $request): JsonResponse
    {
        return Helper::deleteRecord(new RelationshipItem, $request->id);
    }
}
