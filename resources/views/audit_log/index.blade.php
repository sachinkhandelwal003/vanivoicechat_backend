@extends('layouts.app')

@section('content')
<style>
    .audit-card {
        border-radius: 12px;
        border: 1px solid rgba(0, 0, 0, 0.05);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
        transition: transform 0.2s ease;
    }
    .audit-card:hover {
        transform: translateY(-2px);
    }
</style>

<div class="container-fluid mt-3">
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <h5 class="fw-bold text-dark mb-1">
                    <i class="fas fa-history text-primary me-2"></i> Admin & Sub-Admin Audit Logs
                </h5>
                <small class="text-muted">Comprehensive activity tracking for all actions performed in the admin panel</small>
            </div>
            <div>
                @if(Helper::userCan(182, 'can_delete'))
                <button class="btn btn-outline-danger btn-sm rounded-pill px-3" id="btnClearAllLogs">
                    <i class="fas fa-trash-alt me-1"></i> Clear All Logs
                </button>
                @endif
            </div>
        </div>

        <div class="card-body">
            {{-- Summary Stats Widgets --}}
            <div class="row g-3 mb-4">
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="card audit-card text-center p-3 h-100 bg-white">
                        <div class="text-primary mb-2"><i class="fas fa-list-alt fa-2x"></i></div>
                        <h3 class="fw-bold text-dark mb-1" id="stat_total">0</h3>
                        <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.75rem;">Total Logs</small>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="card audit-card text-center p-3 h-100 bg-white">
                        <div class="text-success mb-2"><i class="fas fa-calendar-day fa-2x"></i></div>
                        <h3 class="fw-bold text-success mb-1" id="stat_today">0</h3>
                        <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.75rem;">Today's Activity</small>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="card audit-card text-center p-3 h-100 bg-white">
                        <div class="text-info mb-2"><i class="fas fa-user-shield fa-2x"></i></div>
                        <h3 class="fw-bold text-info mb-1" id="stat_admins">0</h3>
                        <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.75rem;">Active Admins Today</small>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="card audit-card text-center p-3 h-100 bg-white">
                        <div class="text-danger mb-2"><i class="fas fa-exclamation-triangle fa-2x"></i></div>
                        <h3 class="fw-bold text-danger mb-1" id="stat_deletions">0</h3>
                        <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.75rem;">Deletions Recorded</small>
                    </div>
                </div>
            </div>

            {{-- Filter Section --}}
            <div class="card bg-light border-0 p-3 mb-4 rounded-3">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md-3">
                        <label class="form-label fw-semibold small text-muted">Admin User</label>
                        <select class="form-select form-select-sm" id="filter_user">
                            <option value="">All Admins / Sub-Admins</option>
                            @foreach($adminUsers as $admin)
                                <option value="{{ $admin->id }}">{{ $admin->name }} ({{ $admin->email }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-6 col-md-2">
                        <label class="form-label fw-semibold small text-muted">Module</label>
                        <select class="form-select form-select-sm" id="filter_module">
                            <option value="">All Modules</option>
                            @foreach($modules as $mod)
                                <option value="{{ $mod }}">{{ $mod }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-6 col-md-2">
                        <label class="form-label fw-semibold small text-muted">Action Type</label>
                        <select class="form-select form-select-sm" id="filter_action">
                            <option value="">All Actions</option>
                            @foreach($actions as $act)
                                <option value="{{ $act }}">{{ $act }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-6 col-md-2">
                        <label class="form-label fw-semibold small text-muted">Date From</label>
                        <input type="date" class="form-control form-control-sm" id="filter_date_from">
                    </div>

                    <div class="col-6 col-md-2">
                        <label class="form-label fw-semibold small text-muted">Date To</label>
                        <input type="date" class="form-control form-control-sm" id="filter_date_to">
                    </div>

                    <div class="col-12 col-md-1 d-flex gap-1 justify-content-end">
                        <button class="btn btn-primary btn-sm w-100" id="btnFilterSearch" title="Apply Filter">
                            <i class="fas fa-search"></i>
                        </button>
                        <button class="btn btn-secondary btn-sm w-100" id="btnFilterReset" title="Reset Filters">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                </div>
                <div class="mt-2">
                    <input type="text" class="form-control form-control-sm" id="filter_keyword" placeholder="Search keyword (Name, Email, Description, IP)...">
                </div>
            </div>

            {{-- Table --}}
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle w-100 small" id="auditLogsTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 40px;">#</th>
                            <th>Admin / Sub-Admin</th>
                            <th>Module</th>
                            <th>Action</th>
                            <th>Description</th>
                            <th>IP Address</th>
                            <th>Date & Time</th>
                            <th style="width: 80px;">Action</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Log Details Modal --}}
<div class="modal fade" id="logDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-info-circle me-2"></i>Audit Log Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="fw-bold text-muted small">ADMIN USER</label>
                        <div class="fs-6 fw-bold text-dark" id="modal_user">-</div>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-bold text-muted small">TIMESTAMP</label>
                        <div class="fs-6 fw-bold text-dark" id="modal_time">-</div>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-bold text-muted small">MODULE</label>
                        <div><span class="badge bg-light text-dark border px-2 py-1" id="modal_module">-</span></div>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-bold text-muted small">ACTION TYPE</label>
                        <div><span class="badge bg-primary px-2 py-1" id="modal_action">-</span></div>
                    </div>
                    <div class="col-12">
                        <label class="fw-bold text-muted small">DESCRIPTION</label>
                        <div class="p-3 bg-light rounded border text-dark font-monospace" id="modal_desc" style="white-space: pre-wrap;">-</div>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-bold text-muted small">IP ADDRESS</label>
                        <div class="font-monospace text-dark" id="modal_ip">-</div>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-bold text-muted small">USER AGENT</label>
                        <div class="small text-muted text-break" id="modal_agent">-</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function () {
    let table = $('#auditLogsTable').DataTable({
        processing: true,
        serverSide: true,
        searching: false,
        responsive: true,
        ajax: {
            url: '{{ route("audit.logs") }}',
            data: function (d) {
                d.search_keyword = $('#filter_keyword').val();
                d.user_id        = $('#filter_user').val();
                d.module_name    = $('#filter_module').val();
                d.action_type    = $('#filter_action').val();
                d.date_from      = $('#filter_date_from').val();
                d.date_to        = $('#filter_date_to').val();
            },
            dataSrc: function (json) {
                if (json.summary) {
                    $('#stat_total').text(json.summary.total);
                    $('#stat_today').text(json.summary.today);
                    $('#stat_admins').text(json.summary.active_admins);
                    $('#stat_deletions').text(json.summary.deletions);
                }
                return json.data;
            }
        },
        columns: [
            { data: 'DT_RowIndex',    name: 'DT_RowIndex', searchable: false, orderable: false },
            { data: 'admin_user',     name: 'user_name' },
            { data: 'module',         name: 'module' },
            { data: 'action',         name: 'action' },
            { data: 'description',    name: 'description' },
            { data: 'ip_address',     name: 'ip_address' },
            { data: 'created_at',     name: 'created_at' },
            { data: 'action_btn',     name: 'action_btn', searchable: false, orderable: false },
        ]
    });

    // Filters
    $('#btnFilterSearch, #filter_keyword').on('click keyup', function (e) {
        if (e.type === 'keyup' && e.keyCode !== 13) return;
        table.ajax.reload();
    });

    $('#filter_user, #filter_module, #filter_action, #filter_date_from, #filter_date_to').on('change', function () {
        table.ajax.reload();
    });

    $('#btnFilterReset').click(function () {
        $('#filter_keyword').val('');
        $('#filter_user').val('');
        $('#filter_module').val('');
        $('#filter_action').val('');
        $('#filter_date_from').val('');
        $('#filter_date_to').val('');
        table.ajax.reload();
    });

    // View Details Modal
    $(document).on('click', '.btn-view-log', function () {
        $('#modal_user').text($(this).data('user'));
        $('#modal_module').text($(this).data('module'));
        $('#modal_action').text($(this).data('action'));
        $('#modal_desc').text($(this).data('desc'));
        $('#modal_ip').text($(this).data('ip'));
        $('#modal_agent').text($(this).data('agent'));
        $('#modal_time').text($(this).data('time'));

        $('#logDetailModal').modal('show');
    });

    // Delete Single Log
    $(document).on('click', '.btn-delete-log', function () {
        let id = $(this).data('id');
        if (!confirm('Are you sure you want to delete this audit log entry?')) return;

        $.ajax({
            url: '{{ url("audit-logs") }}/' + id,
            type: 'DELETE',
            data: { _token: '{{ csrf_token() }}' },
            success: function (res) {
                if (res.status) {
                    toastr.success(res.message);
                    table.ajax.reload();
                } else {
                    toastr.error(res.message);
                }
            }
        });
    });

    // Clear All Logs
    $('#btnClearAllLogs').click(function () {
        if (!confirm('WARNING: Are you sure you want to clear ALL system audit logs? This action cannot be undone!')) return;

        $.ajax({
            url: '{{ route("audit.logs.clear-all") }}',
            type: 'DELETE',
            data: { _token: '{{ csrf_token() }}' },
            success: function (res) {
                if (res.status) {
                    toastr.success(res.message);
                    table.ajax.reload();
                } else {
                    toastr.error(res.message);
                }
            }
        });
    });
});
</script>
@endpush
