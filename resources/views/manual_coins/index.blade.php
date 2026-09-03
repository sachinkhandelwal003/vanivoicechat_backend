@extends('layouts.app')

@section('content')
<div class="card mb-3">
    <div class="card-header">
        <h5 class="mb-1">
            <i class="fas fa-coins text-warning me-2"></i>
            Manual Coins Send &amp; Deduct
        </h5>
        <small class="text-muted">Manually add or deduct coins from Normal User's Coin Wallet</small>
    </div>

    <div class="card-body">

        {{-- Summary Cards --}}
        <div class="row g-3 mb-4">
            <div class="col-4">
                <div class="card border text-center h-100 shadow-sm">
                    <div class="card-body py-3">
                        <i class="fas fa-receipt text-primary fa-2x mb-2"></i>
                        <h3 class="fw-bold text-primary mb-1" id="total_txns">0</h3>
                        <small class="text-muted text-uppercase fw-semibold">Total Transactions</small>
                    </div>
                </div>
            </div>
            <div class="col-4">
                <div class="card border text-center h-100 shadow-sm">
                    <div class="card-body py-3">
                        <i class="fas fa-coins text-success fa-2x mb-2"></i>
                        <h3 class="fw-bold text-success mb-1" id="total_sent">0</h3>
                        <small class="text-muted text-uppercase fw-semibold">Total Coins Sent</small>
                    </div>
                </div>
            </div>
            <div class="col-4">
                <div class="card border text-center h-100 shadow-sm">
                    <div class="card-body py-3">
                        <i class="fas fa-coins text-danger fa-2x mb-2"></i>
                        <h3 class="fw-bold text-danger mb-1" id="total_deduct">0</h3>
                        <small class="text-muted text-uppercase fw-semibold">Total Coins Deducted</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="d-flex gap-2 mb-4">
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#coinActionModal" id="btnSendCoins" onclick="openCoinModal('send')">
                <i class="fas fa-plus-circle me-1"></i> Send Coins
            </button>
            <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#coinActionModal" id="btnDeductCoins" onclick="openCoinModal('deduct')">
                <i class="fas fa-minus-circle me-1"></i> Deduct Coins
            </button>
        </div>

        {{-- Filter Row --}}
        <div class="row g-2 mb-3 align-items-end">
            <div class="col-6 col-md-3">
                <label class="form-label fw-semibold">Search User / Txn ID</label>
                <input type="text" class="form-control" id="search_keyword" placeholder="Name, UID or Txn ID">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label fw-semibold">Action</label>
                <select class="form-select" id="action_filter">
                    <option value="">All</option>
                    <option value="send">Send</option>
                    <option value="deduct">Deduct</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label fw-semibold">Date From</label>
                <input type="date" class="form-control" id="date_from">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label fw-semibold">Date To</label>
                <input type="date" class="form-control" id="date_to">
            </div>
            <div class="col-12 col-md-3">
                <div class="d-flex gap-2 justify-content-end">
                    <button class="btn btn-primary" id="btnSearch"><i class="fas fa-search me-1"></i>Search</button>
                    <button class="btn btn-secondary" id="btnReset"><i class="fas fa-sync-alt me-1"></i>Reset</button>
                </div>
            </div>
        </div>

        {{-- Transaction History Table --}}
        <div class="table-responsive scrollbar">
            <table class="table table-bordered table-striped align-middle w-100 small" id="coinTxnTable">
                <thead class="bg-200">
                    <tr>
                        <th>#</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Coins</th>
                        <th>Balance Change</th>
                        <th>Reason</th>
                        <th>Admin</th>
                        <th>Txn ID</th>
                        <th>Date &amp; Time</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

