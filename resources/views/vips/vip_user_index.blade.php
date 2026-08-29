@extends('layouts.app')

@section('content')
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">VIP User List</h5>
        </div>

        <div class="card-body">
            <table class="table table-striped table-datatable w-100">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>User</th>
                        <th>VIP</th>
                        <th>Validity</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
@endsection

@section('js')
    <script>
        let table = $('.table-datatable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('vip.user') }}",
            columns: [{
                    data: 'DT_RowIndex',
                    searchable: false,
                    orderable: false
                },
                {
                    data: 'user_info',
                    name: 'user.name'
                },
                {
                    data: 'vip_name',
                    name: 'vip.name'
                },
                {
                    data: 'validity',
                    searchable: false
                },
                {
                    data: 'action',
                    searchable: false,
                    orderable: false
                },
            ]
        });

        $(document).on('click', '.delete', function() {

            var id = $(this).data('id');

            Swal.fire(deleteMessageSwalConfig).then((result) => {

                if (result.isConfirmed) {

                    $.ajax({
                        url: "{{ route('vip.user.delete') }}",
                        type: 'DELETE',
                        data: {
                            id: id,
                            _token: "{{ csrf_token() }}"
                        },
                        success: function(data) {

                            if (data.status) {
                                Swal.fire('', data.message, 'success');
                                table.draw();
                            } else {
                                toastr.error(data.message);
                            }

                        }
                    });

                }

            });

        });
    </script>
@endsection
