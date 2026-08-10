@extends('layouts.app')

@section('content')
    <div class="card mb-3">

        <div class="card-header">
            <div class="row flex-between-end">
                <div class="col-auto align-self-center">
                    <h5 class="mb-0">
                        Coin Seller :: Transaction History
                    </h5>
                </div>
                <div class="col-auto ms-auto">
                    @if (Helper::userCan(144, 'can_view'))
                        <a href="{{ route('coin_seller.transactions.export') }}" class="btn btn-success">

                            <i class="fas fa-file-excel me-1"></i>
                            Seller Recharge Export

                        </a>
                    @endif
                </div>
            </div>
        </div>

        <div class="card-body table-padding">
            <div class="table-responsive scrollbar">
                <table class="table table-striped table-datatable w-100">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Sender</th>
                            <th>Receiver</th>
                            <th>Coins</th>
                            <th>Before</th>
                            <th>After</th>
                            <th>Time</th>
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
                ajax: "{{ route('coin_seller.transactions') }}",
                order: [
                    [6, 'desc']
                ],
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        searchable: false,
                        orderable: false
                    },
                    {
                        data: 'sender',
                        name: 'sender'
                    },
                    {
                        data: 'receiver',
                        name: 'receiver'
                    },
                    {
                        data: 'coins',
                        name: 'coins'
                    },
                    {
                        data: 'balance_before',
                        name: 'balance_before'
                    },
                    {
                        data: 'balance_after',
                        name: 'balance_after'
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
