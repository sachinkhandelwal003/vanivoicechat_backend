@extends('layouts.app')

@section('content')
<div class="card mb-3">
    <div class="card-header">
        <div class="row flex-between-end">
            <div class="col-auto align-self-center">
                <h5 class="mb-0">Customer Support :: List</h5>
            </div>
            <div class="col-auto ms-auto">
                <div class="nav nav-pills nav-pills-falcon">
                    @if(Helper::userCan(159, 'can_add'))
                    <a href="{{ route('customer_support.form') }}" class="btn btn-outline-secondary">
                        <i class="fa fa-plus me-1"></i>
                        Add Support
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-datatable" style="width:100%">
                <thead class="bg-light">
                    <tr>
                        <th>#</th>
                        <th>User</th>
                        <th>Region</th>
                        <th>Shift Time</th>
                        <th>Created Date</th>
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
                url: "{{ route('customer_support') }}",
            },
            columns: [{
                    data: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'user_id',
                    name: 'user_id'
                },
                {
                    data: 'region',
                    name: 'region'
                },
                {
                    data: 'shift_time',
                    name: 'shift_time'
                },
                {
                    data: 'created_at',
                    name: 'created_at'
                },
                {
                    data: 'action',
                    orderable: false,
                    searchable: false
                }
            ]
        });

        // Delete
        $(document).on('click', ".delete", function() {
            var id = $(this).data('id');

            Swal.fire(deleteMessageSwalConfig).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('customer_support.delete') }}",
                        data: {
                            id: id
                        },
                        type: 'DELETE',
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
@endsection
