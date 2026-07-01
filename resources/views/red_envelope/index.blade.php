@extends('layouts.app')

@section('content')
<div class="card mb-3">
    <div class="card-header">
        <div class="row flex-between-end">
            <div class="col-auto align-self-center">
                <h5 class="mb-0">Red Envelope :: List</h5>
            </div>
        </div>
    </div>

    <div class="card-body">

        <div class="table-responsive">
            <table class="table table-striped table-datatable w-100">

                <thead class="bg-light">
                    <tr>
                        <th>#</th>
                        <th>Room Name</th>
                        <th>Sender</th>
                        <th>Country</th>
                        <th>Type</th>
                        <th>Total Amount</th>
                        <th>Total Users</th>
                        <th>Claimed Amount</th>
                        <th>Claimed Users</th>
                        <th>Remaining Amount</th>
                        <th>Remaining Users</th>
                        <th>Status</th>
                        <th>Expires At</th>
                        <th>Created At</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody></tbody>

            </table>
        </div>

    </div>
</div>

<style>
    .action-btn {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        background: #edf2f7;
        border: none;

        display: flex;
        align-items: center;
        justify-content: center;
    }

    .dropdown-menu {
        min-width: 130px;
        border-radius: 10px;
        border: none;
    }

    .dropdown-item {
        padding: 10px 15px;
    }
</style>
@endsection

@section('js')
<script>
    $(function() {

        $('.table-datatable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('red.envelope') }}",
            },
            columns: [
                { data: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'room_id' },
                { data: 'sender_user_id' },
                { data: 'country' },
                { data: 'type' },
                { data: 'total_amount' },
                { data: 'total_users' },
                { data: 'claimed_amount' },
                { data: 'claimed_users' },
                { data: 'remaining_amount' },
                { data: 'remaining_users' },
                { data: 'status' },
                { data: 'expires_at' },
                { data: 'created_at' },
                { data: 'action' }
            ]
        });

    });

    $(document).on('click', '.delete-envelope', function() {

        let id = $(this).data('id');

        Swal.fire({
            title: 'Delete Red Envelope?',
            text: "This action cannot be undone.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes Delete'
        }).then((result) => {

            if (result.isConfirmed) {

                $.ajax({
                    url: '/red-envelope/' + id,
                    type: 'DELETE',
                    data: {
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(response) {

                        $('.table-datatable')
                            .DataTable()
                            .ajax.reload();

                        Swal.fire(
                            'Deleted!',
                            response.message,
                            'success'
                        );
                    }
                });

            }

        });

    });
</script>
@endsection