{{-- Coin Action Modal --}}
<div class="modal fade" id="coinActionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" id="actionModalHeader">
                <h5 class="modal-title" id="actionModalTitle">Send Coins</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">

                {{-- Step 1: Search User --}}
                <div id="step1">
                    <label class="form-label fw-semibold">Enter Normal User ID (UID)</label>
                    <div class="input-group mb-2">
                        <input type="text" class="form-control" id="searchUid" placeholder="e.g. 10001">
                        <button class="btn btn-outline-primary" id="btnSearchUser" type="button">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </div>
                    <div id="searchError" class="text-danger small d-none"></div>
                </div>

                {{-- Step 2: User Found --}}
                <div id="step2" class="d-none">
                    <div class="d-flex align-items-center gap-3 p-2 bg-light rounded mb-3">
                        <img id="found_img" src="" width="50" height="50" class="rounded-circle border">
                        <div>
                            <div class="fw-bold" id="found_name"></div>
                            <small class="text-muted" id="found_uid"></small>
                            <div><small>Current Coins: <strong class="text-primary" id="found_coins"></strong></small></div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary ms-auto" id="btnChangeUser">Change</button>
                    </div>

                    <form id="coinActionForm">
                        <input type="hidden" id="selected_user_id" name="user_id">
                        <input type="hidden" id="selected_action" name="action">

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Coins Amount <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="coins_amount" name="coins" min="1" placeholder="Enter coins amount" required>
                            <div id="deductHint" class="text-muted small mt-1 d-none">Available: <strong id="avail_coins"></strong> coins</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Reason <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="action_reason" name="reason" rows="3" placeholder="Enter reason..." required></textarea>
                        </div>

                        <div class="alert d-none" id="confirmAlert"></div>

                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-secondary w-50" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn w-50" id="btnSubmitCoin">
                                <i class="fas fa-check me-1"></i> Confirm
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function () {

    // ─── DataTable ────────────────────────────────────────────────────────────
    let table = $('#coinTxnTable').DataTable({
        processing: true,
        serverSide: true,
        searching: false,
        responsive: true,
        ajax: {
            url: '{{ route("manual-coins.index") }}',
            data: function (d) {
                d.search_keyword = $('#search_keyword').val();
                d.action         = $('#action_filter').val();
                d.date_from      = $('#date_from').val();
                d.date_to        = $('#date_to').val();
            },
            dataSrc: function (json) {
                $('#total_txns').text(json.summary.total_txns);
                $('#total_sent').text(json.summary.total_sent);
                $('#total_deduct').text(json.summary.total_deduct);
                return json.data;
            }
        },
        columns: [
            { data: 'DT_RowIndex',    name: 'DT_RowIndex',   searchable: false, orderable: false },
            { data: 'user_info',      name: 'user_info' },
            { data: 'action_badge',   name: 'action_badge' },
            { data: 'coins_formatted',name: 'coins_formatted' },
            { data: 'balance_change', name: 'balance_change' },
            { data: 'reason',         name: 'reason' },
            { data: 'admin_name',     name: 'admin_name' },
            { data: 'transaction_id', name: 'transaction_id' },
            { data: 'created_at',     name: 'created_at' },
        ]
    });

    $('#btnSearch').click(function () { table.ajax.reload(); });
    $('#btnReset').click(function () {
        $('#search_keyword').val('');
        $('#action_filter').val('');
        $('#date_from').val('');
        $('#date_to').val('');
        table.ajax.reload();
    });

    // ─── Coin Action Modal ────────────────────────────────────────────────────
    window.openCoinModal = function (action) {
        resetModal();
        $('#selected_action').val(action);
        if (action === 'send') {
            $('#actionModalHeader').css('background', 'linear-gradient(135deg,#28a745,#85d99b)').css('color', '#fff');
            $('#actionModalTitle').text('Send Coins to Normal User');
            $('#btnSubmitCoin').removeClass('btn-danger').addClass('btn-success').html('<i class="fas fa-paper-plane me-1"></i> Send Coins');
            $('#deductHint').addClass('d-none');
        } else {
            $('#actionModalHeader').css('background', 'linear-gradient(135deg,#dc3545,#f99ca3)').css('color', '#fff');
            $('#actionModalTitle').text('Deduct Coins from Normal User');
            $('#btnSubmitCoin').removeClass('btn-success').addClass('btn-danger').html('<i class="fas fa-minus-circle me-1"></i> Deduct Coins');
            $('#deductHint').removeClass('d-none');
        }
    };

    function resetModal () {
        $('#step1').removeClass('d-none');
        $('#step2').addClass('d-none');
        $('#searchUid').val('');
        $('#searchError').addClass('d-none').text('');
        $('#coins_amount').val('');
        $('#action_reason').val('');
        $('#confirmAlert').addClass('d-none');
    }

    // Search user
    $('#btnSearchUser').click(function () {
        let uid = $('#searchUid').val().trim();
        if (!uid) { return; }
        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
        $.ajax({
            url: '{{ route("manual-coins.search-user") }}',
            data: { uid: uid },
            success: function (res) {
                $('#btnSearchUser').prop('disabled', false).html('<i class="fas fa-search"></i> Search');
                if (res.status) {
                    let u = res.user;
                    $('#selected_user_id').val(u.id);
                    $('#found_img').attr('src', u.image);
                    $('#found_name').text(u.name);
                    $('#found_uid').text('UID: ' + u.uid);
                    $('#found_coins').text(u.total_points.toLocaleString());
                    $('#avail_coins').text(u.total_points.toLocaleString());
                    $('#step1').addClass('d-none');
                    $('#step2').removeClass('d-none');
                    $('#searchError').addClass('d-none');
                } else {
                    $('#searchError').removeClass('d-none').text(res.message);
                }
            },
            error: function () {
                $('#btnSearchUser').prop('disabled', false).html('<i class="fas fa-search"></i> Search');
                $('#searchError').removeClass('d-none').text('Network error. Please try again.');
            }
        });
    });

    $('#searchUid').keypress(function (e) {
        if (e.which === 13) { $('#btnSearchUser').click(); }
    });

    // Change user button
    $('#btnChangeUser').click(function () {
        $('#step2').addClass('d-none');
        $('#step1').removeClass('d-none');
        $('#searchUid').val('').focus();
    });

    // Submit form with confirmation
    $('#coinActionForm').submit(function (e) {
        e.preventDefault();
        let action = $('#selected_action').val();
        let coins  = parseInt($('#coins_amount').val());
        let avail  = parseInt($('#found_coins').text().replace(/,/g, ''));
        let reason = $('#action_reason').val().trim();

        if (!coins || coins < 1) {
            toastr.error('Please enter a valid coins amount.');
            return;
        }
        if (!reason) {
            toastr.error('Please enter a reason.');
            return;
        }
        if (action === 'deduct' && coins > avail) {
            toastr.error('Deduct amount (' + coins.toLocaleString() + ') exceeds available balance (' + avail.toLocaleString() + ' coins).');
            return;
        }

        let confirmMsg = action === 'send'
            ? 'Are you sure you want to SEND <strong>' + coins.toLocaleString() + '</strong> coins to <strong>' + $('#found_name').text() + '</strong>?'
            : 'Are you sure you want to DEDUCT <strong>' + coins.toLocaleString() + '</strong> coins from <strong>' + $('#found_name').text() + '</strong>?';

        $('#confirmAlert')
            .removeClass('d-none alert-success alert-danger')
            .addClass(action === 'send' ? 'alert-success' : 'alert-danger')
            .html('<i class="fas fa-exclamation-triangle me-1"></i>' + confirmMsg + '<br><small>Click Confirm below to proceed.</small>');

        // Change button to confirm
        $('#btnSubmitCoin').off('click').on('click', function (ev) {
            ev.preventDefault();
            doProcess(action, coins, reason);
        });

        $('#coinActionForm').off('submit');
    });

    function doProcess(action, coins, reason) {
        $('#btnSubmitCoin').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Processing...');
        let processUrl = '{{ route("manual-coins.process") }}';
        $.ajax({
            url: processUrl,
            method: 'POST',
            data: {
                _token:  '{{ csrf_token() }}',
                user_id: $('#selected_user_id').val(),
                action:  action,
                coins:   coins,
                reason:  reason,
            },
            success: function (res) {
                $('#btnSubmitCoin').prop('disabled', false);
                if (res.status) {
                    toastr.success(res.message);
                    $('#coinActionModal').modal('hide');
                    table.ajax.reload();
                    // Update displayed balance
                    $('#found_coins').text(res.after_coins.toLocaleString());
                } else {
                    toastr.error(res.message);
                    // Re-enable form submit
                    $('#coinActionForm').submit(function (e) { e.preventDefault(); });
                    resetSubmitButton(action);
                }
            },
            error: function () {
                toastr.error('Network error. Please try again.');
                $('#btnSubmitCoin').prop('disabled', false);
                resetSubmitButton(action);
            }
        });
    }

    function resetSubmitButton(action) {
        $('#btnSubmitCoin').off('click');
        if (action === 'send') {
            $('#btnSubmitCoin').html('<i class="fas fa-paper-plane me-1"></i> Send Coins');
        } else {
            $('#btnSubmitCoin').html('<i class="fas fa-minus-circle me-1"></i> Deduct Coins');
        }
        $('#coinActionForm').on('submit', function (e) { e.preventDefault(); });
    }

    // Reset modal on close
    $('#coinActionModal').on('hidden.bs.modal', function () {
        resetModal();
        $('#coinActionForm').off('submit').on('submit', function (e) {
            e.preventDefault();
            let action = $('#selected_action').val();
            let coins  = parseInt($('#coins_amount').val());
            let avail  = parseInt($('#found_coins').text().replace(/,/g, ''));
            let reason = $('#action_reason').val().trim();
            if (!coins || coins < 1) { toastr.error('Please enter a valid coins amount.'); return; }
            if (!reason) { toastr.error('Please enter a reason.'); return; }
            if (action === 'deduct' && coins > avail) { toastr.error('Deduct amount exceeds available balance.'); return; }
            let confirmMsg = action === 'send'
                ? 'Are you sure you want to SEND <strong>' + coins.toLocaleString() + '</strong> coins to <strong>' + $('#found_name').text() + '</strong>?'
                : 'Are you sure you want to DEDUCT <strong>' + coins.toLocaleString() + '</strong> coins from <strong>' + $('#found_name').text() + '</strong>?';
            $('#confirmAlert').removeClass('d-none alert-success alert-danger').addClass(action === 'send' ? 'alert-success' : 'alert-danger').html('<i class="fas fa-exclamation-triangle me-1"></i>' + confirmMsg + '<br><small>Click Confirm below to proceed.</small>');
            $('#btnSubmitCoin').off('click').on('click', function (ev) { ev.preventDefault(); doProcess(action, coins, reason); });
            $('#coinActionForm').off('submit');
        });
    });

});
</script>
@endpush
