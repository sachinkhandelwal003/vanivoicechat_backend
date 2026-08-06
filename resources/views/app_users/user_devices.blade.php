@extends('layouts.app')

@section('content')
    <div class="card mb-3">

        <div class="card-header">
            <div class="row flex-between-end">

                <div class="col-auto align-self-center">
                    <h5 class="mb-0">
                        Device Management :: IMEI Wise Registered Users
                    </h5>
                </div>

            </div>
        </div>

        <div class="card-body table-padding">

            <div class="table-responsive scrollbar">

                <table class="table table-striped table-datatable w-100">

                    <thead>

                        <tr>
                            <th>#</th>
                            <th>IMEI</th>
                            <th>Device</th>
                            <th>OS / App Version</th>
                            <th>Total Accounts</th>
                            <th>Status</th>
                            <th width="130">Action</th>
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

            $('.table-datatable').DataTable({

                processing: true,

                serverSide: true,

                ajax: "{{ route('user.device.list') }}",

                order: [
                    [4, 'desc']
                ],

                columns: [

                    {
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        searchable: false,
                        orderable: false
                    },

                    {
                        data: 'imei',
                        name: 'imei'
                    },

                    {
                        data: 'device',
                        name: 'device',
                        orderable: false
                    },

                    {
                        data: 'os',
                        name: 'os',
                        orderable: false
                    },

                    {
                        data: 'accounts',
                        name: 'accounts',
                        searchable: false
                    },
                    {
                        data: 'status',
                        name: 'status',
                        searchable: false
                    },

                    {
                        data: 'action',
                        name: 'action',
                        searchable: false,
                        orderable: false
                    }

                ]

            });

        });

        $(document).on('click', '.device-ban', function() {

            let imei = $(this).data('imei');

            Swal.fire({
                title: 'Ban Device?',
                text: 'All users registered with this device will not be able to login.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, Ban',
                cancelButtonText: 'Cancel'
            }).then((result) => {

                if (result.isConfirmed) {

                    $.ajax({

                        url: "{{ route('device.ban') }}",

                        type: "POST",

                        data: {
                            imei: imei,
                            _token: "{{ csrf_token() }}"
                        },

                        success: function(res) {

                            if (res.status) {

                                Swal.fire('', res.message, 'success');

                                $('.table-datatable').DataTable().ajax.reload(null, false);

                            } else {

                                toastr.error(res.message);

                            }

                        }

                    });

                }

            });

        });


        $(document).on('click', '.device-unban', function() {

            let imei = $(this).data('imei');

            Swal.fire({
                title: 'Unban Device?',
                text: 'Users of this device can login again.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, Unban',
                cancelButtonText: 'Cancel'
            }).then((result) => {

                if (result.isConfirmed) {

                    $.ajax({

                        url: "{{ route('device.unban') }}",

                        type: "POST",

                        data: {
                            imei: imei,
                            _token: "{{ csrf_token() }}"
                        },

                        success: function(res) {

                            if (res.status) {

                                Swal.fire('', res.message, 'success');

                                $('.table-datatable').DataTable().ajax.reload(null, false);

                            } else {

                                toastr.error(res.message);

                            }

                        }

                    });

                }

            });

        });
    </script>
@endsection
