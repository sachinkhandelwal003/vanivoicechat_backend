@extends('layouts.app')

@section('content')
<div class="card mb-3">
    <div class="card-header">
        <h5 class="mb-1">
            <i class="fas fa-money-bill-wave text-primary me-2"></i>
            Withdrawal Requests
        </h5>
        <small class="text-muted">Manage user bank and USDT withdrawal requests</small>
    </div>

    <div class="card-body">
        <div class="row mb-4">
            <div class="col-xl col-md-6 mb-3">
                <div class="card border h-100 text-center shadow-sm">
                    <div class="card-body">
                        <i class="fas fa-clock text-warning fa-2x mb-2"></i>
                        <h3 class="fw-bold text-warning mb-1" id="pending_count">0</h3>
                        <small class="text-muted text-uppercase">Pending</small>
                    </div>
                </div>
            </div>
            <div class="col-xl col-md-6 mb-3">
                <div class="card border h-100 text-center shadow-sm">
                    <div class="card-body">
                        <i class="fas fa-check-circle text-success fa-2x mb-2"></i>
                        <h3 class="fw-bold text-success mb-1" id="approved_count">0</h3>
                        <small class="text-muted text-uppercase">Approved</small>
                    </div>
                </div>
            </div>
            <div class="col-xl col-md-6 mb-3">
                <div class="card border h-100 text-center shadow-sm">
                    <div class="card-body">
                        <i class="fas fa-times-circle text-danger fa-2x mb-2"></i>
                        <h3 class="fw-bold text-danger mb-1" id="rejected_count">0</h3>
                        <small class="text-muted text-uppercase">Rejected</small>
                    </div>
                </div>
            </div>
            <div class="col-xl col-md-6 mb-3">
                <div class="card border h-100 text-center shadow-sm">
                    <div class="card-body">
                        <i class="fas fa-dollar-sign text-primary fa-2x mb-2"></i>
                        <h3 class="fw-bold text-primary mb-1">$<span id="total_approved_usd">0.00</span></h3>
                        <small class="text-muted text-uppercase">Total Approved</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-2 mb-4 align-items-end">
            <div class="col-6 col-sm-4 col-md-2">
                <label class="form-label">User UID / Txn ID</label>
                <input type="text" class="form-control" id="search_keyword" placeholder="UID or Txn ID">
            </div>
            <div class="col-6 col-sm-4 col-md-2">
                <label class="form-label">Method</label>
                <select class="form-select" id="method">
                    <option value="">All</option>
                    <option value="bank">Bank</option>
                    <option value="usdt">USDT</option>
                </select>
            </div>
            <div class="col-6 col-sm-4 col-md-2">
                <label class="form-label">Status</label>
                <select class="form-select" id="status">
                    <option value="">All</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>
            <div class="col-6 col-sm-4 col-md-2">
                <label class="form-label">Country</label>
                <select class="form-select" id="country_id">
                    <option value="">All Countries</option>
                    @foreach($countries as $country)
                        <option value="{{ $country->id }}">{{ $country->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-sm-4 col-md-2">
                <label class="form-label">Date From</label>
                <input type="date" class="form-control" id="date_from">
            </div>
            <div class="col-6 col-sm-4 col-md-2">
                <label class="form-label">Date To</label>
                <input type="date" class="form-control" id="date_to">
            </div>
            <div class="col-12 col-md-12 mt-3">
                <div class="d-flex gap-2 justify-content-end">
                    <button class="btn btn-primary" id="btnSearch">
                        <i class="fas fa-search me-1"></i>Search
                    </button>
                    <button class="btn btn-secondary" id="btnReset">
                        <i class="fas fa-sync-alt me-1"></i>Reset
                    </button>
                </div>
            </div>
        </div>

        <div class="table-responsive scrollbar">
            <table class="table table-bordered table-striped align-middle w-100" id="withdrawalTable">
                <thead class="bg-200">
                    <tr>
                        <th>#</th>
                        <th>User</th>
                        <th>Country</th>
                        <th>Method</th>
                        <th>Amount ($)</th>
                        <th>Payment Details</th>
                        <th>Status</th>
                        <th>Transaction ID</th>
                        <th>Admin Log</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<!-- Action Modal -->
<div class="modal fade" id="actionModal" tabindex="-1" aria-labelledby="actionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="actionForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="actionModalLabel">Process Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="request_id" name="request_id">
                    <input type="hidden" id="action_type" name="action">

                    <div class="mb-3">
                        <p class="mb-1 text-muted">User: <strong id="modal_user" class="text-dark"></strong></p>
                        <p class="mb-1 text-muted">Amount: <strong id="modal_amount" class="text-dark"></strong></p>
                        <p class="mb-0 text-muted">Method: <strong id="modal_method" class="text-dark"></strong></p>
                    </div>
                    <hr>
                    
                    <div id="approveSection" class="d-none">
                        <div class="mb-3">
                            <label class="form-label">Transaction ID <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="transaction_id" name="transaction_id" placeholder="Enter Transaction ID">
                        </div>
                    </div>

                    <div id="rejectSection" class="d-none">
                        <div class="mb-3">
                            <label class="form-label">Rejection Remarks <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="remarks" name="remarks" rows="3" placeholder="Enter reason for rejection"></textarea>
                            <small class="text-danger mt-1">Note: Rejecting will refund the amount to the user's wallet.</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="btnSubmitAction">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        let table = $('#withdrawalTable').DataTable({
            processing: true,
            serverSide: true,
            searching: false,
            responsive: true,
            ajax: {
                url: "{{ route('withdrawal.requests') }}",
                data: function(d) {
                    d.search_keyword = $("#search_keyword").val();
                    d.method = $("#method").val();
                    d.status = $("#status").val();
                    d.date_from = $("#date_from").val();
                    d.date_to = $("#date_to").val();
                    d.country_id = $("#country_id").val();
                },
                dataSrc: function(json) {
                    $("#pending_count").text(json.summary.pending_count);
                    $("#approved_count").text(json.summary.approved_count);
                    $("#rejected_count").text(json.summary.rejected_count);
                    $("#total_approved_usd").text(json.summary.total_approved_usd);
                    return json.data;
                }
            },
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', searchable: false, orderable: false },
                { data: 'user', name: 'user' },
                { data: 'country', name: 'country' },
                { data: 'method', name: 'method' },
                { data: 'amount', name: 'amount' },
                { data: 'payment_details', name: 'payment_details', sortable: false },
                { data: 'status', name: 'status' },
                { data: 'transaction_id', name: 'transaction_id' },
                { data: 'admin_log', name: 'admin_log' },
                { data: 'requested_at', name: 'requested_at' },
                { data: 'action', name: 'action', searchable: false, orderable: false }
            ]
        });

        $("#btnSearch").click(function() { table.ajax.reload(); });
        
        $("#btnReset").click(function() {
            $("#search_keyword").val("");
            $("#method").val("");
            $("#status").val("");
            $("#date_from").val("");
            $("#date_to").val("");
            $("#country_id").val("");
            table.ajax.reload();
        });

        // Open modal and set data
        $(document).on('click', '.action-btn', function() {
            let id = $(this).data('id');
            let type = $(this).data('type');
            let user = $(this).data('user');
            let amount = $(this).data('amount');
            let method = $(this).data('method');

            $("#request_id").val(id);
            $("#action_type").val(type);
            $("#modal_user").text(user);
            $("#modal_amount").text("$ " + amount);
            $("#modal_method").text(method.toUpperCase());

            if(type === 'approve') {
                $("#actionModalLabel").text("Approve Withdrawal");
                $("#btnSubmitAction").removeClass("btn-danger").addClass("btn-success").text("Approve");
                $("#approveSection").removeClass("d-none");
                $("#rejectSection").addClass("d-none");
                $("#transaction_id").val("");
            } else {
                $("#actionModalLabel").text("Reject Withdrawal");
                $("#btnSubmitAction").removeClass("btn-success").addClass("btn-danger").text("Reject & Refund");
                $("#rejectSection").removeClass("d-none");
                $("#approveSection").addClass("d-none");
                $("#remarks").val("");
            }
        });

        // Handle form submit
        let updateStatusBaseUrl = "{{ route('withdrawal.update-status', '__ID__') }}";

        $("#actionForm").submit(function(e) {
            e.preventDefault();
            let id = $("#request_id").val();
            let url = updateStatusBaseUrl.replace('__ID__', id);
            
            $.ajax({
                url: url,
                type: "POST",
                data: $(this).serialize(),
                beforeSend: function() {
                    $("#btnSubmitAction").prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
                },
                success: function(res) {
                    if(res.status) {
                        toastr.success(res.message);
                        $("#actionModal").modal('hide');
                        table.ajax.reload(null, false);
                    } else {
                        toastr.error(res.message);
                    }
                },
                error: function(err) {
                    toastr.error("An error occurred");
                },
                complete: function() {
                    $("#btnSubmitAction").prop('disabled', false).text("Submit");
                }
            });
        });
    });
</script>
@endpush
