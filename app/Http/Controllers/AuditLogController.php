<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Helper\Helper;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class AuditLogController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = AuditLog::with('user')->latest();

            if ($request->filled('search_keyword')) {
                $kw = $request->search_keyword;
                $query->where(function ($q) use ($kw) {
                    $q->where('user_name', 'like', "%{$kw}%")
                      ->orWhere('user_email', 'like', "%{$kw}%")
                      ->orWhere('module', 'like', "%{$kw}%")
                      ->orWhere('action', 'like', "%{$kw}%")
                      ->orWhere('description', 'like', "%{$kw}%")
                      ->orWhere('ip_address', 'like', "%{$kw}%");
                });
            }

            if ($request->filled('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            if ($request->filled('module_name')) {
                $query->where('module', $request->module_name);
            }

            if ($request->filled('action_type')) {
                $query->where('action', $request->action_type);
            }

            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // Summary stats for widgets
            $totalLogs    = AuditLog::count();
            $todayLogs    = AuditLog::whereDate('created_at', Carbon::today())->count();
            $activeAdmins = AuditLog::whereDate('created_at', Carbon::today())->distinct('user_id')->count('user_id');
            $deleteCount  = AuditLog::whereIn('action', ['Delete', 'Delete Word', 'Remove'])->count();

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('admin_user', function ($row) {
                    $name  = e($row->user_name ?? ($row->user->name ?? 'System'));
                    $email = e($row->user_email ?? ($row->user->email ?? '-'));
                    return '<div>
                        <div class="fw-bold text-dark"><i class="fas fa-user-shield text-primary me-1"></i>' . $name . '</div>
                        <small class="text-muted">' . $email . '</small>
                    </div>';
                })
                ->editColumn('module', function ($row) {
                    return '<span class="badge bg-light text-dark border px-2 py-1"><i class="fas fa-layer-group text-primary me-1"></i>' . e($row->module) . '</span>';
                })
                ->editColumn('action', function ($row) {
                    $act = strtolower($row->action);
                    $color = 'bg-secondary';
                    if (str_contains($act, 'add') || str_contains($act, 'create')) {
                        $color = 'bg-success';
                    } elseif (str_contains($act, 'edit') || str_contains($act, 'update')) {
                        $color = 'bg-info text-dark';
                    } elseif (str_contains($act, 'delete') || str_contains($act, 'remove')) {
                        $color = 'bg-danger';
                    } elseif (str_contains($act, 'send') || str_contains($act, 'recharge')) {
                        $color = 'bg-primary';
                    } elseif (str_contains($act, 'transfer') || str_contains($act, 'assign')) {
                        $color = 'bg-warning text-dark';
                    } elseif (str_contains($act, 'ban') || str_contains($act, 'disable')) {
                        $color = 'bg-dark';
                    }
                    return '<span class="badge ' . $color . ' px-2 py-1">' . e($row->action) . '</span>';
                })
                ->editColumn('description', function ($row) {
                    return '<span class="text-secondary" style="font-size: 0.875rem;">' . e($row->description) . '</span>';
                })
                ->editColumn('ip_address', function ($row) {
                    return '<code class="small text-muted">' . e($row->ip_address ?? '127.0.0.1') . '</code>';
                })
                ->editColumn('created_at', function ($row) {
                    return '<small class="fw-semibold text-dark">' . Carbon::parse($row->created_at)->format('d M Y, h:i A') . '</small>';
                })
                ->addColumn('action_btn', function ($row) {
                    $btn = '<button class="btn btn-sm btn-light border btn-view-log text-primary me-1" 
                                data-id="' . $row->id . '" 
                                data-user="' . e($row->user_name ?? 'System') . '"
                                data-module="' . e($row->module) . '"
                                data-action="' . e($row->action) . '"
                                data-desc="' . e($row->description) . '"
                                data-ip="' . e($row->ip_address) . '"
                                data-agent="' . e($row->user_agent) . '"
                                data-time="' . Carbon::parse($row->created_at)->format('d M Y, h:i:s A') . '"
                                title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>';
                    
                    if (Helper::userCan(182, 'can_delete')) {
                        $btn .= '<button class="btn btn-sm btn-light border text-danger btn-delete-log" data-id="' . $row->id . '" title="Delete Log">
                                    <i class="fas fa-trash"></i>
                                </button>';
                    }

                    return $btn;
                })
                ->rawColumns(['admin_user', 'module', 'action', 'description', 'ip_address', 'created_at', 'action_btn'])
                ->with([
                    'summary' => [
                        'total'         => $totalLogs,
                        'today'         => $todayLogs,
                        'active_admins' => $activeAdmins,
                        'deletions'     => $deleteCount,
                    ]
                ])
                ->make(true);
        }

        // Get list of admin users for filter dropdown
        $adminUsers = User::orderBy('name')->get(['id', 'name', 'email']);
        
        // Get list of unique modules & action types for filter dropdowns
        $modules = AuditLog::select('module')->distinct()->orderBy('module')->pluck('module');
        $actions = AuditLog::select('action')->distinct()->orderBy('action')->pluck('action');

        return view('audit_log.index', compact('adminUsers', 'modules', 'actions'));
    }

    public function show($id)
    {
        $log = AuditLog::with('user')->findOrFail($id);
        return response()->json([
            'status' => true,
            'data'   => $log,
            'formatted_time' => Carbon::parse($log->created_at)->format('d M Y, h:i:s A'),
        ]);
    }

    public function destroy($id)
    {
        if (!Helper::userCan(182, 'can_delete')) {
            return response()->json(['status' => false, 'message' => 'Permission denied.']);
        }

        $log = AuditLog::findOrFail($id);
        $log->delete();

        return response()->json(['status' => true, 'message' => 'Audit log entry deleted successfully.']);
    }

    public function clearAll(Request $request)
    {
        if (!Helper::userCan(182, 'can_delete')) {
            return response()->json(['status' => false, 'message' => 'Permission denied.']);
        }

        AuditLog::truncate();

        Helper::logActivity('Audit Log', 'Clear Logs', 'Admin cleared all system audit log entries.');

        return response()->json(['status' => true, 'message' => 'All audit logs cleared successfully.']);
    }
}
