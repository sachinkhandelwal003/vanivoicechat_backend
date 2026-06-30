@extends('layouts.app')

@section('content')

<div class="card mb-3">
    <div class="card-header">
        <h5>User Badge :: User Badge List</h5>
    </div>

    <div class="card-body">

        {{-- SEARCH FILTERS --}}
        <div class="row mb-3">
            <div class="col-md-3">
                <input type="text" id="uid" class="form-control" placeholder="Serial / UID">
            </div>

            <div class="col-md-3">
                <input type="text" id="username" class="form-control" placeholder="Enter Username">
            </div>

            <div class="col-md-3">
                <input type="text" id="badge_id" class="form-control" placeholder="Badge ID">
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
                        <th>Badge ID</th>
                        <th>Badge Name</th>
                        <th>Badge Resources</th>
                        <th>Usage Status</th>
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
            url: "{{ route('user-badge') }}",
            data: function (d) {
                d.uid = $('#uid').val();
                d.username = $('#username').val();
                d.badge_id = $('#badge_id').val();
            }
        },
        columns: [
            { data: 'user_info' },
            { data: 'badge_id' },
            { data: 'badge_name' },
            { data: 'badge_image' },
            { data: 'status_text' },
            { data: 'time' },
            { data: 'action' },
        ]
    });

    $('#searchBtn').click(function () {
        table.ajax.reload();
    });

    // DELETE ACTION
    $(document).on('click', '.delete', function () {
        let id = $(this).data('id');

        Swal.fire({
            title: "Delete Badge?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Delete"
        }).then((res) => {
            if (res.isConfirmed) {
                $.post("{{ route('user-badge') }}", {
                    id: id,
                    _token: "{{ csrf_token() }}"
                }, function (data) {
                    Swal.fire("Deleted!", "", "success");
                    table.ajax.reload();
                });
            }
        });
    });

});
</script>

@endsection
