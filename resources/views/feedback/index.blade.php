@extends('layouts.app')

@section('content')

<div class="card mb-3">
    <div class="card-header">
        <h5>Feed Back :: Feed Back List</h5>
    </div>

    <div class="card-body">

        {{-- FILTERS --}}
        <div class="row mb-3">

            <div class="col-md-3">
                <input type="text" id="uid" class="form-control" placeholder="Serial / UID">
            </div>

            <div class="col-md-3">
                <input type="text" id="username" class="form-control" placeholder="Please enter your username">
            </div>

            <div class="col-md-2">
                <button class="btn btn-primary w-100" id="searchBtn">Search</button>
            </div>

        </div>

        {{-- DATA TABLE --}}
        <div class="table-responsive">
            <table class="table custom-table table-striped dt-table-hover fs--1 mb-0 table-datatable" style="width:100%">
                <thead class="bg-200 text-900">
                    <tr>
                        <th>#</th>
                        <th>User Name</th>
                        <th>Feedback Content</th>
                        <th>IP</th>
                        <th>Equipment Model</th>
                        <th>Device ID</th>
                        <th>Version No.</th>
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
            url: "{{ route('feedback') }}",
            data: function (d) {
                d.uid = $('#uid').val();
                d.username = $('#username').val();
            }
        },
        order: [[7, 'desc']],
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'user_name' },
            { data: 'feedback_content' },
            { data: 'ip' },
            { data: 'equipment_model' },
            { data: 'device_id' },
            { data: 'version_number' },
            { data: 'created_at' },
            { data: 'action', orderable: false, searchable: false },
        ]
    });

    // FILTER BUTTON
    $('#searchBtn').click(function () {
        table.ajax.reload();
    });

});
</script>

@endsection
