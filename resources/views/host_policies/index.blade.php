@extends('layouts.app')

@section('content')
<div class="card mb-3">

    <div class="card-header">
        <div class="row flex-between-end">
            <div class="col-auto align-self-center">
                <h5 class="mb-0">Host Policy :: List</h5>
            </div>

            <div class="col-auto ms-auto d-flex gap-2">

                @if(Helper::userCan(104, 'can_add'))
                <a href="{{ route('host-policy.form') }}" class="btn btn-outline-secondary">
                    <i class="fa fa-plus me-1"></i> Add Policy
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
                        <th>Level</th>
                        <!-- <th>Time (Hours)</th> -->
                        <th>Target(Gift Value)</th>
                        <th>Host Salary</th>
                        <th>Agent Commission</th>
                        <th>Total Salary</th>
                        <th>Country</th>
                        <th>Status</th>
                        <th>Created Date</th>
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
            ajax: "{{ route('host-policy') }}",

            // Level wise sorting
            order: [
                [0, 'asc']
            ],

            columns: [{
                    data: 'level',
                    name: 'level'
                },
                // {
                //     data: 'time_hours',
                //     name: 'time_hours'
                // },
                {
                    data: 'target_value',
                    name: 'target_value'
                },
                {
                    data: 'host_salary',
                    name: 'host_salary'
                },
                {
                    data: 'agent_commission',
                    name: 'agent_commission'
                },
                {
                    data: 'total_salary',
                    name: 'total_salary'
                },
                {
                    data: 'country',
                    name: 'country'
                },
                {
                    data: 'status',
                    name: 'status'
                },
                {
                    data: 'created_at',
                    name: 'created_at',
                    orderable: false
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
                        url: "{{ route('host-policy.delete') }}",
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
@endsection