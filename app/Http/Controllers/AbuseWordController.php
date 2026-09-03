<?php

namespace App\Http\Controllers;

use App\Models\BannedWord;
use App\Helper\Helper;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class AbuseWordController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = BannedWord::with('createdBy')->latest();

            if ($request->filled('search_keyword')) {
                $query->where('word', 'like', '%' . $request->search_keyword . '%');
            }

            if ($request->filled('category')) {
                $query->where('category', $request->category);
            }

            if ($request->filled('status') && $request->status !== '') {
                $query->where('status', $request->status);
            }

            $total   = BannedWord::count();
            $active  = BannedWord::where('status', 1)->count();
            $disabled = BannedWord::where('status', 0)->count();

            return DataTables::of($query)
                ->addIndexColumn()
                ->editColumn('word', function ($row) {
                    return '<code class="fs-6 text-danger fw-bold">' . e($row->word) . '</code>';
                })
                ->editColumn('category', function ($row) {
                    $colors = [
                        'general'   => 'bg-secondary',
                        'chat'      => 'bg-info text-dark',
                        'profile'   => 'bg-primary',
                        'content'   => 'bg-warning text-dark',
                    ];
                    $color = $colors[$row->category] ?? 'bg-secondary';
                    return '<span class="badge ' . $color . '">' . ucfirst($row->category ?? 'general') . '</span>';
                })
                ->editColumn('status', function ($row) {
                    return $row->status == 1
                        ? '<span class="badge bg-success">Active</span>'
                        : '<span class="badge bg-secondary">Disabled</span>';
                })
                ->addColumn('created_by_name', function ($row) {
                    return $row->createdBy
                        ? '<span class="text-primary fw-semibold">' . e($row->createdBy->name) . '</span>'
                        : '<span class="text-muted">System</span>';
                })
                ->editColumn('created_at', function ($row) {
                    return Carbon::parse($row->created_at)->format('d M Y h:i A');
                })
                ->addColumn('action', function ($row) {
                    $toggleIcon  = $row->status == 1 ? 'fa-ban' : 'fa-check-circle';
                    $toggleClass = $row->status == 1 ? 'text-warning' : 'text-success';
                    $toggleLabel = $row->status == 1 ? 'Disable' : 'Enable';

                    return '
                    <div class="dropdown">
                        <button class="btn btn-sm btn-light border" data-bs-toggle="dropdown">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                                <a href="javascript:void(0)" class="dropdown-item btn-edit"
                                    data-id="' . $row->id . '"
                                    data-word="' . e($row->word) . '"
                                    data-category="' . e($row->category) . '"
                                    data-bs-toggle="modal" data-bs-target="#editModal">
                                    <i class="fas fa-edit text-primary me-1"></i> Edit
                                </a>
                            </li>
                            <li>
                                <a href="javascript:void(0)" class="dropdown-item btn-toggle ' . $toggleClass . '"
                                    data-id="' . $row->id . '" data-status="' . $row->status . '">
                                    <i class="fas ' . $toggleIcon . ' me-1"></i> ' . $toggleLabel . '
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a href="javascript:void(0)" class="dropdown-item text-danger btn-delete"
                                    data-id="' . $row->id . '" data-word="' . e($row->word) . '">
                                    <i class="fas fa-trash me-1"></i> Delete
                                </a>
                            </li>
                        </ul>
                    </div>';
                })
                ->rawColumns(['word', 'category', 'status', 'created_by_name', 'action'])
                ->with([
                    'summary' => [
                        'total'    => $total,
                        'active'   => $active,
                        'disabled' => $disabled,
                    ]
                ])
                ->make(true);
        }

        return view('abuse_word.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'word'     => 'required|string|max:100',
            'category' => 'required|in:general,chat,profile,content',
        ]);

        // Check duplicates (case-insensitive)
        $exists = BannedWord::whereRaw('LOWER(word) = ?', [strtolower(trim($request->word))])->exists();
        if ($exists) {
            return response()->json(['status' => false, 'message' => 'This word is already in the banned list.']);
        }

        $word = BannedWord::create([
            'word'       => strtolower(trim($request->word)),
            'category'   => $request->category,
            'status'     => 1,
            'created_by' => Auth::id(),
        ]);

        return response()->json(['status' => true, 'message' => 'Word "' . $word->word . '" added to banned list.']);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'word'     => 'required|string|max:100',
            'category' => 'required|in:general,chat,profile,content',
        ]);

        $banned = BannedWord::findOrFail($id);

        // Check duplicate excluding self
        $exists = BannedWord::whereRaw('LOWER(word) = ?', [strtolower(trim($request->word))])
            ->where('id', '!=', $id)
            ->exists();
        if ($exists) {
            return response()->json(['status' => false, 'message' => 'This word already exists in the banned list.']);
        }

        $banned->update([
            'word'     => strtolower(trim($request->word)),
            'category' => $request->category,
        ]);

        return response()->json(['status' => true, 'message' => 'Word updated successfully.']);
    }

    public function toggleStatus($id)
    {
        $banned = BannedWord::findOrFail($id);
        $banned->status = $banned->status == 1 ? 0 : 1;
        $banned->save();

        $msg = $banned->status == 1 ? 'Word enabled successfully.' : 'Word disabled successfully.';
        return response()->json(['status' => true, 'message' => $msg, 'new_status' => $banned->status]);
    }

    public function destroy($id)
    {
        $banned = BannedWord::findOrFail($id);
        $word   = $banned->word;
        $banned->delete();

        return response()->json(['status' => true, 'message' => 'Word "' . $word . '" deleted from banned list.']);
    }

    /**
     * API endpoint — returns all active banned words to mobile app
     * Mobile app can call this once on startup and cache locally.
     */
    public function apiList(Request $request)
    {
        $words = BannedWord::where('status', 1)
            ->select('id', 'word', 'category', 'updated_at')
            ->latest('updated_at')
            ->get();

        return response()->json([
            'status' => true,
            'count'  => $words->count(),
            'data'   => $words,
        ]);
    }

    /**
     * API endpoint — bulk import words (comma/newline separated)
     */
    public function bulkImport(Request $request)
    {
        $request->validate(['words' => 'required|string']);

        $rawWords = preg_split('/[\n,]+/', $request->words);
        $added    = 0;
        $skipped  = 0;

        foreach ($rawWords as $w) {
            $w = strtolower(trim($w));
            if (empty($w)) continue;
            $exists = BannedWord::whereRaw('LOWER(word) = ?', [$w])->exists();
            if ($exists) { $skipped++; continue; }
            BannedWord::create([
                'word'       => $w,
                'category'   => $request->category ?? 'general',
                'status'     => 1,
                'created_by' => Auth::id(),
            ]);
            $added++;
        }

        return response()->json([
            'status'  => true,
            'message' => "{$added} words added, {$skipped} duplicates skipped.",
        ]);
    }
}
