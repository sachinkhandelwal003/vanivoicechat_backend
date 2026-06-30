@extends('layouts.app')

@section('content')

<div class="card mb-3">
    <div class="card-header">
        <h5>User Spending Records :: Wallet</h5>
    </div>

    <div class="card-body">

        {{-- FILTERS --}}
        <div class="row mb-3">

            <div class="col-md-2">
                <input type="text" id="uid" class="form-control" placeholder="Serial / UID">
            </div>

            <div class="col-md-2">
                <input type="text" id="username" class="form-control" placeholder="Enter username">
            </div>

            <div class="col-md-2">
                <select id="type" class="form-control">
                    <option value="">Type</option>
                    <option value="income">Income</option>
                    <option value="expenditure">Expenditure</option>
                </select>
            </div>

            <div class="col-md-2">
                <select id="transaction_type" class="form-control">
                    <option value="">User transaction history</option>
                    <option value="Buy">Buy</option>
                    <option value="top up">Top Up</option>
                </select>
            </div>

            <div class="col-md-2">
                <input type="date" id="date_from" class="form-control">
            </div>

            <div class="col-md-2">
                <input type="date" id="date_to" class="form-control">
            </div>

        </div>

        <div class="text-end mb-3">
            <button class="btn btn-primary" id="searchBtn">Search</button>
        </div>

        {{-- WALLET TABLE --}}
        <div class="table-responsive">
            <table class="table table-striped table-datatable" style="width:100%">

                <thead class="bg-light">
                    <tr>
                        <th>User Information</th>
                        <th>Type</th>
                        <th>Transaction Amount</th>
                        <th>Wallet Balance</th>
                        <th>Wallet Type</th>
                        <th>Remark</th>
                        <th>Operate</th>
                        <th>Time</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody></tbody>

            </table>
        </div>

    </div>
</div>

@endsection

@section('js')
<script>
$(function () {

    let table = $('.table-datatable').DataTable({

        ordering: false, // FIX DT_RowIndex error

        ajax: {
            url: "{{ route('walleet') }}",
            data: function (d) {
                d.uid = $('#uid').val();
                d.username = $('#username').val();
                d.type = $('#type').val();
                d.transaction_type = $('#transaction_type').val();
                d.date_from = $('#date_from').val();
                d.date_to = $('#date_to').val();
            }
        },

        columns: [
            { data: 'user_info', name: 'user_info' },
            { data: 'type', name: 'type' },
            { data: 'transaction_amount', name: 'transaction_amount' },
            { data: 'wallet_balance', name: 'wallet_balance' },
            { data: 'wallet_type', name: 'wallet_type' },
            { data: 'remark', name: 'remark' },
            { data: 'operate', name: 'operate' },
            { data: 'created_at', name: 'created_at' },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ]

    });

    $('#searchBtn').click(function () {
        table.ajax.reload();
    });

});
</script>
@endsection
