@extends('layouts.app')

@section('content')
    <div class="card mb-3">

        <div class="card-header">

            <div class="row flex-between-end">

                <div class="col-auto align-self-center">
                    <h5 class="mb-0">Users :: Deleted Users</h5>
                </div>

                <div class="col-auto ms-auto d-flex gap-2">

                    <a href="{{ route('app-users') }}" class="btn btn-outline-secondary">
                        <i class="fa fa-arrow-left me-1"></i>
                        Back To Users
                    </a>

                </div>

            </div>

        </div>

        <div class="card-body table-padding">

            <div class="table-responsive scrollbar">

                <table class="table table-striped table-datatable w-100">

                    <thead>

                        <tr>

                            <th>User</th>

                            <th>Country</th>

                            <th>Deleted At</th>

                            <th width="120">Action</th>

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

                ajax: "{{ route('users.deleted') }}",

                columns: [

                    {
                        data: 'user',
                        name: 'user',
                        orderable: false
                    },

                    {
                        data: 'country',
                        name: 'country'
                    },

                    {
                        data: 'deleted_at',
                        name: 'deleted_at'
                    },

                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    }

                ]

            });


            $(document).on('click', '.restoreUser', function() {

                let id = $(this).data('id');

                Swal.fire({

                    title: 'Restore User?',

                    text: 'This user will be restored.',

                    icon: 'question',

                    showCancelButton: true,

                    confirmButtonText: 'Yes, Restore',

                    cancelButtonText: 'Cancel'

                }).then((result) => {

                    if (result.isConfirmed) {

                        $.ajax({

                            url: "/users-softdelete/restore/" + id,

                            type: "POST",

                            data: {

                                id: id,

                                _token: "{{ csrf_token() }}"

                            },

                            success: function(res) {

                                if (res.status) {

                                    Swal.fire('', res.message, 'success');

                                    table.ajax.reload(null, false);

                                } else {

                                    toastr.error(res.message);

                                }

                            }

                        });

                    }

                });

            });

        });
    </script>
@endsection
