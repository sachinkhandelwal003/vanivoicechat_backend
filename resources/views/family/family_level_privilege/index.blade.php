@extends('layouts.app')

@section('content')
<div class="card mb-3">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">

            <h5 class="mb-0">Family Level Privilege :: Family Level Privilege List</h5>

            <div class="d-flex gap-2">

                <a href="{{ route('family.level', $rankId) }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fa fa-arrow-left me-1"></i> Back
                </a>

                @if(Helper::userCan(157, 'can_add'))
                <a href="{{ route('family.level.privilege.add', $levelId) }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fa fa-plus me-1"></i> Add Family Level Privilege
                </a>
                @endif

            </div>
        </div>
    </div>

    <div class="card-body">

        <div class="table-responsive">
            <table class="table table-striped table-datatable" style="width:100%">

                <thead class="bg-light">
                    <tr>
                        <th>#</th>
                        <th>Level Badge</th>
                        <th>Level Frame</th>
                        <th>Members</th>
                        <th>Admin</th>
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
                url: "{{ route('family.level.privilege',$levelId) }}",
            },
            columns: [{
                    data: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'level_badge'
                },
                {
                    data: 'level_frame'
                },
                {
                    data: 'members'
                },
                {
                    data: 'admin'
                },
                {
                    data: 'action',
                    orderable: false,
                    searchable: false
                }
            ]
        });

        $(document).on('click', ".delete", function() {
            var id = $(this).data('id')
            Swal.fire(deleteMessageSwalConfig).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('family.level.privilege.delete') }} ",
                        data: {
                            'id': id
                        },
                        type: 'DELETE',
                        success: function(data) {
                            if (data.status) {
                                Swal.fire('', data?.message, "success")
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
