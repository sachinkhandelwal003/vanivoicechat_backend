@extends('layouts.app')

@section('content')
    <div class="card mb-3">

        <div class="card-header">
            <div class="row flex-between-end">

                <div class="col-auto align-self-center">
                    <h5 class="mb-0">
                        Theme Management :: Custom Theme Requests
                    </h5>
                </div>

                <div class="col-auto ms-auto">

                    <span class="badge bg-warning text-dark">
                        Pending Admin Approval
                    </span>

                </div>

            </div>
        </div>

        <div class="card-body table-padding">
            <div class="table-responsive scrollbar">
                <table class="table table-striped table-datatable w-100">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Theme</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th width="100">Action</th>
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

            var table = $('.table-datatable').DataTable({

                processing: true,

                serverSide: true,

                ajax: "{{ route('theme.custom.requests') }}",

                order: [
                    [4, 'desc']
                ],

                columns: [

                    {
                        data: 'user',
                        name: 'user',
                        orderable: false,
                        searchable: false
                    },

                    {
                        data: 'theme',
                        name: 'theme',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'status',
                        name: 'status'
                    },

                    {
                        data: 'time',
                        name: 'time',
                        orderable: false,
                        searchable: false
                    },

                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    }

                ]

            });

            $(document).on('click', ".delete", function() {
                var id = $(this).data('id')
                Swal.fire(deleteMessageSwalConfig).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ route('theme.delete') }} ",
                            data: {
                                'id': id
                            },
                            type: 'DELETE',
                            success: function(data) {
                                if (data.status) {
                                    Swal.fire('', data?.message, "success")
                                    table.draw();
                                } else {
                                    toastr.error(data.message);
                                }
                            }
                        });
                    }
                });
            });



            // APPROVE

            $(document).on('click', '.approveTheme', function() {

                let id = $(this).data('id');

                Swal.fire({

                    title: 'Approve Theme?',

                    text: 'This theme will become available to the user.',

                    icon: 'question',

                    showCancelButton: true,

                    confirmButtonText: 'Approve'

                }).then((result) => {

                    if (result.isConfirmed) {

                        $.ajax({

                            url: "/theme/" + id + "/approve",

                            type: "POST",

                            data: {

                                _token: "{{ csrf_token() }}"

                            },

                            success: function(res) {

                                Swal.fire('', res.message, 'success');

                                table.ajax.reload(null, false);

                            },

                            error: function(xhr) {

                                Swal.fire('Error', xhr.responseJSON.message, 'error');

                            }

                        });

                    }

                });

            });



            // REJECT

            $(document).on('click', '.rejectTheme', function() {

                let id = $(this).data('id');

                Swal.fire({

                    title: 'Reject Theme?',

                    text: 'This request will be rejected.',

                    icon: 'warning',

                    showCancelButton: true,

                    confirmButtonText: 'Reject'

                }).then((result) => {

                    if (result.isConfirmed) {

                        $.ajax({

                            url: "/theme/" + id + "/reject",

                            type: "POST",

                            data: {

                                _token: "{{ csrf_token() }}"

                            },

                            success: function(res) {

                                Swal.fire('', res.message, 'success');

                                table.ajax.reload(null, false);

                            },

                            error: function(xhr) {

                                Swal.fire('Error', xhr.responseJSON.message, 'error');

                            }

                        });

                    }

                });

            });

        });
    </script>
@endsection
