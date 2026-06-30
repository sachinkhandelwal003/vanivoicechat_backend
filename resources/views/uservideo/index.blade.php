@extends('layouts.app')

@section('content')

<style>
.video-hover-box {
    position: relative;
    display: inline-block;
}
.video-hover-box .video-popup {
    display: none;
    position: absolute;
    top: 30px;
    left: -50px;
    z-index: 999;
    background: white;
    padding: 8px;
    border-radius: 8px;
    box-shadow: 0px 0px 10px #ccc;
}
.video-hover-box:hover .video-popup {
    display: block;
}
</style>

<div class="card mb-3">
    <div class="card-header">
        <h5>User Video :: User Video List</h5>
    </div>

    <div class="card-body">

        {{-- Filters --}}
        <div class="row mb-3">
            <div class="col-md-3">
                <input type="text" id="uid" class="form-control" placeholder="Serial / UID">
            </div>

            <div class="col-md-3">
                <input type="text" id="username" class="form-control" placeholder="Enter username">
            </div>

            <div class="col-md-3">
                <select id="review_status" class="form-control">
                    <option value="">Review Status</option>
                    <option value="approved">Approved</option>
                    <option value="pending">Pending</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>

            <div class="col-md-2">
                <button class="btn btn-primary w-100" id="searchBtn">Search</button>
            </div>
        </div>

        {{-- TABLE --}}
        <table class="table table-striped table-datatable">
            <thead class="bg-light">
                <tr>
                    <th>User Information</th>
                    <th>Amount</th>
                    <th>Review Status</th>
                    <th>Video Content</th>
                    <th>Time</th>
                    <th>Operate</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>

    </div>
</div>

@endsection

@section('js')
<script>
$(function () {
    let table = $('.table-datatable').DataTable({
        ajax: {
            url: "{{ route('user-video') }}",
            data: function(d) {
                d.uid = $('#uid').val();
                d.username = $('#username').val();
                d.review_status = $('#review_status').val();
            }
        },
        columns: [
            { data: 'user_info' },
            { data: 'amount' },
            { data: 'review_status' },
            { data: 'video_preview' },
            { data: 'created_at' },
            { data: 'action' },
        ]
    });

    $('#searchBtn').click(function(){
        table.ajax.reload();
    });

    // DELETE
    $(document).on('click', '.delete', function () {
        let id = $(this).data('id');

        Swal.fire({
            title: "Delete Video?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes"
        }).then((res)=>{
            if(res.isConfirmed){
                $.post("{{ route('user-video') }}", {
                    id: id,
                    _token: "{{ csrf_token() }}"
                }, function(data){
                    if(data.status){
                        Swal.fire("Deleted!", data.message, "success");
                        table.ajax.reload();
                    }
                });
            }
        });
    });
});
</script>
@endsection
