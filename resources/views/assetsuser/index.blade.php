@extends('layouts.app')

@section('content')

<div class="card mb-3">
    <div class="card-header">
        <h5>Assets User :: List of Prolific Users</h5>
    </div>

    <div class="card-body">

        {{-- FILTERS --}}
        <div class="row mb-3">

            <div class="col-md-3">
                <input type="text" id="uid" class="form-control" placeholder="Serial / UID">
            </div>

            <div class="col-md-3">
                <input type="text" id="username" class="form-control" placeholder="Please enter username">
            </div>

            <div class="col-md-2">
                <button class="btn btn-primary w-100" id="searchBtn">Search</button>
            </div>
        </div>

        {{-- TABLE --}}
        <div class="table-responsive">
            <table class="table table-striped table-datatable" style="width:100%">
                <thead class="bg-light">
                    <tr>
                        <th>User Information</th>
                        <th>Recharge Amount</th>
                        <th>Area</th>
                        <th>Recharge Details</th>
                        <th>Remark</th>
                        <th>Time</th>
                        <th>Operate</th>
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
        ajax: {
            url: "{{ route('assets-user') }}",
            data: function (d) {
                d.uid = $('#uid').val();
                d.username = $('#username').val();
            }
        },
        columns: [
            { data: 'user_info' },
            { data: 'recharge_amount' },
            { data: 'area' },
            { data: 'recharge_details' },
            { data: 'remark', defaultContent: '-' },
            { data: 'created_at' },
            { data: 'action' },
        ]
    });

    $('#searchBtn').click(function () {
        table.ajax.reload();
    });

});
</script>
@endsection
