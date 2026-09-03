@extends('layouts.app')

@section('content')
<div class="card mb-3">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h5 class="mb-1">
                <i class="fas fa-handshake text-primary me-2"></i>
                Relationship Fee Configure
            </h5>
            <small class="text-muted">Configure Invite Fee Coins and Break Fee Coins country-wise for relationship types</small>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addFeeModal">
                <i class="fas fa-plus me-1"></i> Add Fee Config
            </button>
        </div>
    </div>

    <div class="card-body">

        {{-- Summary Cards --}}
        <div class="row g-3 mb-4">
            <div class="col-12 col-md-4">
                <div class="card border text-center h-100 shadow-sm">
                    <div class="card-body py-3">
                        <i class="fas fa-sliders-h text-primary fa-2x mb-2"></i>
                        <h3 class="fw-bold text-primary mb-1" id="total_configs">0</h3>
                        <small class="text-muted text-uppercase fw-semibold">Total Fee Configs</small>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card border text-center h-100 shadow-sm">
                    <div class="card-body py-3">
                        <i class="fas fa-check-circle text-success fa-2x mb-2"></i>
                        <h3 class="fw-bold text-success mb-1" id="active_configs">0</h3>
                        <small class="text-muted text-uppercase fw-semibold">Active Configs</small>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card border text-center h-100 shadow-sm">
                    <div class="card-body py-3">
                        <i class="fas fa-globe text-info fa-2x mb-2"></i>
                        <h3 class="fw-bold text-info mb-1" id="total_countries">0</h3>
                        <small class="text-muted text-uppercase fw-semibold">Available Countries</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="card border bg-light mb-4">
            <div class="card-body p-3">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md-3">
                        <label class="form-label fw-semibold small">Search</label>
                        <input type="text" class="form-control form-control-sm" id="search_keyword" placeholder="Relationship Type or Country">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label fw-semibold small">Country</label>
                        <select class="form-select form-select-sm" id="country_filter">
                            <option value="">All Configurations</option>
                            <option value="all">All Countries (Default)</option>
                            @foreach($countries as $country)
                                <option value="{{ $country->id }}">{{ $country->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label fw-semibold small">Relationship Type</label>
                        <select class="form-select form-select-sm" id="type_filter">
                            <option value="">All Types</option>
                            <option value="CP">CP</option>
                            <option value="Brother">Brother</option>
                            <option value="Sister">Sister</option>
                            <option value="Confident">Confident</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label fw-semibold small">Status</label>
                        <select class="form-select form-select-sm" id="status_filter">
                            <option value="">All Status</option>
                            <option value="1">Active</option>
                            <option value="0">Disabled</option>
                        </select>
                    </div>
                </div>
                <div class="d-flex justify-content-end gap-2 mt-3">
                    <button class="btn btn-primary btn-sm px-3" id="btnSearch">
                        <i class="fas fa-search me-1"></i> Search
                    </button>
                    <button class="btn btn-secondary btn-sm px-3" id="btnReset">
                        <i class="fas fa-sync-alt me-1"></i> Reset
                    </button>
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="table-responsive scrollbar">
            <table class="table table-bordered table-striped align-middle w-100 small" id="relFeeTable">
                <thead class="bg-200">
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>Country</th>
                        <th>Relationship Type</th>
                        <th>Invite Fee (Coins)</th>
                        <th>Break Fee (Coins)</th>
                        <th>Status</th>
                        <th>Created Date</th>
                        <th style="width: 80px;" class="text-center">Action</th>
                    </tr>
                </thead>
            </table>
        </div>

    </div>
</div>

{{-- Add Modal --}}
<div class="modal fade" id="addFeeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title text-white"><i class="fas fa-plus-circle me-2"></i>Add Relationship Fee Config</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form id="addFeeForm">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Target Country</label>
                        <select class="form-select" name="country_id" id="add_country_id">
                            <option value="">All Countries (Global Default)</option>
                            @foreach($countries as $country)
                                <option value="{{ $country->id }}">{{ $country->name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Select specific country or leave for global default.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Relationship Type <span class="text-danger">*</span></label>
                        <select class="form-select mb-2" id="add_rel_type_select">
                            <option value="CP">CP</option>
                            <option value="Brother">Brother</option>
                            <option value="Sister">Sister</option>
                            <option value="Confident">Confident</option>
                            <option value="custom">-- Custom Type --</option>
                        </select>
                        <input type="text" class="form-control" name="relationship_type" id="add_relationship_type" value="CP" placeholder="e.g. Bestie, Buddy" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Invite Fee (Coins) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="invite_fee" min="0" placeholder="e.g. 10000" required>
                        <small class="text-muted">Coins deducted when user invites someone for this relationship.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Break Fee (Coins) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="break_fee" min="0" placeholder="e.g. 20000" required>
                        <small class="text-muted">Coins deducted when relationship is broken.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Status</label>
                        <select class="form-select" name="status">
                            <option value="1">Active</option>
                            <option value="0">Disabled</option>
                        </select>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-secondary w-50" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success w-50"><i class="fas fa-check me-1"></i>Save Configuration</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Edit Modal --}}
<div class="modal fade" id="editFeeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title text-white"><i class="fas fa-edit me-2"></i>Edit Relationship Fee Config</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form id="editFeeForm">
                    <input type="hidden" id="edit_id">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Target Country</label>
                        <select class="form-select" name="country_id" id="edit_country_id">
                            <option value="">All Countries (Global Default)</option>
                            @foreach($countries as $country)
                                <option value="{{ $country->id }}">{{ $country->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Relationship Type <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="relationship_type" id="edit_relationship_type" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Invite Fee (Coins) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="invite_fee" id="edit_invite_fee" min="0" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Break Fee (Coins) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="break_fee" id="edit_break_fee" min="0" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Status</label>
                        <select class="form-select" name="status" id="edit_status">
                            <option value="1">Active</option>
                            <option value="0">Disabled</option>
                        </select>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-secondary w-50" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary w-50"><i class="fas fa-save me-1"></i>Update Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function () {

    let storeUrl  = "{{ route('relationship.fee.configs.store') }}";
    let updateUrl = "{{ route('relationship.fee.configs.update', '__ID__') }}";
    let deleteUrl = "{{ route('relationship.fee.configs.destroy', '__ID__') }}";
    let toggleUrl = "{{ route('relationship.fee.configs.toggle', '__ID__') }}";

    let table = $('#relFeeTable').DataTable({
        processing: true,
        serverSide: true,
        searching: false,
        responsive: true,
        ajax: {
            url: '{{ route("relationship.fee.configs") }}',
            data: function (d) {
                d.search_keyword    = $('#search_keyword').val();
                d.country_id        = $('#country_filter').val();
                d.relationship_type = $('#type_filter').val();
                d.status            = $('#status_filter').val();
            },
            dataSrc: function (json) {
                if (json.summary) {
                    $('#total_configs').text(json.summary.total_configs);
                    $('#active_configs').text(json.summary.active_configs);
                    $('#total_countries').text(json.summary.total_countries);
                }
                return json.data;
            }
        },
        columns: [
            { data: 'DT_RowIndex',        name: 'DT_RowIndex',        searchable: false, orderable: false },
            { data: 'country',            name: 'country' },
            { data: 'relationship_type',   name: 'relationship_type' },
            { data: 'invite_fee',         name: 'invite_fee' },
            { data: 'break_fee',          name: 'break_fee' },
            { data: 'status',             name: 'status' },
            { data: 'created_at',         name: 'created_at' },
            { data: 'action',             name: 'action', searchable: false, orderable: false },
        ]
    });

    $('#btnSearch').click(function () { table.ajax.reload(); });

    $('#btnReset').click(function () {
        $('#search_keyword').val('');
        $('#country_filter').val('');
        $('#type_filter').val('');
        $('#status_filter').val('');
        table.ajax.reload();
    });

    $('#search_keyword').keypress(function (e) {
        if (e.which === 13) { table.ajax.reload(); }
    });

    // Preset select toggle for Add Modal
    $('#add_rel_type_select').change(function () {
        let val = $(this).val();
        if (val === 'custom') {
            $('#add_relationship_type').val('').focus();
        } else {
            $('#add_relationship_type').val(val);
        }
    });

    // Add Form Submit
    $('#addFeeForm').submit(function (e) {
        e.preventDefault();
        let btn = $(this).find('[type=submit]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Saving...');
        $.ajax({
            url: storeUrl,
            method: 'POST',
            data: $(this).serialize() + '&_token={{ csrf_token() }}',
            success: function (res) {
                btn.prop('disabled', false).html('<i class="fas fa-check me-1"></i>Save Configuration');
                if (res.status) {
                    toastr.success(res.message);
                    $('#addFeeModal').modal('hide');
                    $('#addFeeForm')[0].reset();
                    $('#add_relationship_type').val('CP');
                    table.ajax.reload();
                } else {
                    toastr.error(res.message);
                }
            },
            error: function (xhr) {
                btn.prop('disabled', false).html('<i class="fas fa-check me-1"></i>Save Configuration');
                let msg = 'Error saving configuration.';
                if (xhr.responseJSON && xhr.responseJSON.message) { msg = xhr.responseJSON.message; }
                toastr.error(msg);
            }
        });
    });

    // Edit Modal Open
    $(document).on('click', '.btn-edit', function () {
        let id      = $(this).data('id');
        let country = $(this).data('country');
        let type    = $(this).data('type');
        let invite  = $(this).data('invite');
        let breakF  = $(this).data('break');
        let status  = $(this).data('status');

        $('#edit_id').val(id);
        $('#edit_country_id').val(country);
        $('#edit_relationship_type').val(type);
        $('#edit_invite_fee').val(invite);
        $('#edit_break_fee').val(breakF);
        $('#edit_status').val(status);

        $('#editFeeModal').modal('show');
    });

    // Edit Form Submit
    $('#editFeeForm').submit(function (e) {
        e.preventDefault();
        let id  = $('#edit_id').val();
        let btn = $(this).find('[type=submit]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Updating...');
        $.ajax({
            url: updateUrl.replace('__ID__', id),
            method: 'POST',
            data: $(this).serialize() + '&_token={{ csrf_token() }}',
            success: function (res) {
                btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i>Update Changes');
                if (res.status) {
                    toastr.success(res.message);
                    $('#editFeeModal').modal('hide');
                    table.ajax.reload();
                } else {
                    toastr.error(res.message);
                }
            },
            error: function (xhr) {
                btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i>Update Changes');
                let msg = 'Error updating configuration.';
                if (xhr.responseJSON && xhr.responseJSON.message) { msg = xhr.responseJSON.message; }
                toastr.error(msg);
            }
        });
    });

    // Toggle Status
    $(document).on('click', '.btn-toggle', function () {
        let id     = $(this).data('id');
        let status = $(this).data('status');
        let action = status == 1 ? 'disable' : 'enable';
        if (!confirm('Are you sure you want to ' + action + ' this configuration?')) return;

        $.ajax({
            url: toggleUrl.replace('__ID__', id),
            method: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function (res) {
                if (res.status) {
                    toastr.success(res.message);
                    table.ajax.reload(null, false);
                } else {
                    toastr.error(res.message);
                }
            }
        });
    });

    // Delete Configuration
    $(document).on('click', '.btn-delete', function () {
        let id   = $(this).data('id');
        let type = $(this).data('type');
        if (!confirm('Delete fee configuration for "' + type + '"?')) return;

        $.ajax({
            url: deleteUrl.replace('__ID__', id),
            method: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function (res) {
                if (res.status) {
                    toastr.success(res.message);
                    table.ajax.reload(null, false);
                } else {
                    toastr.error(res.message);
                }
            },
            error: function () {
                toastr.error('Error deleting configuration.');
            }
        });
    });

});
</script>
@endpush
