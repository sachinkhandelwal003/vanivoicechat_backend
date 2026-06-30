@extends('layouts.app')

@section('content')

<div class="card mb-3">
    <div class="card-header">
        <h5>Violations :: Violations List</h5>
    </div>

    <div class="card-body">

        {{-- FILTERS --}}
        <div class="row mb-3">
            <div class="col-md-3">
                <select id="type" class="form-control">
                    <option value="">Type</option>
                    <option value="user">User</option>
                    <option value="room">Room</option>
                    <option value="agency">Agency</option>
                </select>
            </div>

            <div class="col-md-3">
                <select id="operator" class="form-control">
                    <option value="">Operator</option>
                    <option value="Admin">Admin</option>
                    <option value="Support">Support</option>
                    <option value="System">System</option>
                </select>
            </div>

            <div class="col-md-2">
                <button class="btn btn-primary w-100" id="searchBtn">Search</button>
            </div>
        </div>


        {{-- TABLE --}}
        <div class="table-responsive">
            <table class="table custom-table table-striped dt-table-hover table-datatable" style="width:100%">

                <thead class="bg-200 text-900">
                    <tr>
                        <th>#</th>
                        <th>Violation Information</th>
                        <th>Illegal Content</th>
                        <th>Type</th>
                        <th>Description of Violation</th>
                        <th>Operator</th>
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
            url: "{{ route('violation') }}",
            data: function (d) {
                d.type = $('#type').val();
                d.operator = $('#operator').val();
            }
        },
        order: [[6, 'desc']],
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'violation_user' },
            { data: 'illegal_content' },
            { data: 'type' },
            { data: 'description_of_violation' },
            { data: 'operator' },
            { data: 'created_at' },
            { data: 'action', orderable: false, searchable: false }
        ]
    });

    $('#searchBtn').click(function () {
        table.ajax.reload();
    });

});
</script>
@endsection
