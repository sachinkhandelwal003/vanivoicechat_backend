@extends('layouts.app')

@section('content')
<div class="card mb-3">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h5 class="mb-1">
                <i class="fas fa-user-slash text-danger me-2"></i>
                Room Kick Log
            </h5>
            <small class="text-muted">Complete history of users kicked/removed from voice chat rooms</small>
        </div>
    </div>

    <div class="card-body">

        {{-- Summary Cards --}}
        <div class="row g-3 mb-4">
            <div class="col-12 col-md-4">
                <div class="card border text-center h-100 shadow-sm">
                    <div class="card-body py-3">
                        <i class="fas fa-history text-primary fa-2x mb-2"></i>
                        <h3 class="fw-bold text-primary mb-1" id="total_kicks">0</h3>
                        <small class="text-muted text-uppercase fw-semibold">Total Kick Logs</small>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card border text-center h-100 shadow-sm">
                    <div class="card-body py-3">
                        <i class="fas fa-user-clock text-warning fa-2x mb-2"></i>
                        <h3 class="fw-bold text-warning mb-1" id="today_kicks">0</h3>
                        <small class="text-muted text-uppercase fw-semibold">Kicked Today</small>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card border text-center h-100 shadow-sm">
                    <div class="card-body py-3">
                        <i class="fas fa-calendar-alt text-danger fa-2x mb-2"></i>
                        <h3 class="fw-bold text-danger mb-1" id="month_kicks">0</h3>
                        <small class="text-muted text-uppercase fw-semibold">Kicked This Month</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="card border bg-light mb-4">
            <div class="card-body p-3">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md-4">
                        <label class="form-label fw-semibold small">Search (Room / User / Kicked By)</label>
                        <input type="text" class="form-control form-control-sm" id="search_keyword" placeholder="Name, Room ID, or User ID">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label fw-semibold small">Country</label>
                        <select class="form-select form-select-sm" id="country_filter">
                            <option value="">All Countries</option>
                            @foreach($countries as $country)
                                <option value="{{ $country->id }}">{{ $country->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-5">
                        <div class="row g-1">
                            <div class="col-6">
                                <label class="form-label fw-semibold small">Date From</label>
                                <input type="date" class="form-control form-control-sm" id="date_from">
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold small">Date To</label>
                                <input type="date" class="form-control form-control-sm" id="date_to">
                            </div>
                        </div>
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
            <table class="table table-bordered table-striped align-middle w-100 small" id="roomKickLogTable">
                <thead class="bg-200">
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>Room Info</th>
                        <th>Kicked User</th>
                        <th>Kicked By</th>
                        <th>Country</th>
                        <th>Kick Status</th>
                        <th>Date &amp; Time</th>
                        <th style="width: 140px;" class="text-center">Action</th>
                    </tr>
                </thead>
            </table>
        </div>

    </div>
</div>

{{-- Detail Modal --}}
<div class="modal fade" id="viewDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title text-white">
                    <i class="fas fa-user-slash me-2"></i> Kick Log Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="table-responsive">
                    <table class="table table-bordered mb-0 small">
                        <tbody>
                            <tr>
                                <th class="bg-light text-muted" style="width: 35%;">Room Name</th>
                                <td id="modal_room_name" class="fw-bold text-dark"></td>
                            </tr>
                            <tr>
                                <th class="bg-light text-muted">Room ID</th>
                                <td id="modal_room_id" class="fw-semibold"></td>
                            </tr>
                            <tr>
                                <th class="bg-light text-muted">Kicked User</th>
                                <td id="modal_user_name" class="fw-bold text-danger"></td>
                            </tr>
                            <tr>
                                <th class="bg-light text-muted">User ID</th>
                                <td id="modal_user_uid"></td>
                            </tr>
                            <tr>
                                <th class="bg-light text-muted">Country</th>
                                <td id="modal_user_country"></td>
                            </tr>
                            <tr>
                                <th class="bg-light text-muted">Kicked By</th>
                                <td id="modal_kicker_name" class="fw-bold text-primary"></td>
                            </tr>
                            <tr>
                                <th class="bg-light text-muted">Kicker ID</th>
                                <td id="modal_kicker_uid"></td>
                            </tr>
                            <tr>
                                <th class="bg-light text-muted">Kick Status</th>
                                <td id="modal_reason" class="text-break fw-semibold"></td>
                            </tr>
                            <tr>
                                <th class="bg-light text-muted">Date &amp; Time</th>
                                <td id="modal_date_time" class="fw-semibold"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function () {

    let baseUrl   = '{{ url("admin/room-kick-logs") }}';
    let deleteUrl = "{{ route('room.kick.logs.destroy', ':id') }}";

    let table = $('#roomKickLogTable').DataTable({
        processing: true,
        serverSide: true,
        searching: false,
        responsive: true,
        ajax: {
            url: '{{ route("room.kick.logs") }}',
            data: function (d) {
                d.search_keyword = $('#search_keyword').val();
                d.country_id     = $('#country_filter').val();
                d.date_from      = $('#date_from').val();
                d.date_to        = $('#date_to').val();
            },
            dataSrc: function (json) {
                if (json.summary) {
                    $('#total_kicks').text(json.summary.total_kicks);
                    $('#today_kicks').text(json.summary.today_kicks);
                    $('#month_kicks').text(json.summary.month_kicks);
                }
                return json.data;
            }
        },
        columns: [
            { data: 'DT_RowIndex',    name: 'DT_RowIndex',    searchable: false, orderable: false },
            { data: 'room_info',      name: 'room_info' },
            { data: 'kicked_user',    name: 'kicked_user' },
            { data: 'kicked_by',      name: 'kicked_by' },
            { data: 'country',        name: 'country' },
            { data: 'reason',         name: 'reason' },
            { data: 'created_at',     name: 'created_at' },
            { data: 'action',         name: 'action', searchable: false, orderable: false },
        ]
    });

    $('#btnSearch').click(function () { table.ajax.reload(); });

    $('#btnReset').click(function () {
        $('#search_keyword').val('');
        $('#country_filter').val('');
        $('#date_from').val('');
        $('#date_to').val('');
        table.ajax.reload();
    });

    // Enter key search
    $('#search_keyword').keypress(function (e) {
        if (e.which === 13) { table.ajax.reload(); }
    });

    // View Details Modal
    $(document).on('click', '.btn-view-detail', function () {
        let info = $(this).data('info');
        if (typeof info === 'string') {
            info = JSON.parse(info);
        }

        $('#modal_room_name').text(info.room_name);
        $('#modal_room_id').text(info.room_id);
        $('#modal_user_name').text(info.user_name);
        $('#modal_user_uid').text(info.user_uid);
        $('#modal_user_country').text(info.user_country);
        $('#modal_kicker_name').text(info.kicker_name);
        $('#modal_kicker_uid').text(info.kicker_uid);
        $('#modal_reason').text(info.reason);
        $('#modal_date_time').text(info.created_at);

        $('#viewDetailModal').modal('show');
    });

    // Remove Kick Log
    $(document).on('click', '.btn-delete-log', function () {
        let id = $(this).data('id');
        if (!confirm('Are you sure you want to remove this kick log entry? This will not affect the room or user.')) {
            return;
        }

        $.ajax({
            url: deleteUrl.replace(':id', id),
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function (res) {
                if (res.status) {
                    toastr.success(res.message);
                    table.ajax.reload(null, false);
                } else {
                    toastr.error(res.message);
                }
            },
            error: function (xhr) {
                let msg = 'Error removing kick log entry.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                toastr.error(msg);
            }
        });
    });

});
</script>
@endpush
