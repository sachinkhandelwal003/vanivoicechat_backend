@extends('layouts.app')

@section('content')
    <div class="card">

        <div class="card-header">
            <h5 class="mb-0">SVIP User List</h5>
        </div>

        <div class="card-body">

            <div class="row mb-3">

                <div class="col-md-3">
                    <input type="text" id="uid" class="form-control" placeholder="Search UID">
                </div>

                <div class="col-md-3">
                    <input type="text" id="username" class="form-control" placeholder="Search Username">
                </div>

            </div>

            <div class="table-responsive">

                <table class="table table-striped table-datatable w-100">

                    <thead>
                        <tr>
                            <th>#</th>
                            <th>User</th>
                            <th>SVIP</th>
                            <th>Coins</th>
                            <th>Start</th>
                            <th>End</th>
                            <th>Status</th>
                        </tr>
                    </thead>

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
                    url: "{{ route('svip.users') }}",
                    data: function(d) {
                        d.uid = $('#uid').val();
                        d.username = $('#username').val();
                    }
                },

                columns: [{
                        data: 'DT_RowIndex',
                        searchable: false,
                        orderable: false
                    },
                    {
                        data: 'user_info'
                    },
                    {
                        data: 'svip_name'
                    },
                    {
                        data: 'coins_used'
                    },
                    {
                        data: 'start_at'
                    },
                    {
                        data: 'end_at'
                    },
                    {
                        data: 'status',
                        searchable: false
                    }
                ]
            });

            $('#uid,#username').keyup(function() {
                table.draw();
            });

        });

        $(document).on('click', ".delete", function() {

            var id = $(this).data('id');

            Swal.fire(deleteMessageSwalConfig).then((result) => {

                if (result.isConfirmed) {

                    $.ajax({
                        url: "{{ route('svip.users.delete') }}",
                        type: "DELETE",
                        data: {
                            _token: "{{ csrf_token() }}",
                            id: id
                        },
                        success: function(data) {

                            if (data.status) {
                                Swal.fire('', data.message, 'success');
                                table.draw();
                            } else {
                                toastr.error(data.message);
                            }

                        }
                    });

                }

            });

        });
    </script>
@endsection
