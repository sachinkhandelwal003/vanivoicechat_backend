@extends('layouts.app')

@section('content')
    <div class="card mb-3">

        <div class="card-header">

            <div class="row flex-between-end">

                <div class="col-auto align-self-center">
                    <h5 class="mb-0">Invite Users :: List</h5>
                </div>

            </div>

        </div>

        <div class="card-body table-padding">

            <div class="table-responsive scrollbar">

                <table class="table table-striped table-datatable w-100">

                    <thead>

                        <tr>

                            <th>#</th>
                            <th>Inviter</th>
                            <th>Invited User</th>
                            <th>Invite Code</th>
                            <th>Status</th>
                            <th>Accepted At</th>
                            <th>Created At</th>

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

                ajax: "{{ route('invite-users') }}",

                order: [
                    [6, 'desc']
                ],

                columns: [

                    {
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        searchable: false,
                        orderable: false
                    },

                    {
                        data: 'inviter',
                        name: 'inviter',
                        orderable: false,
                        searchable: false
                    },

                    {
                        data: 'invited_user',
                        name: 'invited_user',
                        orderable: false,
                        searchable: false
                    },

                    {
                        data: 'invite_code',
                        name: 'invite_code'
                    },

                    {
                        data: 'is_completed',
                        name: 'is_completed'
                    },

                    {
                        data: 'completed_at',
                        name: 'completed_at'
                    },

                    {
                        data: 'created_at',
                        name: 'created_at'
                    }

                ]

            });

        });
    </script>
@endsection
