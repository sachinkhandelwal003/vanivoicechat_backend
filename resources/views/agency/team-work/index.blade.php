@extends('layouts.app')

@section('content')
    <div class="card mb-3">

        <div class="card-header">
            <div class="row flex-between-end">

                <div class="col-auto align-self-center">
                    <h5 class="mb-0">Agency :: Team Work</h5>
                </div>

            </div>
        </div>

        <div class="card-body table-padding">

            <div class="table-responsive scrollbar">

                <table class="table table-striped table-datatable w-100">

                    <thead>

                        <tr>
                            <th>User</th>
                            {{-- <th>BD User</th> --}}
                            <th>Country</th>
                            <th>Host Count</th>
                            <th>Created Date</th>
                            <th width="120">Team Work</th>
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

                ajax: "{{ route('agency-team-work') }}",

                order: [
                    [4, 'desc']
                ],

                columns: [

                    {
                        data: 'agency',
                        name: 'agency',
                        orderable: false
                    },

                    // {
                    //     data: 'bd',
                    //     name: 'bd',
                    //     orderable: false
                    // },

                    {
                        data: 'country',
                        name: 'country'
                    },

                    {
                        data: 'host_count',
                        name: 'host_count',
                        searchable: false
                    },

                    {
                        data: 'created_at',
                        name: 'created_at'
                    },

                    {
                        data: 'team_work',
                        name: 'team_work',
                        orderable: false,
                        searchable: false
                    }

                ]

            });

        });
    </script>
@endsection
