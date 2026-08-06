@extends('layouts.app')

@section('content')
    <div class="card mb-3">

        <div class="card-header">
            <div class="row flex-between-end">
                <div class="col-auto align-self-center">
                    <h5 class="mb-0">Coin Recharge History</h5>
                </div>
            </div>
        </div>

        <div class="card-body table-padding">

            <div class="table-responsive scrollbar">

                <table class="table table-striped table-datatable w-100">

                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Seller / Merchant</th>
                            <th>Role</th>
                            <th>Recharge To</th>
                            <th>Coins</th>
                            <th>Transaction Type</th>
                            <th>Remark</th>
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
                    url: "{{ route('sellers.recharge.history') }}"
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
                        data: 'seller',
                        name: 'seller.name'
                    },

                    {
                        data: 'role',
                        name: 'role'
                    },

                    {
                        data: 'receiver',
                        name: 'user.name'
                    },

                    {
                        data: 'coin',
                        name: 'coin'
                    },

                    {
                        data: 'transaction_type',
                        name: 'transaction_type'
                    },

                    {
                        data: 'remark',
                        name: 'remark'
                    },

                    {
                        data: 'created',
                        name: 'created_at'
                    },

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
