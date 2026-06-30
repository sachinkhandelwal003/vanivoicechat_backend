@extends('layouts.app')

@section('content')

<div class="card mb-3">
    <div class="card-header">
        <h5>User Speaker :: Speaker List</h5>
    </div>

    <div class="card-body">

        {{-- SEARCH --}}
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


    </div>
</div>

@endsection

@section('js')
<script src="{{ asset('assets/js/sweetalert2.min.js') }}"></script>

<script>
$(function(){

    let table = $('.table-datatable').DataTable({
        ajax : {
            url : "{{ route('speaker-list') }}",
            data : function(d){
                d.uid = $('#uid').val();
                d.username = $('#username').val();
            }
        },
        columns : [
            { data: 'user_info' },
            { data: 'content' },
            { data: 'created_at' },
            { data: 'action' },
        ]
    });

    $('#searchBtn').click(function(){
        table.ajax.reload();
    });

    // DELETE
    $(document).on("click", ".delete", function(){
        var id = $(this).data('id');

        Swal.fire({
            title: "Are you sure?",
            text: "Delete this record?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes, Delete"
        }).then((res)=>{

            if(res.isConfirmed){
                $.post("{{ route('speaker-list') }}", {
                    _token : "{{ csrf_token() }}",
                    id : id
                }, function(data){

                    if(data.status){
                        Swal.fire("Deleted", data.message, "success");
                        table.ajax.reload();
                    } else {
                        Swal.fire("Error", data.message, "error");
                    }

                });
            }

        });
    });

});
</script>

@endsection
