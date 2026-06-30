@extends('layouts.app')

@section('content')

<div class="card mb-3">
    <div class="card-header">
        <h5>User Item Backpack :: List</h5>
    </div>

    <div class="card-body">

        {{-- SEARCH --}}
        <div class="row mb-3">
            <div class="col-md-3">
                <input type="text" id="uid" class="form-control" placeholder="Serial / UID">
            </div>

            <div class="col-md-3">
                <input type="text" id="username" class="form-control" placeholder="Enter username">
            </div>

            <div class="col-md-2">
                <button class="btn btn-primary w-100" id="searchBtn">Search</button>
            </div>
        </div>

        {{-- TABLE --}}
        <div class="table-responsive">
            <table class="table table-striped table-datatable">
                <thead class="bg-light">
                    <tr>
                        <th>User Information</th>
                        <th>Tool quantity</th>
                        <th>Tool name</th>
                        <th>Have you worn it?</th>
                        <th>Giftable?</th>
                        <th>Prop Cover</th>
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
$(function() {

    let table = $('.table-datatable').DataTable({
        ajax: {
            url: "{{ route('user-backpack') }}",
            data: function (d) {
                d.uid = $('#uid').val();
                d.username = $('#username').val();
            }
        },
        columns: [
            { data: 'user_info' },
            { data: 'tool_quantity' },
            { data: 'tool_name' },
            { data: 'is_worn' },
            { data: 'is_giftable' },
            { data: 'photo' },
            { data: 'created_at' },
            { data: 'action' },
        ]
    });

    $('#searchBtn').click(function() {
        table.ajax.reload();
    });

});
</script>
@endsection
