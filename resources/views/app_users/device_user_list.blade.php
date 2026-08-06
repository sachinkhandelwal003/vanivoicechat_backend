@extends('layouts.app')

@section('content')
    <div class="card mb-3">

        <div class="card-header">

            <div class="row flex-between-end">

                <div class="col-auto align-self-center">
                    <h5 class="mb-0">
                        Device Users
                    </h5>

                    <small class="text-muted">
                        IMEI :
                        <span class="fw-bold text-primary">{{ $imei }}</span>
                    </small>
                </div>

                <div class="col-auto ms-auto">

                    <a href="{{ route('user.device.list') }}" class="btn btn-outline-secondary">

                        <i class="fas fa-arrow-left me-1"></i>

                        Back

                    </a>

                </div>

            </div>

        </div>

        <div class="card-body table-padding">

            <div class="table-responsive scrollbar">

                <table class="table table-striped table-datatable w-100">

                    <thead>

                        <tr>

                            <th>#</th>

                            <th>User</th>

                            <th>Email</th>

                            <th>Country</th>

                            <th>Registration Time</th>

                            <th>Status</th>

                        </tr>

                    </thead>

                    <tbody></tbody>

                </table>

            </div>

        </div>

    </div>
@endsection


@section('js')
    <script>
        $(function() {

            $('.table-datatable').DataTable({

                processing: true,

                serverSide: true,

                ajax: "{{ route('device.user.list', ['imei' => $imei]) }}",

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
                        data: 'user',
                        name: 'user',
                        orderable: false
                    },

                    {
                        data: 'email',
                        name: 'email'
                    },

                    {
                        data: 'country',
                        name: 'country'
                    },

                    {
                        data: 'registered_at',
                        name: 'registration_time'
                    },

                    {
                        data: 'status',
                        name: 'status',
                        orderable: false
                    }

                ]

            });

        });
    </script>
@endsection
