@extends('layouts.app')

@section('content')
<div class="card mb-3">

    <div class="card-header">
        <div class="row flex-between-end">
            <div class="col-auto align-self-center">
                <h5 class="mb-0">BD :: BD List</h5>
            </div>

            <div class="col-auto ms-auto d-flex gap-2">

                @if(Helper::userCan(104, 'can_add'))
                <a href="{{ route('bd-user.form') }}" class="btn btn-outline-secondary">
                    <i class="fa fa-plus me-1"></i> Add BD
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
                        <th>User</th>
                        <th>Admin</th>
                        <th>Agency</th>
                        <th>Country</th>
                        <th>WhatsApp</th>
                        <th>Status</th>
                        <th>Time (Created / Updated)</th>
                        <th width="100px">Action</th>
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
            ajax: "{{ route('bd-user') }}",
            order: [
                [5, 'desc']
            ],
            columns: [{
                    data: 'user',
                    name: 'user',
                    orderable: false
                },
                {
                    data: 'admin',
                    name: 'admin',
                    orderable: false
                },
                {
                    data: 'agency_count',
                    name: 'agency_count',
                    searchable: false
                },
                {
                    data: 'country',
                    name: 'country'
                },
                {
                    data: 'whatsapp_number',
                    name: 'whatsapp_number'
                },
                {
                    data: 'status',
                    name: 'status'
                },
                {
                    data: 'time',
                    name: 'time'
                },
                {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false
                }
            ]
        });

        // DELETE
        $(document).on('click', ".delete", function() {
            var id = $(this).data('id');

            Swal.fire(deleteMessageSwalConfig).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('bd-user.delete') }}",
                        type: "DELETE",
                        data: {
                            id: id,
                            _token: "{{ csrf_token() }}"
                        },
                        success: function(data) {
                            if (data.status) {
                                Swal.fire('', data.message, "success");
                                table.draw();
                            } else {
                                toastr.error(data.message);
                            }
                        }
                    });
                }
            });
        });

    });
</script>

<script>
    $(document).on('click', '.convert-admin', function() {

        let id = $(this).data('id');

        Swal.fire({
            title: 'Convert To Admin?',
            text: 'BD role will be removed and Admin role will be assigned.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes Convert'
        }).then((result) => {

            if (result.isConfirmed) {

                $.ajax({

                    url: '/bd-user/' + id + '/convert-admin',

                    type: 'POST',

                    data: {
                        _token: '{{ csrf_token() }}'
                    },

                    success: function(res) {

                        Swal.fire(
                            'Success',
                            res.message,
                            'success'
                        );

                        $('.table-datatable').DataTable().ajax.reload();
                    },

                    error: function(xhr) {

                        Swal.fire(
                            'Error',
                            xhr.responseJSON.message,
                            'error'
                        );
                    }
                });
            }
        });
    });
</script>
@endsection