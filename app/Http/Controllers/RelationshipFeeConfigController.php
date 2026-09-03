<?php

namespace App\Http\Controllers;

use App\Models\RelationshipFeeConfig;
use App\Models\Country;
use App\Helper\Helper;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class RelationshipFeeConfigController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {

            $query = RelationshipFeeConfig::with('country')->latest();

            // Search Keyword
            if ($request->filled('search_keyword')) {
                $kw = trim($request->search_keyword);
                $query->where(function ($q) use ($kw) {
                    $q->where('relationship_type', 'like', "%{$kw}%")
                      ->orWhereHas('country', function ($cq) use ($kw) {
                          $cq->where('name', 'like', "%{$kw}%");
                      });
                });
            }

            // Country Filter
            if ($request->filled('country_id')) {
                if ($request->country_id === 'all') {
                    $query->whereNull('country_id');
                } else {
                    $query->where('country_id', $request->country_id);
                }
            }

            // Relationship Type Filter
            if ($request->filled('relationship_type')) {
                $query->where('relationship_type', $request->relationship_type);
            }

            // Status Filter
            if ($request->filled('status') && $request->status !== '') {
                $query->where('status', $request->status);
            }

            $totalConfigs   = (clone $query)->count();
            $activeConfigs  = (clone $query)->where('status', 1)->count();
            $totalCountries = Country::count();

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('country', function ($row) {
                    if (!$row->country) {
                        return '<span class="badge bg-secondary"><i class="fas fa-globe me-1"></i>All Countries (Default)</span>';
                    }
                    return '<span class="fw-semibold text-dark"><i class="fas fa-globe-americas me-1 text-primary"></i>' . e($row->country->name) . '</span>';
                })
                ->editColumn('relationship_type', function ($row) {
                    $typeStyles = [
                        'CP'        => 'background-color: #dc3545; color: #ffffff;',
                        'Brother'   => 'background-color: #0d6efd; color: #ffffff;',
                        'Sister'    => 'background-color: #0dcaf0; color: #000000;',
                        'Confident' => 'background-color: #6f42c1; color: #ffffff;',
                    ];
                    $style = $typeStyles[$row->relationship_type] ?? 'background-color: #6c757d; color: #ffffff;';
                    return '<span class="badge px-3 py-2 fs-7" style="' . $style . '"><i class="fas fa-heart me-1"></i>' . e($row->relationship_type) . '</span>';
                })
                ->editColumn('invite_fee', function ($row) {
                    return '<span class="fw-bold text-success"><i class="fas fa-coins me-1 text-warning"></i>' . number_format($row->invite_fee) . ' Coins</span>';
                })
                ->editColumn('break_fee', function ($row) {
                    return '<span class="fw-bold text-danger"><i class="fas fa-coins me-1 text-warning"></i>' . number_format($row->break_fee) . ' Coins</span>';
                })
                ->editColumn('status', function ($row) {
                    return $row->status == 1
                        ? '<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Active</span>'
                        : '<span class="badge bg-secondary"><i class="fas fa-times-circle me-1"></i>Disabled</span>';
                })
                ->editColumn('created_at', function ($row) {
                    return Carbon::parse($row->created_at)->format('d-m-Y h:i A');
                })
                ->addColumn('action', function ($row) {
                    $toggleIcon  = $row->status == 1 ? 'fa-ban' : 'fa-check-circle';
                    $toggleClass = $row->status == 1 ? 'text-warning' : 'text-success';
                    $toggleLabel = $row->status == 1 ? 'Disable' : 'Enable';

                    return '
                    <div class="dropdown text-center">
                        <button class="btn btn-sm btn-light border" data-bs-toggle="dropdown">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a href="javascript:void(0)" class="dropdown-item btn-edit"
                                    data-id="' . $row->id . '"
                                    data-country="' . ($row->country_id ?? '') . '"
                                    data-type="' . e($row->relationship_type) . '"
                                    data-invite="' . $row->invite_fee . '"
                                    data-break="' . $row->break_fee . '"
                                    data-status="' . $row->status . '">
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
                                    data-id="' . $row->id . '" data-type="' . e($row->relationship_type) . '">
                                    <i class="fas fa-trash me-1"></i> Delete
                                </a>
                            </li>
                        </ul>
                    </div>';
                })
                ->rawColumns(['country', 'relationship_type', 'invite_fee', 'break_fee', 'status', 'action'])
                ->with([
                    'summary' => [
                        'total_configs'   => $totalConfigs,
                        'active_configs'  => $activeConfigs,
                        'total_countries' => $totalCountries,
                    ]
                ])
                ->make(true);
        }

        $countries = Country::orderBy('name')->get(['id', 'name']);
        return view('relationship_fee_config.index', compact('countries'));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'relationship_type' => 'required|string|max:100',
            'invite_fee'        => 'required|integer|min:0',
            'break_fee'         => 'required|integer|min:0',
        ]);

        $countryId = $request->filled('country_id') ? $request->country_id : null;
        $type      = trim($request->relationship_type);

        // Check Duplicate
        $exists = RelationshipFeeConfig::where('country_id', $countryId)
            ->whereRaw('LOWER(relationship_type) = ?', [strtolower($type)])
            ->exists();

        if ($exists) {
            return response()->json(['status' => false, 'message' => 'Configuration for this Country & Relationship Type already exists.']);
        }

        $config = RelationshipFeeConfig::create([
            'country_id'        => $countryId,
            'relationship_type' => $type,
            'invite_fee'        => $request->invite_fee,
            'break_fee'         => $request->break_fee,
            'status'            => $request->status ?? 1,
            'created_by'        => Auth::id(),
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Relationship Fee configured successfully for ' . $type . '.'
        ]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $request->validate([
            'relationship_type' => 'required|string|max:100',
            'invite_fee'        => 'required|integer|min:0',
            'break_fee'         => 'required|integer|min:0',
        ]);

        $config    = RelationshipFeeConfig::findOrFail($id);
        $countryId = $request->filled('country_id') ? $request->country_id : null;
        $type      = trim($request->relationship_type);

        // Check Duplicate excluding current
        $exists = RelationshipFeeConfig::where('country_id', $countryId)
            ->whereRaw('LOWER(relationship_type) = ?', [strtolower($type)])
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            return response()->json(['status' => false, 'message' => 'Configuration for this Country & Relationship Type already exists.']);
        }

        $config->update([
            'country_id'        => $countryId,
            'relationship_type' => $type,
            'invite_fee'        => $request->invite_fee,
            'break_fee'         => $request->break_fee,
            'status'            => $request->status ?? $config->status,
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Relationship Fee configuration updated successfully.'
        ]);
    }

    public function toggleStatus($id): JsonResponse
    {
        $config = RelationshipFeeConfig::findOrFail($id);
        $config->status = $config->status == 1 ? 0 : 1;
        $config->save();

        $msg = $config->status == 1 ? 'Fee configuration enabled.' : 'Fee configuration disabled.';
        return response()->json(['status' => true, 'message' => $msg, 'new_status' => $config->status]);
    }

    public function destroy($id): JsonResponse
    {
        $config = RelationshipFeeConfig::findOrFail($id);
        $type   = $config->relationship_type;
        $config->delete();

        return response()->json(['status' => true, 'message' => 'Fee configuration for ' . $type . ' deleted successfully.']);
    }
}
