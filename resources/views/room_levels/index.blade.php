@extends('layouts.app')

@section('content')
    <div class="card mb-3">

        <div class="card-header">
            <div class="row flex-between-end">

                <div class="col-auto align-self-center">
                    <h5 class="mb-0">Room Level :: List</h5>
                </div>

                <div class="col-auto ms-auto">

                    @if (Helper::userCan(137, 'can_add'))
                        <a href="{{ route('room-levels.form') }}" class="btn btn-primary">
                            <i class="fa fa-plus me-1"></i> Add Room Level
                        </a>
                    @endif

                </div>

            </div>
        </div>

        <div class="card-body table-padding">

            <div class="table-responsive">

                <table class="table table-bordered table-striped table-datatable w-100">

                    <thead>
                        <tr>
                            <th width="60">ID</th>
                            <th>Level</th>
                            <th>XP</th>
                            <th>Admins</th>
                            <th>Members</th>
                            <th>Status</th>
                            <th width="170">Created At</th>
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

                ajax: "{{ route('room-levels') }}",

                order: [
                    [0, 'desc']
                ],

                columns: [

                    {
                        data: 'id',
                        name: 'id'
                    },

                    {
                        data: 'level',
                        name: 'level'
                    },

                    {
                        data: 'xp',
                        name: 'xp'
                    },

                    {
                        data: 'admins',
                        name: 'admins'
                    },

                    {
                        data: 'members',
                        name: 'members'
                    },

                    {
                        data: 'status',
                        name: 'status',
                        orderable: false,
                        searchable: false
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
                    }

                ]

            });

            // Delete

            $(document).on('click', '.delete', function() {

                let id = $(this).data('id');

                Swal.fire(deleteMessageSwalConfig).then((result) => {

                    if (result.isConfirmed) {

                        $.ajax({

                            url: "{{ route('room-levels.delete') }}",

                            type: "DELETE",

                            data: {
                                id: id,
                                _token: "{{ csrf_token() }}"
                            },

                            success: function(res) {

                                if (res.status) {

                                    Swal.fire('', res.message, 'success');

                                    table.draw(false);

                                } else {

                                    toastr.error(res.message);

                                }

                            },

                            error: function() {

                                toastr.error('Something went wrong.');

                            }

                        });

                    }

                });

            });

        });
    </script>
@endsection
