@extends("layouts.app")

@section("content")
<div class="card mb-3">
    <div class="card-header">
        <h5 class="mb-1">
            <i class="fas fa-exchange-alt text-primary me-2"></i>
            USD Coins Exchange Log
        </h5>
        <small class="text-muted">Complete history of USD to Coins exchange transactions</small>
    </div>

    <div class="card-body">

        {{-- Summary Cards --}}
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card border text-center h-100 shadow-sm">
                    <div class="card-body py-3">
                        <i class="fas fa-receipt text-primary fa-2x mb-2"></i>
                        <h3 class="fw-bold text-primary mb-1" id="total_exchanges">0</h3>
                        <small class="text-muted text-uppercase fw-semibold">Total Exchanges</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border text-center h-100 shadow-sm">
                    <div class="card-body py-3">
                        <i class="fas fa-dollar-sign text-success fa-2x mb-2"></i>
                        <h3 class="fw-bold text-success mb-1">$<span id="total_usd">0.00</span></h3>
                        <small class="text-muted text-uppercase fw-semibold">Total USD Exchanged</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border text-center h-100 shadow-sm">
                    <div class="card-body py-3">
                        <i class="fas fa-coins text-warning fa-2x mb-2"></i>
                        <h3 class="fw-bold text-warning mb-1"><span id="total_coins">0</span></h3>
                        <small class="text-muted text-uppercase fw-semibold">Total Coins Given</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border text-center h-100 shadow-sm">
                    <div class="card-body py-3">
                        <i class="fas fa-check-circle text-success fa-2x mb-2"></i>
                        <h3 class="fw-bold text-success mb-1" id="success_count">0</h3>
                        <small class="text-muted text-uppercase fw-semibold">Successful</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="row g-2 mb-4 align-items-end">
            <div class="col-6 col-sm-4 col-md-2">
                <label class="form-label fw-semibold">User Name / ID / Txn</label>
                <input type="text" class="form-control" id="search_keyword" placeholder="Search...">
            </div>
            <div class="col-6 col-sm-4 col-md-2">
                <label class="form-label fw-semibold">Role</label>
                <select class="form-select" id="role">
                    <option value="">All Roles</option>
                    <option value="normal">Normal User</option>
                    <option value="host">Host</option>
                    <option value="agency">Agency</option>
                    <option value="seller">Seller</option>
                    <option value="merchant">Merchant</option>
                </select>
            </div>
            <div class="col-6 col-sm-4 col-md-2">
                <label class="form-label fw-semibold">Country</label>
                <select class="form-select" id="country_id">
                    <option value="">All Countries</option>
                    @foreach($countries as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-sm-4 col-md-2">
                <label class="form-label fw-semibold">Status</label>
                <select class="form-select" id="status">
                    <option value="">All</option>
                    <option value="success">Success</option>
                    <option value="failed">Failed</option>
                </select>
            </div>
            <div class="col-6 col-sm-4 col-md-2">
                <label class="form-label fw-semibold">Date From</label>
                <input type="date" class="form-control" id="date_from">
            </div>
            <div class="col-6 col-sm-4 col-md-2">
                <label class="form-label fw-semibold">Date To</label>
                <input type="date" class="form-control" id="date_to">
            </div>
            <div class="col-12 mt-2">
                <div class="d-flex gap-2 justify-content-end">
                    <button class="btn btn-primary px-4" id="btnSearch">
                        <i class="fas fa-search me-1"></i> Search
                    </button>
                    <button class="btn btn-secondary px-4" id="btnReset">
                        <i class="fas fa-sync-alt me-1"></i> Reset
                    </button>
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="table-responsive scrollbar">
            <table class="table table-bordered table-striped align-middle w-100 small" id="exchangeLogTable">
                <thead class="bg-200">
                    <tr>
                        <th>#</th>
                        <th>User</th>
                        <th>Role</th>
                        <th>Country</th>
                        <th>USD Amount</th>
                        <th>Exchange Rate</th>
                        <th>Coins Received</th>
                        <th>Wallet Type</th>
                        <th>Transaction ID</th>
                        <th>Status</th>
                        <th>Date & Time</th>
                        <th>Details</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

{{-- Detail Modal --}}
<div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg,#6C3FC8,#9b6dff); color:#fff;">
                <h5 class="modal-title" id="detailModalLabel">
                    <i class="fas fa-exchange-alt me-2"></i>Exchange Transaction Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted fw-semibold" style="width:40%">User</td>
                        <td><strong id="d_user"></strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-semibold">User ID</td>
                        <td><code id="d_uid"></code></td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-semibold">Transaction ID</td>
                        <td><code id="d_txn"></code></td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-semibold">USD Amount</td>
                        <td><span class="text-success fw-bold">$<span id="d_usd"></span></span></td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-semibold">Exchange Rate</td>
                        <td><span id="d_rate"></span> coins per USD</td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-semibold">Coins Received</td>
                        <td><span class="text-warning fw-bold"><i class="fas fa-coins me-1"></i><span id="d_coins"></span></span></td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-semibold">Wallet Type</td>
                        <td><span id="d_wallet"></span></td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-semibold">Status</td>
                        <td><span id="d_status"></span></td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-semibold">Date & Time</td>
                        <td><span id="d_date"></span></td>
                    </tr>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function () {
    let table = $("#exchangeLogTable").DataTable({
        processing: true,
        serverSide: true,
        searching: false,
        responsive: true,
        ajax: {
            url: "{{ route('exchange.log') }}",
            data: function (d) {
                d.search_keyword = $("#search_keyword").val();
                d.role           = $("#role").val();
                d.country_id     = $("#country_id").val();
                d.status         = $("#status").val();
                d.date_from      = $("#date_from").val();
                d.date_to        = $("#date_to").val();
            },
            dataSrc: function (json) {
                $("#total_exchanges").text(json.summary.total_exchanges);
                $("#total_usd").text(json.summary.total_usd);
                $("#total_coins").text(json.summary.total_coins);
                $("#success_count").text(json.summary.success_count);
                return json.data;
            }
        },
        columns: [
            { data: "DT_RowIndex",    name: "DT_RowIndex",    searchable: false, orderable: false },
            { data: "user_info",      name: "user_info" },
            { data: "role",           name: "role" },
            { data: "country",        name: "country" },
            { data: "usd_amount",     name: "usd_amount" },
            { data: "exchange_rate",  name: "exchange_rate" },
            { data: "coins_received", name: "coins_received" },
            { data: "wallet_type",    name: "wallet_type" },
            { data: "transaction_id", name: "transaction_id" },
            { data: "status",         name: "status" },
            { data: "created_at",     name: "created_at" },
            { data: "action",         name: "action", searchable: false, orderable: false }
        ]
    });

    $("#btnSearch").click(function () { table.ajax.reload(); });

    $("#btnReset").click(function () {
        $("#search_keyword").val("");
        $("#role").val("");
        $("#country_id").val("");
        $("#status").val("");
        $("#date_from").val("");
        $("#date_to").val("");
        table.ajax.reload();
    });

    // Detail modal
    $(document).on("click", ".btn-view-detail", function () {
        $("#d_user").text($(this).data("user"));
        $("#d_uid").text($(this).data("uid"));
        $("#d_txn").text($(this).data("txn"));
        $("#d_usd").text($(this).data("usd"));
        $("#d_rate").text($(this).data("rate"));
        $("#d_coins").text($(this).data("coins"));
        $("#d_wallet").text($(this).data("wallet") || "Main");
        $("#d_date").text($(this).data("date"));

        let st = $(this).data("status");
        if (st === "success") {
            $("#d_status").html('<span class="badge bg-success"><i class="fas fa-check me-1"></i>Success</span>');
        } else {
            $("#d_status").html('<span class="badge bg-danger"><i class="fas fa-times me-1"></i>Failed</span>');
        }
    });
});
</script>
@endpush

