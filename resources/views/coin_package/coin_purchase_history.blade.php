@extends('layouts.app')

@section('content')
    <div class="card mb-3">

        <div class="card-header">
            <div class="row flex-between-end">

                <div class="col-auto align-self-center">
                    <h5 class="mb-0">Coin Purchase History</h5>
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
                            <th>Coins</th>
                            <th>Bonus Coins</th>
                            <th>Total Coins</th>
                            <th>Amount</th>
                            <th>Type</th>
                            <th>Date & Time</th>
                            {{-- <th width="80">Action</th> --}}
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

                ajax: {
                    url: "{{ route('coin.purchase.history') }}"
                },

                order: [
                    [0, 'desc']
                ],

                columns: [

                    {
                        data: 'DT_RowIndex',
                        searchable: false,
                        orderable: false
                    },

                    {
                        data: 'user',
                        name: 'user.name'
                    },

                    {
                        data: 'coins',
                        name: 'coins'
                    },

                    {
                        data: 'bonus_coins',
                        name: 'bonus_coins'
                    },

                    {
                        data: 'total_coins',
                        name: 'total_coins'
                    },

                    {
                        data: 'amount',
                        name: 'amount'
                    },

                    {
                        data: 'type',
                        name: 'type'
                    },

                    {
                        data: 'created',
                        name: 'created_at'
                    }

                    // {
                    //     data: 'action',
                    //     searchable: false,
                    //     orderable: false
                    // }

                ]

            });

        });
    </script>
@endsection
