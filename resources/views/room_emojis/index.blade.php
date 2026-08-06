@extends('layouts.app')

@section('content')
    <div class="card mb-3">

        <div class="card-header">
            <div class="row flex-between-end">

                <div class="col-auto align-self-center">
                    <h5 class="mb-0">Room Emoji :: List</h5>
                </div>

                <div class="col-auto ms-auto">

                    @if (Helper::userCan(136, 'can_add'))
                        <a href="{{ route('room-emojis.form') }}" class="btn btn-primary">
                            <i class="fa fa-plus me-1"></i> Add Emoji
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
                            <th width="70">ID</th>
                            <th width="100">Emoji</th>
                            <th>Title</th>
                            <th>Type</th>
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

                ajax: "{{ route('room-emojis') }}",

                order: [
                    [0, 'desc']
                ],

                columns: [

                    {
                        data: 'id',
                        name: 'id'
                    },

                    {
                        data: 'file',
                        name: 'file',
                        orderable: false,
                        searchable: false
                    },

                    {
                        data: 'title',
                        name: 'title'
                    },

                    {
                        data: 'type',
                        name: 'type'
                    },

                    {
                        data: 'status',
                        name: 'status',
                        orderable: false
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

                            url: "{{ route('room-emojis.delete') }}",

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
