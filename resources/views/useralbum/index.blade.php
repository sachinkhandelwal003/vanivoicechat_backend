@extends('layouts.app')

@section('content')

<div class="card mb-3">
    <div class="card-header">
        <h5>User Album :: User Album List</h5>
    </div>

    <div class="card-body">

        {{-- SEARCH FILTERS --}}
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
                        <th>Photo Album</th>
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
<script src="{{ asset('assets/js/sweetalert2.min.js') }}"></script>

<script>
$(function() {

    let table = $('.table-datatable').DataTable({
        ajax: {
            url: "{{ route('user-album') }}",
            data: function (d) {
                d.uid = $('#uid').val();
                d.username = $('#username').val();
            }
        },
        columns: [
            { data: 'user_info' },
            { data: 'photo' },
            { data: 'created_at' },
            { data: 'action' },
        ]
    });

    $('#searchBtn').click(function() {
        table.ajax.reload();
    });


    // DELETE IMAGE
    $(document).on('click', '.delete', function () {
        let id = $(this).data('id');

        Swal.fire({
            title: "Are you sure?",
            text: "Delete this album image?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes, Delete",
        }).then((res)=>{

            if(res.isConfirmed){

                $.ajax({
                    url: "{{ route('user-album') }}",
                    type: "POST",
                    data: {
                        id: id,
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(data){
                        if(data.status){
                            Swal.fire("Deleted!", data.message, "success");
                            table.ajax.reload();
                        } else {
                            Swal.fire("Error", data.message, "error");
                        }
                    }
                });

            }

        });

    });

});
</script>

@endsection
