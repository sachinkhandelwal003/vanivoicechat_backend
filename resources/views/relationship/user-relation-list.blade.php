@extends('layouts.app')

@section('content')
<div class="card mb-3">

    <div class="card-header">
        <div class="row flex-between-end">
            <div class="col-auto align-self-center">
                <h5 class="mb-0">Relationship :: User List</h5>
            </div>
        </div>
    </div>

    <div class="card-body">

        <div class="table-responsive">
            <table class="table table-striped table-datatable w-100">

                <thead class="bg-light">
                    <tr>
                        <th>#</th>
                        <th>Sender</th>
                        <th>Receiver</th>
                        <th>Type</th>
                        <th>Package</th>
                        <th>Status</th>
                        <th>Date</th>
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

            ajax: {
                url: "{{ route('relationship.user.relation.list') }}"
            },

            columns: [

                {
                    data: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                },

                {
                    data: 'sender'
                },

                {
                    data: 'receiver'
                },

                {
                    data: 'type'
                },

                {
                    data: 'relation_item'
                },

                {
                    data: 'status'
                },

                {
                    data: 'created_at'
                }

            ]
        });

    });
</script>

@endsection