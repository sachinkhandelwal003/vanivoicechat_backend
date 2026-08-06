@extends('layouts.app')

@section('content')
    <div class="card mb-3">
        <div class="card-header">
            <div class="row flex-between-end">
                <div class="col-auto align-self-center">
                    <h5 class="mb-0">
                        User Role Tags :: Tag List
                    </h5>
                </div>

                <div class="col-auto ms-auto">
                    @if (Helper::userCan(125, 'can_add'))
                        <a href="{{ route('user-role-tags.add') }}" class="btn btn-outline-secondary">
                            <i class="fa fa-plus me-1"></i>
                            Add User Role Tag
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <div class="card-body">

            <div class="table-responsive">
                <table class="table table-striped table-datatable" style="width:100%">

                    <thead class="bg-light">
                        <tr>
                            <th>Name</th>
                            <th>Role Type</th>
                            <th>File Type</th>
                            <th>Preview</th>
                            <th>Status</th>
                            <th>Created At</th>
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
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('user-role-tags') }}"
                },
                columns: [

                    {
                        data: 'name'
                    },
                    {
                        data: 'role_type'
                    },
                    {
                        data: 'file_type'
                    },
                    {
                        data: 'file'
                    },
                    {
                        data: 'status'
                    },
                    {
                        data: 'created_at'
                    },
                    {
                        data: 'action',
                        orderable: false,
                        searchable: false
                    }
                ]
            });


            $(document).on('click', '.delete', function() {

                let id = $(this).data('id');

                Swal.fire(deleteMessageSwalConfig)
                    .then((result) => {

                        if (result.isConfirmed) {

                            $.ajax({
                                url: "{{ route('user-role-tags') }}",
                                type: "DELETE",
                                data: {
                                    id: id
                                },
                                success: function(data) {

                                    if (data.status) {

                                        Swal.fire(
                                            '',
                                            data.message,
                                            'success'
                                        );

                                        table.draw();

                                    } else {

                                        toastr.error(data.message);

                                    }
                                }
                            });
                        }
                    });
            });

        });
    </script>
@endsection
