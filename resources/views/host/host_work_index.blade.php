@extends('layouts.app')

@section('content')
    <div class="card">
        <div class="card-header">
            <h4>Host Work Data</h4>
        </div>

        <div class="card-body">

            <table class="table table-bordered w-100" id="hostTable">

                <thead>

                    <tr>
                        <th>#</th>
                        <th>Host</th>
                        {{-- <th>Agency</th> --}}
                        <th>Total Gift Value</th>
                        <th>Host Since</th>
                    </tr>

                </thead>

            </table>

        </div>

    </div>
@endsection


@section('js')
    <script>
        $('#hostTable').DataTable({

            processing: true,
            serverSide: true,

            ajax: "{{ route('host.work') }}",

            columns: [

                {
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    searchable: false,
                    orderable: false
                },
                {
                    data: 'host',
                    name: 'host'
                },
                // {
                //     data: 'agency',
                //     name: 'agency'
                // },
                {
                    data: 'gift_value',
                    name: 'gift_value'
                },
                {
                    data: 'host_since',
                    name: 'host_since'
                },

            ]

        });
    </script>
@endsection
