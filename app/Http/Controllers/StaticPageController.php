<?php

namespace App\Http\Controllers;

use App\Models\Cms;
use App\Helper\Helper;
use App\Models\StaticPage;
use Illuminate\View\View;
use Illuminate\Http\Request;
use \Yajra\Datatables\Datatables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class StaticPageController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {

            $data = StaticPage::select('id', 'title', 'description', 'type', 'created_at');

            return Datatables::of($data)

                ->editColumn('description', function ($row) {
                    // Remove HTML tags
                    $text = strip_tags($row->description);

                    // Short text (80 chars)
                    $short = \Illuminate\Support\Str::limit($text, 80);

                    // Tooltip with full description
                    return '<span title="' . e($text) . '">' . $short . '</span>';
                })

                ->editColumn('created_at', function ($row) {
                    return $row->created_at ? $row->created_at->format('d M, Y') : '';
                })

                ->editColumn('status', function ($row) {
                    return $row->status == 1
                        ? '<small class="badge fw-semi-bold rounded-pill badge-light-success">Active</small>'
                        : '<small class="badge fw-semi-bold rounded-pill badge-light-danger">Inactive</small>';
                })

                ->addColumn('action', function ($row) {

                    $btn = '<button class="text-600 btn-reveal dropdown-toggle btn btn-link btn-sm" type="button" data-bs-toggle="dropdown">
                            <span class="fas fa-ellipsis-h fs--1"></span>
                        </button>
                        <div class="dropdown-menu">';

                    if (Helper::userCan(104, 'can_edit')) {
                        $btn .= '<a class="dropdown-item" href="' . route('static-page.edit', $row->id) . '">Edit</a>';
                    }
                    if (Helper::userCan(104, 'can_delete')) {
                        $btn .= '<a class="dropdown-item text-danger delete-btn" data-id="' . $row->id . '" href="javascript:void(0)">Delete</a>';
                    }
                    $btn .= '</div>';

                    return Helper::userAllowed(104) ? $btn : '';
                })

                ->orderColumn('created_at', function ($query, $order) {
                    $query->orderBy('created_at', $order);
                })

                // IMPORTANT: allow HTML render
                ->rawColumns(['description', 'action', 'status'])

                ->make(true);
        }

        return view('staticpages.index');
    }

    public function add(): View
    {
        return view('staticpages.add');
    }

    public function save(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string'],
        ]);

        $validated['description'] = trim($validated['description']);

        StaticPage::create($validated);

        return redirect()
            ->route('static-page')
            ->with('success', 'Static Page Added Successfully..!!');
    }
  public function edit($id): View|RedirectResponse
{
    $cms = StaticPage::find($id);

    if (!$cms) {
        return redirect()
            ->route('static-page')
            ->with('error', 'Static Page Not Found..!!');
    }

    return view('staticpages.edit', compact('cms'));
}

public function update(Request $request, $id): RedirectResponse
{
    $cms = StaticPage::find($id);

    if (!$cms) {
        return redirect()
            ->route('static-page')
            ->with('error', 'Static Page Not Found..!!');
    }

    $validated = $request->validate([
        'title'       => ['required', 'string', 'max:255'],
        'type'        => ['required', 'string', 'max:100'],
        'description' => ['required', 'string'],
    ]);

    $validated['description'] = trim($validated['description']);

    $cms->update($validated);

    return redirect()
        ->route('static-page')
        ->with('success', 'Static Page Updated Successfully..!!');
}
    public function delete($id)
    {
        try {
            $page = StaticPage::findOrFail($id);

            // Direct delete (no soft delete as per your requirement)
            $page->delete();

            return response()->json([
                'status' => true,
                'message' => 'Static Page deleted successfully!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong!'
            ]);
        }
    }
}
