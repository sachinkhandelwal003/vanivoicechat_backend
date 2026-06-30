@extends('layouts.app')

@section('content')

<div class="card mb-3">
    <div class="card-header">
        <h5>User Music :: User Music List</h5>
    </div>

    <div class="card-body">

        {{-- FILTERS --}}
        <div class="row mb-3">

            <div class="col-md-3">
                <input type="text" id="uid" class="form-control" placeholder="Serial / UID">
            </div>

            <div class="col-md-3">
                <input type="text" id="username" class="form-control" placeholder="Enter username">
            </div>

            <div class="col-md-3">
                <select id="review_status" class="form-control">
                    <option value="">Review status</option>
                    <option value="pending">Pending review</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>

            <div class="col-md-2">
                <button id="searchBtn" class="btn btn-primary w-100">Search</button>
            </div>

        </div>

        {{-- TABLE --}}
        <div class="table-responsive">
            <table class="table table-striped table-datatable" style="width:100%">
                <thead>
                    <tr>
                        <th>User Information</th>
                        <th>Amount</th>
                        <th>Review status</th>
                        <th>Music content</th>
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

$(function(){

    let table = $('.table-datatable').DataTable({
        ajax: {
            url: "{{ route('user-music') }}",
            data: function(d){
                d.uid = $('#uid').val();
                d.username = $('#username').val();
                d.review_status = $('#review_status').val();
            }
        },
        columns: [
            { data: 'user_info' },
            { data: 'amount' },
            { data: 'review_status' },
            { data: 'music_content' },
            { data: 'created_at' },
            { data: 'action' }
        ]
    });

    $('#searchBtn').click(() => table.ajax.reload());

    // DELETE
    $(document).on('click', '.delete', function () {
        let id = $(this).data('id');

        Swal.fire({
            title: "Are you sure?",
            text: "Delete this music entry?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes, Delete"
        }).then((result) => {
            if (result.isConfirmed) {

                $.post("{{ route('user-music') }}",
                {
                    id: id,
                    _token: "{{ csrf_token() }}"
                },
                function(res){
                    if(res.status){
                        Swal.fire("Deleted!", res.message, "success");
                        table.ajax.reload();
                    }
                });

            }
        });

    });

});
</script>
@endsection
