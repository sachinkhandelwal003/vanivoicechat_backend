@extends('layouts.app')

@section('content')
<div class="card mb-3">
    <div class="card-header">
        <div class="row flex-between-end">
            <div class="col-auto align-self-center">
                <h5 class="mb-0" data-anchor="data-anchor">Static Page :: Static Page</h5>
            </div>
            <div class="col-auto ms-auto">
                <div class="nav nav-pills nav-pills-falcon">
                    @if(Helper::userCan(169, 'can_add'))
                    <a href="{{ route('static-page.create') }}" class="btn btn-outline-secondary">
                        <i class="fa fa-plus me-1"></i>
                        Add Static Page
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div class="card-body table-padding">
        <div class="table-responsive scrollbar">
            <table class="table custom-table table-striped dt-table-hover fs--1 mb-0 table-datatable"
                style="width:100%">
                <thead class="bg-200 text-900">
                    <tr>
                        <th>Type</th>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Created Date</th>
                        <th width="100px">Action</th>
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
<script type="text/javascript">
    $(function () {
        var table = $('.table-datatable').DataTable({
            ajax: "{{ route('static-page') }}",
            order: [
                [3, 'desc']
            ],
            columns: [{
                data: 'type',
                name: 'type',
                orderable: false,
                searchable: false
            },
            {
                data: 'title',
                name: 'title'
            },
            {
                data: 'description',
                name: 'description'
            },
            {
                data: 'created_at',
                name: 'created_at'
            },
            {
                data: 'action',
                name: 'action',
                orderable: false,
                searchable: false
            },
            ]
        });

    });
</script>

<script>
$(document).on('click', '.delete-btn', function () {

    let id = $(this).data('id');

    Swal.fire({
        title: "Are you sure?",
        text: "This will delete the static page!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        confirmButtonText: "Yes, delete it!"
    }).then((result) => {
        if (result.isConfirmed) {

            $.ajax({
                url: "/static-page/" + id,
                type: "DELETE",
                data: {
                    _token: "{{ csrf_token() }}"
                },
                success: function (response) {

                    if (response.status) {
                        Swal.fire("Deleted!", response.message, "success");

                        // Reload DataTable
                        $('.data-table').DataTable().ajax.reload();
                    } else {
                        Swal.fire("Error!", response.message, "error");
                    }
                }
            });

        }
    });

});
</script>
@endsection
