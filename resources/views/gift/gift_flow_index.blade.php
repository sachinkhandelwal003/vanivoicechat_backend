@extends('layouts.app')

@section('content')
<div class="card mb-3">
    <div class="card-header">
        <div class="row flex-between-end">
            <div class="col-auto align-self-center">
                <h5 class="mb-0" data-anchor="data-anchor">Gift :: Gift Flow List</h5>
            </div>
        </div>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-datatable" style="width:100%">
                <thead class="bg-light">
                    <tr>
                        <th>#</th>
                        <th>Sender</th>
                        <th>Number of gifts</th>
                        <th>Number of recipients</th>
                        <th>Unit price</th>
                        <th>Total price</th>
                        <th>Time</th>
                        <th>Operate</th>
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

        let table = $('.table-datatable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('giftrecords') }}",
            },
            columns: [
                {
                    data: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'sender',
                    name: 'sender.name'
                },
                {
                    data: 'number_of_gifts',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'number_of_recipients',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'unit_price',
                    name: 'coin_value'
                },
                {
                    data: 'total_price',
                    name: 'total_value'
                },
                {
                    data: 'time',
                    name: 'created_at'
                },
                {
                    data: 'action',
                    orderable: false,
                    searchable: false
                }
            ]
        });
    });
</script>
@endsection