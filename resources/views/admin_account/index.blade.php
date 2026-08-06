@extends('layouts.app')

@section('content')
<div class="card mb-3">

    <div class="card-header">
        <div class="row flex-between-end">
            <div class="col-auto align-self-center">
                <h5 class="mb-0">Admin :: Admin List</h5>
            </div>

            <div class="col-auto ms-auto d-flex gap-2">

                {{-- ADD ADMIN --}}
                @if(Helper::userCan(138, 'can_add'))
                <a href="{{ route('admin.account.form') }}" class="btn btn-outline-secondary">
                    <i class="fa fa-plus me-1"></i> Add Admin
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
                        <th>Country</th>
                        <th>WhatsApp</th>
                        <th>BD</th>
                        <th>Agency</th>
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
    $(function () {

        var table = $('.table-datatable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('admin.account') }}",
            order: [[4, 'desc']],
            columns: [
                { data: 'user', name: 'user', orderable: false },
                { data: 'country', name: 'country', orderable: false },
                { data: 'whatsapp_number', name: 'whatsapp_number' },
                {data: 'bd_count', name: 'bd_count', searchable: false},
                {data: 'agency_count', name: 'agency_count', searchable: false},
                { data: 'status', name: 'status' },
                { data: 'time', name: 'time', orderable: false, searchable: false },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ]
        });

        // DELETE
        $(document).on('click', ".delete", function () {
            var id = $(this).data('id');

            Swal.fire(deleteMessageSwalConfig).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('admin.account.delete') }}",
                        type: "DELETE",
                        data: {
                            id: id,
                            _token: "{{ csrf_token() }}"
                        },
                        success: function (data) {
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
@endsection
